<?php
/**
 * Premium library: activation, lite/premium conflict handling, schema.org output.
 *
 * Extracted from library.php (ticket: library split).
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivate lite when premium activates; show one-time notice.
 *
 * @return void
 */
function toplist_block_on_activate(): void {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( is_plugin_active( 'toplist-block-lite/toplist-block-lite.php' ) ) {
		deactivate_plugins( 'toplist-block-lite/toplist-block-lite.php', true );
		set_transient( 'toplist_upgraded_from_lite', 1, WEEK_IN_SECONDS );
	}
}

/**
 * Prevent both plugins from staying active together.
 *
 * @return void
 */
function toplist_block_conflict_guard(): void {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		return;
	}
	$lite = 'toplist-block-lite/toplist-block-lite.php';
	$pro  = 'toplist-block/toplist-block.php';
	if ( is_plugin_active( $lite ) && is_plugin_active( $pro ) ) {
		deactivate_plugins( $lite, true );
		set_transient( 'toplist_upgraded_from_lite', 1, WEEK_IN_SECONDS );
	}
}

/**
 * One-time notice after upgrading from lite.
 *
 * @return void
 */
function toplist_block_upgraded_from_lite_notice(): void {
	if ( ! current_user_can( 'manage_options' ) || ! get_transient( 'toplist_upgraded_from_lite' ) ) {
		return;
	}
	echo '<div class="notice notice-success is-dismissible"><p>';
	echo esc_html__( 'Toplist Block Pro is active. Your existing Toplist blocks are unchanged. Enter your license under Settings → Toplist Block to unlock libraries and import.', 'toplist' );
	echo '</p></div>';
	delete_transient( 'toplist_upgraded_from_lite' );
}

/**
 * Build ItemList schema.org JSON-LD for a toplist.
 *
 * @param array<int, array<string, mixed>> $items Parsed items.
 * @param string                           $heading List heading.
 * @return array<string, mixed>
 */
function toplist_build_itemlist_schema( array $items, string $heading ): array {
	$list = array();
	foreach ( $items as $i => $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$name = toplist_clean_text( $item['product'] ?? '' );
		if ( '' === $name ) {
			$name = toplist_clean_text( $item['operator'] ?? '' );
		}
		if ( '' === $name ) {
			continue;
		}
		$entry = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $name,
		);
		$url   = toplist_clean_text( $item['href'] ?? '' );
		if ( '' !== $url ) {
			$entry['url'] = $url;
		}
		$list[] = $entry;
	}

	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => '' !== $heading ? $heading : __( 'Toplist', 'toplist' ),
		'itemListElement' => $list,
	);
}
