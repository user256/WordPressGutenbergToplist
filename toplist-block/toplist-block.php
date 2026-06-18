<?php
/**
 * Plugin Name: Toplist Block
 * Description: A Gutenberg Toplist block. No build tools required.
 * Version: 0.1.2
 * Text Domain: toplist
 * Author: A medly of bots
 * License: GPL-2.0-or-later
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPLIST_BLOCK_PATH' ) ) {
	define( 'TOPLIST_BLOCK_PATH', __DIR__ );
}

if ( ! defined( 'TOPLIST_BLOCK_VERSION' ) ) {
	define( 'TOPLIST_BLOCK_VERSION', '0.1.2' );
}

add_action( 'init', 'toplist_load_textdomain' );

/**
 * Load plugin translations.
 *
 * @return void
 */
function toplist_load_textdomain(): void {
	load_plugin_textdomain( 'toplist', false, dirname( plugin_basename( TOPLIST_BLOCK_PATH . '/toplist-block.php' ) ) . '/languages' );
}

require_once TOPLIST_BLOCK_PATH . '/includes/core/parsing.php';

// @toplist-premium-start
require_once TOPLIST_BLOCK_PATH . '/includes/pro/class-toplist-block-pro-bootstrap.php';
// @toplist-premium-end

/**
 * Register the toplist block and assets.
 *
 * @return void
 */
