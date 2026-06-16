<?php
/**
 * Geo-variant toplists (ticket 601).
 *
 * @package Toplist_Block
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * @return void
 */
function toplist_register_geo_hooks(): void {
	add_filter('toplist_supported_fields', 'toplist_geo_append_field');
	add_filter('toplist_render_items', 'toplist_geo_filter_render_items', 10, 3);
	add_action('add_meta_boxes_toplist_list', 'toplist_geo_register_metabox', 25);
	add_action('save_post_toplist_list', 'toplist_geo_save_list_meta', 12);
}

/**
 * @param array<int, string> $fields Field keys.
 * @return array<int, string>
 */
function toplist_geo_append_field(array $fields): array {
	if (!in_array('geo', $fields, true)) {
		$fields[] = 'geo';
	}
	return $fields;
}

/**
 * @return string Two-letter ISO country or empty.
 */
function toplist_get_visitor_geo_code(): string {
	$code = '';
	if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
		$code = strtoupper(sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_CF_IPCOUNTRY'])));
	} elseif (!empty($_SERVER['GEOIP_COUNTRY_CODE'])) {
		$code = strtoupper(sanitize_text_field(wp_unslash((string) $_SERVER['GEOIP_COUNTRY_CODE'])));
	}
	if (strlen($code) !== 2 || $code === 'XX' || $code === 'T1') {
		$code = '';
	}
	/**
	 * Filter detected visitor country code (ISO 3166-1 alpha-2).
	 *
	 * @param string $code Detected code or empty.
	 */
	return strtoupper((string) apply_filters('toplist_visitor_geo_code', $code));
}

/**
 * @param array<int, array<string, mixed>> $items       Items.
 * @param array<string, mixed>             $attributes  Block attributes.
 * @param int                              $context_id  List post ID.
 * @return array<int, array<string, mixed>>
 */
function toplist_geo_filter_render_items(array $items, array $attributes, int $context_id): array {
	$mode = 'auto';
	if (isset($attributes['geoMode']) && is_string($attributes['geoMode'])) {
		$mode = $attributes['geoMode'];
	}
	if ($mode === 'off') {
		return $items;
	}

	$target = '';
	if ($mode === 'custom' && isset($attributes['geoCode']) && is_string($attributes['geoCode'])) {
		$target = strtoupper(sanitize_text_field($attributes['geoCode']));
	} elseif ($mode === 'list' && $context_id > 0) {
		$meta = get_post_meta($context_id, '_toplist_geo_default', true);
		$target = is_string($meta) ? strtoupper(sanitize_text_field($meta)) : '';
	} else {
		$target = toplist_get_visitor_geo_code();
	}

	$fallback = '';
	if ($context_id > 0) {
		$meta = get_post_meta($context_id, '_toplist_geo_fallback', true);
		$fallback = is_string($meta) ? strtoupper(sanitize_text_field($meta)) : '';
	}
	if ($fallback === '' && isset($attributes['geoFallback']) && is_string($attributes['geoFallback'])) {
		$fallback = strtoupper(sanitize_text_field($attributes['geoFallback']));
	}

	return toplist_geo_pick_items($items, $target, $fallback);
}

/**
 * @param array<int, array<string, mixed>> $items    Items.
 * @param string                           $target   Visitor/list geo.
 * @param string                           $fallback Fallback geo when no match.
 * @return array<int, array<string, mixed>>
 */
function toplist_geo_pick_items(array $items, string $target, string $fallback): array {
	$has_geo = false;
	foreach ($items as $item) {
		if (!is_array($item)) {
			continue;
		}
		if (toplist_clean_text($item['geo'] ?? '') !== '') {
			$has_geo = true;
			break;
		}
	}
	if (!$has_geo) {
		return $items;
	}

	$match_codes = array();
	if ($target !== '') {
		$match_codes[] = $target;
	}
	if ($fallback !== '' && $fallback !== $target) {
		$match_codes[] = $fallback;
	}
	$match_codes[] = '*';

	foreach ($match_codes as $code) {
		$subset = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$row_geo = strtoupper(toplist_clean_text($item['geo'] ?? ''));
			if ($row_geo === '') {
				$subset[] = $item;
				continue;
			}
			$codes = array_map('trim', explode(',', $row_geo));
			if ($code === '*' || in_array($code, $codes, true)) {
				$subset[] = $item;
			}
		}
		if ($subset !== array()) {
			return array_values($subset);
		}
	}

	return $items;
}

/**
 * @return void
 */
function toplist_geo_register_metabox(): void {
	add_meta_box(
		'toplist_geo_box',
		__('Geo targeting', 'toplist'),
		'toplist_geo_render_metabox',
		'toplist_list',
		'side',
		'default'
	);
}

/**
 * @param WP_Post $post Post.
 * @return void
 */
function toplist_geo_render_metabox($post): void {
	wp_nonce_field('toplist_save_geo_meta', 'toplist_geo_meta_nonce');
	$default = get_post_meta($post->ID, '_toplist_geo_default', true);
	$fallback = get_post_meta($post->ID, '_toplist_geo_fallback', true);
	echo '<p><label for="toplist_geo_default"><strong>' . esc_html__('Default country', 'toplist') . '</strong></label></p>';
	echo '<input type="text" class="widefat" id="toplist_geo_default" name="toplist_geo_default" maxlength="2" value="' . esc_attr(is_string($default) ? $default : '') . '" placeholder="GB" />';
	echo '<p><label for="toplist_geo_fallback"><strong>' . esc_html__('Fallback country', 'toplist') . '</strong></label></p>';
	echo '<input type="text" class="widefat" id="toplist_geo_fallback" name="toplist_geo_fallback" maxlength="2" value="' . esc_attr(is_string($fallback) ? $fallback : '') . '" placeholder="IE" />';
	echo '<p class="description">' . esc_html__('Optional ISO codes. Rows may include a geo column (e.g. GB,IE). Visitor country uses Cloudflare GEOIP headers or the toplist_visitor_geo_code filter.', 'toplist') . '</p>';
}

/**
 * @param int $post_id Post ID.
 * @return void
 */
function toplist_geo_save_list_meta(int $post_id): void {
	if (!isset($_POST['toplist_geo_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['toplist_geo_meta_nonce'])), 'toplist_save_geo_meta')) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	$default = isset($_POST['toplist_geo_default']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['toplist_geo_default']))) : '';
	$fallback = isset($_POST['toplist_geo_fallback']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['toplist_geo_fallback']))) : '';
	update_post_meta($post_id, '_toplist_geo_default', $default);
	update_post_meta($post_id, '_toplist_geo_fallback', $fallback);
}
