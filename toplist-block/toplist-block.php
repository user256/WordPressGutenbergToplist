<?php
/**
 * Plugin Name: Toplist Block
 * Description: A Gutenberg Toplist block. No build tools required.
 * Version: 0.1.2
 * Author: A medly of bots
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Normalize arbitrary value to a trimmed string.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function toplist_clean_text($value)
{
	return is_string($value) ? trim($value) : '';
}

/**
 * Normalize semicolon-delimited or array value to a clean string list.
 *
 * @param mixed $value Raw value.
 * @return array
 */
function toplist_clean_list($value)
{
	if (is_array($value)) {
		return array_values(array_filter(array_map('toplist_clean_text', $value)));
	}

	if (is_string($value)) {
		return array_values(array_filter(array_map('toplist_clean_text', explode(';', $value))));
	}

	return array();
}

/**
 * Supported field names used for directives and render gating.
 *
 * @return array
 */
function toplist_supported_fields()
{
	return array(
		'operator',
		'product',
		'offer',
		'href',
		'logo',
		'year',
		'ctaText',
		'terms',
		'bullets',
		'payout',
		'code',
		'rating',
		'regulator',
		'payments',
		'games',
		'liveGames',
		'smallPrint',
		'readReviewHref',
		'readReviewText',
		'withdrawals',
	);
}

/**
 * Determine whether a toplist item has renderable content.
 *
 * @param mixed $item Item object/array.
 * @return bool
 */
function toplist_item_has_content($item)
{
	if (!is_array($item)) {
		return false;
	}

	foreach (array('operator', 'product', 'offer', 'href', 'logo', 'year', 'terms', 'payout', 'code', 'rating', 'regulator', 'liveGames', 'smallPrint', 'readReviewHref') as $key) {
		if (toplist_clean_text($item[$key] ?? '') !== '') {
			return true;
		}
	}

	foreach (array('bullets', 'payments', 'games', 'withdrawals') as $list_key) {
		if (!empty(toplist_clean_list($item[$list_key] ?? array()))) {
			return true;
		}
	}

	return false;
}

/**
 * Determine if a field is eligible to render from header directives.
 *
 * @param string $field Field key.
 * @param array  $includes Included keys from header.
 * @param array  $excludes Excluded keys from header.
 * @return bool
 */
function toplist_field_is_included($field, $includes, $excludes)
{
	if (in_array($field, $excludes, true)) {
		return false;
	}

	if (!empty($includes)) {
		return in_array($field, $includes, true);
	}

	return true;
}

/**
 * Read a global boolean option for toplist settings.
 *
 * @param string $key Option key.
 * @param bool   $default Default value.
 * @return bool
 */
function toplist_get_global_bool_option($key, $default = true)
{
	$raw = get_option($key, $default ? '1' : '0');
	if (is_bool($raw)) {
		return $raw;
	}
	return in_array(strtolower((string) $raw), array('1', 'true', 'yes', 'on'), true);
}

/**
 * Read a global text option for toplist settings.
 *
 * @param string $key Option key.
 * @param string $default Default value.
 * @return string
 */
function toplist_get_global_text_option($key, $default = '')
{
	$value = get_option($key, $default);
	return is_string($value) ? trim($value) : $default;
}

/**
 * Parse and normalize a directive/header token.
 *
 * @param string $token Raw token.
 * @return array
 */
function toplist_normalize_directive_token($token)
{
	$raw = trim((string) $token);
	$excluded = false;
	$supported = toplist_supported_fields();
	$lookup = array();

	foreach ($supported as $field) {
		$lookup[strtolower($field)] = $field;
	}

	if ($raw === '') {
		return array(
			'field' => '',
			'excluded' => false,
			'recognized' => false,
		);
	}

	if ($raw[0] === '-' || $raw[0] === '!') {
		$excluded = true;
		$raw = trim(substr($raw, 1));
	}

	$canonical = $lookup[strtolower($raw)] ?? '';
	return array(
		'field' => $canonical,
		'excluded' => $excluded,
		'recognized' => $canonical !== '',
	);
}

/**
 * Heuristic to detect header/directive row.
 *
 * @param array $parts Pipe-delimited row parts.
 * @return bool
 */
function toplist_detect_header_row($parts)
{
	$recognized = 0;

	foreach ($parts as $part) {
		$token = toplist_normalize_directive_token($part);
		if ($token['recognized']) {
			$recognized += 1;
		}
		if (preg_match('/https?:\/\//i', (string) $part)) {
			return false;
		}
	}

	return $recognized >= 3;
}

/**
 * Parse local/saved lines to normalized items + include/exclude directives.
 *
 * @param string $text Raw textarea content.
 * @param array  $defaults Default labels.
 * @return array
 */
function toplist_parse_lines_to_items($text, $defaults = array())
{
	$default_cta_text = trim((string) ($defaults['defaultCtaText'] ?? 'Visit'));
	$default_read_review_text = trim((string) ($defaults['defaultReadReviewText'] ?? 'Read Review'));
	$default_header_row = trim((string) ($defaults['defaultHeaderRow'] ?? ''));
	$default_cta_text = $default_cta_text !== '' ? $default_cta_text : 'Visit';
	$default_read_review_text = $default_read_review_text !== '' ? $default_read_review_text : 'Read Review';
	$lines = preg_split('/\r\n|\r|\n/', (string) $text);
	$lines = is_array($lines) ? array_values(array_filter(array_map('trim', $lines), 'strlen')) : array();
	$items = array();
	$includes = array();
	$excludes = array();
	$start_index = 0;

	if (empty($lines)) {
		return array(
			'items' => array(),
			'includes' => array(),
			'excludes' => array(),
		);
	}

	$header_parts = explode('|', $lines[0]);
	$header_tokens = array_map('toplist_normalize_directive_token', $header_parts);
	$file_header = toplist_detect_header_row($header_parts);

	if ($file_header) {
		$has_header = true;
		$start_index = 1;
	} elseif ($default_header_row !== '') {
		$default_header_parts = explode('|', $default_header_row);
		$first_line_parts = explode('|', $lines[0]);
		// Wide rows are full fixed-column pipe data: do not apply a short virtual header (would drop columns).
		if (count($first_line_parts) <= count($default_header_parts)) {
			$header_parts = $default_header_parts;
			$header_tokens = array_map('toplist_normalize_directive_token', $header_parts);
			$has_header = true;
			$start_index = 0;
		} else {
			$has_header = false;
			$start_index = 0;
		}
	} else {
		$has_header = false;
		$start_index = 0;
	}

	if ($has_header) {
		foreach ($header_tokens as $token) {
			if (!$token['recognized']) {
				continue;
			}
			if ($token['excluded']) {
				$excludes[] = $token['field'];
			} else {
				$includes[] = $token['field'];
			}
		}
	}

	for ($i = $start_index; $i < count($lines); $i += 1) {
		$parts = array_map('trim', explode('|', $lines[$i]));
		$item = array(
			'operator' => '',
			'product' => '',
			'offer' => '',
			'href' => '',
			'logo' => '',
			'year' => '',
			'ctaText' => '',
			'terms' => '',
			'bullets' => array(),
			'payout' => '',
			'code' => '',
			'rating' => '',
			'regulator' => '',
			'payments' => array(),
			'games' => array(),
			'liveGames' => '',
			'smallPrint' => '',
			'readReviewHref' => '',
			'readReviewText' => '',
			'withdrawals' => array(),
		);

		if ($has_header) {
			for ($col = 0; $col < count($header_tokens); $col += 1) {
				$token = $header_tokens[$col];
				$value = trim((string) ($parts[$col] ?? ''));
				if (!$token['recognized'] || $token['excluded']) {
					continue;
				}
				if (in_array($token['field'], array('bullets', 'payments', 'games', 'withdrawals'), true)) {
					$item[$token['field']] = toplist_clean_list($value);
				} else {
					$item[$token['field']] = $value;
				}
			}
		} else {
			$item = array(
				'operator' => (string) ($parts[0] ?? ''),
				'product' => (string) ($parts[1] ?? ''),
				'offer' => (string) ($parts[2] ?? ''),
				'href' => (string) ($parts[3] ?? ''),
				'logo' => (string) ($parts[4] ?? ''),
				'year' => (string) ($parts[5] ?? ''),
				'ctaText' => (string) ($parts[6] ?? ''),
				'terms' => (string) ($parts[7] ?? ''),
				'bullets' => toplist_clean_list((string) ($parts[8] ?? '')),
				'payout' => (string) ($parts[9] ?? ''),
				'code' => (string) ($parts[10] ?? ''),
				'rating' => (string) ($parts[11] ?? ''),
				'regulator' => (string) ($parts[12] ?? ''),
				'payments' => toplist_clean_list((string) ($parts[13] ?? '')),
				'games' => toplist_clean_list((string) ($parts[14] ?? '')),
				'liveGames' => (string) ($parts[15] ?? ''),
				'smallPrint' => (string) ($parts[16] ?? ''),
				'readReviewHref' => (string) ($parts[17] ?? ''),
				'readReviewText' => (string) ($parts[18] ?? ''),
				'withdrawals' => toplist_clean_list((string) ($parts[19] ?? '')),
			);
		}

		$item['ctaText'] = trim((string) $item['ctaText']) !== '' ? trim((string) $item['ctaText']) : $default_cta_text;
		$item['readReviewText'] = trim((string) $item['readReviewText']) !== '' ? trim((string) $item['readReviewText']) : $default_read_review_text;

		if (toplist_item_has_content($item)) {
			$items[] = $item;
		}
	}

	return array(
		'items' => array_values($items),
		'includes' => array_values(array_unique($includes)),
		'excludes' => array_values(array_unique($excludes)),
	);
}

/**
 * Normalize external toplist JSON list fields (e.g. features, payments).
 *
 * @param mixed $value Raw value.
 * @return array
 */
function toplist_external_json_string_list($value)
{
	if (is_array($value)) {
		return array_values(array_filter(array_map('toplist_clean_text', $value)));
	}
	if (is_string($value)) {
		$t = trim($value);
		return $t !== '' ? array($t) : array();
	}
	return array();
}

