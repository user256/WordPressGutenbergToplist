<?php
/**
 * Core parsing and normalization layer (shared by lite and premium).
 *
 * Pure data functions with no WordPress hooks: pipe/CSV parsing, directive and
 * header detection, external-JSON mapping, and pipe/JSON serialization.
 * Extracted from toplist-block.php (ticket: entry-file split).
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
