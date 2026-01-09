<?php
/**
 * Plugin Name: Toplist Block
 * Description: A Gutenberg Toplist block. No build tools required.
 * Version: 0.1.2
 * Author: A medly of bots
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH'))
	exit;

function toplist_register_block()
{
	// Debug: Log when function is called
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log('Toplist: toplist_register_block() called');
	}

	wp_register_script(
		'toplist-block',
		plugins_url('block.js', __FILE__),
		array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
		filemtime(__DIR__ . '/block.js')
	);

	// Debug: Log script registration
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log('Toplist: block.js registered at ' . plugins_url('block.js', __FILE__));
	}

	wp_register_style(
		'toplist-style',
		plugins_url('style.css', __FILE__),
		array(),
		filemtime(__DIR__ . '/style.css')
	);

	wp_register_script(
		'toplist-view',
		plugins_url('view.js', __FILE__),
		array(),
		filemtime(__DIR__ . '/view.js'),
		true
	);

	register_block_type('toplist/rankings', array(
		'editor_script' => 'toplist-block',
		'style' => 'toplist-style',
		'script' => 'toplist-view',
		'render_callback' => 'toplist_render',
		'attributes' => array(
			'items' => array(
				'type' => 'array',
				'default' => array(),
				'items' => array('type' => 'object'),
			),
			'listId' => array(
				'type' => 'number',
				'default' => 1,
			),
			'listType' => array(
				'type' => 'string',
				'default' => 'product-ranking-best',
			),
			'disclaimer' => array(
				'type' => 'string',
				'default' => '#ad. 18+. Gamble Responsibly. GambleAware.org.',
			),
			'customCSS' => array(
				'type' => 'string',
				'default' => '',
			),
			'showYear' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showLogo' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showTerms' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'showBullets' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'_lines' => array(
				'type' => 'string',
				'default' => '',
			),
		),
	));

	// Debug: Log successful registration
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log('Toplist: Block "toplist/rankings" registered successfully');
	}
}
add_action('init', 'toplist_register_block');

// Include admin diagnostics page
if (is_admin()) {
	require_once __DIR__ . '/admin-diagnostics.php';
	require_once __DIR__ . '/settings-page.php';
}

function toplist_render($attributes)
{
	$items = isset($attributes['items']) && is_array($attributes['items']) ? $attributes['items'] : array();
	if (empty($items))
		return '';

	$list_id = isset($attributes['listId']) ? (int) $attributes['listId'] : 1;
	$list_type = isset($attributes['listType']) ? (string) $attributes['listType'] : 'product-ranking-best';
	$disc = isset($attributes['disclaimer']) ? (string) $attributes['disclaimer'] : '';
	$custom_css = isset($attributes['customCSS']) ? (string) $attributes['customCSS'] : '';

	// Visibility settings
	$show_year = isset($attributes['showYear']) ? (bool) $attributes['showYear'] : true;
	$show_logo = isset($attributes['showLogo']) ? (bool) $attributes['showLogo'] : true;
	$show_terms = isset($attributes['showTerms']) ? (bool) $attributes['showTerms'] : true;
	$show_bullets = isset($attributes['showBullets']) ? (bool) $attributes['showBullets'] : true;

	ob_start();

	$global_css = get_option('toplist_global_css', '');
	$global_css = trim(wp_strip_all_tags($global_css));

	if ($global_css !== '') {
		echo '<style>' . $global_css . '</style>';
	}

	// Output custom CSS if provided
	if (!empty($custom_css)) {
		echo '<style>' . wp_strip_all_tags($custom_css) . '</style>';
	}
	?>
	<ol class="automation-rwd-table rwd-table toplist" data-toplist-listid="<?php echo esc_attr($list_id); ?>"
		data-toplist-listtype="<?php echo esc_attr($list_type); ?>">
		<?php foreach ($items as $i => $item):
			$pos = $i + 1;
			$operator = isset($item['operator']) ? (string) $item['operator'] : '';
			$product = isset($item['product']) ? (string) $item['product'] : '';
			$offer = isset($item['offer']) ? (string) $item['offer'] : '';
			$href = isset($item['href']) ? (string) $item['href'] : '';
			$logo = isset($item['logo']) ? (string) $item['logo'] : '';
			$year = isset($item['year']) ? (string) $item['year'] : '';
			$ctaText = isset($item['ctaText']) ? (string) $item['ctaText'] : 'Visit';
			$terms = isset($item['terms']) ? (string) $item['terms'] : '';
			$bullets = isset($item['bullets']) && is_array($item['bullets']) ? $item['bullets'] : array();
			?>
			<li class="operator-item automation-operator-item operator-item-v2"
				data-operator="<?php echo esc_attr($operator); ?>" data-product="<?php echo esc_attr($product); ?>"
				data-position="<?php echo esc_attr($pos); ?>">
				<div class="operator-main">
					<div class="op-left">
						<div class="operator-column-ranking-v2"><?php echo esc_html($pos); ?></div>

						<div class="operator-column-logo-v2 logo-wrapper">
							<?php if ($show_logo): ?>
								<?php if ($href): ?>
									<a class="operator-item__image_link exit-page-link" rel="nofollow" target="_blank"
										href="<?php echo esc_url($href); ?>">
										<?php if ($logo): ?>
											<img class="op-logo" src="<?php echo esc_url($logo); ?>" loading="lazy"
												alt="<?php echo esc_attr($product ?: $operator); ?>" width="150" height="100" />
										<?php else: ?>
											<span class="op-logo-fallback"><?php echo esc_html($product ?: $operator); ?></span>
										<?php endif; ?>
									</a>
								<?php else: ?>
									<?php if ($logo): ?>
										<img class="op-logo" src="<?php echo esc_url($logo); ?>" loading="lazy"
											alt="<?php echo esc_attr($product ?: $operator); ?>" width="150" height="100" />
									<?php else: ?>
										<span class="op-logo-fallback"><?php echo esc_html($product ?: $operator); ?></span>
									<?php endif; ?>
								<?php endif; ?>
							<?php endif; ?>

							<?php if ($show_year && $year): ?>
								<div class="operator-established-year-v2">Launched <?php echo esc_html($year); ?></div>
							<?php endif; ?>

							<button type="button" class="more_info_button" data-toplist-toggle="details">Hide Details</button>
						</div>
					</div>

					<div class="op-right">
						<div class="operator-column-bonus-v2">
							<?php if ($href): ?>
								<a class="offer-description exit-page-link" rel="nofollow" target="_blank"
									href="<?php echo esc_url($href); ?>">
									<?php echo esc_html($offer); ?>
								</a>
							<?php else: ?>
								<div class="offer-description"><?php echo esc_html($offer); ?></div>
							<?php endif; ?>
						</div>

						<div class="operator-playnow-column-v2">
							<?php if ($href): ?>
								<a class="operator-item__cta_link exit-page-link" rel="nofollow" target="_blank"
									href="<?php echo esc_url($href); ?>">
									<span class="button-blue-v2"><?php echo esc_html($ctaText); ?></span>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<?php if (($show_terms && $disc) || ($show_terms && $terms)): ?>
					<div class="operator-item-link read-terms-link-v2">
						<span class="terms-and-conditions"><?php echo esc_html(trim($disc . ' ' . $terms)); ?></span>
					</div>
				<?php endif; ?>

				<div class="more-info-table" data-toplist-details style="display:block;">
					<?php if ($show_bullets && !empty($bullets)): ?>
						<div class="attributes-list">
							<?php foreach ($bullets as $b): ?>
								<div class="green-tick attribute-list-item"><?php echo esc_html((string) $b); ?></div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
	return ob_get_clean();
}
