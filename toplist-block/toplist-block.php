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

// @toplist-premium-start
require_once TOPLIST_BLOCK_PATH . '/includes/pro/class-toplist-block-pro-bootstrap.php';
// @toplist-premium-end

/**
 * Normalize arbitrary value to a trimmed string.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function toplist_clean_text( $value ): string {
	return is_string( $value ) ? trim( $value ) : '';
}

/**
 * Normalize semicolon-delimited or array value to a clean string list.
 *
 * @param mixed $value Raw value.
 * @return array<string>
 */
function toplist_clean_list( $value ): array {
	if ( is_array( $value ) ) {
		return array_values( array_filter( array_map( 'toplist_clean_text', $value ) ) );
	}

	if ( is_string( $value ) ) {
		return array_values( array_filter( array_map( 'toplist_clean_text', explode( ';', $value ) ) ) );
	}

	return array();
}

/**
 * Supported field names used for directives and render gating.
 *
 * @return array<string>
 */
function toplist_supported_fields(): array {
	return array(
		'operator',
		'product',
		'offer',
		'href',
		'logo',
		'year',
		'ctaText',
		'terms',
		'bullets',
		'payout',
		'code',
		'rating',
		'regulator',
		'payments',
		'games',
		'liveGames',
		'smallPrint',
		'readReviewHref',
		'readReviewText',
		'withdrawals',
	);
}

/**
 * Determine whether a toplist item has renderable content.
 *
 * @param mixed $item Item object/array.
 * @return bool
 */
