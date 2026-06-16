<?php
/**
 * Toplist edit screen UX: live preview + per-list overrides (ticket 610).
 *
 * @package Toplist_Block
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * @return array<string, array<string, string>>
 */
function toplist_get_toggle_groups(): array {
	return array(
		'Identity & Trust' => array(
			'toplist_global_show_logo' => 'Show Logo',
			'toplist_global_show_year' => 'Show Launch Year',
			'toplist_global_show_rating' => 'Show Rating',
			'toplist_global_show_regulator' => 'Show Regulator',
		),
		'Offer & Terms' => array(
			'toplist_global_show_offer' => 'Show Offer',
			'toplist_global_show_terms' => 'Show Terms & Conditions',
			'toplist_global_show_bullets' => 'Show Bullet Points',
			'toplist_global_show_payout' => 'Show Payout',
			'toplist_global_show_small_print' => 'Show Small Print',
		),
		'Actions & Payments' => array(
			'toplist_global_show_code' => 'Show Code',
			'toplist_global_show_read_review' => 'Show Read Review',
			'toplist_global_show_payments' => 'Show Payments',
		),
		'Game Details' => array(
			'toplist_global_show_games' => 'Show Games',
			'toplist_global_show_live_games' => 'Show Live Games',
			'toplist_global_show_withdrawals' => 'Show Withdrawals',
		),
	);
}

/**
 * @param int    $list_context_id Saved toplist post ID (0 = none).
 * @param string $global_key      Global option key.
 * @param bool   $default         Default when global unset.
 * @return bool
 */
function toplist_get_effective_bool_option(int $list_context_id, string $global_key, bool $default = true): bool {
	if ($list_context_id > 0) {
		$raw = get_post_meta($list_context_id, '_toplist_ov_' . $global_key, true);
		if (is_string($raw) && $raw !== '') {
			return in_array(strtolower($raw), array('1', 'true', 'yes', 'on'), true);
		}
	}
	return toplist_get_global_bool_option($global_key, $default);
}

/**
 * @param int    $list_context_id Saved toplist post ID.
 * @param string $global_key      Global option key.
 * @param string $default         Default text.
 * @return string
 */
function toplist_get_effective_text_option(int $list_context_id, string $global_key, string $default = ''): string {
	if ($list_context_id > 0) {
		$meta_key = '_toplist_ov_' . $global_key;
		$raw = get_post_meta($list_context_id, $meta_key, true);
		if (is_string($raw) && $raw !== '') {
			return trim($raw);
		}
	}
	return toplist_get_global_text_option($global_key, $default);
}

/**
 * @param int $list_context_id Saved toplist post ID.
 * @return string
 */
function toplist_get_effective_list_css(int $list_context_id): string {
	if ($list_context_id > 0) {
		$css = get_post_meta($list_context_id, '_toplist_list_css', true);
		if (is_string($css) && trim($css) !== '') {
			return trim(toplist_sanitize_css($css));
		}
	}
	$global = get_option('toplist_global_css', '');
	return is_string($global) ? trim(wp_strip_all_tags($global)) : '';
}

/**
 * @return void
 */
function toplist_register_editor_ux_hooks(): void {
	add_filter('toplist_render_list_context_id', 'toplist_filter_render_list_context_id', 10, 4);
	add_filter('toplist_get_render_bool', 'toplist_filter_render_bool_override', 10, 4);
	add_filter('toplist_get_render_css', 'toplist_filter_render_css_override', 10, 2);
	add_action('add_meta_boxes_toplist_list', 'toplist_register_editor_ux_metaboxes', 20);
	add_action('save_post_toplist_list', 'toplist_save_list_overrides', 15);
	add_action('wp_ajax_toplist_preview_list', 'toplist_ajax_preview_list');
	add_action('admin_enqueue_scripts', 'toplist_enqueue_editor_ux_assets');
}

/**
 * @return void
 */