/**
 * Normalize games from external JSON (array or space-separated string).
 *
 * @param mixed $value Raw value.
 * @return array
 */
function toplist_external_json_games_to_list($value)
{
	if (is_array($value)) {
		return array_values(array_filter(array_map('toplist_clean_text', $value)));
	}
	if (is_string($value)) {
		return array_values(array_filter(preg_split('/\s+/', trim($value))));
	}
	return array();
}

/**
 * Normalize withdrawals from external JSON.
 *
 * @param mixed $value Raw value.
 * @return array
 */
function toplist_external_json_withdrawals_list($value)
{
	if (is_array($value)) {
		return toplist_external_json_string_list($value);
	}
	$s = toplist_clean_text($value);
	if ($s === '') {
		return array();
	}
	if (strpos($s, ';') !== false) {
		return toplist_clean_list($s);
	}
	return array($s);
}

/**
 * Map one external JSON row (toplist.json shape) to an internal item.
 *
 * @param array $row Decoded object.
 * @return array|null Item or null if empty.
 */
function toplist_external_json_row_to_item($row)
{
	if (!is_array($row)) {
		return null;
	}

	$name = toplist_clean_text($row['name'] ?? '');
	$visit = toplist_clean_text($row['visit_link'] ?? '');
	$bonus_link = toplist_clean_text($row['bonus_link'] ?? '');
	$href = $visit !== '' ? $visit : $bonus_link;

	$item = array(
		'operator' => $name,
		'product' => $name,
		'offer' => toplist_clean_text($row['bonus'] ?? ''),
		'href' => $href,
		'logo' => toplist_clean_text($row['image_url'] ?? ''),
		'year' => toplist_clean_text($row['launched'] ?? ''),
		'ctaText' => '',
		'terms' => '',
		'bullets' => toplist_external_json_string_list($row['features'] ?? array()),
		'payout' => toplist_clean_text($row['payout_time'] ?? ''),
		'code' => toplist_clean_text($row['code'] ?? ''),
		'rating' => isset($row['rating']) && (is_numeric($row['rating']) || is_string($row['rating']))
			? toplist_clean_text((string) $row['rating'])
			: '',
		'regulator' => toplist_clean_text($row['regulator'] ?? ''),
		'payments' => toplist_external_json_string_list($row['payments'] ?? array()),
		'games' => toplist_external_json_games_to_list($row['games'] ?? ''),
		'liveGames' => toplist_clean_text($row['live_games'] ?? ''),
		'smallPrint' => '',
		'readReviewHref' => toplist_clean_text($row['review_link'] ?? ''),
		'readReviewText' => '',
		'withdrawals' => toplist_external_json_withdrawals_list($row['withdrawals'] ?? ''),
	);

	$default_cta = 'Visit';
	$default_rr = 'Read Review';
	$item['ctaText'] = trim((string) $item['ctaText']) !== '' ? $item['ctaText'] : $default_cta;
	$item['readReviewText'] = trim((string) $item['readReviewText']) !== '' ? $item['readReviewText'] : $default_rr;

	return toplist_item_has_content($item) ? $item : null;
}

/**
 * Decode external toplist.json body into internal items.
 *
 * @param string $json_string Raw JSON.
 * @return array{items:array,error:string}
 */
function toplist_decode_external_toplist_json($json_string)
{
	$json_string = is_string($json_string) ? trim($json_string) : '';
	$json_string = preg_replace('/^\xEF\xBB\xBF/', '', $json_string);
	if ($json_string === '') {
		return array(
			'items' => array(),
			'error' => 'empty',
		);
	}

	$decoded = json_decode($json_string, true);
	if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
		return array(
			'items' => array(),
			'error' => 'invalid',
		);
	}

	$items = array();
	foreach ($decoded as $row) {
		$item = toplist_external_json_row_to_item($row);
		if ($item !== null) {
			$items[] = $item;
		}
	}

	if (empty($items)) {
		return array(
			'items' => array(),
			'error' => 'empty',
		);
	}

	return array(
		'items' => $items,
		'error' => '',
	);
}

/**
 * Serialize internal items to pipe-delimited post content (no header row).
 *
 * @param array $items Normalized items.
 * @param array $defaults Optional default CTA / read-review labels.
 * @return string
 */
function toplist_items_to_pipe_content($items, $defaults = array())
{
	$default_cta = trim((string) ($defaults['defaultCtaText'] ?? 'Visit'));
	$default_read = trim((string) ($defaults['defaultReadReviewText'] ?? 'Read Review'));
	$default_cta = $default_cta !== '' ? $default_cta : 'Visit';
	$default_read = $default_read !== '' ? $default_read : 'Read Review';

	$lines = array();
	foreach ($items as $it) {
		if (!is_array($it)) {
			continue;
		}
		$parts = array(
			toplist_clean_text($it['operator'] ?? ''),
			toplist_clean_text($it['product'] ?? ''),
			toplist_clean_text($it['offer'] ?? ''),
			toplist_clean_text($it['href'] ?? ''),
			toplist_clean_text($it['logo'] ?? ''),
			toplist_clean_text($it['year'] ?? ''),
			toplist_clean_text($it['ctaText'] ?? '') !== '' ? toplist_clean_text($it['ctaText'] ?? '') : $default_cta,
			toplist_clean_text($it['terms'] ?? ''),
			implode(';', toplist_clean_list($it['bullets'] ?? array())),
			toplist_clean_text($it['payout'] ?? ''),
			toplist_clean_text($it['code'] ?? ''),
			toplist_clean_text($it['rating'] ?? ''),
			toplist_clean_text($it['regulator'] ?? ''),
			implode(';', toplist_clean_list($it['payments'] ?? array())),
			implode(';', toplist_clean_list($it['games'] ?? array())),
			toplist_clean_text($it['liveGames'] ?? ''),
			toplist_clean_text($it['smallPrint'] ?? ''),
			toplist_clean_text($it['readReviewHref'] ?? ''),
			toplist_clean_text($it['readReviewText'] ?? '') !== '' ? toplist_clean_text($it['readReviewText'] ?? '') : $default_read,
			implode(';', toplist_clean_list($it['withdrawals'] ?? array())),
		);
		$lines[] = implode('|', $parts);
	}

	return implode("\n", $lines);
}

/**
 * Build export rows matching repository toplist.json schema.
 *
 * @param array $items Normalized items.
 * @return array
 */
function toplist_items_to_external_json_rows($items)
{
	$rows = array();
	$pos = 1;

	foreach ($items as $item) {
		if (!is_array($item)) {
			continue;
		}

		$href = toplist_clean_text($item['href'] ?? '');
		$games = toplist_clean_list($item['games'] ?? array());
		$withdrawals = toplist_clean_list($item['withdrawals'] ?? array());
		$rating_raw = toplist_clean_text(is_scalar($item['rating'] ?? '') ? (string) $item['rating'] : '');
		$rating_out = null;
		if ($rating_raw !== '') {
			$rating_out = is_numeric($rating_raw) ? (float) $rating_raw : $rating_raw;
		}

		$review = toplist_clean_text($item['readReviewHref'] ?? '');

		$rows[] = array(
			'position' => (string) $pos,
			'name' => toplist_clean_text($item['product'] ?? '') !== '' ? toplist_clean_text($item['product'] ?? '') : toplist_clean_text($item['operator'] ?? ''),
			'rating' => $rating_out,
			'launched' => toplist_clean_text($item['year'] ?? ''),
			'regulator' => toplist_clean_text($item['regulator'] ?? ''),
			'bonus' => toplist_clean_text($item['offer'] ?? ''),
			'bonus_link' => $href !== '' ? $href : null,
			'payout_time' => toplist_clean_text($item['payout'] ?? ''),
			'features' => toplist_clean_list($item['bullets'] ?? array()),
			'games' => implode(' ', $games),
			'live_games' => toplist_clean_text($item['liveGames'] ?? ''),
			'withdrawals' => !empty($withdrawals) ? implode(' ', $withdrawals) : '',
			'code' => toplist_clean_text($item['code'] ?? ''),
			'image_url' => toplist_clean_text($item['logo'] ?? ''),
			'visit_link' => null,
			'review_link' => $review !== '' ? $review : null,
			'payments' => toplist_clean_list($item['payments'] ?? array()),
		);
		$pos += 1;
	}

	return $rows;
}

/**
 * Register Toplists library custom post type.
 *
 * @return void
 */
function toplist_register_cpt()
{
	register_post_type('toplist_list', array(
		'labels' => array(
			'name' => __('Toplists', 'toplist'),
			'singular_name' => __('Toplist', 'toplist'),
			'add_new_item' => __('Add New Toplist', 'toplist'),
			'edit_item' => __('Edit Toplist', 'toplist'),
			'new_item' => __('New Toplist', 'toplist'),
			'view_item' => __('View Toplist', 'toplist'),
			'search_items' => __('Search Toplists', 'toplist'),
			'not_found' => __('No toplists found', 'toplist'),
		),
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_rest' => true,
		'has_archive' => false,
		'rewrite' => false,
		'menu_icon' => 'dashicons-list-view',
		'supports' => array('title', 'revisions'),
		'capability_type' => 'post',
		'map_meta_cap' => true,
	));
}

/**
 * Keep Toplist library in classic editor textarea mode for raw pipe content.
 *
 * @param bool   $use_block_editor Whether block editor is enabled.
 * @param string $post_type Post type name.
 * @return bool
 */
function toplist_disable_block_editor_for_toplists($use_block_editor, $post_type)
{
	if ($post_type === 'toplist_list') {
		return false;
	}
	return $use_block_editor;
}

/**
 * Add ID and modified columns to Toplists admin list.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function toplist_toplists_admin_columns($columns)
{
	return array(
		'cb' => $columns['cb'] ?? '',
		'title' => __('Name', 'toplist'),
		'toplist_id' => __('Shortcode / ID', 'toplist'),
		'modified' => __('Last Modified', 'toplist'),
		'date' => $columns['date'] ?? __('Date', 'toplist'),
	);
}

/**
 * Render custom Toplists admin columns.
 *
 * @param string $column Column key.
 * @param int    $post_id Post ID.
 * @return void
 */
