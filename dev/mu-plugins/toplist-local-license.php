<?php
/**
 * Plugin Name: Toplist Block Local License Config
 * Description: Dev-only license API constants. Regenerate via scripts/setup-local-license.sh.
 *
 * This template is copied to wp-content/mu-plugins/ by the setup script with your
 * portal API key. Defaults target the PHP built-in server on 127.0.0.1:9080.
 */

if (!defined('TOPLIST_BLOCK_LICENSE_API_URL')) {
	define('TOPLIST_BLOCK_LICENSE_API_URL', 'http://127.0.0.1:9080/api/v1/toplist-block/validate');
}
if (!defined('TOPLIST_BLOCK_LICENSE_API_KEY')) {
	define('TOPLIST_BLOCK_LICENSE_API_KEY', 'REPLACE_ME_RUN_setup-local-license.sh');
}
