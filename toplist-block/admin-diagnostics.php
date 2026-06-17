<?php
/**
 * Admin Diagnostics Page for Toplist Block
 *
 * This file adds a diagnostics page to WordPress admin
 * to help debug the block remotely.
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add admin menu.
add_action( 'admin_menu', 'toplist_add_diagnostics_page' );

/**
 * Add the diagnostics submenu page.
 *
 * @return void
 */
function toplist_add_diagnostics_page() {
	add_submenu_page(
		'tools.php',
		'Toplist Block Diagnostics',
		'Toplist Diagnostics',
		'manage_options',
		'toplist-diagnostics',
		'toplist_diagnostics_page'
	);
}

/**
 * Render the diagnostics page.
 *
 * @return void
 */
function toplist_diagnostics_page() {
	?>
	<div class="wrap">
		<h1>Toplist Block Diagnostics</h1>

		<div class="notice notice-info">
			<p><strong>How to use:</strong> This page helps diagnose why the Toplist block isn't working. Check each section
				below for potential issues.</p>
		</div>

		<?php
		$checks = array();

		// Check 1: WordPress Version.
		global $wp_version;
		$wp_ok    = version_compare( $wp_version, '5.8', '>=' );
		$checks[] = array(
			'title'   => 'WordPress Version',
			'status'  => $wp_ok,
			'message' => $wp_ok
				? "✓ WordPress $wp_version (requires 5.8+)"
				: "✗ WordPress $wp_version - UPGRADE REQUIRED (needs 5.8+)",
		);

		// Check 2: PHP Version.
		$php_ok   = version_compare( PHP_VERSION, '7.4', '>=' );
		$checks[] = array(
			'title'   => 'PHP Version',
			'status'  => $php_ok,
			'message' => $php_ok
				? '✓ PHP ' . PHP_VERSION . ' (requires 7.4+)'
				: '✗ PHP ' . PHP_VERSION . ' - UPGRADE REQUIRED (needs 7.4+)',
		);

		// Check 3: Plugin Files.
		$plugin_dir     = plugin_dir_path( __FILE__ );
		$required_files = array( 'block.js', 'style.css', 'view.js', 'toplist-block.php' );
		$files_ok       = true;
		$file_details   = array();

		foreach ( $required_files as $file ) {
			$path     = $plugin_dir . $file;
			$exists   = file_exists( $path );
			$readable = $exists && is_readable( $path );
			$files_ok = $files_ok && $exists && $readable;

			$file_details[] = sprintf(
				'%s %s (%s, %s)',
				$exists && $readable ? '✓' : '✗',
				$file,
				$exists ? 'exists' : 'MISSING',
				$readable ? 'readable' : 'NOT READABLE'
			);
		}

		$checks[] = array(
			'title'   => 'Plugin Files',
			'status'  => $files_ok,
			'message' => implode( '<br>', $file_details ),
		);

		// Check 4: Block Registration.
		$registry         = WP_Block_Type_Registry::get_instance();
		$block_registered = $registry->is_registered( 'toplist/rankings' );

		$checks[] = array(
			'title'   => 'Block Registration',
			'status'  => $block_registered,
			'message' => $block_registered
				? '✓ Block "toplist/rankings" is registered'
				: '✗ Block "toplist/rankings" is NOT registered - check PHP errors',
		);

		// Check 5: Script Registration.
		global $wp_scripts;
		$script_registered = isset( $wp_scripts->registered['toplist-block'] );

		$checks[] = array(
			'title'   => 'JavaScript Registration',
			'status'  => $script_registered,
			'message' => $script_registered
				? '✓ block.js is registered'
				: '✗ block.js is NOT registered',
		);

		if ( $script_registered ) {
			$script   = $wp_scripts->registered['toplist-block'];
			$checks[] = array(
				'title'   => 'JavaScript URL',
				'status'  => true,
				'message' => '📍 ' . $script->src,
			);
		}

		// Check 6: All Registered Blocks.
		$all_blocks     = array_keys( $registry->get_all_registered() );
		$toplist_blocks = array_filter(
			$all_blocks,
			function ( $name ) {
				return false !== strpos( $name, 'toplist' );
			}
		);

		$checks[] = array(
			'title'   => 'All Toplist Blocks',
			'status'  => ! empty( $toplist_blocks ),
			'message' => ! empty( $toplist_blocks )
				? '✓ Found: ' . implode( ', ', $toplist_blocks )
				: 'ℹ No blocks with "toplist" in the name found',
		);

		// Display results.
		foreach ( $checks as $check ) {
			$class = $check['status'] ? 'notice-success' : 'notice-error';
			?>
			<div class="notice <?php echo esc_attr( $class ); ?> inline">
				<h3>
					<?php echo esc_html( $check['title'] ); ?>
				</h3>
				<p>
					<?php echo wp_kses_post( $check['message'] ); ?>
				</p>
			</div>
			<?php
		}
		?>

		<hr>

		<h2>Browser Console Check</h2>
		<div class="notice notice-warning inline">
			<p><strong>Next Step:</strong> Open the browser console (F12) and look for messages starting with "Toplist:"</p>
			<ol>
				<li>Go to <strong>Posts → Add New</strong></li>
				<li>Press <strong>F12</strong> to open Developer Tools</li>
				<li>Go to the <strong>Console</strong> tab</li>
				<li>Look for these messages:
					<ul>
						<li><code>Toplist: Initializing block script...</code></li>
						<li><code>Toplist: Block "toplist/rankings" registered successfully!</code></li>
					</ul>
				</li>
				<li>If you see RED errors, copy them and check the error details</li>
			</ol>
		</div>

		<h2>Common Issues & Solutions</h2>
		<table class="widefat">
			<thead>
				<tr>
					<th>Issue</th>
					<th>Solution</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Block doesn't appear in inserter</td>
					<td>
						1. Deactivate and reactivate the plugin<br>
						2. Clear browser cache (Ctrl+Shift+R)<br>
						3. Check browser console for JavaScript errors
					</td>
				</tr>
				<tr>
					<td>"wp is not defined" error</td>
					<td>WordPress dependencies not loading - check if other blocks work</td>
				</tr>
				<tr>
					<td>Block registered but not visible</td>
					<td>Check if "Widgets" category exists - try changing category to "common"</td>
				</tr>
				<tr>
					<td>JavaScript file 404 error</td>
					<td>File path issue - check that block.js is in the plugin directory</td>
				</tr>
			</tbody>
		</table>

		<hr>

		<h2>Debug Information</h2>
		<textarea readonly style="width: 100%; height: 200px; font-family: monospace; font-size: 12px;">
		<?php
		echo esc_html( "WordPress Version: $wp_version\n" );
		echo esc_html( 'PHP Version: ' . PHP_VERSION . "\n" );
		echo esc_html( "Plugin Directory: $plugin_dir\n" );
		echo esc_html( 'Plugin URL: ' . plugins_url( '', __FILE__ ) . "\n" );
		echo esc_html( "\nAll Registered Blocks (" . count( $all_blocks ) . "):\n" );
		foreach ( $all_blocks as $block_name ) {
			echo esc_html( "  - $block_name\n" );
		}
		?>
		</textarea>

	</div>
	<?php
}