function toplist_toplists_admin_column_content($column, $post_id)
{
	if ($column === 'toplist_id') {
		echo '<code>[toplist id=&quot;' . esc_html((string) $post_id) . '&quot;]</code><br>';
		echo '<small>#' . esc_html((string) $post_id) . '</small>';
		return;
	}
	if ($column === 'modified') {
		echo esc_html(get_the_modified_date('', $post_id));
	}
}

/**
 * Register REST endpoints for saved toplists library.
 *
 * @return void
 */
function toplist_register_rest_routes()
{
	register_rest_route('toplist-block/v1', '/toplists', array(
		'methods' => 'GET',
		'permission_callback' => function () {
			return current_user_can('edit_posts');
		},
		'callback' => function () {
			$posts = get_posts(array(
				'post_type' => 'toplist_list',
				'post_status' => array('publish', 'private', 'draft'),
				'posts_per_page' => -1,
				'orderby' => 'title',
				'order' => 'ASC',
			));
			$data = array();
			foreach ($posts as $post) {
				if (!current_user_can('edit_post', $post->ID)) {
					continue;
				}
				$data[] = array(
					'id' => (int) $post->ID,
					'name' => get_the_title($post),
					'modified' => get_post_modified_time(DATE_ATOM, false, $post),
				);
			}
			return rest_ensure_response($data);
		},
	));

	register_rest_route('toplist-block/v1', '/toplists/(?P<id>\d+)', array(
		'methods' => 'GET',
		'permission_callback' => function ($request) {
			$post_id = (int) $request['id'];
			return current_user_can('edit_post', $post_id);
		},
		'callback' => function ($request) {
			$post_id = (int) $request['id'];
			$post = get_post($post_id);
			if (!$post || $post->post_type !== 'toplist_list') {
				return new WP_Error('toplist_not_found', __('Toplist not found.', 'toplist'), array('status' => 404));
			}
			return rest_ensure_response(array(
				'id' => (int) $post->ID,
				'name' => get_the_title($post),
				'content' => (string) $post->post_content,
				'modified' => get_post_modified_time(DATE_ATOM, false, $post),
			));
		},
	));

	register_rest_route('toplist-block/v1', '/toplists', array(
		'methods' => 'POST',
		'permission_callback' => function () {
			return current_user_can('edit_posts');
		},
		'args' => array(
			'name' => array('required' => true, 'type' => 'string'),
			'content' => array('required' => true, 'type' => 'string'),
		),
		'callback' => function ($request) {
			$name = sanitize_text_field((string) $request->get_param('name'));
			$content = (string) $request->get_param('content');
			if ($name === '') {
				return new WP_Error('toplist_invalid_name', __('Toplist name is required.', 'toplist'), array('status' => 400));
			}

			$post_id = wp_insert_post(array(
				'post_type' => 'toplist_list',
				'post_title' => $name,
				'post_content' => $content,
				'post_status' => 'publish',
			), true);

			if (is_wp_error($post_id)) {
				return $post_id;
			}

			return rest_ensure_response(array(
				'id' => (int) $post_id,
				'name' => $name,
			));
		},
	));
}

/**
 * Render plain textarea metabox for toplist raw content.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function toplist_render_raw_content_metabox($post)
{
	wp_nonce_field('toplist_save_raw_content', 'toplist_raw_content_nonce');
	$content = is_string($post->post_content) ? $post->post_content : '';
	echo '<p>' . esc_html__('Use one line per item. Pipe-delimited format is supported, including optional first-row header directives.', 'toplist') . '</p>';
	echo '<textarea name="toplist_raw_content" id="toplist_raw_content" style="width:100%;min-height:320px;font-family:monospace;" spellcheck="false">' . esc_textarea($content) . '</textarea>';
}

/**
 * Render CSV / JSON import/export metabox for a single toplist.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function toplist_render_csv_tools_metabox($post)
{
	$pid = (int) $post->ID;
	$export_url = wp_nonce_url(
		admin_url('admin-post.php?action=toplist_export_csv&post_id=' . $pid),
		'toplist_export_csv_' . $pid
	);
	$export_json_url = wp_nonce_url(
		admin_url('admin-post.php?action=toplist_export_json&post_id=' . $pid),
		'toplist_export_json_' . $pid
	);
	$csv_form_id = 'toplist-import-csv-form-' . $pid;
	$json_form_id = 'toplist-import-json-form-' . $pid;
	$file_id = 'toplist-import-file-' . $pid;
	$json_file_id = 'toplist-json-import-file-' . $pid;
	$csv_btn_id = 'toplist-import-submit-csv-' . $pid;
	$json_btn_id = 'toplist-import-submit-json-' . $pid;

	echo '<p><strong>' . esc_html__('CSV', 'toplist') . '</strong></p>';
	echo '<p><a class="button button-secondary" href="' . esc_url($export_url) . '">' . esc_html__('Export as CSV', 'toplist') . '</a></p>';
	echo '<p><label for="' . esc_attr($file_id) . '"><strong>' . esc_html__('Import CSV', 'toplist') . '</strong></label></p>';
	echo '<input type="file" id="' . esc_attr($file_id) . '" name="toplist_csv_file" form="' . esc_attr($csv_form_id) . '" accept=".csv,text/csv" />';
	echo '<p style="margin-top:10px;"><button type="button" class="button button-primary" id="' . esc_attr($csv_btn_id) . '">' . esc_html__('Import CSV', 'toplist') . '</button></p>';
	echo '<p class="description">' . esc_html__('CSV header can use field names: operator, product, offer, href, logo, year, ctaText, terms, bullets, payout, code, rating, regulator, payments, games, liveGames, smallPrint, readReviewHref, readReviewText, withdrawals.', 'toplist') . '</p>';

	echo '<hr><p><strong>' . esc_html__('JSON (toplist.json)', 'toplist') . '</strong></p>';
	echo '<p><a class="button button-secondary" href="' . esc_url($export_json_url) . '">' . esc_html__('Export as JSON', 'toplist') . '</a></p>';
	echo '<p><label for="' . esc_attr($json_file_id) . '"><strong>' . esc_html__('Import JSON', 'toplist') . '</strong></label></p>';
	echo '<input type="file" id="' . esc_attr($json_file_id) . '" name="toplist_json_file" form="' . esc_attr($json_form_id) . '" accept=".json,application/json" />';
	echo '<p style="margin-top:10px;"><button type="button" class="button button-primary" id="' . esc_attr($json_btn_id) . '">' . esc_html__('Import JSON', 'toplist') . '</button></p>';
	echo '<p class="description">' . esc_html__('Array of objects: name, rating, launched, regulator, bonus, bonus_link, payout_time, features, games, live_games, withdrawals, code, image_url, visit_link, review_link, payments (see repo toplist/toplist.json).', 'toplist') . '</p>';
}

/**
 * Print import &lt;form&gt; tags outside #post (valid HTML; metabox controls use form="…").
 *
 * @return void
 */
function toplist_print_import_forms_in_footer()
{
	if (!is_admin()) {
		return;
	}
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'toplist_list') {
		return;
	}
	global $post;
	if (!$post instanceof WP_Post || $post->post_type !== 'toplist_list') {
		return;
	}
	$pid = (int) $post->ID;
	$action = esc_url(admin_url('admin-post.php'));
	$csv_id = 'toplist-import-csv-form-' . $pid;
	$json_id = 'toplist-import-json-form-' . $pid;

	echo '<div class="toplist-import-forms" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">';
	echo '<form id="' . esc_attr($csv_id) . '" method="post" action="' . $action . '" enctype="multipart/form-data">';
	wp_nonce_field('toplist_import_csv_' . $pid, 'toplist_import_csv_nonce');
	echo '<input type="hidden" name="action" value="toplist_import_csv" />';
	echo '<input type="hidden" name="post_id" value="' . esc_attr((string) $pid) . '" />';
	echo '</form>';
	echo '<form id="' . esc_attr($json_id) . '" method="post" action="' . $action . '" enctype="multipart/form-data">';
	wp_nonce_field('toplist_import_json_' . $pid, 'toplist_import_json_nonce');
	echo '<input type="hidden" name="action" value="toplist_import_json" />';
	echo '<input type="hidden" name="post_id" value="' . esc_attr((string) $pid) . '" />';
	echo '</form>';
	echo '</div>';
	$msg_csv = wp_json_encode(__('Choose a CSV file first.', 'toplist'));
	$msg_json = wp_json_encode(__('Choose a JSON file first.', 'toplist'));
	echo '<script>(function(){var p=' . (int) $pid . ';var $=function(i){return document.getElementById(i);};';
	echo 'var cf=$("toplist-import-csv-form-"+p),jf=$("toplist-import-json-form-"+p),cb=$("toplist-import-submit-csv-"+p),jb=$("toplist-import-submit-json-"+p),ci=$("toplist-import-file-"+p),ji=$("toplist-json-import-file-"+p);';
	echo 'if(cb&&cf){cb.addEventListener("click",function(){if(!ci||!ci.files||!ci.files.length){window.alert(' . $msg_csv . ');return;}cf.submit();});}';
	echo 'if(jb&&jf){jb.addEventListener("click",function(){if(!ji||!ji.files||!ji.files.length){window.alert(' . $msg_json . ');return;}jf.submit();});}';
	echo '})();</script>';
}

