<?php
/**
 * Outbound click tracking (ticket 602).
 *
 * @package Toplist_Block
 */

if (!defined('ABSPATH')) {
	exit;
}

const TOPLIST_CLICK_COUNTS_OPTION = 'toplist_outbound_click_counts';

/**
 * @return void
 */
function toplist_register_click_tracking_hooks(): void {
	add_action('admin_init', 'toplist_click_tracking_register_settings');
	add_action('admin_post_toplist_outbound_click', 'toplist_handle_outbound_click');
	add_action('rest_api_init', 'toplist_register_click_stats_rest_route');
	add_filter('toplist_outbound_link_html', 'toplist_filter_outbound_link_html', 10, 4);
	add_action('toplist_settings_premium_panels', 'toplist_render_click_tracking_settings_panel');
	add_action('toplist_after_render_list', 'toplist_render_click_disclosure', 10, 2);
}

/**
 * @return void
 */
function toplist_click_tracking_register_settings(): void {
	register_setting('toplist_settings', 'toplist_click_tracking_enabled');
	register_setting('toplist_settings', 'toplist_click_obfuscate_links');
	register_setting('toplist_settings', 'toplist_click_disclosure_text');
}

/**
 * @return bool
 */
function toplist_click_tracking_enabled(): bool {
	return toplist_get_global_bool_option('toplist_click_tracking_enabled', false);
}

/**
 * @return bool
 */
function toplist_click_obfuscation_enabled(): bool {
	return toplist_get_global_bool_option('toplist_click_obfuscate_links', false);
}

/**
 * @return string
 */
function toplist_click_disclosure_text(): string {
	$text = get_option('toplist_click_disclosure_text', '');
	if (!is_string($text) || trim($text) === '') {
		return __('We measure anonymous outbound clicks on operator links to improve our rankings. No personal data is stored.', 'toplist');
	}
	return trim($text);
}

/**
 * @param string $url      Destination URL.
 * @param int    $list_id  List context ID.
 * @param int    $row      Row index (1-based).
 * @param string $kind     Link kind: cta|review.
 * @return string
 */
function toplist_build_tracked_outbound_url(string $url, int $list_id, int $row, string $kind): string {
	if (!toplist_click_tracking_enabled() || $url === '') {
		return $url;
	}
	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => 'toplist_outbound_click',
				'to' => rawurlencode($url),
				'list' => $list_id,
				'row' => $row,
				'kind' => $kind,
			),
			admin_url('admin-post.php')
		),
		'toplist_click_' . $list_id . '_' . $row . '_' . $kind
	);
}

/**
 * Render an outbound anchor with optional premium tracking/obfuscation.
 *
 * @param string $url      Destination URL.
 * @param string $content  Inner HTML (escaped).
 * @param string $class    CSS classes.
 * @param int    $list_id  List context ID.
 * @param int    $row      Row position (1-based).
 * @param string $kind     Link kind.
 * @return string
 */
function toplist_outbound_link(string $url, string $content, string $class, int $list_id, int $row, string $kind): string {
	if ($url === '') {
		return '';
	}
	$html = '<a class="' . esc_attr($class) . '" rel="nofollow" target="_blank" href="' . esc_url($url) . '">' . $content . '</a>';
	return (string) apply_filters(
		'toplist_outbound_link_html',
		$html,
		$url,
		$list_id,
		array(
			'_row' => $row,
			'_link_kind' => $kind,
		)
	);
}

/**
 * @param string               $html    Original anchor HTML.
 * @param string               $url     Destination.
 * @param int                  $list_id List ID.
 * @param array<string, mixed> $item    Row meta.
 * @return string
 */
function toplist_filter_outbound_link_html(string $html, string $url, int $list_id, array $item): string {
	if ($url === '' || !toplist_click_tracking_enabled()) {
		return $html;
	}

	$row_val = $item['_row'] ?? 0;
	$row = is_numeric($row_val) ? (int) $row_val : 0;
	$kind_val = $item['_link_kind'] ?? 'cta';
	$kind = is_string($kind_val) ? $kind_val : 'cta';
	$tracked = toplist_build_tracked_outbound_url($url, $list_id, $row, $kind);

	if (toplist_click_obfuscation_enabled()) {
		$label = wp_strip_all_tags($html);
		return '<a href="#" class="toplist-outbound-obfuscated" role="link" data-toplist-outbound="' . esc_attr(base64_encode($tracked)) . '" onclick="window.toplistFollowOutbound && window.toplistFollowOutbound(this);return false;">' . ($label !== '' ? $label : esc_html__('Visit', 'toplist')) . '</a>';
	}

	return preg_replace('#href=(["\'])' . preg_quote($url, '#') . '\\1#', 'href="' . esc_url($tracked) . '"', $html, 1) ?? $html;
}

