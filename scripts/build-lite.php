<?php
/**
 * Build script: generate premium zip and lite distribution.
 *
 * Usage: php scripts/build-lite.php
 * Run from repo root (toplist-block/ must exist).
 *
 * @package Toplist_Block
 */

if (PHP_SAPI !== 'cli') {
	exit("Run from CLI only.\n");
}

const TOPLIST_LITE_TEXT_DOMAIN = 'toplist-block-lite';
const TOPLIST_LITE_UPGRADE_URL   = 'https://example.com/account/toplist-block/licenses.php';
// Override at build: TOPLIST_LITE_UPGRADE_URL=... php scripts/build-lite.php
$lite_upgrade_url = getenv('TOPLIST_LITE_UPGRADE_URL');
if (is_string($lite_upgrade_url) && $lite_upgrade_url !== '') {
	define('TOPLIST_LITE_UPGRADE_URL_RUNTIME', $lite_upgrade_url);
} else {
	define('TOPLIST_LITE_UPGRADE_URL_RUNTIME', TOPLIST_LITE_UPGRADE_URL);
}

const TOPLIST_DIST_EXCLUDE = array(
	'tickets/',
	'tickets/completed/',
	'docs/',
	'scripts/',
	'tests/',
	'.git/',
	'toplist-block-lite/',
	'toplist-block-lite.zip',
	'toplist-block.zip',
	'toplist.json',
	'toplist_updated.json',
	'toplist-229.csv',
	'toplist-229.json',
	'sample-data.txt',
	'sample-global-css',
	'CLAUDE.md',
	'.gitignore',
	'process_tickets.py',
	'README.md',
	'.DS_Store',
);

const TOPLIST_PREMIUM_DELETE_FILES = array(
	'admin-diagnostics.php',
	'check-plugin.php',
	'includes/class-toplist-block-util.php',
	'includes/class-toplist-block-license.php',
	'includes/class-toplist-block-license-admin.php',
	'includes/class-toplist-block-updater.php',
	'assets/admin-spreadsheet.js',
);

$root = getcwd();
if (!is_dir("$root/toplist-block")) {
	exit("Run from repo root (toplist-block/ must exist).\n");
}

$src         = "$root/toplist-block";
$dst         = "$root/toplist-block-lite";
$lite_zip    = "$root/toplist-block-lite.zip";
$premium_zip = "$root/toplist-block.zip";

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function toplist_rm_rf(string $dir): void
{
	if (!is_dir($dir)) {
		return;
	}
	$items = scandir($dir);
	if ($items === false) {
		return;
	}
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$path = "$dir/$item";
		if (is_dir($path)) {
			toplist_rm_rf($path);
		} else {
			unlink($path);
		}
	}
	rmdir($dir);
}

function toplist_copy_tree(string $src, string $dst): void
{
	if (!is_dir($dst)) {
		mkdir($dst, 0755, true);
	}
	$items = scandir($src);
	if ($items === false) {
		return;
	}
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$from = "$src/$item";
		$to   = "$dst/$item";
		if (is_dir($from)) {
			toplist_copy_tree($from, $to);
		} else {
			copy($from, $to);
		}
	}
}

function toplist_strip_premium_markers(string $content): string
{
	$content = (string) preg_replace(
		'/\r?\n?\/\/ @toplist-premium-start\r?\n.*?\/\/ @toplist-premium-end\r?\n?/s',
		"\n",
		$content
	);
	$content = (string) preg_replace(
		'/\r?\n?\/\* @toplist-premium-start \*\/\r?\n.*?\/\* @toplist-premium-end \*\/\r?\n?/s',
		"\n",
		$content
	);
	return $content;
}

function toplist_zip_directory(string $source_dir, string $zip_path, string $zip_root_name): void
{
	if (file_exists($zip_path)) {
		unlink($zip_path);
	}
	$zip = new ZipArchive();
	if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
		exit("Failed to create zip: $zip_path\n");
	}
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($source_dir, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach ($iterator as $file) {
		/** @var SplFileInfo $file */
		$path = $file->getPathname();
		$rel  = substr($path, strlen($source_dir) + 1);
		$arc  = $zip_root_name . '/' . str_replace('\\', '/', $rel);
		if ($file->isDir()) {
			$zip->addEmptyDir($arc);
		} else {
			$zip->addFile($path, $arc);
		}
	}
	$zip->close();
}

function toplist_read_version_from_content(string $content): string
{
	if (preg_match('/Version:\s*([^\r\n]+)/', $content, $m)) {
		return trim($m[1]);
	}
	return '0.0.0';
}