/**
 * Render GUI row builder metabox for adding one row at a time.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function toplist_render_row_builder_metabox($post)
{
	$field_labels = array(
		'operator' => __('Operator', 'toplist'),
		'product' => __('Product', 'toplist'),
		'offer' => __('Offer', 'toplist'),
		'href' => __('Primary URL (href)', 'toplist'),
		'logo' => __('Logo URL', 'toplist'),
		'year' => __('Launch Year', 'toplist'),
		'ctaText' => __('CTA Text', 'toplist'),
		'terms' => __('Terms', 'toplist'),
		'bullets' => __('Bullets (; separated)', 'toplist'),
		'payout' => __('Payout', 'toplist'),
		'code' => __('Code', 'toplist'),
		'rating' => __('Rating', 'toplist'),
		'regulator' => __('Regulator', 'toplist'),
		'payments' => __('Payments (; separated)', 'toplist'),
		'games' => __('Games (; separated)', 'toplist'),
		'liveGames' => __('Live Games', 'toplist'),
		'smallPrint' => __('Small Print', 'toplist'),
		'readReviewHref' => __('Read Review URL', 'toplist'),
		'readReviewText' => __('Read Review Text', 'toplist'),
		'withdrawals' => __('Withdrawals (; separated)', 'toplist'),
	);

	echo '<p>' . esc_html__('Fill fields below and click "Add Row to Toplist". The row is appended to the raw content textarea.', 'toplist') . '</p>';
	echo '<div id="toplist-row-builder" style="display:grid;gap:10px;grid-template-columns:repeat(2,minmax(0,1fr));max-width:980px;">';
	foreach ($field_labels as $field => $label) {
		echo '<label style="display:grid;gap:4px;">';
		echo '<span><strong>' . esc_html($label) . '</strong></span>';
		echo '<input type="text" data-toplist-field="' . esc_attr($field) . '" class="regular-text" style="width:100%;">';
		echo '</label>';
	}
	echo '</div>';
	echo '<p style="margin-top:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">';
	echo '<button type="button" class="button" id="toplist-logo-upload">' . esc_html__('Upload/Select Logo', 'toplist') . '</button>';
	echo '<button type="button" class="button button-primary" id="toplist-add-row">' . esc_html__('Add Row to Toplist', 'toplist') . '</button>';
	echo '<button type="button" class="button" id="toplist-clear-row-builder">' . esc_html__('Clear Builder', 'toplist') . '</button>';
	echo '</p>';
	echo '<p class="description">' . esc_html__('Logo uploader sets the Logo URL field automatically. Lists should use semicolons between values.', 'toplist') . '</p>';
	echo '<script>(function(){';
	echo 'var builder=document.getElementById("toplist-row-builder");';
	echo 'var raw=document.getElementById("toplist_raw_content");';
	echo 'if(!builder||!raw){return;}';
	echo 'function getField(name){return builder.querySelector(\'[data-toplist-field="\'+name+\'"]\');}';
	echo 'function val(name){var i=getField(name);return i?String(i.value||"").trim():"";}';
	echo 'function setVal(name,v){var i=getField(name);if(i){i.value=v||"";}}';
	echo 'var order=' . wp_json_encode(array_values(toplist_supported_fields())) . ';';
	echo 'document.getElementById("toplist-add-row").addEventListener("click",function(){';
	echo 'var parts=[];order.forEach(function(f){parts.push(val(f));});';
	echo 'var row=parts.join("|");';
	echo 'if(!row.replace(/\\|/g,"").trim()){window.alert(' . wp_json_encode(__('Please fill at least one field before adding a row.', 'toplist')) . ');return;}';
	echo 'raw.value=(raw.value?raw.value.replace(/\\s+$/,"")+"\\n":"")+row;';
	echo 'raw.dispatchEvent(new Event("change",{bubbles:true}));';
	echo '});';
	echo 'document.getElementById("toplist-clear-row-builder").addEventListener("click",function(){';
	echo 'order.forEach(function(f){setVal(f,"");});';
	echo '});';
	echo 'var uploadBtn=document.getElementById("toplist-logo-upload");';
	echo 'if(uploadBtn){uploadBtn.addEventListener("click",function(e){e.preventDefault();var mediaApi=(window.wp&&window.wp.media)?window.wp.media:null;if(!mediaApi){window.alert(' . wp_json_encode(__('Media uploader is not available on this screen. Please refresh and try again.', 'toplist')) . ');return;}var frame=mediaApi({title:' . wp_json_encode(__('Select Logo', 'toplist')) . ',button:{text:' . wp_json_encode(__('Use this image', 'toplist')) . '},multiple:false});frame.on("select",function(){var selection=frame.state().get("selection");var first=selection&&selection.first?selection.first():null;var attachment=first&&first.toJSON?first.toJSON():null;if(attachment&&attachment.url){setVal("logo",attachment.url);}});frame.open();});}';
	echo '})();</script>';
}

/**
 * Register metaboxes for Toplists CPT edit screen.
 *
 * @return void
*/
function toplist_register_toplist_metaboxes()
{
	add_meta_box(
		'toplist_raw_content_box',
		__('Toplist Content', 'toplist'),
		'toplist_render_raw_content_metabox',
		'toplist_list',
		'normal',
		'high'
	);

	add_meta_box(
		'toplist_csv_tools_box',
		__('Import / Export', 'toplist'),
		'toplist_render_csv_tools_metabox',
		'toplist_list',
		'side',
		'default'
	);

	add_meta_box(
		'toplist_row_builder_box',
		__('Add Row (GUI Builder)', 'toplist'),
		'toplist_render_row_builder_metabox',
		'toplist_list',
		'normal',
		'default'
	);
}

/**
 * Save plain textarea content back into post_content for toplist_list.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function toplist_save_toplist_raw_content($post_id)
{
	if (!isset($_POST['toplist_raw_content_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['toplist_raw_content_nonce'])), 'toplist_save_raw_content')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	if (!isset($_POST['toplist_raw_content'])) {
		return;
	}

	$raw_content = (string) wp_unslash($_POST['toplist_raw_content']);

	remove_action('save_post_toplist_list', 'toplist_save_toplist_raw_content');
	wp_update_post(array(
		'ID' => (int) $post_id,
		'post_content' => $raw_content,
	));
	add_action('save_post_toplist_list', 'toplist_save_toplist_raw_content');
}

/**
 * Normalize CSV header to canonical field key.
 *
 * @param string $value Header cell.
 * @return string
 */
function toplist_normalize_csv_header($value)
{
	$normalized = strtolower(trim((string) $value));
	$normalized = preg_replace('/^\xEF\xBB\xBF/u', '', $normalized);
	$lookup = array();
	foreach (toplist_supported_fields() as $field) {
		$lookup[strtolower($field)] = $field;
	}
	return $lookup[$normalized] ?? '';
}

/**
 * Export one saved toplist as CSV.
 *
 * @return void
 */
function toplist_handle_export_csv()
{
	$post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
	if (!$post_id || !current_user_can('edit_post', $post_id)) {
		wp_die(esc_html__('You do not have permission to export this toplist.', 'toplist'));
	}
	check_admin_referer('toplist_export_csv_' . $post_id);

	$post = get_post($post_id);
	if (!$post || $post->post_type !== 'toplist_list') {
		wp_die(esc_html__('Toplist not found.', 'toplist'));
	}

	$parsed = toplist_parse_lines_to_items((string) $post->post_content, array());
	$items = $parsed['items'];
	$fields = toplist_supported_fields();

	nocache_headers();
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=toplist-' . $post_id . '.csv');

	$out = fopen('php://output', 'w');
	fputcsv($out, $fields);

	foreach ($items as $item) {
		$row = array();
		foreach ($fields as $field) {
			$value = $item[$field] ?? '';
			if (in_array($field, array('bullets', 'payments', 'games', 'withdrawals'), true)) {
				$value = is_array($value) ? implode(';', $value) : toplist_clean_text($value);
			}
			$row[] = (string) $value;
		}
		fputcsv($out, $row);
	}
	fclose($out);
	exit;
}

/**
 * Export one saved toplist as JSON (toplist.json schema).
 *
 * @return void
 */
function toplist_handle_export_json()
{
	$post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
	if (!$post_id || !current_user_can('edit_post', $post_id)) {
		wp_die(esc_html__('You do not have permission to export this toplist.', 'toplist'));
	}
	check_admin_referer('toplist_export_json_' . $post_id);

	$post = get_post($post_id);
	if (!$post || $post->post_type !== 'toplist_list') {
		wp_die(esc_html__('Toplist not found.', 'toplist'));
	}

	$parsed = toplist_parse_lines_to_items((string) $post->post_content, array());
	$items = is_array($parsed['items'] ?? null) ? $parsed['items'] : array();
	$rows = toplist_items_to_external_json_rows($items);

	nocache_headers();
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename=toplist-' . $post_id . '.json');

	echo wp_json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	exit;
}

/**
 * Import JSON (toplist.json schema) into one saved toplist.
 *
 * @return void
 */
