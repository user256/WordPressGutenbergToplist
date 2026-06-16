<?php
/**
 * REST sync + remote source cron (ticket 611).
 *
 * @package Toplist_Block
 */

if (!defined('ABSPATH')) {
	exit;
}

const TOPLIST_REMOTE_SYNC_CRON_HOOK = 'toplist_remote_source_sync';

/**
 * @return void
 */
function toplist_register_api_sync_hooks(): void {
	add_action('rest_api_init', 'toplist_register_sync_rest_route');
	add_action(TOPLIST_REMOTE_SYNC_CRON_HOOK, 'toplist_run_remote_source_sync_batch');
	add_action('save_post_toplist_list', 'toplist_maybe_schedule_remote_sync', 20, 1);
}

/**
 * @return void
 */
function toplist_register_sync_rest_route(): void {
	register_rest_route(
		'toplist-block/v1',
		'/sync/(?P<id>\d+)',
		array(
			'methods' => 'POST',
			'permission_callback' => function ($request) {
				$post_id = (int) $request->get_param('id');
				return current_user_can('edit_post', $post_id);
			},
			'args' => array(
				'items' => array(
					'type' => 'array',
					'required' => false,
				),
				'content' => array(
					'type' => 'string',
					'required' => false,
				),
			),
			'callback' => 'toplist_rest_sync_toplist',
		)
	);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function toplist_rest_sync_toplist($request) {
	$id_param = $request->get_param('id');
	$post_id = is_numeric($id_param) ? (int) $id_param : 0;
	$post = get_post($post_id);
	if (!$post || $post->post_type !== 'toplist_list') {
		return new WP_Error('toplist_not_found', __('Toplist not found.', 'toplist'), array('status' => 404));
	}

	$content = '';
	$items = $request->get_param('items');
	if (is_array($items) && $items !== array()) {
		$content = toplist_items_to_pipe_content($items, array());
	} else {
		$raw = $request->get_param('content');
		if (is_string($raw) && trim($raw) !== '') {
			$content = wp_kses_post($raw);
		}
	}

	if (trim($content) === '') {
		return new WP_Error('toplist_empty_sync', __('Sync payload must include items or content.', 'toplist'), array('status' => 400));
	}

	$result = toplist_apply_toplist_content($post_id, $content);
	if (is_wp_error($result)) {
		return $result;
	}

	return rest_ensure_response(
		array(
			'id' => $post_id,
			'modified' => get_post_modified_time(DATE_ATOM, false, $post),
			'rows' => count(toplist_parse_lines_to_items($content, array())['items']),
		)
	);
}

/**
 * @param int    $post_id Post ID.
 * @param string $content Pipe content.
 * @return true|WP_Error
 */
function toplist_apply_toplist_content(int $post_id, string $content) {
	$updated = wp_update_post(
		array(
			'ID' => $post_id,
			'post_content' => $content,
		),
		true
	);
	if (is_wp_error($updated)) {
		return $updated;
	}

	toplist_bust_list_render_cache($post_id);
	do_action('toplist_list_synced', $post_id);

	return true;
}

/**
 * @param int $post_id Post ID.
 * @return void
 */
function toplist_bust_list_render_cache(int $post_id): void {
	delete_transient('toplist_list_html_' . $post_id);
	clean_post_cache($post_id);
}

/**
 * @param int $post_id Post ID.
 * @return void
 */
function toplist_maybe_schedule_remote_sync(int $post_id): void {
	if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
		return;
	}

	$url = get_post_meta($post_id, '_toplist_remote_source_url', true);
	if (!is_string($url) || trim($url) === '') {
		return;
	}

	if (!wp_next_scheduled(TOPLIST_REMOTE_SYNC_CRON_HOOK)) {
		wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', TOPLIST_REMOTE_SYNC_CRON_HOOK);
	}
}

/**
 * @return void
 */
function toplist_run_remote_source_sync_batch(): void {
	$posts = get_posts(
		array(
			'post_type' => 'toplist_list',
			'post_status' => array('publish', 'private', 'draft'),
			'posts_per_page' => 50,
			'meta_query' => array(
				array(
					'key' => '_toplist_remote_source_url',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	foreach ($posts as $post) {
		$url = get_post_meta($post->ID, '_toplist_remote_source_url', true);
		if (!is_string($url) || trim($url) === '') {
			continue;
		}
		toplist_fetch_remote_toplist($post->ID, $url);
	}
}

/**
 * @param int    $post_id Post ID.
 * @param string $url     Remote URL.
 * @return true|WP_Error
 */
function toplist_fetch_remote_toplist(int $post_id, string $url) {
	$url = esc_url_raw(trim($url));
	if ($url === '') {
		return new WP_Error('toplist_invalid_url', __('Remote source URL is invalid.', 'toplist'));
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 30,
			'headers' => array(
				'Accept' => 'application/json',
			),
		)
	);
	if (is_wp_error($response)) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code($response);
	if ($code < 200 || $code >= 300) {
		return new WP_Error('toplist_remote_http', sprintf(__('Remote source returned HTTP %d.', 'toplist'), $code));
	}

	$body = wp_remote_retrieve_body($response);
	$decoded = json_decode($body, true);
	if (!is_array($decoded)) {
		return new WP_Error('toplist_remote_json', __('Remote source did not return valid JSON.', 'toplist'));
	}

	$content = '';
	if (isset($decoded['items']) && is_array($decoded['items'])) {
		$content = toplist_items_to_pipe_content($decoded['items'], array());
	} elseif (isset($decoded['content']) && is_string($decoded['content'])) {
		$content = wp_kses_post($decoded['content']);
	}

	if (trim($content) === '') {
		return new WP_Error('toplist_remote_empty', __('Remote JSON must include items or content.', 'toplist'));
	}

	return toplist_apply_toplist_content($post_id, $content);
}

/**
 * Remote source metabox on list edit screen.
 *
 * @param WP_Post $post Post.
 * @return void
 */
function toplist_render_remote_source_metabox($post): void {
	wp_nonce_field('toplist_save_remote_source', 'toplist_remote_source_nonce');
	$url = get_post_meta($post->ID, '_toplist_remote_source_url', true);
	$url = is_string($url) ? $url : '';
	echo '<p><label for="toplist_remote_source_url"><strong>' . esc_html__('Remote source URL', 'toplist') . '</strong></label></p>';
	echo '<input type="url" class="widefat" id="toplist_remote_source_url" name="toplist_remote_source_url" value="' . esc_attr($url) . '" placeholder="https://example.com/toplist.json" />';
	echo '<p class="description">' . esc_html__('Optional JSON endpoint ({ "items": [...] } or { "content": "pipe..." }). Synced hourly when set.', 'toplist') . '</p>';
}

/**
 * @param int $post_id Post ID.
 * @return void
 */
function toplist_save_remote_source_meta(int $post_id): void {
	if (!isset($_POST['toplist_remote_source_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['toplist_remote_source_nonce'])), 'toplist_save_remote_source')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$url = isset($_POST['toplist_remote_source_url']) ? esc_url_raw(wp_unslash($_POST['toplist_remote_source_url'])) : '';
	update_post_meta($post_id, '_toplist_remote_source_url', $url);
	toplist_maybe_schedule_remote_sync($post_id);
}