function toplist_lite_upgrade_notice_php(): string
{
	return <<<'PHP'

/**
 * WP.org-compliant upgrade notice (no locked features).
 *
 * @return void
 */
function toplist_lite_upgrade_notice()
{
	if (!is_admin() || !current_user_can('manage_options')) {
		return;
	}
	if (get_user_meta(get_current_user_id(), 'toplist_lite_upgrade_notice_dismissed', true)) {
		return;
	}
	$url = 'LITE_UPGRADE_URL';
	echo '<div class="notice notice-info is-dismissible" data-toplist-lite-notice="1"><p>';
	echo esc_html__('You are using Toplist Block Lite. Toplist Block Pro adds reusable toplist libraries, bulk CSV/JSON import, and live-linked lists.', 'toplist-block-lite');
	echo ' <a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">';
	echo esc_html__('Learn about Toplist Block Pro', 'toplist-block-lite');
	echo '</a></p></div>';
	echo '<script>(function(){var n=document.querySelector("[data-toplist-lite-notice]");if(!n||!window.jQuery)return;jQuery(n).on("click",".notice-dismiss",function(){jQuery.post(ajaxurl,{action:"toplist_lite_dismiss_upgrade_notice",_ajax_nonce:"' . esc_js(wp_create_nonce('toplist_lite_dismiss')) . '"});});})();</script>';
}
add_action('admin_notices', 'toplist_lite_upgrade_notice');
add_action('wp_ajax_toplist_lite_dismiss_upgrade_notice', function () {
	check_ajax_referer('toplist_lite_dismiss');
	if (!current_user_can('manage_options')) {
		wp_send_json_error(null, 403);
	}
	update_user_meta(get_current_user_id(), 'toplist_lite_upgrade_notice_dismissed', '1');
	wp_send_json_success();
});

register_activation_hook(__FILE__, function () {
	if (!function_exists('is_plugin_active')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if (is_plugin_active('toplist-block/toplist-block.php')) {
		deactivate_plugins(plugin_basename(__FILE__));
		wp_die(
			esc_html__('Deactivate Toplist Block Pro before activating Toplist Block Lite.', 'toplist-block-lite'),
			esc_html__('Plugin conflict', 'toplist-block-lite'),
			array('back_link' => true)
		);
	}
});

PHP;
}

function toplist_assert_lite_clean(string $lite_dir): void
{
	$errors = array();
	$whitelist = array('Toplist Block Pro', 'toplist-block-pro', 'example.com/toplist-block-pro');
	$needles   = array(
		'toplist_list',
		'toplist_handle_import',
		'toplist_register_rest_routes',
		'class-toplist-block-license',
		'class-toplist-block-updater',
		'data-toplist-spreadsheet',
		'toplist-spreadsheet',
		'toplist_build_itemlist_schema',
		'schemaEnabled',
		'pre_set_site_transient_update_plugins',
		'renderLibraryTab',
		'apiFetch({',
		'if (false)',
	);

	foreach (toplist_files_with_exts($lite_dir, array('php', 'js')) as $file) {
		$lines = preg_split('/\R/', (string) file_get_contents($file));
		foreach ($lines as $i => $line) {
			foreach ($whitelist as $allow) {
				if (strpos($line, $allow) !== false) {
					continue 2;
				}
			}
			foreach ($needles as $needle) {
				if (strpos($line, $needle) !== false) {
					$rel      = ltrim(str_replace($lite_dir, '', $file), '/');
					$errors[] = "$rel:" . ($i + 1) . " [$needle]";
				}
			}
		}
	}

	$main = "$lite_dir/toplist-block-lite.php";
	if (!file_exists($main)) {
		$errors[] = 'Missing toplist-block-lite.php';
	} else {
		$header = (string) file_get_contents($main);
		if (strpos($header, 'Text Domain: ' . TOPLIST_LITE_TEXT_DOMAIN) === false) {
			$errors[] = 'Lite main file missing text domain ' . TOPLIST_LITE_TEXT_DOMAIN;
		}
	}

	if ($errors) {
		echo "BUILD FAILED — lite smoke checks:\n  " . implode("\n  ", $errors) . "\n";
		exit(1);
	}
	echo "Lite-tree smoke checks passed.\n";
}

function toplist_files_with_exts(string $dir, array $exts): array
{
	$out = array();
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
	);
	foreach ($iterator as $file) {
		/** @var SplFileInfo $file */
		if (!$file->isFile()) {
			continue;
		}
		$ext = strtolower($file->getExtension());
		if (in_array($ext, $exts, true)) {
			$out[] = $file->getPathname();
		}
	}
	return $out;
}

// ---------------------------------------------------------------------------
// 1. Clean & copy lite
// ---------------------------------------------------------------------------
if (is_dir($dst)) {
	echo "Removing old $dst …\n";
	toplist_rm_rf($dst);
}
if (file_exists($lite_zip)) {
	unlink($lite_zip);
}

echo "Copying $src → $dst …\n";
toplist_copy_tree($src, $dst);

// ---------------------------------------------------------------------------
// 2. Delete premium-only files from lite
// ---------------------------------------------------------------------------
foreach (TOPLIST_PREMIUM_DELETE_FILES as $rel) {
	$path = "$dst/$rel";
	if (file_exists($path)) {
		echo "  DELETE $rel\n";
		unlink($path);
	}
}

// ---------------------------------------------------------------------------
// 3. Rename lite main plugin file
// ---------------------------------------------------------------------------
rename("$dst/toplist-block.php", "$dst/toplist-block-lite.php");