function toplist_handle_import_json()
{
	$post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
	if (!$post_id || !current_user_can('edit_post', $post_id)) {
		wp_die(esc_html__('You do not have permission to import into this toplist.', 'toplist'));
	}
	if (!isset($_POST['toplist_import_json_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['toplist_import_json_nonce'])), 'toplist_import_json_' . $post_id)) {
		wp_die(esc_html__('Invalid JSON import request.', 'toplist'));
	}
	if (empty($_FILES['toplist_json_file']['tmp_name'])) {
		wp_safe_redirect(add_query_arg('toplist_json_import', 'empty', get_edit_post_link($post_id, '')));
		exit;
	}

	$tmp = $_FILES['toplist_json_file']['tmp_name'];
	$raw = file_get_contents($tmp);
	if ($raw === false) {
		wp_safe_redirect(add_query_arg('toplist_json_import', 'failed', get_edit_post_link($post_id, '')));
		exit;
	}

	$result = toplist_decode_external_toplist_json($raw);
	if ($result['error'] === 'invalid') {
		wp_safe_redirect(add_query_arg('toplist_json_import', 'invalid', get_edit_post_link($post_id, '')));
		exit;
	}
	if ($result['error'] === 'empty') {
		wp_safe_redirect(add_query_arg('toplist_json_import', 'empty', get_edit_post_link($post_id, '')));
		exit;
	}

	$new_content = toplist_items_to_pipe_content($result['items'], array());
	wp_update_post(array(
		'ID' => $post_id,
		'post_content' => $new_content,
	));

	wp_safe_redirect(add_query_arg('toplist_json_import', 'success', get_edit_post_link($post_id, '')));
	exit;
}

/**
 * Export all saved toplists as a single CSV.
 *
 * @return void
 */
function toplist_handle_export_all_csv()
{
	if (!current_user_can('edit_posts')) {
		wp_die(esc_html__('You do not have permission to export toplists.', 'toplist'));
	}
	check_admin_referer('toplist_export_all_csv');

	$posts = get_posts(array(
		'post_type' => 'toplist_list',
		'post_status' => array('publish', 'private', 'draft'),
		'orderby' => 'title',
		'order' => 'ASC',
		'posts_per_page' => -1,
		'no_found_rows' => true,
	));

	$fields = toplist_supported_fields();
	$csv_headers = array_merge(array('toplist', 'toplist_id'), $fields);

	nocache_headers();
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=toplists-all.csv');

	$out = fopen('php://output', 'w');
	fputcsv($out, $csv_headers);

	foreach ($posts as $post) {
		if (!($post instanceof WP_Post)) {
			continue;
		}

		$parsed = toplist_parse_lines_to_items((string) $post->post_content, array());
		$items = is_array($parsed['items'] ?? null) ? $parsed['items'] : array();

		foreach ($items as $item) {
			$row = array((string) $post->post_title, (string) $post->ID);
			foreach ($fields as $field) {
				$value = $item[$field] ?? '';
				if (in_array($field, array('bullets', 'payments', 'games', 'withdrawals'), true)) {
					$value = is_array($value) ? implode(';', $value) : toplist_clean_text($value);
				}
				$row[] = (string) $value;
			}
			fputcsv($out, $row);
		}
	}

	fclose($out);
	exit;
}

/**
 * Parse a bulk CSV file and create/update multiple toplists.
 *
 * @param string $file Path to uploaded CSV temporary file.
 * @return array{status:string,updated:int,created:int,rows:int,groups:int}
 */
function toplist_process_bulk_import_csv_file($file)
{
	$file = is_string($file) ? trim($file) : '';
	if ($file === '') {
		return array(
			'status' => 'empty',
			'updated' => 0,
			'created' => 0,
			'rows' => 0,
			'groups' => 0,
		);
	}

	$handle = fopen($file, 'r');
	if (!$handle) {
		return array(
			'status' => 'failed',
			'updated' => 0,
			'created' => 0,
			'rows' => 0,
			'groups' => 0,
		);
	}

	$header_row = fgetcsv($handle);
	if (!is_array($header_row) || empty($header_row)) {
		fclose($handle);
		return array(
			'status' => 'empty',
			'updated' => 0,
			'created' => 0,
			'rows' => 0,
			'groups' => 0,
		);
	}

	$name_index = -1;
	$id_index = -1;
	$field_indexes = array();
	$header_fields = array();
	foreach ($header_row as $i => $cell) {
		$raw = strtolower(trim((string) $cell));
		$raw = preg_replace('/^\xEF\xBB\xBF/u', '', $raw);
		if (in_array($raw, array('toplist', 'toplist_name', 'name', 'list'), true)) {
			$name_index = (int) $i;
			continue;
		}
		if (in_array($raw, array('toplist_id', 'list_id', 'id'), true)) {
			$id_index = (int) $i;
			continue;
		}
		$field = toplist_normalize_csv_header((string) $cell);
		if ($field === '') {
			continue;
		}
		$field_indexes[(int) $i] = $field;
		if (!in_array($field, $header_fields, true)) {
			$header_fields[] = $field;
		}
	}

	if (($name_index < 0 && $id_index < 0) || empty($header_fields)) {
		fclose($handle);
		return array(
			'status' => 'bad_header',
			'updated' => 0,
			'created' => 0,
			'rows' => 0,
			'groups' => 0,
		);
	}

	$groups = array();
	$rows_read = 0;
	while (($csv_row = fgetcsv($handle)) !== false) {
		$rows_read += 1;
		$name = $name_index >= 0 ? trim((string) ($csv_row[$name_index] ?? '')) : '';
		$raw_id = $id_index >= 0 ? trim((string) ($csv_row[$id_index] ?? '')) : '';
		$post_id = $raw_id !== '' ? (int) $raw_id : 0;

		// Fallback for exports where name/id headers are present but not recognized as expected.
		if ($name === '') {
			$fallback_name = trim((string) ($csv_row[0] ?? ''));
			if ($fallback_name !== '' && !is_numeric($fallback_name)) {
				$name = $fallback_name;
			}
		}
		if ($post_id <= 0 && $raw_id === '') {
			$fallback_id = trim((string) ($csv_row[1] ?? ''));
			if ($fallback_id !== '' && ctype_digit($fallback_id)) {
				$post_id = (int) $fallback_id;
			}
		}

		if ($post_id > 0) {
			$post = get_post($post_id);
			if (!$post || $post->post_type !== 'toplist_list') {
				$post_id = 0;
			}
		}

		if ($post_id <= 0 && $name === '') {
			continue;
		}

		$key = $post_id > 0 ? 'id:' . $post_id : 'name:' . strtolower($name);
		if (!isset($groups[$key])) {
			$groups[$key] = array(
				'post_id' => $post_id,
				'name' => $name,
				'rows' => array(),
			);
		}

		$row_values = array();
		foreach ($field_indexes as $index => $field) {
			$row_values[$field] = trim((string) ($csv_row[$index] ?? ''));
		}

		$line_parts = array();
		foreach ($header_fields as $field) {
			$line_parts[] = $row_values[$field] ?? '';
		}
		$line = implode('|', $line_parts);
		if (trim($line) !== '') {
			$groups[$key]['rows'][] = $line;
		}
	}
	fclose($handle);

	if (empty($groups)) {
		return array(
			'status' => 'empty',
			'updated' => 0,
			'created' => 0,
			'rows' => $rows_read,
			'groups' => 0,
		);
	}

	$updated = 0;
	$created = 0;
	foreach ($groups as $group) {
		if (empty($group['rows'])) {
			continue;
		}

		$lines = array(implode('|', $header_fields));
		$lines = array_merge($lines, $group['rows']);
		$content = implode("\n", $lines);

		$post_id = (int) ($group['post_id'] ?? 0);
		$name = trim((string) ($group['name'] ?? ''));

		if ($post_id > 0) {
			wp_update_post(array(
				'ID' => $post_id,
				'post_content' => $content,
			));
			$updated += 1;
			continue;
		}

		$existing = array();
		if ($name !== '') {
			$existing = get_posts(array(
				'post_type' => 'toplist_list',
				'post_status' => array('publish', 'private', 'draft'),
				'title' => $name,
				'posts_per_page' => 1,
				'no_found_rows' => true,
			));
		}

		if (!empty($existing) && $existing[0] instanceof WP_Post) {
			wp_update_post(array(
				'ID' => (int) $existing[0]->ID,
				'post_content' => $content,
			));
			$updated += 1;
			continue;
		}

		$new_id = wp_insert_post(array(
			'post_type' => 'toplist_list',
			'post_title' => $name !== '' ? $name : __('Imported Toplist', 'toplist'),
			'post_content' => $content,
			'post_status' => 'publish',
		), true);

		if (!is_wp_error($new_id) && (int) $new_id > 0) {
			$created += 1;
		}
	}

	return array(
		'status' => ($updated + $created) > 0 ? 'success' : 'no_changes',
		'updated' => $updated,
		'created' => $created,
		'rows' => $rows_read,
		'groups' => count($groups),
	);
}

/**
 * Import a single CSV and create/update multiple toplists.
 *
 * Expected CSV columns:
 * - `toplist` (or `name`) and/or `toplist_id`
 * - standard toplist fields (operator, product, offer, etc.)
 *
 * @return void
 */
function toplist_handle_import_all_csv()
{
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to bulk import toplists.', 'toplist'));
	}

	check_admin_referer('toplist_import_all_csv', 'toplist_import_all_csv_nonce');

	$redirect_url = admin_url('options-general.php?page=toplist-settings');
	$result = toplist_process_bulk_import_csv_file($_FILES['toplist_bulk_csv_file']['tmp_name'] ?? '');
	$upload_error = isset($_FILES['toplist_bulk_csv_file']['error']) ? (int) $_FILES['toplist_bulk_csv_file']['error'] : 0;
	wp_safe_redirect(add_query_arg(array(
		'toplist_bulk_import' => $result['status'],
		'toplist_bulk_updated' => (int) $result['updated'],
		'toplist_bulk_created' => (int) $result['created'],
		'toplist_bulk_rows' => (int) $result['rows'],
		'toplist_bulk_groups' => (int) $result['groups'],
		'toplist_bulk_upload_error' => $upload_error,
	), $redirect_url));
	exit;
}

/**
 * Download a CSV template for bulk import.
 *
 * @return void
 */
function toplist_handle_export_bulk_template_csv()
{
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to export the bulk template.', 'toplist'));
	}
	check_admin_referer('toplist_export_bulk_template_csv');

	$fields = toplist_supported_fields();
	$headers = array_merge(array('toplist', 'toplist_id'), $fields);

	nocache_headers();
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=toplist-bulk-import-template.csv');

	$out = fopen('php://output', 'w');
	fputcsv($out, $headers);

	$sample = array_fill(0, count($headers), '');
	$sample_map = array(
		'toplist' => 'Example Toplist',
		'operator' => 'Operator Name',
		'product' => 'Casino Brand',
		'offer' => '100% Bonus + 50 Free Spins',
		'href' => 'https://example.com',
		'logo' => 'https://example.com/logo.png',
		'year' => '2026',
		'ctaText' => 'Visit Casino',
		'terms' => '18+ T&Cs apply',
		'bullets' => 'Fast payouts;Welcome bonus',
		'payout' => 'Instant',
		'code' => 'WELCOME50',
		'rating' => '4.8',
		'regulator' => 'MGA',
		'payments' => 'Visa;Mastercard;PayPal',
		'games' => 'Slots;Live Casino',
		'liveGames' => 'Yes',
		'smallPrint' => 'Wagering requirements apply',
		'readReviewHref' => 'https://example.com/review',
		'readReviewText' => 'Read Review',
		'withdrawals' => 'Bank Transfer;Skrill',
	);

	foreach ($headers as $i => $header) {
		$sample[$i] = (string) ($sample_map[$header] ?? '');
	}
	fputcsv($out, $sample);
	fclose($out);
	exit;
}