function toplist_register_editor_ux_metaboxes(): void {
	add_meta_box(
		'toplist_live_preview_box',
		__('Live Preview', 'toplist'),
		'toplist_render_live_preview_metabox',
		'toplist_list',
		'normal',
		'low'
	);

	add_meta_box(
		'toplist_list_overrides_box',
		__('Per-List Theme & Visibility', 'toplist'),
		'toplist_render_list_overrides_metabox',
		'toplist_list',
		'side',
		'default'
	);

	add_meta_box(
		'toplist_remote_source_box',
		__('Remote Sync', 'toplist'),
		'toplist_render_remote_source_metabox',
		'toplist_list',
		'side',
		'default'
	);
}

/**
 * @param int                  $context_id Current list context.
 * @param array<string, mixed> $attributes Block attributes.
 * @param int                  $saved_id   Saved toplist ID.
 * @param string               $mode       Saved toplist mode.
 * @return int
 */
function toplist_filter_render_list_context_id(int $context_id, array $attributes, int $saved_id, string $mode): int {
	if ($saved_id > 0 && in_array($mode, array('linked', 'preview'), true)) {
		return $saved_id;
	}
	return $context_id;
}

/**
 * @param bool   $value      Global default.
 * @param int    $context_id List post ID.
 * @param string $global_key Option key.
 * @param bool   $default    Fallback default.
 * @return bool
 */
function toplist_filter_render_bool_override(bool $value, int $context_id, string $global_key, bool $default): bool {
	if ($context_id <= 0) {
		return $value;
	}
	return toplist_get_effective_bool_option($context_id, $global_key, $default);
}

/**
 * @param string $css        CSS from prior filters.
 * @param int    $context_id List post ID.
 * @return string
 */
function toplist_filter_render_css_override(string $css, int $context_id): string {
	if ($context_id <= 0) {
		return $css;
	}
	return toplist_get_effective_list_css($context_id);
}

/**
 * @param WP_Post $post Post.
 * @return void
 */
function toplist_render_live_preview_metabox($post): void {
	echo '<div id="toplist-live-preview-root" data-post-id="' . esc_attr((string) $post->ID) . '">';
	echo '<p class="description">' . esc_html__('Preview updates as you edit pipe data or override settings.', 'toplist') . '</p>';
	echo '<div id="toplist-live-preview-frame" class="toplist-live-preview-frame" style="border:1px solid #ccd0d4;padding:12px;background:#fff;min-height:120px;"></div>';
	echo '</div>';
}

/**
 * @param WP_Post $post Post.
 * @return void
 */
function toplist_render_list_overrides_metabox($post): void {
	wp_nonce_field('toplist_save_list_overrides', 'toplist_list_overrides_nonce');

	$list_css = get_post_meta($post->ID, '_toplist_list_css', true);
	$list_css = is_string($list_css) ? $list_css : '';

	echo '<p><label for="toplist_list_css"><strong>' . esc_html__('List CSS override', 'toplist') . '</strong></label></p>';
	echo '<textarea id="toplist_list_css" name="toplist_list_css" rows="4" class="widefat code" placeholder="' . esc_attr__('Inherits global CSS when empty', 'toplist') . '">' . esc_textarea($list_css) . '</textarea>';

	foreach (toplist_get_toggle_groups() as $group_label => $fields) {
		echo '<p><strong>' . esc_html($group_label) . '</strong></p>';
		foreach ($fields as $option_name => $label) {
			$meta_key = '_toplist_ov_' . $option_name;
			$override = get_post_meta($post->ID, $meta_key, true);
			$is_override = is_string($override) && $override !== '';
			$global_on = toplist_get_global_bool_option($option_name, true);
			$checked = $is_override ? in_array(strtolower($override), array('1', 'true', 'yes', 'on'), true) : $global_on;
			$inherit_id = 'toplist_inherit_' . esc_attr($option_name);
			$field_id = 'toplist_ov_' . esc_attr($option_name);

			echo '<p style="margin:0 0 8px;">';
			echo '<label style="display:block;margin-bottom:4px;">';
			echo '<input type="checkbox" id="' . $inherit_id . '" class="toplist-override-inherit" data-target="' . esc_attr($field_id) . '" name="toplist_override_inherit[' . esc_attr($option_name) . ']" value="1" ' . checked(!$is_override, true, false) . ' /> ';
			echo esc_html__('Use global', 'toplist') . ' (' . ($global_on ? esc_html__('on', 'toplist') : esc_html__('off', 'toplist')) . ')';
			echo '</label>';
			echo '<label for="' . $field_id . '" style="' . ($is_override ? '' : 'opacity:.55;') . '">';
			echo '<input type="checkbox" id="' . $field_id . '" name="toplist_override_toggle[' . esc_attr($option_name) . ']" value="1" ' . checked($checked, true, false) . ($is_override ? '' : ' disabled') . ' /> ';
			echo esc_html($label);
			echo '</label></p>';
		}
	}
}