// ---------------------------------------------------------------------------
// 4. Strip premium markers from lite PHP/JS
// ---------------------------------------------------------------------------
foreach (toplist_files_with_exts($dst, array('php', 'js')) as $file) {
	$original = (string) file_get_contents($file);
	$stripped = toplist_strip_premium_markers($original);
	if ($stripped !== $original) {
		file_put_contents($file, $stripped);
	}
}

// ---------------------------------------------------------------------------
// 5. Patch lite main plugin file
// ---------------------------------------------------------------------------
$main_file = "$dst/toplist-block-lite.php";
$main      = (string) file_get_contents($main_file);
$version   = toplist_read_version_from_content($main);

$main = str_replace(
	array(
		'Plugin Name: Toplist Block',
		'Text Domain: toplist',
	),
	array(
		'Plugin Name: Toplist Block Lite',
		'Text Domain: ' . TOPLIST_LITE_TEXT_DOMAIN,
	),
	$main
);

$main = preg_replace(
	"/array\\('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch'\\)/",
	"array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n')",
	$main
);

$notice = str_replace('LITE_UPGRADE_URL', TOPLIST_LITE_UPGRADE_URL_RUNTIME, toplist_lite_upgrade_notice_php());
$main  .= "\n" . $notice;

file_put_contents($main_file, $main);

// ---------------------------------------------------------------------------
// 6. Patch lite block.js — drop apiFetch from IIFE
// ---------------------------------------------------------------------------
$block_js = "$dst/block.js";
$js       = (string) file_get_contents($block_js);
$js       = str_replace(
	'(function (blocks, element, blockEditor, components, i18n, apiFetch) {',
	'(function (blocks, element, blockEditor, components, i18n) {',
	$js
);
$js = preg_replace(
	"/,\s*\n\twindow\\.wp\\.apiFetch\s*\n\)/",
	"\n)",
	$js
);
file_put_contents($block_js, $js);

// ---------------------------------------------------------------------------
// 7. Lite readme.txt
// ---------------------------------------------------------------------------
$readme = <<<TXT
=== Toplist Block Lite ===
Contributors: user256
Tags: block, toplist, casino, affiliate, gutenberg
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: {$version}-lite
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gutenberg toplist block with pipe-delimited editing and global styling.

== Description ==

Toplist Block Lite adds a **Toplist** Gutenberg block for ranked lists (casinos, products, etc.). Edit rows as pipe-delimited lines in the block editor. Global CSS and field visibility live under Settings → Toplist Block.

**Toplist Block Pro** (separate plugin) adds reusable toplist libraries, bulk CSV/JSON import, and live-linked lists.

== Installation ==

1. Upload the plugin zip via Plugins → Add New → Upload.
2. Activate Toplist Block Lite.
3. Add the Toplist block in the block editor.

== Frequently Asked Questions ==

= Where is the Pro version? =

Toplist Block Pro is sold separately and is not required for the lite block to work.

= Can I upgrade to Pro later? =

Yes. Deactivate lite, install Toplist Block Pro from your portal account, and enter your license. Existing Toplist blocks keep working. See the plugin documentation for upgrade steps.

== Changelog ==

= {$version}-lite =
* Initial lite build from premium source.

TXT;
file_put_contents("$dst/readme.txt", $readme);

// Copy translation template into lite tree.
$pot_src = "$root/toplist-block/languages/toplist.pot";
if (is_readable($pot_src)) {
	$pot_dst_dir = "$dst/languages";
	if (!is_dir($pot_dst_dir)) {
		mkdir($pot_dst_dir, 0755, true);
	}
	copy($pot_src, "$pot_dst_dir/toplist.pot");
}

// ---------------------------------------------------------------------------
// 8. Zip lite
// ---------------------------------------------------------------------------
echo "Creating $lite_zip …\n";
toplist_zip_directory($dst, $lite_zip, 'toplist-block-lite');

toplist_assert_lite_clean($dst);

// ---------------------------------------------------------------------------
// 9. Premium zip (from source, not lite)
// ---------------------------------------------------------------------------
if (file_exists($premium_zip)) {
	unlink($premium_zip);
}
echo "Creating $premium_zip …\n";
$staging = sys_get_temp_dir() . '/toplist-block-premium-' . getmypid();
toplist_rm_rf($staging);
mkdir($staging, 0755, true);
toplist_copy_tree($src, "$staging/toplist-block");

$zip = new ZipArchive();
if ($zip->open($premium_zip, ZipArchive::CREATE) !== true) {
	exit("Failed to create premium zip.\n");
}
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator("$staging/toplist-block", FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
	/** @var SplFileInfo $file */
	$path = $file->getPathname();
	$rel  = substr($path, strlen("$staging/toplist-block") + 1);
	$arc  = 'toplist-block/' . str_replace('\\', '/', $rel);
	if ($file->isDir()) {
		$zip->addEmptyDir($arc);
	} else {
		$zip->addFile($path, $arc);
	}
}
$zip->close();
toplist_rm_rf($staging);

echo "Premium distribution checks passed.\n";
echo "Done.\n  Lite:    $lite_zip\n  Premium: $premium_zip\n";
