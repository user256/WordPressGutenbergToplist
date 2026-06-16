<?php
/**
 * Premium bootstrap — single entry for pro-only modules (ticket 613).
 *
 * @package Toplist_Block
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Loads premium classes and registers always-on hooks.
 */
final class Toplist_Block_Pro_Bootstrap {

	/**
	 * @return void
	 */
	public static function init(): void {
		$base = dirname(__DIR__);

		require_once $base . '/class-toplist-block-util.php';
		require_once $base . '/class-toplist-block-license.php';
		require_once $base . '/class-toplist-block-license-admin.php';
		require_once $base . '/class-toplist-block-updater.php';

		Toplist_Block_License_Admin::init();
		Toplist_Block_Updater::init();
		add_action(Toplist_Block_License::CRON_HOOK, array('Toplist_Block_License', 'do_recheck'));
		register_activation_hook(TOPLIST_BLOCK_PATH . '/toplist-block.php', 'toplist_block_on_activate');
		add_action('admin_init', 'toplist_block_conflict_guard');
		add_action('admin_notices', 'toplist_block_upgraded_from_lite_notice');

		require_once __DIR__ . '/editor-ux.php';
		require_once __DIR__ . '/api-sync.php';
	}
}

Toplist_Block_Pro_Bootstrap::init();