/**
 * @param int $post_id Post ID.
 * @return void
 */
function toplist_save_list_overrides(int $post_id): void {
	if (!isset($_POST['toplist_list_overrides_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['toplist_list_overrides_nonce'])), 'toplist_save_list_overrides')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$css = isset($_POST['toplist_list_css']) ? toplist_sanitize_css(wp_unslash($_POST['toplist_list_css'])) : '';
	update_post_meta($post_id, '_toplist_list_css', $css);

	$inherit = isset($_POST['toplist_override_inherit']) && is_array($_POST['toplist_override_inherit'])
		? array_map('sanitize_key', wp_unslash($_POST['toplist_override_inherit']))
		: array();
	$toggles = isset($_POST['toplist_override_toggle']) && is_array($_POST['toplist_override_toggle'])
		? wp_unslash($_POST['toplist_override_toggle'])
		: array();

	foreach (toplist_get_toggle_groups() as $fields) {
		foreach ($fields as $option_name => $label) {
			$meta_key = '_toplist_ov_' . $option_name;
			if (isset($inherit[$option_name])) {
				delete_post_meta($post_id, $meta_key);
				continue;
			}
			$value = !empty($toggles[$option_name]) ? '1' : '0';
			update_post_meta($post_id, $meta_key, $value);
		}
	}

	toplist_bust_list_render_cache($post_id);
}

/**
 * @return void
 */
function toplist_ajax_preview_list(): void {
	check_ajax_referer('toplist_preview_list', 'nonce');

	$post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
	if ($post_id <= 0 || !current_user_can('edit_post', $post_id)) {
		wp_send_json_error(array('message' => __('Permission denied.', 'toplist')), 403);
	}

	$content = isset($_POST['content']) ? (string) wp_unslash($_POST['content']) : '';
	$html = toplist_render_list_preview_html($post_id, $content);
	wp_send_json_success(array('html' => $html));
}

/**
 * @param int    $post_id Post ID.
 * @param string $content Pipe content.
 * @return string
 */
function toplist_render_list_preview_html(int $post_id, string $content): string {
	$parsed = toplist_parse_lines_to_items($content, array());
	return toplist_render(
		array(
			'items' => $parsed['items'],
			'fieldIncludes' => $parsed['includes'],
			'fieldExcludes' => $parsed['excludes'],
			'savedToplistId' => $post_id,
			'savedToplistMode' => 'preview',
			'listId' => $post_id,
		)
	);
}

/**
 * @param string $hook Admin hook.
 * @return void
 */
function toplist_enqueue_editor_ux_assets($hook): void {
	if ($hook !== 'post.php' && $hook !== 'post-new.php') {
		return;
	}
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== 'toplist_list') {
		return;
	}

	wp_enqueue_style('toplist-block', plugins_url('style.css', TOPLIST_BLOCK_PATH . '/toplist-block.php'), array(), TOPLIST_BLOCK_VERSION);
	wp_enqueue_script(
		'toplist-editor-ux',
		plugins_url('assets/admin-editor-ux.js', TOPLIST_BLOCK_PATH . '/toplist-block.php'),
		array(),
		TOPLIST_BLOCK_VERSION,
		true
	);
	wp_localize_script(
		'toplist-editor-ux',
		'toplistEditorUx',
		array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('toplist_preview_list'),
		)
	);
}
