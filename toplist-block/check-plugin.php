<?php
/**
 * Toplist Block - Diagnostic Tool
 * Run from command line: php check-plugin.php
 */

echo "=== Toplist Block Diagnostics ===\n\n";

// Check files exist
echo "1. FILE CHECK\n";
echo str_repeat("-", 50) . "\n";
$files = ['toplist-block.php', 'block.js', 'style.css', 'view.js'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
        echo "  Size: " . filesize($file) . " bytes\n";
        echo "  Readable: " . (is_readable($file) ? 'Yes' : 'No') . "\n";
        echo "  Permissions: " . substr(sprintf('%o', fileperms($file)), -4) . "\n";
    } else {
        echo "✗ $file MISSING\n";
    }
}

// Check PHP syntax
echo "\n2. PHP SYNTAX CHECK\n";
echo str_repeat("-", 50) . "\n";
exec('php -l toplist-block.php 2>&1', $output, $return);
echo implode("\n", $output) . "\n";

// Check for common issues in PHP file
echo "\n3. PHP FILE ANALYSIS\n";
echo str_repeat("-", 50) . "\n";
$php_content = file_get_contents('toplist-block.php');

// Check for BOM
if (substr($php_content, 0, 3) === "\xEF\xBB\xBF") {
    echo "⚠ WARNING: File has UTF-8 BOM (may cause issues)\n";
} else {
    echo "✓ No BOM detected\n";
}

// Check for output before <?php
if (preg_match('/^[^<]/', $php_content)) {
    echo "⚠ WARNING: Content before <?php tag\n";
} else {
    echo "✓ No content before <?php tag\n";
}

// Check for closing ?>
if (preg_match('/\?>\s*$/', $php_content)) {
echo "⚠ WARNING: Closing ?> tag found (not recommended)\n";
} else {
echo "✓ No closing ?> tag (good practice)\n";
}

// Check JavaScript files
echo "\n4. JAVASCRIPT FILE ANALYSIS\n";
echo str_repeat("-", 50) . "\n";

$js_files = ['block.js', 'view.js'];
foreach ($js_files as $js_file) {
if (file_exists($js_file)) {
$js_content = file_get_contents($js_file);
echo "\n$js_file:\n";
echo " Size: " . strlen($js_content) . " bytes\n";

// Check for common issues
if (strpos($js_content, 'console.log') !== false) {
echo " ℹ Contains console.log statements\n";
}

// Count functions
preg_match_all('/function\s+\w+/', $js_content, $matches);
echo " Functions: " . count($matches[0]) . "\n";
}
}

// Check CSS
echo "\n5. CSS FILE ANALYSIS\n";
echo str_repeat("-", 50) . "\n";
if (file_exists('style.css')) {
$css_content = file_get_contents('style.css');
echo "Size: " . strlen($css_content) . " bytes\n";
preg_match_all('/\{/', $css_content, $matches);
echo "CSS Rules: ~" . count($matches[0]) . "\n";
}

// WordPress environment check
echo "\n6. WORDPRESS ENVIRONMENT\n";
echo str_repeat("-", 50) . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";

// Check if running in WordPress context
if (defined('ABSPATH')) {
echo "✓ Running in WordPress context\n";
echo "WordPress Version: " . (function_exists('get_bloginfo') ? get_bloginfo('version') : 'Unknown') . "\n";
} else {
echo "ℹ Not running in WordPress context (standalone check)\n";
echo " To check WordPress integration, activate the plugin and check debug.log\n";
}

echo "\n7. RECOMMENDATIONS\n";
echo str_repeat("-", 50) . "\n";
echo "1. Enable WordPress debug mode in wp-config.php:\n";
echo " define('WP_DEBUG', true);\n";
echo " define('WP_DEBUG_LOG', true);\n";
echo " define('SCRIPT_DEBUG', true);\n\n";
echo "2. Check browser console for JavaScript errors\n";
echo "3. Verify plugin is activated in WordPress admin\n";
echo "4. Try deactivating other plugins to check for conflicts\n";
echo "5. Test with a default WordPress theme\n";

echo "\n=== Diagnostics Complete ===\n";