/**
 * Import CSV into one saved toplist.
 *
 * @return void
 */
function toplist_handle_import_csv()
{
	$post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
	if (!$post_id || !current_user_can('edit_post', $post_id)) {
		wp_die(esc_html__('You do not have permission to import into this toplist.', 'toplist'));
	}
	if (!isset($_POST['toplist_import_csv_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['toplist_import_csv_nonce'])), 'toplist_import_csv_' . $post_id)) {
		wp_die(esc_html__('Invalid CSV import request.', 'toplist'));
	}
	if (empty($_FILES['toplist_csv_file']['tmp_name'])) {
		wp_safe_redirect(add_query_arg('toplist_import', 'empty', get_edit_post_link($post_id, '')));
		exit;
	}

	$file = $_FILES['toplist_csv_file']['tmp_name'];
	$handle = fopen($file, 'r');
	if (!$handle) {
		wp_safe_redirect(add_query_arg('toplist_import', 'failed', get_edit_post_link($post_id, '')));
		exit;
	}

	$header_row = fgetcsv($handle);
	if (!is_array($header_row)) {
		fclose($handle);
		wp_safe_redirect(add_query_arg('toplist_import', 'empty', get_edit_post_link($post_id, '')));
		exit;
	}

	$normalized_headers = array();
	foreach ($header_row as $header_cell) {
		$normalized_headers[] = toplist_normalize_csv_header($header_cell);
	}

	$recognized_headers = array_values(array_filter($normalized_headers));
	$use_index_mapping = count($recognized_headers) < 3;
	$fields = toplist_supported_fields();
	$columns = $use_index_mapping ? $fields : $normalized_headers;
	$header_for_lines = $use_index_mapping ? $fields : array_values(array_filter($normalized_headers));

	$lines = array();
	if (!empty($header_for_lines)) {
		$lines[] = implode('|', $header_for_lines);
	}

	while (($csv_row = fgetcsv($handle)) !== false) {
		$values_by_field = array();
		foreach ($fields as $field) {
			$values_by_field[$field] = '';
		}

		for ($i = 0; $i < count($columns); $i += 1) {
			$field = $columns[$i] ?? '';
			if ($field === '' || !in_array($field, $fields, true)) {
				continue;
			}
			$values_by_field[$field] = trim((string) ($csv_row[$i] ?? ''));
		}

		$line_parts = array();
		foreach ($header_for_lines as $field) {
			$line_parts[] = $values_by_field[$field] ?? '';
		}
		$line = implode('|', $line_parts);
		if (trim($line) !== '') {
			$lines[] = $line;
		}
	}
	fclose($handle);

	$new_content = implode("\n", $lines);
	wp_update_post(array(
		'ID' => $post_id,
		'post_content' => $new_content,
	));

	wp_safe_redirect(add_query_arg('toplist_import', 'success', get_edit_post_link($post_id, '')));
	exit;
}

/**
 * Show import status notices on toplist edit screen.
 *
 * @return void
 */
function toplist_import_admin_notice()
{
	$screen = get_current_screen();
	if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'toplist_list') {
		return;
	}
	$status = isset($_GET['toplist_import']) ? sanitize_key((string) $_GET['toplist_import']) : '';

	if ($status !== '') {
		$message = '';
		$class = 'notice notice-info';
		if ($status === 'success') {
			$message = __('CSV imported successfully.', 'toplist');
			$class = 'notice notice-success';
		} elseif ($status === 'empty') {
			$message = __('CSV import failed: file was empty or invalid.', 'toplist');
			$class = 'notice notice-warning';
		} elseif ($status === 'failed') {
			$message = __('CSV import failed: unable to read the uploaded file.', 'toplist');
			$class = 'notice notice-error';
		}

		if ($message !== '') {
			echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($message) . '</p></div>';
		}
	}

	$json_status = isset($_GET['toplist_json_import']) ? sanitize_key((string) $_GET['toplist_json_import']) : '';
	if ($json_status !== '') {
		$json_message = '';
		$json_class = 'notice notice-info';
		if ($json_status === 'success') {
			$json_message = __('JSON imported successfully.', 'toplist');
			$json_class = 'notice notice-success';
		} elseif ($json_status === 'empty') {
			$json_message = __('JSON import failed: file was empty or no valid rows.', 'toplist');
			$json_class = 'notice notice-warning';
		} elseif ($json_status === 'failed') {
			$json_message = __('JSON import failed: unable to read the uploaded file.', 'toplist');
			$json_class = 'notice notice-error';
		} elseif ($json_status === 'invalid') {
			$json_message = __('JSON import failed: invalid JSON (expected a JSON array).', 'toplist');
			$json_class = 'notice notice-error';
		}

		if ($json_message !== '') {
			echo '<div class="' . esc_attr($json_class) . '"><p>' . esc_html($json_message) . '</p></div>';
		}
	}
}

/**
 * Enqueue media uploader for toplist edit screen row builder.
 *
 * @param string $hook_suffix Admin hook.
 * @return void
 */
function toplist_enqueue_toplist_admin_assets($hook_suffix)
{
	if ($hook_suffix !== 'post.php' && $hook_suffix !== 'post-new.php') {
		return;
	}
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	$post_type = '';
	if ($screen && !empty($screen->post_type)) {
		$post_type = (string) $screen->post_type;
	} elseif (isset($_GET['post_type'])) {
		$post_type = sanitize_key((string) $_GET['post_type']);
	} elseif (isset($_GET['post'])) {
		$post_type = get_post_type((int) $_GET['post']) ?: '';
	}
	if ($post_type !== 'toplist_list') {
		return;
	}
	wp_enqueue_media();
}

function toplist_register_block()
{
	wp_register_script(
		'toplist-block',
		plugins_url('block.js', __FILE__),
		array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch'),
		filemtime(__DIR__ . '/block.js')
	);
	wp_add_inline_script(
		'toplist-block',
		'window.toplistBlockSettings = ' . wp_json_encode(array(
			'globalDefaultHeaderEnabled' => toplist_get_global_bool_option('toplist_global_enable_default_header', false),
			'globalDefaultHeaderRow' => toplist_get_global_text_option('toplist_global_default_header_row', ''),
			'globalToplistHeading' => toplist_get_global_text_option('toplist_global_heading_text', ''),
		)) . ';',
		'before'
	);

	wp_register_style(
		'toplist-style',
		plugins_url('style.css', __FILE__),
		array(),
		filemtime(__DIR__ . '/style.css')
	);

	wp_register_script(
		'toplist-view',
		plugins_url('view.js', __FILE__),
		array(),
		filemtime(__DIR__ . '/view.js'),
		true
	);

	register_block_type('toplist/rankings', array(
		'editor_script' => 'toplist-block',
		'style' => 'toplist-style',
		'script' => 'toplist-view',
		'render_callback' => 'toplist_render',
		'attributes' => array(
			'items' => array(
				'type' => 'array',
				'default' => array(),
				'items' => array('type' => 'object'),
			),
			'listId' => array(
				'type' => 'number',
				'default' => 1,
			),
			'listType' => array(
				'type' => 'string',
				'default' => 'product-ranking-best',
			),
			'disclaimer' => array(
				'type' => 'string',
				'default' => '#ad. 18+. Gamble Responsibly. GambleAware.org.',
			),
			'customCSS' => array(
				'type' => 'string',
				'default' => '',
			),
			'defaultCtaText' => array(
				'type' => 'string',
				'default' => 'Visit',
			),
			'defaultReadReviewText' => array(
				'type' => 'string',
				'default' => 'Read Review',
			),
			'showYear' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showLogo' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showTerms' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showBullets' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showOffer' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showPayout' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showCode' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showRating' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showRegulator' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showPayments' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showGames' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showLiveGames' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showSmallPrint' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showReadReview' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showWithdrawals' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'fieldIncludes' => array(
				'type' => 'array',
				'default' => array(),
			),
			'fieldExcludes' => array(
				'type' => 'array',
				'default' => array(),
			),
			'savedToplistId' => array(
				'type' => 'number',
				'default' => 0,
			),
			'savedToplistMode' => array(
				'type' => 'string',
				'default' => 'linked',
			),
			'defaultHeaderMode' => array(
				'type' => 'string',
				'default' => 'global',
			),
			'defaultHeaderRow' => array(
				'type' => 'string',
				'default' => '',
			),
			'headingMode' => array(
				'type' => 'string',
				'default' => 'global',
			),
			'headingText' => array(
				'type' => 'string',
				'default' => '',
			),
		),
	));
}
add_action('init', 'toplist_register_block');
add_action('init', 'toplist_register_cpt');
add_filter('use_block_editor_for_post_type', 'toplist_disable_block_editor_for_toplists', 10, 2);
add_filter('manage_toplist_list_posts_columns', 'toplist_toplists_admin_columns');
add_action('manage_toplist_list_posts_custom_column', 'toplist_toplists_admin_column_content', 10, 2);
add_action('rest_api_init', 'toplist_register_rest_routes');
add_action('add_meta_boxes_toplist_list', 'toplist_register_toplist_metaboxes');
add_action('save_post_toplist_list', 'toplist_save_toplist_raw_content');
add_action('admin_post_toplist_export_csv', 'toplist_handle_export_csv');
add_action('admin_post_toplist_export_json', 'toplist_handle_export_json');
add_action('admin_post_toplist_export_all_csv', 'toplist_handle_export_all_csv');
add_action('admin_post_toplist_export_bulk_template_csv', 'toplist_handle_export_bulk_template_csv');
add_action('admin_post_toplist_import_csv', 'toplist_handle_import_csv');
add_action('admin_post_toplist_import_json', 'toplist_handle_import_json');
add_action('admin_post_toplist_import_all_csv', 'toplist_handle_import_all_csv');
add_action('admin_notices', 'toplist_import_admin_notice');
add_action('admin_enqueue_scripts', 'toplist_enqueue_toplist_admin_assets');
add_action('admin_footer', 'toplist_print_import_forms_in_footer', 5);

if (is_admin()) {
	require_once __DIR__ . '/admin-diagnostics.php';
	require_once __DIR__ . '/settings-page.php';
}

function toplist_render($attributes)
{
	$list_id = isset($attributes['listId']) ? (int) $attributes['listId'] : 1;
	$list_type = isset($attributes['listType']) ? (string) $attributes['listType'] : 'product-ranking-best';
	$disc = isset($attributes['disclaimer']) ? (string) $attributes['disclaimer'] : '';
	$custom_css = isset($attributes['customCSS']) ? (string) $attributes['customCSS'] : '';
	$saved_toplist_id = isset($attributes['savedToplistId']) ? (int) $attributes['savedToplistId'] : 0;
	$saved_toplist_mode = isset($attributes['savedToplistMode']) ? (string) $attributes['savedToplistMode'] : 'linked';
	$default_header_mode = isset($attributes['defaultHeaderMode']) ? (string) $attributes['defaultHeaderMode'] : 'global';
	$block_default_header_row = isset($attributes['defaultHeaderRow']) ? trim((string) $attributes['defaultHeaderRow']) : '';
	$heading_mode = isset($attributes['headingMode']) ? (string) $attributes['headingMode'] : 'global';
	$block_heading_text = isset($attributes['headingText']) ? trim((string) $attributes['headingText']) : '';
	$lines = '';
	$global_default_cta_text = toplist_get_global_text_option('toplist_global_default_cta_text', 'Visit');
	$global_default_read_review_text = toplist_get_global_text_option('toplist_global_default_read_review_text', 'Read Review');
	$global_default_cta_text = $global_default_cta_text !== '' ? $global_default_cta_text : 'Visit';
	$global_default_read_review_text = $global_default_read_review_text !== '' ? $global_default_read_review_text : 'Read Review';
	$default_cta_text = toplist_clean_text($attributes['defaultCtaText'] ?? $global_default_cta_text);
	$default_cta_text = $default_cta_text !== '' ? $default_cta_text : $global_default_cta_text;
	$default_read_review_text = toplist_clean_text($attributes['defaultReadReviewText'] ?? $global_default_read_review_text);
	$default_read_review_text = $default_read_review_text !== '' ? $default_read_review_text : $global_default_read_review_text;
	$global_default_header_enabled = toplist_get_global_bool_option('toplist_global_enable_default_header', false);
	$global_default_header_row = toplist_get_global_text_option('toplist_global_default_header_row', '');
	$global_heading_text = toplist_get_global_text_option('toplist_global_heading_text', '');
	$effective_default_header_row = '';
	$effective_heading_text = '';

	if ($default_header_mode === 'custom') {
		$effective_default_header_row = $block_default_header_row;
	} elseif ($default_header_mode === 'global') {
		$effective_default_header_row = $global_default_header_enabled ? $global_default_header_row : '';
	}

	if ($heading_mode === 'custom') {
		$effective_heading_text = $block_heading_text;
	} elseif ($heading_mode === 'global') {
		$effective_heading_text = $global_heading_text;
	}

	$supported_fields = toplist_supported_fields();
	$field_includes = array();
	$field_excludes = array();

	// Linked mode: source all rows from saved Toplist library post content.
	if ($saved_toplist_mode === 'linked' && $saved_toplist_id > 0) {
		$saved_post = get_post($saved_toplist_id);
		if ($saved_post && $saved_post->post_type === 'toplist_list') {
			$lines = (string) $saved_post->post_content;
		}
	}

	if (trim($lines) !== '') {
		$parsed = toplist_parse_lines_to_items($lines, array(
			'defaultCtaText' => $default_cta_text,
			'defaultReadReviewText' => $default_read_review_text,
			'defaultHeaderRow' => $effective_default_header_row,
		));
		$items = $parsed['items'];
		$field_includes = array_values(array_unique(array_values(array_intersect($supported_fields, $parsed['includes']))));
		$field_excludes = array_values(array_unique(array_values(array_intersect($supported_fields, $parsed['excludes']))));
	} else {
		$items = isset($attributes['items']) && is_array($attributes['items']) ? $attributes['items'] : array();
		$items = array_values(array_filter($items, 'toplist_item_has_content'));
		$field_includes = isset($attributes['fieldIncludes']) && is_array($attributes['fieldIncludes'])
			? array_values(array_unique(array_values(array_intersect($supported_fields, $attributes['fieldIncludes']))))
			: array();
		$field_excludes = isset($attributes['fieldExcludes']) && is_array($attributes['fieldExcludes'])
			? array_values(array_unique(array_values(array_intersect($supported_fields, $attributes['fieldExcludes']))))
			: array();
	}

	if (empty($items)) {
		return '';
	}

	$show_year = (isset($attributes['showYear']) ? (bool) $attributes['showYear'] : true) && toplist_get_global_bool_option('toplist_global_show_year', true);
	$show_logo = (isset($attributes['showLogo']) ? (bool) $attributes['showLogo'] : true) && toplist_get_global_bool_option('toplist_global_show_logo', true);
	$show_terms = (isset($attributes['showTerms']) ? (bool) $attributes['showTerms'] : true) && toplist_get_global_bool_option('toplist_global_show_terms', true);
	$show_bullets = (isset($attributes['showBullets']) ? (bool) $attributes['showBullets'] : true) && toplist_get_global_bool_option('toplist_global_show_bullets', true);
	$show_offer = (isset($attributes['showOffer']) ? (bool) $attributes['showOffer'] : true) && toplist_get_global_bool_option('toplist_global_show_offer', true);
	$show_payout = (isset($attributes['showPayout']) ? (bool) $attributes['showPayout'] : true) && toplist_get_global_bool_option('toplist_global_show_payout', true);
	$show_code = (isset($attributes['showCode']) ? (bool) $attributes['showCode'] : true) && toplist_get_global_bool_option('toplist_global_show_code', true);
	$show_rating = (isset($attributes['showRating']) ? (bool) $attributes['showRating'] : true) && toplist_get_global_bool_option('toplist_global_show_rating', true);
	$show_regulator = (isset($attributes['showRegulator']) ? (bool) $attributes['showRegulator'] : true) && toplist_get_global_bool_option('toplist_global_show_regulator', true);
	$show_payments = (isset($attributes['showPayments']) ? (bool) $attributes['showPayments'] : true) && toplist_get_global_bool_option('toplist_global_show_payments', true);
	$show_games = (isset($attributes['showGames']) ? (bool) $attributes['showGames'] : true) && toplist_get_global_bool_option('toplist_global_show_games', true);
	$show_live_games = (isset($attributes['showLiveGames']) ? (bool) $attributes['showLiveGames'] : true) && toplist_get_global_bool_option('toplist_global_show_live_games', true);
	$show_small_print = (isset($attributes['showSmallPrint']) ? (bool) $attributes['showSmallPrint'] : true) && toplist_get_global_bool_option('toplist_global_show_small_print', true);
	$show_read_review = (isset($attributes['showReadReview']) ? (bool) $attributes['showReadReview'] : true) && toplist_get_global_bool_option('toplist_global_show_read_review', true);
	$show_withdrawals = (isset($attributes['showWithdrawals']) ? (bool) $attributes['showWithdrawals'] : true) && toplist_get_global_bool_option('toplist_global_show_withdrawals', true);

	ob_start();

	$global_css = get_option('toplist_global_css', '');
	$global_css = trim(wp_strip_all_tags($global_css));
	if ($global_css !== '') {
		echo '<style>' . $global_css . '</style>';
	}
	if ($custom_css !== '') {
		echo '<style>' . wp_strip_all_tags($custom_css) . '</style>';
	}
	if ($effective_heading_text !== '') {
		echo '<h2 class="toplist-heading">' . esc_html($effective_heading_text) . '</h2>';
	}
	?>
	<ol class="automation-rwd-table rwd-table toplist" data-toplist-listid="<?php echo esc_attr($list_id); ?>"
		data-toplist-listtype="<?php echo esc_attr($list_type); ?>">
		<?php foreach ($items as $i => $item):
			$pos = $i + 1;

			$operator = toplist_field_is_included('operator', $field_includes, $field_excludes) ? toplist_clean_text($item['operator'] ?? '') : '';
			$product = toplist_field_is_included('product', $field_includes, $field_excludes) ? toplist_clean_text($item['product'] ?? '') : '';
			$offer = toplist_field_is_included('offer', $field_includes, $field_excludes) ? toplist_clean_text($item['offer'] ?? '') : '';
			$href = toplist_field_is_included('href', $field_includes, $field_excludes) ? esc_url_raw(toplist_clean_text($item['href'] ?? '')) : '';
			$logo = toplist_field_is_included('logo', $field_includes, $field_excludes) ? esc_url_raw(toplist_clean_text($item['logo'] ?? '')) : '';
			$year = toplist_field_is_included('year', $field_includes, $field_excludes) ? toplist_clean_text($item['year'] ?? '') : '';
			$terms_enabled = toplist_field_is_included('terms', $field_includes, $field_excludes);
			$terms = $terms_enabled ? toplist_clean_text($item['terms'] ?? '') : '';
			$payout = toplist_field_is_included('payout', $field_includes, $field_excludes) ? toplist_clean_text($item['payout'] ?? '') : '';
			$code = toplist_field_is_included('code', $field_includes, $field_excludes) ? toplist_clean_text($item['code'] ?? '') : '';
			$rating = toplist_field_is_included('rating', $field_includes, $field_excludes) ? toplist_clean_text($item['rating'] ?? '') : '';
			$regulator = toplist_field_is_included('regulator', $field_includes, $field_excludes) ? toplist_clean_text($item['regulator'] ?? '') : '';
			$live_games = toplist_field_is_included('liveGames', $field_includes, $field_excludes) ? toplist_clean_text($item['liveGames'] ?? '') : '';
			$small_print = toplist_field_is_included('smallPrint', $field_includes, $field_excludes) ? toplist_clean_text($item['smallPrint'] ?? '') : '';
			$read_review_href = toplist_field_is_included('readReviewHref', $field_includes, $field_excludes) ? esc_url_raw(toplist_clean_text($item['readReviewHref'] ?? '')) : '';

			$cta_text_enabled = toplist_field_is_included('ctaText', $field_includes, $field_excludes);
			$read_review_text_enabled = toplist_field_is_included('readReviewText', $field_includes, $field_excludes);
			$cta_text_value = $cta_text_enabled ? toplist_clean_text($item['ctaText'] ?? '') : '';
			$read_review_text_value = $read_review_text_enabled ? toplist_clean_text($item['readReviewText'] ?? '') : '';
			$ctaText = $cta_text_value !== '' ? $cta_text_value : $default_cta_text;
			$read_review_text = $read_review_text_value !== '' ? $read_review_text_value : $default_read_review_text;

			$bullets = toplist_field_is_included('bullets', $field_includes, $field_excludes) ? toplist_clean_list($item['bullets'] ?? array()) : array();
			$payments = toplist_field_is_included('payments', $field_includes, $field_excludes) ? toplist_clean_list($item['payments'] ?? array()) : array();
			$games = toplist_field_is_included('games', $field_includes, $field_excludes) ? toplist_clean_list($item['games'] ?? array()) : array();
			$withdrawals = toplist_field_is_included('withdrawals', $field_includes, $field_excludes) ? toplist_clean_list($item['withdrawals'] ?? array()) : array();

			$summary_bullets = array_slice($bullets, 0, 2);
			$extra_bullets = array_slice($bullets, 2);

				$has_logo_content = (bool) $logo || $product !== '' || $operator !== '';
				$has_identity_text = ($product !== '' || $operator !== '');
				$has_identity_meta = $has_identity_text || $has_rating || ($show_year && $year !== '') || $has_regulator;
			$has_offer = $show_offer && $offer !== '';
			$has_cta = $href !== '' && $ctaText !== '';
			$has_read_review = $show_read_review && $read_review_href !== '';
			$has_payout = $show_payout && $payout !== '';
			$has_code = $show_code && $code !== '';
			$has_rating = $show_rating && $rating !== '';
			$has_regulator = $show_regulator && $regulator !== '';
			$has_payments = $show_payments && !empty($payments);
			$has_games = $show_games && !empty($games);
			$has_live_games = $show_live_games && $live_games !== '';
			$has_small_print = $show_small_print && $small_print !== '';
			$has_withdrawals = $show_withdrawals && !empty($withdrawals);
			$has_terms = $show_terms && $terms_enabled && (toplist_clean_text($disc) !== '' || $terms !== '');
			$has_summary_bullets = $show_bullets && !empty($summary_bullets);
			$has_extra_bullets = $show_bullets && !empty($extra_bullets);
			$has_summary_details = $has_payout || $has_summary_bullets;
			$has_extra_details = $has_extra_bullets || $has_games || $has_live_games || $has_small_print || $has_withdrawals;
			$has_details = $has_summary_details || $has_extra_details;
			$has_play_column = $has_code || $has_read_review || $has_cta || $has_payments;
			$details_expanded = (1 === (int) $pos);
			?>
			<li class="operator-item automation-operator-item operator-item-v2"
				data-operator="<?php echo esc_attr($operator); ?>" data-product="<?php echo esc_attr($product); ?>"
				data-position="<?php echo esc_attr($pos); ?>">
				<div class="operator-main">
					<div class="op-left">
						<div class="operator-column-ranking-v2"><span><?php echo esc_html($pos); ?></span></div>

							<?php if (($show_logo && $has_logo_content) || $has_identity_meta): ?>
								<div class="operator-column-logo-v2 logo-wrapper">
									<?php if ($show_logo && $has_logo_content): ?>
										<?php if ($href): ?>
											<a class="operator-item__image_link exit-page-link" rel="nofollow" target="_blank"
												href="<?php echo esc_url($href); ?>">
												<?php if ($logo): ?>
													<img class="op-logo" src="<?php echo esc_url($logo); ?>" loading="lazy"
														alt="<?php echo esc_attr($product ?: $operator); ?>" width="150" height="100" />
												<?php else: ?>
													<span class="op-logo-fallback"><?php echo esc_html($product ?: $operator); ?></span>
												<?php endif; ?>
											</a>
										<?php else: ?>
											<?php if ($logo): ?>
												<img class="op-logo" src="<?php echo esc_url($logo); ?>" loading="lazy"
													alt="<?php echo esc_attr($product ?: $operator); ?>" width="150" height="100" />
											<?php else: ?>
												<span class="op-logo-fallback"><?php echo esc_html($product ?: $operator); ?></span>
											<?php endif; ?>
										<?php endif; ?>
									<?php endif; ?>

									<?php if ($has_identity_meta): ?>
										<div class="operator-title-row-v2">
											<?php if ($has_identity_text): ?>
												<div class="operator-product-name-v2"><?php echo esc_html($product ?: $operator); ?></div>
											<?php endif; ?>
										<?php if ($has_rating): ?>
											<div class="operator-rating-v2"><span class="operator-rating-star-v2" aria-hidden="true">★</span> <?php echo esc_html($rating); ?> <span class="operator-rating-outof-v2">/ 5</span></div>
										<?php endif; ?>
									</div>
								<?php endif; ?>

								<?php if ($show_year && $year): ?>
									<div class="operator-established-year-v2">Launched <?php echo esc_html($year); ?></div>
								<?php endif; ?>

								<?php if ($has_regulator): ?>
									<div class="operator-regulator-v2">Regulated by: <?php echo esc_html($regulator); ?></div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="op-right">
						<?php if ($has_offer || $has_terms || $has_details): ?>
							<div class="operator-column-bonus-v2">
								<?php if ($has_offer): ?>
									<?php if ($href): ?>
										<a class="offer-description exit-page-link" rel="nofollow" target="_blank"
											href="<?php echo esc_url($href); ?>">
											<?php echo esc_html($offer); ?>
										</a>
									<?php else: ?>
										<div class="offer-description"><?php echo esc_html($offer); ?></div>
									<?php endif; ?>
								<?php endif; ?>

								<?php if ($has_terms && !$has_small_print): ?>
									<div class="operator-item-link read-terms-link-v2">
										<span class="terms-and-conditions"><?php echo esc_html(trim($disc . ' ' . $terms)); ?></span>
									</div>
								<?php endif; ?>

								<?php if ($has_details): ?>
									<div class="more-info-table">
										<?php if ($has_payout): ?>
											<div class="operator-payout-v2"><strong>Payout time:</strong> <?php echo esc_html($payout); ?></div>
										<?php endif; ?>

										<?php if ($has_summary_bullets): ?>
											<div class="attributes-list attributes-list--summary">
												<?php foreach ($summary_bullets as $b): ?>
													<?php
													$bullet_text = (string) $b;
													$is_negative = (bool) preg_match('/^\s*[-!xX]\s+/', $bullet_text);
													$bullet_text = preg_replace('/^\s*[-!xX]\s+/', '', $bullet_text);
													?>
													<div class="<?php echo $is_negative ? 'gray-cross ' : 'green-tick '; ?>attribute-list-item"><?php echo esc_html($bullet_text); ?></div>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>

										<?php if ($has_extra_details): ?>
											<div class="more-info-extra" data-toplist-details style="display:<?php echo $details_expanded ? 'block' : 'none'; ?>;">
												<?php if ($has_extra_bullets): ?>
													<div class="attributes-list attributes-list--extra">
														<?php foreach ($extra_bullets as $b): ?>
															<?php
															$bullet_text = (string) $b;
															$is_negative = (bool) preg_match('/^\s*[-!xX]\s+/', $bullet_text);
															$bullet_text = preg_replace('/^\s*[-!xX]\s+/', '', $bullet_text);
															?>
															<div class="<?php echo $is_negative ? 'gray-cross ' : 'green-tick '; ?>attribute-list-item"><?php echo esc_html($bullet_text); ?></div>
														<?php endforeach; ?>
													</div>
												<?php endif; ?>

												<?php if ($has_games): ?>
													<div class="operator-games-v2"><strong>Games:</strong> <?php echo esc_html(implode(' ', $games)); ?></div>
												<?php endif; ?>
												<?php if ($has_live_games): ?>
													<div class="operator-live-games-v2"><strong>Live Games:</strong> <?php echo esc_html($live_games); ?></div>
												<?php endif; ?>
												<?php if ($has_withdrawals): ?>
													<div class="operator-withdrawals-v2"><strong>Withdrawals:</strong> <?php echo esc_html(implode(', ', $withdrawals)); ?></div>
												<?php endif; ?>
												<?php if ($has_small_print): ?>
													<div class="operator-small-print-v2"><?php echo esc_html($small_print); ?></div>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ($has_play_column): ?>
							<div class="operator-playnow-column-v2">
								<?php if ($has_code): ?>
									<div class="operator-code-v2">Use code: <strong><?php echo esc_html($code); ?></strong></div>
								<?php endif; ?>
								<?php if ($has_read_review): ?>
									<a class="operator-item__cta_link exit-page-link" rel="nofollow" target="_blank"
										href="<?php echo esc_url($read_review_href); ?>">
										<span class="button-ghost-v2"><?php echo esc_html($read_review_text); ?></span>
									</a>
								<?php endif; ?>
								<?php if ($has_cta): ?>
									<a class="operator-item__cta_link exit-page-link" rel="nofollow" target="_blank"
										href="<?php echo esc_url($href); ?>">
										<span class="button-blue-v2"><?php echo esc_html($ctaText); ?></span>
									</a>
								<?php endif; ?>
								<?php if ($has_payments): ?>
									<div class="operator-payments-v2">
										<?php foreach ($payments as $payment): ?>
											<span class="payment-chip-v2"><?php echo esc_html($payment); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<?php if ($has_extra_details): ?>
					<button type="button" class="more_info_button<?php echo $details_expanded ? '' : ' is-collapsed'; ?>" data-toplist-toggle="details" aria-expanded="<?php echo $details_expanded ? 'true' : 'false'; ?>"><?php echo $details_expanded ? 'Hide Details' : 'Show Details'; ?></button>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
	return ob_get_clean();
}
