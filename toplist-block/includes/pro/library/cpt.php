<?php
/**
 * Premium library: Toplists custom post type and admin list columns.
 *
 * Extracted from library.php (ticket: library split).
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Toplists library custom post type.
 *
 * @return void
 */
function toplist_register_cpt() {
	register_post_type(
		'toplist_list',
		array(
			'labels'          => array(
				'name'          => __( 'Toplists', 'toplist' ),
				'singular_name' => __( 'Toplist', 'toplist' ),
				'add_new_item'  => __( 'Add New Toplist', 'toplist' ),
				'edit_item'     => __( 'Edit Toplist', 'toplist' ),
				'new_item'      => __( 'New Toplist', 'toplist' ),
				'view_item'     => __( 'View Toplist', 'toplist' ),
				'search_items'  => __( 'Search Toplists', 'toplist' ),
				'not_found'     => __( 'No toplists found', 'toplist' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => true,
			'has_archive'     => false,
			'rewrite'         => false,
			'menu_icon'       => 'dashicons-list-view',
			'supports'        => array( 'title', 'revisions' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);
}

/**
 * Keep Toplist library in classic editor textarea mode for raw pipe content.
 *
 * @param bool   $use_block_editor Whether block editor is enabled.
 * @param string $post_type Post type name.
 * @return bool
 */
function toplist_disable_block_editor_for_toplists( $use_block_editor, $post_type ) {
	if ( 'toplist_list' === $post_type ) {
		return false;
	}
	return $use_block_editor;
}

/**
 * Add ID and modified columns to Toplists admin list.
 *
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function toplist_toplists_admin_columns( array $columns ): array {
	return array(
		'cb'         => $columns['cb'] ?? '',
		'title'      => __( 'Name', 'toplist' ),
		'toplist_id' => __( 'Shortcode / ID', 'toplist' ),
		'modified'   => __( 'Last Modified', 'toplist' ),
		'date'       => $columns['date'] ?? __( 'Date', 'toplist' ),
	);
}

/**
 * Render custom Toplists admin columns.
 *
 * @param string $column Column key.
 * @param int    $post_id Post ID.
 * @return void
 */
function toplist_toplists_admin_column_content( $column, $post_id ) {
	if ( 'toplist_id' === $column ) {
		echo '<code>[toplist id=&quot;' . esc_html( (string) $post_id ) . '&quot;]</code><br>';
		echo '<small>#' . esc_html( (string) $post_id ) . '</small>';
		return;
	}
	if ( 'modified' === $column ) {
		$modified = get_the_modified_date( '', $post_id );
		echo esc_html( is_string( $modified ) ? $modified : '' );
	}
}