function toplist_item_has_content( $item ): bool {
	if ( ! is_array( $item ) ) {
		return false;
	}

	foreach ( array( 'operator', 'product', 'offer', 'href', 'logo', 'year', 'terms', 'payout', 'code', 'rating', 'regulator', 'liveGames', 'smallPrint', 'readReviewHref' ) as $key ) {
		if ( toplist_clean_text( $item[ $key ] ?? '' ) !== '' ) {
			return true;
		}
	}

	foreach ( array( 'bullets', 'payments', 'games', 'withdrawals' ) as $list_key ) {
		if ( ! empty( toplist_clean_list( $item[ $list_key ] ?? array() ) ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Determine if a field is eligible to render from header directives.
 *
 * @param string        $field Field key.
 * @param array<string> $includes Included keys from header.
 * @param array<string> $excludes Excluded keys from header.
 * @return bool
 */
function toplist_field_is_included( string $field, array $includes, array $excludes ): bool {
	if ( in_array( $field, $excludes, true ) ) {
		return false;
	}

	if ( ! empty( $includes ) ) {
		return in_array( $field, $includes, true );
	}

	return true;
}

/**
 * Read a global boolean option for toplist settings.
 *
 * @param string $key Option key.
 * @param bool   $fallback Fallback value.
 * @return bool
 */
function toplist_get_global_bool_option( string $key, bool $fallback = true ): bool {
	$raw = get_option( $key, $fallback ? '1' : '0' );
	if ( is_bool( $raw ) ) {
		return $raw;
	}
	return is_string( $raw ) && in_array( strtolower( $raw ), array( '1', 'true', 'yes', 'on' ), true );
}

/**
 * Read a global text option for toplist settings.
 *
 * @param string $key Option key.
 * @param string $fallback Fallback value.
 * @return string
 */
function toplist_get_global_text_option( string $key, string $fallback = '' ): string {
	$value = get_option( $key, $fallback );
	return is_string( $value ) ? trim( $value ) : $fallback;
}

/**
 * Parse and normalize a directive/header token.
 *
 * @param string $token Raw token.
 * @return array<string, mixed>
 */
function toplist_normalize_directive_token( string $token ): array {
	$raw       = trim( (string) $token );
	$excluded  = false;
	$supported = toplist_supported_fields();
	$lookup    = array();

	foreach ( $supported as $field ) {
		$lookup[ strtolower( $field ) ] = $field;
	}

	if ( '' === $raw ) {
		return array(
			'field'      => '',
			'excluded'   => false,
			'recognized' => false,
		);
	}

	if ( '-' === $raw[0] || '!' === $raw[0] ) {
		$excluded = true;
		$raw      = trim( substr( $raw, 1 ) );
	}

	$canonical = $lookup[ strtolower( $raw ) ] ?? '';
	return array(
		'field'      => $canonical,
		'excluded'   => $excluded,
		'recognized' => '' !== $canonical,
	);
}

/**
 * Heuristic to detect header/directive row.
 *
 * @param array<string> $parts Pipe-delimited row parts.
 * @return bool
 */
function toplist_detect_header_row( array $parts ): bool {
	$recognized = 0;

	foreach ( $parts as $part ) {
		$token = toplist_normalize_directive_token( $part );
		if ( $token['recognized'] ) {
			++$recognized;
		}
		if ( preg_match( '/https?:\/\//i', (string) $part ) ) {
			return false;
		}
	}

	return $recognized >= 3;
}

/**
 * Parse local/saved lines to normalized items + include/exclude directives.
 *
 * @param string               $text Raw textarea content.
 * @param array<string, mixed> $defaults Default labels.
 * @return array<string, mixed>
 */
function toplist_parse_lines_to_items( string $text, array $defaults = array() ): array {
	$default_cta_text         = is_string( $defaults['defaultCtaText'] ?? 'Visit' ) ? trim( $defaults['defaultCtaText'] ?? 'Visit' ) : 'Visit';
	$default_read_review_text = is_string( $defaults['defaultReadReviewText'] ?? 'Read Review' ) ? trim( $defaults['defaultReadReviewText'] ?? 'Read Review' ) : 'Read Review';
	$default_header_row       = is_string( $defaults['defaultHeaderRow'] ?? '' ) ? trim( $defaults['defaultHeaderRow'] ?? '' ) : '';
	$default_cta_text         = '' !== $default_cta_text ? $default_cta_text : 'Visit';
	$default_read_review_text = '' !== $default_read_review_text ? $default_read_review_text : 'Read Review';
	$lines                    = preg_split( '/\r\n|\r|\n/', (string) $text );
	$lines                    = is_array( $lines ) ? array_values( array_filter( array_map( 'trim', $lines ), fn( string $v ): bool => '' !== $v ) ) : array();
	$items                    = array();
	$includes                 = array();
	$excludes                 = array();
	$start_index              = 0;

	if ( empty( $lines ) ) {
		return array(
			'items'    => array(),
			'includes' => array(),
			'excludes' => array(),
		);
	}

	$header_parts  = explode( '|', $lines[0] );
	$header_tokens = array_map( 'toplist_normalize_directive_token', $header_parts );
	$file_header   = toplist_detect_header_row( $header_parts );

	if ( $file_header ) {
		$has_header  = true;
		$start_index = 1;
	} elseif ( '' !== $default_header_row ) {
		$default_header_parts = explode( '|', $default_header_row );
		$first_line_parts     = explode( '|', $lines[0] );
		// Wide rows are full fixed-column pipe data: do not apply a short virtual header (would drop columns).
		if ( count( $first_line_parts ) <= count( $default_header_parts ) ) {
			$header_parts  = $default_header_parts;
			$header_tokens = array_map( 'toplist_normalize_directive_token', $header_parts );
			$has_header    = true;
			$start_index   = 0;
		} else {
			$has_header  = false;
			$start_index = 0;
		}
	} else {
		$has_header  = false;
		$start_index = 0;
	}

	if ( $has_header ) {
		foreach ( $header_tokens as $token ) {
			if ( ! $token['recognized'] ) {
				continue;
			}
			if ( $token['excluded'] ) {
				$excludes[] = $token['field'];
			} else {
				$includes[] = $token['field'];
			}
		}
	}

	$lines_count = count( $lines );
	for ( $i = $start_index; $i < $lines_count; ++$i ) {
		$parts = array_map( 'trim', explode( '|', $lines[ $i ] ) );
		$item  = array(
			'operator'       => '',
			'product'        => '',
			'offer'          => '',
			'href'           => '',
			'logo'           => '',
			'year'           => '',
			'ctaText'        => '',
			'terms'          => '',
			'bullets'        => array(),
			'payout'         => '',
			'code'           => '',
			'rating'         => '',
			'regulator'      => '',
			'payments'       => array(),
			'games'          => array(),
			'liveGames'      => '',
			'smallPrint'     => '',
			'readReviewHref' => '',
			'readReviewText' => '',
			'withdrawals'    => array(),
		);

		if ( $has_header ) {
			$header_tokens_count = count( $header_tokens );
			for ( $col = 0; $col < $header_tokens_count; ++$col ) {
				$token = $header_tokens[ $col ];
				$value = trim( (string) ( $parts[ $col ] ?? '' ) );
				if ( ! $token['recognized'] || $token['excluded'] ) {
					continue;
				}
				if ( in_array( $token['field'], array( 'bullets', 'payments', 'games', 'withdrawals' ), true ) ) {
					$item[ $token['field'] ] = toplist_clean_list( $value );
				} else {
					$item[ $token['field'] ] = $value;
				}
			}
		} else {
			$item = array(
				'operator'       => (string) ( $parts[0] ?? '' ),
				'product'        => (string) ( $parts[1] ?? '' ),
				'offer'          => (string) ( $parts[2] ?? '' ),
				'href'           => (string) ( $parts[3] ?? '' ),
				'logo'           => (string) ( $parts[4] ?? '' ),
				'year'           => (string) ( $parts[5] ?? '' ),
				'ctaText'        => (string) ( $parts[6] ?? '' ),
				'terms'          => (string) ( $parts[7] ?? '' ),
				'bullets'        => toplist_clean_list( (string) ( $parts[8] ?? '' ) ),
				'payout'         => (string) ( $parts[9] ?? '' ),
				'code'           => (string) ( $parts[10] ?? '' ),
				'rating'         => (string) ( $parts[11] ?? '' ),
				'regulator'      => (string) ( $parts[12] ?? '' ),
				'payments'       => toplist_clean_list( (string) ( $parts[13] ?? '' ) ),
				'games'          => toplist_clean_list( (string) ( $parts[14] ?? '' ) ),
				'liveGames'      => (string) ( $parts[15] ?? '' ),
				'smallPrint'     => (string) ( $parts[16] ?? '' ),
				'readReviewHref' => (string) ( $parts[17] ?? '' ),
				'readReviewText' => (string) ( $parts[18] ?? '' ),
				'withdrawals'    => toplist_clean_list( (string) ( $parts[19] ?? '' ) ),
				'geo'            => toplist_clean_text( (string) ( $parts[20] ?? '' ) ),
			);
		}

		$item['ctaText']        = is_string( $item['ctaText'] ) && '' !== trim( $item['ctaText'] ) ? trim( $item['ctaText'] ) : $default_cta_text;
		$item['readReviewText'] = is_string( $item['readReviewText'] ) && '' !== trim( $item['readReviewText'] ) ? trim( $item['readReviewText'] ) : $default_read_review_text;

		if ( toplist_item_has_content( $item ) ) {
			$items[] = $item;
		}
	}

	return array(
		'items'    => array_values( $items ),
		'includes' => array_values( array_unique( $includes ) ),
		'excludes' => array_values( array_unique( $excludes ) ),
	);
}

/**
 * Normalize external toplist JSON list fields (e.g. features, payments).
 *
 * @param mixed $value Raw value.
 * @return array<string>
 */
function toplist_external_json_string_list( $value ): array {
	if ( is_array( $value ) ) {
		return array_values( array_filter( array_map( 'toplist_clean_text', $value ) ) );
	}
	if ( is_string( $value ) ) {
		$t = trim( $value );
		return '' !== $t ? array( $t ) : array();
	}
	return array();
}

/**
 * Normalize games from external JSON (array or space-separated string).
 *
 * @param mixed $value Raw value.
 * @return array<string>
 */
function toplist_external_json_games_to_list( $value ): array {
	if ( is_array( $value ) ) {
		return array_values( array_filter( array_map( 'toplist_clean_text', $value ) ) );
	}
	if ( is_string( $value ) ) {
		$parts = preg_split( '/\s+/', trim( $value ) );
		return is_array( $parts ) ? array_values( array_filter( $parts ) ) : array();
	}
	return array();
}

/**
 * Normalize withdrawals from external JSON.
 *
 * @param mixed $value Raw value.
 * @return array<string>
 */
function toplist_external_json_withdrawals_list( $value ): array {
	if ( is_array( $value ) ) {
		return toplist_external_json_string_list( $value );
	}
	$s = toplist_clean_text( $value );
	if ( '' === $s ) {
		return array();
	}
	if ( false !== strpos( $s, ';' ) ) {
		return toplist_clean_list( $s );
	}
	return array( $s );
}

/**
 * Map one external JSON row (toplist.json shape) to an internal item.
 *
 * @param array<string, mixed> $row Decoded object.
 * @return array<string, mixed>|null Item or null if empty.
 */
function toplist_external_json_row_to_item( array $row ): ?array {
	if ( ! is_array( $row ) ) {
		return null;
	}

	$name       = toplist_clean_text( $row['name'] ?? '' );
	$visit      = toplist_clean_text( $row['visit_link'] ?? '' );
	$bonus_link = toplist_clean_text( $row['bonus_link'] ?? '' );
	$href       = '' !== $visit ? $visit : $bonus_link;

	$item = array(
		'operator'       => $name,
		'product'        => $name,
		'offer'          => toplist_clean_text( $row['bonus'] ?? '' ),
		'href'           => $href,
		'logo'           => toplist_clean_text( $row['image_url'] ?? '' ),
		'year'           => toplist_clean_text( $row['launched'] ?? '' ),
		'ctaText'        => '',
		'terms'          => '',
		'bullets'        => toplist_external_json_string_list( $row['features'] ?? array() ),
		'payout'         => toplist_clean_text( $row['payout_time'] ?? '' ),
		'code'           => toplist_clean_text( $row['code'] ?? '' ),
		'rating'         => isset( $row['rating'] ) && ( is_numeric( $row['rating'] ) || is_string( $row['rating'] ) )
			? toplist_clean_text( (string) $row['rating'] )
			: '',
		'regulator'      => toplist_clean_text( $row['regulator'] ?? '' ),
		'payments'       => toplist_external_json_string_list( $row['payments'] ?? array() ),
		'games'          => toplist_external_json_games_to_list( $row['games'] ?? '' ),
		'liveGames'      => toplist_clean_text( $row['live_games'] ?? '' ),
		'smallPrint'     => '',
		'readReviewHref' => toplist_clean_text( $row['review_link'] ?? '' ),
		'readReviewText' => '',
		'withdrawals'    => toplist_external_json_withdrawals_list( $row['withdrawals'] ?? '' ),
	);

	$default_cta            = 'Visit';
	$default_rr             = 'Read Review';
	$item['ctaText']        = '' !== trim( (string) $item['ctaText'] ) ? $item['ctaText'] : $default_cta;
	$item['readReviewText'] = '' !== trim( (string) $item['readReviewText'] ) ? $item['readReviewText'] : $default_rr;

	return toplist_item_has_content( $item ) ? $item : null;
}

/**
 * Decode external toplist.json body into internal items.
 *
 * @param string $json_string Raw JSON.
 * @return array<string, mixed>
 */
function toplist_decode_external_toplist_json( string $json_string ): array {
	$json_string = trim( $json_string );
	$json_string = preg_replace( '/^\xEF\xBB\xBF/', '', $json_string );
	if ( ! is_string( $json_string ) ) {
		$json_string = '';
	}
	if ( '' === $json_string ) {
		return array(
			'items' => array(),
			'error' => 'empty',
		);
	}

	$decoded = json_decode( $json_string, true );
	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
		return array(
			'items' => array(),
			'error' => 'invalid',
		);
	}

	$items = array();
	foreach ( $decoded as $row ) {
		$item = toplist_external_json_row_to_item( $row );
		if ( null !== $item ) {
			$items[] = $item;
		}
	}

	if ( empty( $items ) ) {
		return array(
			'items' => array(),
			'error' => 'empty',
		);
	}

	return array(
		'items' => $items,
		'error' => '',
	);
}

/**
 * Serialize internal items to pipe-delimited post content (no header row).
 *
 * @param array<array<string, mixed>> $items Normalized items.
 * @param array<string, mixed>        $defaults Optional default CTA / read-review labels.
 * @return string
 */
function toplist_items_to_pipe_content( array $items, array $defaults = array() ): string {
	$default_cta  = is_string( $defaults['defaultCtaText'] ?? 'Visit' ) ? trim( $defaults['defaultCtaText'] ?? 'Visit' ) : 'Visit';
	$default_read = is_string( $defaults['defaultReadReviewText'] ?? 'Read Review' ) ? trim( $defaults['defaultReadReviewText'] ?? 'Read Review' ) : 'Read Review';
	$default_cta  = '' !== $default_cta ? $default_cta : 'Visit';
	$default_read = '' !== $default_read ? $default_read : 'Read Review';

	$lines = array();
	foreach ( $items as $it ) {
		if ( ! is_array( $it ) ) {
			continue;
		}
		$parts   = array(
			toplist_clean_text( $it['operator'] ?? '' ),
			toplist_clean_text( $it['product'] ?? '' ),
			toplist_clean_text( $it['offer'] ?? '' ),
			toplist_clean_text( $it['href'] ?? '' ),
			toplist_clean_text( $it['logo'] ?? '' ),
			toplist_clean_text( $it['year'] ?? '' ),
			toplist_clean_text( $it['ctaText'] ?? '' ) !== '' ? toplist_clean_text( $it['ctaText'] ?? '' ) : $default_cta,
			toplist_clean_text( $it['terms'] ?? '' ),
			implode( ';', toplist_clean_list( $it['bullets'] ?? array() ) ),
			toplist_clean_text( $it['payout'] ?? '' ),
			toplist_clean_text( $it['code'] ?? '' ),
			toplist_clean_text( $it['rating'] ?? '' ),
			toplist_clean_text( $it['regulator'] ?? '' ),
			implode( ';', toplist_clean_list( $it['payments'] ?? array() ) ),
			implode( ';', toplist_clean_list( $it['games'] ?? array() ) ),
			toplist_clean_text( $it['liveGames'] ?? '' ),
			toplist_clean_text( $it['smallPrint'] ?? '' ),
			toplist_clean_text( $it['readReviewHref'] ?? '' ),
			toplist_clean_text( $it['readReviewText'] ?? '' ) !== '' ? toplist_clean_text( $it['readReviewText'] ?? '' ) : $default_read,
			implode( ';', toplist_clean_list( $it['withdrawals'] ?? array() ) ),
			toplist_clean_text( $it['geo'] ?? '' ),
		);
		$lines[] = implode( '|', $parts );
	}

	return implode( "\n", $lines );
}

/**
 * Build export rows matching repository toplist.json schema.
 *
 * @param array<array<string, mixed>> $items Normalized items.
 * @return array<array<string, mixed>>
 */
function toplist_items_to_external_json_rows( array $items ): array {
	$rows = array();
	$pos  = 1;

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$href        = toplist_clean_text( $item['href'] ?? '' );
		$games       = toplist_clean_list( $item['games'] ?? array() );
		$withdrawals = toplist_clean_list( $item['withdrawals'] ?? array() );
		$rating_raw  = toplist_clean_text( is_scalar( $item['rating'] ?? '' ) && is_string( $item['rating'] ?? '' ) ? $item['rating'] ?? '' : '' );
		$rating_out  = null;
		if ( '' !== $rating_raw ) {
			$rating_out = is_numeric( $rating_raw ) ? (float) $rating_raw : $rating_raw;
		}

		$review = toplist_clean_text( $item['readReviewHref'] ?? '' );

		$rows[] = array(
			'position'    => (string) $pos,
			'name'        => toplist_clean_text( $item['product'] ?? '' ) !== '' ? toplist_clean_text( $item['product'] ?? '' ) : toplist_clean_text( $item['operator'] ?? '' ),
			'rating'      => $rating_out,
			'launched'    => toplist_clean_text( $item['year'] ?? '' ),
			'regulator'   => toplist_clean_text( $item['regulator'] ?? '' ),
			'bonus'       => toplist_clean_text( $item['offer'] ?? '' ),
			'bonus_link'  => '' !== $href ? $href : null,
			'payout_time' => toplist_clean_text( $item['payout'] ?? '' ),
			'features'    => toplist_clean_list( $item['bullets'] ?? array() ),
			'games'       => implode( ' ', $games ),
			'live_games'  => toplist_clean_text( $item['liveGames'] ?? '' ),
			'withdrawals' => ! empty( $withdrawals ) ? implode( ' ', $withdrawals ) : '',
			'code'        => toplist_clean_text( $item['code'] ?? '' ),
			'image_url'   => toplist_clean_text( $item['logo'] ?? '' ),
			'visit_link'  => null,
			'review_link' => '' !== $review ? $review : null,
			'payments'    => toplist_clean_list( $item['payments'] ?? array() ),
		);
		++$pos;
	}

	return $rows;
}

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
