<?php
/**
 * Premium library loader: CPT, import/export, REST, metaboxes (ticket 613).
 *
 * Split into focused modules under library/; this file loads them and owns the
 * single hook-registration orchestrator (toplist_boot_premium_features).
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/library/activation.php';
require_once __DIR__ . '/library/cpt.php';
require_once __DIR__ . '/library/rest.php';
require_once __DIR__ . '/library/metaboxes.php';
require_once __DIR__ . '/library/export.php';
require_once __DIR__ . '/library/import.php';

/**
 * Register library/import features only when license is valid.
 *
 * @return void
 */
function toplist_boot_premium_features() {
	if ( ! class_exists( 'Toplist_Block_License' ) || ! Toplist_Block_License::is_valid() ) {
		return;
	}

	add_action( 'init', 'toplist_register_cpt' );
	add_filter( 'use_block_editor_for_post_type', 'toplist_disable_block_editor_for_toplists', 10, 2 );
	add_filter( 'manage_toplist_list_posts_columns', 'toplist_toplists_admin_columns' );
	add_action( 'manage_toplist_list_posts_custom_column', 'toplist_toplists_admin_column_content', 10, 2 );
	add_action( 'rest_api_init', 'toplist_register_rest_routes' );
	add_action( 'add_meta_boxes_toplist_list', 'toplist_register_toplist_metaboxes' );
	add_action( 'save_post_toplist_list', 'toplist_save_toplist_raw_content' );
	add_action( 'admin_post_toplist_export_csv', 'toplist_handle_export_csv' );
	add_action( 'admin_post_toplist_export_json', 'toplist_handle_export_json' );
	add_action( 'admin_post_toplist_export_all_csv', 'toplist_handle_export_all_csv' );
	add_action( 'admin_post_toplist_export_bulk_template_csv', 'toplist_handle_export_bulk_template_csv' );
	add_action( 'admin_post_toplist_import_csv', 'toplist_handle_import_csv' );
	add_action( 'admin_post_toplist_import_json', 'toplist_handle_import_json' );
	add_action( 'admin_post_toplist_import_all_csv', 'toplist_handle_import_all_csv' );
	add_action( 'admin_notices', 'toplist_import_admin_notice' );
	add_action( 'admin_enqueue_scripts', 'toplist_enqueue_toplist_admin_assets' );
	add_action( 'admin_footer', 'toplist_print_import_forms_in_footer', 5 );

	if ( function_exists( 'toplist_register_editor_ux_hooks' ) ) {
		toplist_register_editor_ux_hooks();
	}
	if ( function_exists( 'toplist_register_geo_hooks' ) ) {
		toplist_register_geo_hooks();
	}
	if ( function_exists( 'toplist_register_click_tracking_hooks' ) ) {
		toplist_register_click_tracking_hooks();
	}
	if ( function_exists( 'toplist_register_card_builder_hooks' ) ) {
		toplist_register_card_builder_hooks();
	}
	add_action( 'save_post_toplist_list', 'toplist_save_remote_source_meta' );
}

add_action( 'plugins_loaded', 'toplist_boot_premium_features', 20 );
