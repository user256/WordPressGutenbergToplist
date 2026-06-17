<?php
/**
 * Toplist Block - Diagnostic Tool
 * Run from command line: php check-plugin.php
 *
 * @package Toplist_Block
 */

echo esc_html( "=== Toplist Block Diagnostics ===\n\n" );

// Check files exist.
echo esc_html( "1. FILE CHECK\n" );
echo esc_html( str_repeat( '-', 50 ) ) . "\n";
$files = array( 'toplist-block.php', 'block.js', 'style.css', 'view.js' );
foreach ( $files as $file ) {
	if ( file_exists( $file ) ) {
		echo esc_html( "✓ $file exists\n" );
		echo esc_html( '  Size: ' . filesize( $file ) . " bytes\n" );
		echo esc_html( '  Readable: ' . ( is_readable( $file ) ? 'Yes' : 'No' ) . "\n" );
		echo esc_html( '  Permissions: ' . substr( sprintf( '%o', fileperms( $file ) ), -4 ) . "\n" );
	} else {
		echo esc_html( "✗ $file MISSING\n" );
	}
}

// Check PHP syntax.
echo "\n" . esc_html( "2. PHP SYNTAX CHECK\n" );
echo esc_html( str_repeat( '-', 50 ) ) . "\n";
exec( 'php -l toplist-block.php 2>&1', $output, $return ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
echo esc_html( implode( "\n", $output ) ) . "\n";

// Check for common issues in PHP file.
echo "\n" . esc_html( "3. PHP FILE ANALYSIS\n" );
echo esc_html( str_repeat( '-', 50 ) ) . "\n";
$php_content = file_get_contents( 'toplist-block.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

// Check for BOM.
if ( substr( $php_content, 0, 3 ) === "\xEF\xBB\xBF" ) {
	echo esc_html( "⚠ WARNING: File has UTF-8 BOM (may cause issues)\n" );
} else {
	echo esc_html( "✓ No BOM detected\n" );
}

// Check for output before <?php.
if ( preg_match( '/^[^<]/', $php_content ) ) {
	echo esc_html( "⚠ WARNING: Content before <?php tag\n" );
} else {
	echo esc_html( "✓ No content before <?php tag\n" );
}

// Check for closing  closing tag tag.
if ( preg_match( '/\?>\s*$/', $php_content ) ) {
	echo esc_html( "⚠ WARNING: Closing ?> tag found (not recommended).\n" );
} else {
	echo esc_html( "✓ No closing ?> tag (good practice).\n" );
}

// Check JavaScript files.
echo "\n" . esc_html( "4. JAVASCRIPT FILE ANALYSIS\n" );
echo esc_html( str_repeat( '-', 50 ) ) . "\n";

$js_files = array( 'block.js', 'view.js' );
foreach ( $js_files as $js_file ) {
	if ( file_exists( $js_file ) ) {
		$js_content = file_get_contents( $js_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		echo "\n" . esc_html( "$js_file:\n" );
		echo esc_html( ' Size: ' . strlen( $js_content ) . " bytes\n" );

		// Check for common issues..
		if ( strpos( $js_content, 'console.log' ) !== false ) {
			echo esc_html( " ℹ Contains console.log statements\n" );
		}

		// Count functions.
		preg_match_all( '/function\s+\w+/', $js_content, $matches );
		echo esc_html( ' Functions: ' . count( $matches[0] ) . "\n" );
	}
}

// Check CSS.
echo "\n" . esc_html( "5. CSS FILE ANALYSIS\n" );
echo esc_html( str_repeat( '-', 50 ) ) . "\n";
if ( file_exists( 'style.css' ) ) {
	$css_content = file_get_contents( 'style.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	echo esc_html( 'Size: ' . strlen( $css_content ) . " bytes\n" );
	preg_match_all( '/\{/', $css_content, $matches );
	echo esc_html( 'CSS Rules: ~' . count( $matches[0] ) . "\n" );
}

// WordPress environment check.
echo "\n" . esc_html( "6. WordPress ENVIRONMENT\n" );
echo esc_html( str_repeat( '-', 50 ) ) . "\n";
echo esc_html( 'PHP Version: ' . PHP_VERSION . "\n" );

// Check if running in WordPress context.
if ( defined( 'ABSPATH' ) ) {
	echo esc_html( "✓ Running in WordPress context\n" );
	echo esc_html( 'WordPress Version: ' . ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : 'Unknown' ) . "\n" );
} else {
	echo esc_html( "ℹ Not running in WordPress context (standalone check)\n" );
	echo esc_html( " To check WordPress integration, activate the plugin and check debug.log\n" );
}

echo "\n" . esc_html( "7. RECOMMENDATIONS\n" );
echo esc_html( str_repeat( '-', 50 ) ) . "\n";
echo esc_html( "1. Enable WordPress debug mode in wp-config.php:\n" );
echo esc_html( " define('WP_DEBUG', true);\n" );
echo esc_html( " define('WP_DEBUG_LOG', true);\n" );
echo esc_html( " define('SCRIPT_DEBUG', true);\n\n" );
echo esc_html( "2. Check browser console for JavaScript errors\n" );
echo esc_html( "3. Verify plugin is activated in WordPress admin\n" );
echo esc_html( "4. Try deactivating other plugins to check for conflicts\n" );
echo esc_html( "5. Test with a default WordPress theme\n" );

echo "\n" . esc_html( "=== Diagnostics Complete ===\n" );