/**
 * @return void
 */
function toplist_handle_outbound_click(): void {
	$list_param = $_GET['list'] ?? 0;
	$row_param = $_GET['row'] ?? 0;
	$list_id = is_numeric($list_param) ? (int) $list_param : 0;
	$row = is_numeric($row_param) ? (int) $row_param : 0;
	$kind = isset($_GET['kind']) ? sanitize_key((string) wp_unslash($_GET['kind'])) : 'cta';
	check_admin_referer('toplist_click_' . $list_id . '_' . $row . '_' . $kind);

	$to = isset($_GET['to']) ? esc_url_raw(rawurldecode((string) wp_unslash($_GET['to']))) : '';
	if ($to === '') {
		wp_safe_redirect(home_url('/'));
		exit;
	}

	toplist_record_outbound_click($list_id, $row, $kind, $to);
	wp_safe_redirect($to, 302);
	exit;
}

/**
 * @param int    $list_id List ID.
 * @param int    $row     Row index.
 * @param string $kind    Link kind.
 * @param string $url     Destination URL.
 * @return void
 */
function toplist_record_outbound_click(int $list_id, int $row, string $kind, string $url): void {
	$key = md5($list_id . '|' . $row . '|' . $kind . '|' . $url);
	$counts = get_option(TOPLIST_CLICK_COUNTS_OPTION, array());
	if (!is_array($counts)) {
		$counts = array();
	}
	if (!isset($counts[$key]) || !is_array($counts[$key])) {
		$counts[$key] = array(
			'list_id' => $list_id,
			'row' => $row,
			'kind' => $kind,
			'url' => $url,
			'count' => 0,
		);
	}
	$counts[$key]['count'] = (int) ($counts[$key]['count'] ?? 0) + 1;
	update_option(TOPLIST_CLICK_COUNTS_OPTION, $counts, false);
}

/**
 * @return void
 */
function toplist_register_click_stats_rest_route(): void {
	register_rest_route(
		'toplist-block/v1',
		'/click-stats',
		array(
			'methods' => 'GET',
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'callback' => function () {
				$counts = get_option(TOPLIST_CLICK_COUNTS_OPTION, array());
				return rest_ensure_response(is_array($counts) ? array_values($counts) : array());
			},
		)
	);
}

/**
 * @param int                  $list_id    List ID.
 * @param array<string, mixed> $attributes Attributes.
 * @return void
 */
function toplist_render_click_disclosure(int $list_id, array $attributes): void {
	if (!toplist_click_tracking_enabled()) {
		return;
	}
	echo '<p class="toplist-click-disclosure" style="font-size:12px;opacity:.85;margin-top:8px;">' . esc_html(toplist_click_disclosure_text()) . '</p>';
}

/**
 * Settings panel rows for click tracking.
 *
 * @return void
 */
function toplist_render_click_tracking_settings_panel(): void {
	if (!current_user_can('manage_options')) {
		return;
	}
	$enabled = toplist_click_tracking_enabled();
	$obfuscate = toplist_click_obfuscation_enabled();
	$disclosure = get_option('toplist_click_disclosure_text', '');
	$disclosure = is_string($disclosure) ? $disclosure : '';
	$stats_url = rest_url('toplist-block/v1/click-stats');
	?>
	<h2><?php esc_html_e('Outbound click tracking', 'toplist'); ?></h2>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e('Enable tracking', 'toplist'); ?></th>
			<td>
				<label><input type="checkbox" name="toplist_click_tracking_enabled" value="1" <?php checked($enabled); ?> />
					<?php esc_html_e('Record anonymous outbound clicks via redirect endpoint', 'toplist'); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e('Obfuscate links', 'toplist'); ?></th>
			<td>
				<label><input type="checkbox" name="toplist_click_obfuscate_links" value="1" <?php checked($obfuscate); ?> />
					<?php esc_html_e('Hide raw affiliate URLs from href attributes (JS redirect)', 'toplist'); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="toplist_click_disclosure_text"><?php esc_html_e('Disclosure text', 'toplist'); ?></label></th>
			<td>
				<textarea class="large-text" rows="2" id="toplist_click_disclosure_text" name="toplist_click_disclosure_text"><?php echo esc_textarea($disclosure); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e('Stats export', 'toplist'); ?></th>
			<td>
				<p><a class="button" href="<?php echo esc_url($stats_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('View JSON stats', 'toplist'); ?></a></p>
				<p class="description"><?php esc_html_e('Counts are stored without IP addresses. Use for per-list reporting or external analytics.', 'toplist'); ?></p>
			</td>
		</tr>
	</table>
	<?php
}