function toplist_register_block(): void {
	$mtime_js   = filemtime( __DIR__ . '/block.js' );
	$mtime_css  = filemtime( __DIR__ . '/style.css' );
	$mtime_view = filemtime( __DIR__ . '/view.js' );
	wp_register_script(
		'toplist-block',
		plugins_url( 'block.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch' ),
		false !== $mtime_js ? (string) $mtime_js : null,
		true
	);
	wp_add_inline_script(
		'toplist-block',
		'window.toplistBlockSettings = ' . wp_json_encode(
			array(
				'globalDefaultHeaderEnabled' => toplist_get_global_bool_option( 'toplist_global_enable_default_header', false ),
				'globalDefaultHeaderRow'     => toplist_get_global_text_option( 'toplist_global_default_header_row', '' ),
				'globalToplistHeading'       => toplist_get_global_text_option( 'toplist_global_heading_text', '' ),
			)
		) . ';',
		'before'
	);

	wp_register_style(
		'toplist-style',
		plugins_url( 'style.css', __FILE__ ),
		array(),
		false !== $mtime_css ? (string) $mtime_css : null
	);

	wp_register_script(
		'toplist-view',
		plugins_url( 'view.js', __FILE__ ),
		array(),
		false !== $mtime_view ? (string) $mtime_view : null,
		true
	);

	register_block_type(
		'toplist/rankings',
		array(
			'editor_script'   => 'toplist-block',
			'style'           => 'toplist-style',
			'script'          => 'toplist-view',
			'render_callback' => 'toplist_render',
			'attributes'      => array(
				'items'                 => array(
					'type'    => 'array',
					'default' => array(),
					'items'   => array( 'type' => 'object' ),
				),
				'listId'                => array(
					'type'    => 'number',
					'default' => 1,
				),
				'listType'              => array(
					'type'    => 'string',
					'default' => 'product-ranking-best',
				),
				'disclaimer'            => array(
					'type'    => 'string',
					'default' => '#ad. 18+. Gamble Responsibly. GambleAware.org.',
				),
				'customCSS'             => array(
					'type'    => 'string',
					'default' => '',
				),
				'defaultCtaText'        => array(
					'type'    => 'string',
					'default' => 'Visit',
				),
				'defaultReadReviewText' => array(
					'type'    => 'string',
					'default' => 'Read Review',
				),
				'showYear'              => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showLogo'              => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showTerms'             => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showBullets'           => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showOffer'             => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showPayout'            => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showCode'              => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showRating'            => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showRegulator'         => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showPayments'          => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showGames'             => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showLiveGames'         => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showSmallPrint'        => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showReadReview'        => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showWithdrawals'       => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'fieldIncludes'         => array(
					'type'    => 'array',
					'default' => array(),
				),
				'fieldExcludes'         => array(
					'type'    => 'array',
					'default' => array(),
				),
				// @toplist-premium-start
				'savedToplistId'        => array(
					'type'    => 'number',
					'default' => 0,
				),
				'savedToplistMode'      => array(
					'type'    => 'string',
					'default' => 'linked',
				),
				'schemaEnabled'         => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'geoMode'               => array(
					'type'    => 'string',
					'default' => 'auto',
				),
				'geoCode'               => array(
					'type'    => 'string',
					'default' => '',
				),
				'geoFallback'           => array(
					'type'    => 'string',
					'default' => '',
				),
				// @toplist-premium-end
				'defaultHeaderMode'     => array(
					'type'    => 'string',
					'default' => 'global',
				),
				'defaultHeaderRow'      => array(
					'type'    => 'string',
					'default' => '',
				),
				'headingMode'           => array(
					'type'    => 'string',
					'default' => 'global',
				),
				'headingText'           => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		)
	);
}
add_action( 'init', 'toplist_register_block' );

if ( is_admin() ) {
	require_once __DIR__ . '/settings-page.php';
}

/**
 * Outbound link helper — delegates to premium tracking when available.
 *
 * @param string $url     Destination URL.
 * @param string $content Inner HTML.
 * @param string $css_class   CSS class list.
 * @param int    $list_id List context ID.
 * @param int    $row     Row number.
 * @param string $kind    Link kind.
 * @return string
 */
function toplist_maybe_outbound_link( string $url, string $content, string $css_class, int $list_id, int $row, string $kind ): string {
	if ( '' === $url ) {
		return '';
	}
	if ( function_exists( 'toplist_outbound_link' ) ) {
		return toplist_outbound_link( $url, $content, $css_class, $list_id, $row, $kind );
	}
	return '<a class="' . esc_attr( $css_class ) . '" rel="nofollow" target="_blank" href="' . esc_url( $url ) . '">' . $content . '</a>';
}

/**
 * Allowed HTML for outbound link helpers used inside rendered list markup.
 *
 * @return array<string, array<string, bool>>
 */
function toplist_allowed_link_html(): array {
	return array(
		'a'    => array(
			'class'                 => true,
			'rel'                   => true,
			'target'                => true,
			'href'                  => true,
			'role'                  => true,
			'onclick'               => true,
			'data-toplist-outbound' => true,
		),
		'img'  => array(
			'class'   => true,
			'src'     => true,
			'loading' => true,
			'alt'     => true,
			'width'   => true,
			'height'  => true,
		),
		'span' => array(
			'class'       => true,
			'aria-hidden' => true,
		),
	);
}

/**
 * Attach per-render CSS to the block's registered style handle.
 *
 * Used instead of echoing raw <style> tags during render so user/site CSS is
 * delivered through the WordPress enqueue APIs (ticket 711). The block declares
 * `'style' => 'toplist-style'`, so the handle is enqueued whenever the block
 * renders; we enqueue defensively and append the CSS as an inline style.
 *
 * @param string $css CSS to attach. Empty strings are ignored.
 * @return void
 */
function toplist_add_render_inline_css( string $css ): void {
	// Strip any markup (notably a "</style>" breakout) before it reaches the
	// stylesheet. CSS itself is not HTML-escaped — escaping would corrupt valid
	// selectors such as "a > b" — so wp_strip_all_tags is the right guard here.
	$css = trim( wp_strip_all_tags( $css ) );
	if ( '' === $css ) {
		return;
	}
	if ( ! wp_style_is( 'toplist-style', 'registered' ) ) {
		return;
	}
	if ( ! wp_style_is( 'toplist-style', 'enqueued' ) ) {
		wp_enqueue_style( 'toplist-style' );
	}
	wp_add_inline_style( 'toplist-style', $css );
}

/**
 * Render the toplist block.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @return string
 */
function toplist_render( array $attributes ): string {
	$list_id                         = ( isset( $attributes['listId'] ) && is_numeric( $attributes['listId'] ) ) ? (int) $attributes['listId'] : 1;
	$list_type                       = ( isset( $attributes['listType'] ) && is_string( $attributes['listType'] ) ) ? $attributes['listType'] : 'product-ranking-best';
	$disc                            = ( isset( $attributes['disclaimer'] ) && is_string( $attributes['disclaimer'] ) ) ? $attributes['disclaimer'] : '';
	$custom_css                      = ( isset( $attributes['customCSS'] ) && is_string( $attributes['customCSS'] ) ) ? $attributes['customCSS'] : '';
	$saved_toplist_id                = ( isset( $attributes['savedToplistId'] ) && is_numeric( $attributes['savedToplistId'] ) ) ? (int) $attributes['savedToplistId'] : 0;
	$saved_toplist_mode              = ( isset( $attributes['savedToplistMode'] ) && is_string( $attributes['savedToplistMode'] ) ) ? $attributes['savedToplistMode'] : 'linked';
	$default_header_mode             = ( isset( $attributes['defaultHeaderMode'] ) && is_string( $attributes['defaultHeaderMode'] ) ) ? $attributes['defaultHeaderMode'] : 'global';
	$block_default_header_row        = ( isset( $attributes['defaultHeaderRow'] ) && is_string( $attributes['defaultHeaderRow'] ) ) ? trim( $attributes['defaultHeaderRow'] ) : '';
	$heading_mode                    = ( isset( $attributes['headingMode'] ) && is_string( $attributes['headingMode'] ) ) ? $attributes['headingMode'] : 'global';
	$block_heading_text              = ( isset( $attributes['headingText'] ) && is_string( $attributes['headingText'] ) ) ? trim( $attributes['headingText'] ) : '';
	$lines                           = '';
	$global_default_cta_text         = toplist_get_global_text_option( 'toplist_global_default_cta_text', 'Visit' );
	$global_default_read_review_text = toplist_get_global_text_option( 'toplist_global_default_read_review_text', 'Read Review' );
	$global_default_cta_text         = '' !== $global_default_cta_text ? $global_default_cta_text : 'Visit';
	$global_default_read_review_text = '' !== $global_default_read_review_text ? $global_default_read_review_text : 'Read Review';
	$default_cta_text                = toplist_clean_text( $attributes['defaultCtaText'] ?? $global_default_cta_text );
	$default_cta_text                = '' !== $default_cta_text ? $default_cta_text : $global_default_cta_text;
	$default_read_review_text        = toplist_clean_text( $attributes['defaultReadReviewText'] ?? $global_default_read_review_text );
	$default_read_review_text        = '' !== $default_read_review_text ? $default_read_review_text : $global_default_read_review_text;
	$global_default_header_enabled   = toplist_get_global_bool_option( 'toplist_global_enable_default_header', false );
	$global_default_header_row       = toplist_get_global_text_option( 'toplist_global_default_header_row', '' );
	$global_heading_text             = toplist_get_global_text_option( 'toplist_global_heading_text', '' );
	$effective_default_header_row    = '';
	$effective_heading_text          = '';

	if ( 'custom' === $default_header_mode ) {
		$effective_default_header_row = $block_default_header_row;
	} elseif ( 'global' === $default_header_mode ) {
		$effective_default_header_row = $global_default_header_enabled ? $global_default_header_row : '';
	}

	if ( 'custom' === $heading_mode ) {
		$effective_heading_text = $block_heading_text;
	} elseif ( 'global' === $heading_mode ) {
		$effective_heading_text = $global_heading_text;
	}

	$supported_fields = toplist_supported_fields();
	$field_includes   = array();
	$field_excludes   = array();

	// @toplist-premium-start
	// Linked mode: source all rows from saved Toplist library post content.
	if (
		'linked' === $saved_toplist_mode
		&& $saved_toplist_id > 0
		&& class_exists( 'Toplist_Block_License' )
		&& Toplist_Block_License::is_valid()
	) {
		$saved_post = get_post( $saved_toplist_id );
		if ( $saved_post && 'toplist_list' === $saved_post->post_type ) {
			$lines = (string) $saved_post->post_content;
		}
	}
	// @toplist-premium-end

	$list_context_id = (int) apply_filters( 'toplist_render_list_context_id', 0, $attributes, $saved_toplist_id, $saved_toplist_mode );

	if ( '' !== trim( $lines ) ) {
		$parsed         = toplist_parse_lines_to_items(
			$lines,
			array(
				'defaultCtaText'        => $default_cta_text,
				'defaultReadReviewText' => $default_read_review_text,
				'defaultHeaderRow'      => $effective_default_header_row,
			)
		);
		$items          = $parsed['items'];
		$field_includes = is_array( $parsed['includes'] ?? null ) ? array_values( array_unique( array_values( array_intersect( $supported_fields, $parsed['includes'] ) ) ) ) : array();
		$field_excludes = is_array( $parsed['excludes'] ?? null ) ? array_values( array_unique( array_values( array_intersect( $supported_fields, $parsed['excludes'] ) ) ) ) : array();
	} else {
		$items          = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
		$items          = array_values( array_filter( $items, 'toplist_item_has_content' ) );
		$field_includes = isset( $attributes['fieldIncludes'] ) && is_array( $attributes['fieldIncludes'] )
			? array_values( array_unique( array_values( array_intersect( $supported_fields, $attributes['fieldIncludes'] ) ) ) )
			: array();
		$field_excludes = isset( $attributes['fieldExcludes'] ) && is_array( $attributes['fieldExcludes'] )
			? array_values( array_unique( array_values( array_intersect( $supported_fields, $attributes['fieldExcludes'] ) ) ) )
			: array();
	}

	if ( empty( $items ) ) {
		return '';
	}

	$items = apply_filters( 'toplist_render_items', $items, $attributes, $list_context_id );

	if ( empty( $items ) ) {
		return '';
	}

	$card_layout_css = (string) apply_filters( 'toplist_render_card_css', '', $list_context_id );

	$show_year        = ( isset( $attributes['showYear'] ) ? (bool) $attributes['showYear'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_year', true ), $list_context_id, 'toplist_global_show_year', true );
	$show_logo        = ( isset( $attributes['showLogo'] ) ? (bool) $attributes['showLogo'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_logo', true ), $list_context_id, 'toplist_global_show_logo', true );
	$show_terms       = ( isset( $attributes['showTerms'] ) ? (bool) $attributes['showTerms'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_terms', true ), $list_context_id, 'toplist_global_show_terms', true );
	$show_bullets     = ( isset( $attributes['showBullets'] ) ? (bool) $attributes['showBullets'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_bullets', true ), $list_context_id, 'toplist_global_show_bullets', true );
	$show_offer       = ( isset( $attributes['showOffer'] ) ? (bool) $attributes['showOffer'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_offer', true ), $list_context_id, 'toplist_global_show_offer', true );
	$show_payout      = ( isset( $attributes['showPayout'] ) ? (bool) $attributes['showPayout'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_payout', true ), $list_context_id, 'toplist_global_show_payout', true );
	$show_code        = ( isset( $attributes['showCode'] ) ? (bool) $attributes['showCode'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_code', true ), $list_context_id, 'toplist_global_show_code', true );
	$show_rating      = ( isset( $attributes['showRating'] ) ? (bool) $attributes['showRating'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_rating', true ), $list_context_id, 'toplist_global_show_rating', true );
	$show_regulator   = ( isset( $attributes['showRegulator'] ) ? (bool) $attributes['showRegulator'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_regulator', true ), $list_context_id, 'toplist_global_show_regulator', true );
	$show_payments    = ( isset( $attributes['showPayments'] ) ? (bool) $attributes['showPayments'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_payments', true ), $list_context_id, 'toplist_global_show_payments', true );
	$show_games       = ( isset( $attributes['showGames'] ) ? (bool) $attributes['showGames'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_games', true ), $list_context_id, 'toplist_global_show_games', true );
	$show_live_games  = ( isset( $attributes['showLiveGames'] ) ? (bool) $attributes['showLiveGames'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_live_games', true ), $list_context_id, 'toplist_global_show_live_games', true );
	$show_small_print = ( isset( $attributes['showSmallPrint'] ) ? (bool) $attributes['showSmallPrint'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_small_print', true ), $list_context_id, 'toplist_global_show_small_print', true );
	$show_read_review = ( isset( $attributes['showReadReview'] ) ? (bool) $attributes['showReadReview'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_read_review', true ), $list_context_id, 'toplist_global_show_read_review', true );
	$show_withdrawals = ( isset( $attributes['showWithdrawals'] ) ? (bool) $attributes['showWithdrawals'] : true ) && (bool) apply_filters( 'toplist_get_render_bool', toplist_get_global_bool_option( 'toplist_global_show_withdrawals', true ), $list_context_id, 'toplist_global_show_withdrawals', true );

	$global_css = (string) apply_filters( 'toplist_get_render_css', '', $list_context_id );
	if ( '' === $global_css ) {
		$raw_css    = get_option( 'toplist_global_css', '' );
		$global_css = is_string( $raw_css ) ? trim( wp_strip_all_tags( $raw_css ) ) : '';
	}

	// Deliver per-render CSS through the block's registered style handle via
	// wp_add_inline_style() rather than echoing raw <style> tags (ticket 711).
	// The helper strips markup, so raw values are fine here.
	toplist_add_render_inline_css( $global_css );
	toplist_add_render_inline_css( $custom_css );
	toplist_add_render_inline_css( $card_layout_css );

	ob_start();

	if ( '' !== $effective_heading_text ) {
		echo '<h2 class="toplist-heading">' . esc_html( $effective_heading_text ) . '</h2>';
	}
	?>
	<ol class="automation-rwd-table rwd-table toplist" data-toplist-listid="<?php echo esc_attr( (string) $list_id ); ?>"
		data-toplist-listtype="<?php echo esc_attr( $list_type ); ?>">
		<?php
		foreach ( $items as $i => $item ) :
			$pos = $i + 1;

			$operator         = toplist_field_is_included( 'operator', $field_includes, $field_excludes ) ? toplist_clean_text( $item['operator'] ?? '' ) : '';
			$product          = toplist_field_is_included( 'product', $field_includes, $field_excludes ) ? toplist_clean_text( $item['product'] ?? '' ) : '';
			$offer            = toplist_field_is_included( 'offer', $field_includes, $field_excludes ) ? toplist_clean_text( $item['offer'] ?? '' ) : '';
			$href             = toplist_field_is_included( 'href', $field_includes, $field_excludes ) ? esc_url_raw( toplist_clean_text( $item['href'] ?? '' ) ) : '';
			$logo             = toplist_field_is_included( 'logo', $field_includes, $field_excludes ) ? esc_url_raw( toplist_clean_text( $item['logo'] ?? '' ) ) : '';
			$year             = toplist_field_is_included( 'year', $field_includes, $field_excludes ) ? toplist_clean_text( $item['year'] ?? '' ) : '';
			$terms_enabled    = toplist_field_is_included( 'terms', $field_includes, $field_excludes );
			$terms            = $terms_enabled ? toplist_clean_text( $item['terms'] ?? '' ) : '';
			$payout           = toplist_field_is_included( 'payout', $field_includes, $field_excludes ) ? toplist_clean_text( $item['payout'] ?? '' ) : '';
			$code             = toplist_field_is_included( 'code', $field_includes, $field_excludes ) ? toplist_clean_text( $item['code'] ?? '' ) : '';
			$rating           = toplist_field_is_included( 'rating', $field_includes, $field_excludes ) ? toplist_clean_text( $item['rating'] ?? '' ) : '';
			$regulator        = toplist_field_is_included( 'regulator', $field_includes, $field_excludes ) ? toplist_clean_text( $item['regulator'] ?? '' ) : '';
			$live_games       = toplist_field_is_included( 'liveGames', $field_includes, $field_excludes ) ? toplist_clean_text( $item['liveGames'] ?? '' ) : '';
			$small_print      = toplist_field_is_included( 'smallPrint', $field_includes, $field_excludes ) ? toplist_clean_text( $item['smallPrint'] ?? '' ) : '';
			$read_review_href = toplist_field_is_included( 'readReviewHref', $field_includes, $field_excludes ) ? esc_url_raw( toplist_clean_text( $item['readReviewHref'] ?? '' ) ) : '';

			$cta_text_enabled         = toplist_field_is_included( 'ctaText', $field_includes, $field_excludes );
			$read_review_text_enabled = toplist_field_is_included( 'readReviewText', $field_includes, $field_excludes );
			$cta_text_value           = $cta_text_enabled ? toplist_clean_text( $item['ctaText'] ?? '' ) : '';
			$read_review_text_value   = $read_review_text_enabled ? toplist_clean_text( $item['readReviewText'] ?? '' ) : '';
			$cta_text                 = '' !== $cta_text_value ? $cta_text_value : $default_cta_text;
			$read_review_text         = '' !== $read_review_text_value ? $read_review_text_value : $default_read_review_text;

			$bullets     = toplist_field_is_included( 'bullets', $field_includes, $field_excludes ) ? toplist_clean_list( $item['bullets'] ?? array() ) : array();
			$payments    = toplist_field_is_included( 'payments', $field_includes, $field_excludes ) ? toplist_clean_list( $item['payments'] ?? array() ) : array();
			$games       = toplist_field_is_included( 'games', $field_includes, $field_excludes ) ? toplist_clean_list( $item['games'] ?? array() ) : array();
			$withdrawals = toplist_field_is_included( 'withdrawals', $field_includes, $field_excludes ) ? toplist_clean_list( $item['withdrawals'] ?? array() ) : array();

			$summary_bullets = array_slice( $bullets, 0, 2 );
			$extra_bullets   = array_slice( $bullets, 2 );

				$has_logo_content    = (bool) $logo || '' !== $product || '' !== $operator;
				$has_identity_text   = '' !== $product || '' !== $operator;
				$has_offer           = $show_offer && '' !== $offer;
				$has_cta             = '' !== $href && '' !== $cta_text;
				$has_read_review     = $show_read_review && '' !== $read_review_href;
				$has_payout          = $show_payout && '' !== $payout;
				$has_code            = $show_code && '' !== $code;
				$has_rating          = $show_rating && '' !== $rating;
				$has_regulator       = $show_regulator && '' !== $regulator;
				$has_identity_meta   = $has_identity_text || $has_rating || ( $show_year && '' !== $year ) || $has_regulator;
				$has_payments        = $show_payments && ! empty( $payments );
				$has_games           = $show_games && ! empty( $games );
				$has_live_games      = $show_live_games && '' !== $live_games;
				$has_small_print     = $show_small_print && '' !== $small_print;
				$has_withdrawals     = $show_withdrawals && ! empty( $withdrawals );
				$has_terms           = $show_terms && $terms_enabled && ( '' !== toplist_clean_text( $disc ) || '' !== $terms );
				$has_summary_bullets = $show_bullets && ! empty( $summary_bullets );
				$has_extra_bullets   = $show_bullets && ! empty( $extra_bullets );
				$has_summary_details = $has_payout || $has_summary_bullets;
				$has_extra_details   = $has_extra_bullets || $has_games || $has_live_games || $has_small_print || $has_withdrawals;
				$has_details         = $has_summary_details || $has_extra_details;
				$has_play_column     = $has_code || $has_read_review || $has_cta || $has_payments;
				$details_expanded    = 1 === (int) $pos;
			?>
			<li class="operator-item automation-operator-item operator-item-v2"
				data-operator="<?php echo esc_attr( $operator ); ?>" data-product="<?php echo esc_attr( $product ); ?>"
				data-position="<?php echo esc_attr( (string) $pos ); ?>">
				<div class="operator-main">
					<div class="op-left">
						<div class="operator-column-ranking-v2"><span><?php echo esc_html( (string) $pos ); ?></span></div>

						<?php if ( ( $show_logo && $has_logo_content ) || $has_identity_meta ) : ?>
							<div class="operator-column-logo-v2 logo-wrapper">
									<?php if ( $show_logo && $has_logo_content ) : ?>
										<?php if ( $href ) : ?>
											<?php
											echo wp_kses(
												toplist_maybe_outbound_link(
													$href,
													$logo
														? '<img class="op-logo" src="' . esc_url( $logo ) . '" loading="lazy" alt="' . esc_attr( $product ? $product : $operator ) . '" width="150" height="100" />'
														: '<span class="op-logo-fallback">' . esc_html( $product ? $product : $operator ) . '</span>',
													'operator-item__image_link exit-page-link',
													$list_context_id,
													$pos,
													'logo'
												),
												toplist_allowed_link_html()
											);
											?>
										<?php else : ?>
											<?php if ( $logo ) : ?>
											<img class="op-logo" src="<?php echo esc_url( $logo ); ?>" loading="lazy"
												alt="<?php echo esc_attr( $product ? $product : $operator ); ?>" width="150" height="100" />
											<?php else : ?>
												<span class="op-logo-fallback"><?php echo esc_html( $product ? $product : $operator ); ?></span>
											<?php endif; ?>
										<?php endif; ?>
									<?php endif; ?>

									<?php if ( $has_identity_meta ) : ?>
										<div class="operator-title-row-v2">
											<?php if ( $has_identity_text ) : ?>
												<div class="operator-product-name-v2"><?php echo esc_html( $product ? $product : $operator ); ?></div>
											<?php endif; ?>
											<?php if ( $has_rating ) : ?>
												<div class="operator-rating-v2"><span class="operator-rating-star-v2" aria-hidden="true">★</span> <?php echo esc_html( $rating ); ?> <span class="operator-rating-outof-v2">/ 5</span></div>
											<?php endif; ?>
										</div>
									<?php endif; ?>

								<?php if ( $show_year && $year ) : ?>
									<div class="operator-established-year-v2">Launched <?php echo esc_html( $year ); ?></div>
								<?php endif; ?>

								<?php if ( $has_regulator ) : ?>
									<div class="operator-regulator-v2">Regulated by: <?php echo esc_html( $regulator ); ?></div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="op-right">
						<?php if ( $has_offer || $has_terms || $has_details ) : ?>
							<div class="operator-column-bonus-v2">
									<?php if ( $has_offer ) : ?>
										<?php if ( $href ) : ?>
											<?php
											echo wp_kses(
												toplist_maybe_outbound_link(
													$href,
													esc_html( $offer ),
													'offer-description exit-page-link',
													$list_context_id,
													$pos,
													'offer'
												),
												toplist_allowed_link_html()
											);
											?>
										<?php else : ?>
											<div class="offer-description"><?php echo esc_html( $offer ); ?></div>
									<?php endif; ?>
								<?php endif; ?>

								<?php if ( $has_terms && ! $has_small_print ) : ?>
									<div class="operator-item-link read-terms-link-v2">
										<span class="terms-and-conditions"><?php echo esc_html( trim( (string) $disc . ' ' . (string) $terms ) ); ?></span>
									</div>
								<?php endif; ?>

								<?php if ( $has_details ) : ?>
									<div class="more-info-table">
										<?php if ( $has_payout ) : ?>
											<div class="operator-payout-v2"><strong>Payout time:</strong> <?php echo esc_html( $payout ); ?></div>
										<?php endif; ?>

										<?php if ( $has_summary_bullets ) : ?>
											<div class="attributes-list attributes-list--summary">
												<?php foreach ( $summary_bullets as $b ) : ?>
													<?php
														$bullet_text = (string) $b;
														$is_negative = (bool) preg_match( '/^\s*[-!xX]\s+/', $bullet_text );
														$bullet_text = preg_replace( '/^\s*[-!xX]\s+/', '', $bullet_text );
													?>
														<div class="<?php echo esc_attr( $is_negative ? 'gray-cross ' : 'green-tick ' ); ?>attribute-list-item"><?php echo esc_html( (string) $bullet_text ); ?></div>
													<?php endforeach; ?>
												</div>
											<?php endif; ?>

											<?php if ( $has_extra_details ) : ?>
												<div class="more-info-extra" data-toplist-details style="display:<?php echo esc_attr( $details_expanded ? 'block' : 'none' ); ?>;">
													<?php if ( $has_extra_bullets ) : ?>
														<div class="attributes-list attributes-list--extra">
															<?php foreach ( $extra_bullets as $b ) : ?>
																<?php
																$bullet_text = (string) $b;
																$is_negative = (bool) preg_match( '/^\s*[-!xX]\s+/', $bullet_text );
																$bullet_text = preg_replace( '/^\s*[-!xX]\s+/', '', $bullet_text );
																?>
																<div class="<?php echo esc_attr( $is_negative ? 'gray-cross ' : 'green-tick ' ); ?>attribute-list-item"><?php echo esc_html( (string) $bullet_text ); ?></div>
															<?php endforeach; ?>
														</div>
													<?php endif; ?>

												<?php if ( $has_games ) : ?>
													<div class="operator-games-v2"><strong>Games:</strong> <?php echo esc_html( implode( ' ', $games ) ); ?></div>
												<?php endif; ?>
												<?php if ( $has_live_games ) : ?>
													<div class="operator-live-games-v2"><strong>Live Games:</strong> <?php echo esc_html( $live_games ); ?></div>
												<?php endif; ?>
												<?php if ( $has_withdrawals ) : ?>
													<div class="operator-withdrawals-v2"><strong>Withdrawals:</strong> <?php echo esc_html( implode( ', ', $withdrawals ) ); ?></div>
												<?php endif; ?>
												<?php if ( $has_small_print ) : ?>
													<div class="operator-small-print-v2"><?php echo esc_html( $small_print ); ?></div>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ( $has_play_column ) : ?>
							<div class="operator-playnow-column-v2">
								<?php if ( $has_code ) : ?>
									<div class="operator-code-v2">Use code: <strong><?php echo esc_html( $code ); ?></strong></div>
								<?php endif; ?>
									<?php if ( $has_read_review ) : ?>
										<?php
										echo wp_kses(
											toplist_maybe_outbound_link(
												$read_review_href,
												'<span class="button-ghost-v2">' . esc_html( $read_review_text ) . '</span>',
												'operator-item__cta_link exit-page-link',
												$list_context_id,
												$pos,
												'review'
											),
											toplist_allowed_link_html()
										);
										?>
									<?php endif; ?>
									<?php if ( $has_cta ) : ?>
										<?php
										echo wp_kses(
											toplist_maybe_outbound_link(
												$href,
												'<span class="button-blue-v2">' . esc_html( $cta_text ) . '</span>',
												'operator-item__cta_link exit-page-link',
												$list_context_id,
												$pos,
												'cta'
											),
											toplist_allowed_link_html()
										);
										?>
									<?php endif; ?>
								<?php if ( $has_payments ) : ?>
									<div class="operator-payments-v2">
										<?php foreach ( $payments as $payment ) : ?>
											<span class="payment-chip-v2"><?php echo esc_html( $payment ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

					<?php if ( $has_extra_details ) : ?>
						<button type="button" class="more_info_button<?php echo esc_attr( $details_expanded ? '' : ' is-collapsed' ); ?>" data-toplist-toggle="details" aria-expanded="<?php echo esc_attr( $details_expanded ? 'true' : 'false' ); ?>"><?php echo esc_html( $details_expanded ? 'Hide Details' : 'Show Details' ); ?></button>
					<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
	do_action( 'toplist_after_render_list', $list_context_id, $attributes );
	// @toplist-premium-start
	if (
		! empty( $attributes['schemaEnabled'] )
		&& class_exists( 'Toplist_Block_License' )
		&& Toplist_Block_License::is_valid()
	) {
		$schema = toplist_build_itemlist_schema( $items, $effective_heading_text );
		if ( ! empty( $schema['itemListElement'] ) ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
		}
	}
	// @toplist-premium-end
	$output = ob_get_clean();
	return false === $output ? '' : $output;
}
