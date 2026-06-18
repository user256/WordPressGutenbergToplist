<?php
/**
 * Premium library: CSV/JSON export handlers and bulk template download.
 *
 * Extracted from import-export.php (ticket: library split).
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export one saved toplist as CSV.
 *
 * @return void
 */
function toplist_handle_export_csv() {
	$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to export this toplist.', 'toplist' ) );
	}
	check_admin_referer( 'toplist_export_csv_' . $post_id );

	$post = get_post( $post_id );
	if ( ! $post || 'toplist_list' !== $post->post_type ) {
		wp_die( esc_html__( 'Toplist not found.', 'toplist' ) );
	}

	$parsed = toplist_parse_lines_to_items( (string) $post->post_content, array() );
	$items  = is_array( $parsed['items'] ?? null ) ? $parsed['items'] : array();
	$fields = toplist_supported_fields();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=toplist-' . $post_id . '.csv' );

	$out = fopen( 'php://output', 'w' );
	if ( false !== $out ) {
		fputcsv( $out, $fields );

		foreach ( $items as $item ) {
			$row = array();
			foreach ( $fields as $field ) {
				$value = $item[ $field ] ?? '';
				if ( in_array( $field, array( 'bullets', 'payments', 'games', 'withdrawals' ), true ) ) {
					$value = is_array( $value ) ? implode( ';', $value ) : toplist_clean_text( is_scalar( $value ) ? (string) $value : '' );
				}
				$row[] = is_scalar( $value ) ? (string) $value : '';
			}
			fputcsv( $out, $row );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing an output stream handle created for CSV export.
	}
	exit;
}

/**
 * Export one saved toplist as JSON (toplist.json schema).
 *
 * @return void
 */
function toplist_handle_export_json() {
	$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to export this toplist.', 'toplist' ) );
	}
	check_admin_referer( 'toplist_export_json_' . $post_id );

	$post = get_post( $post_id );
	if ( ! $post || 'toplist_list' !== $post->post_type ) {
		wp_die( esc_html__( 'Toplist not found.', 'toplist' ) );
	}

	$parsed = toplist_parse_lines_to_items( (string) $post->post_content, array() );
	$items  = is_array( $parsed['items'] ?? null ) ? $parsed['items'] : array();
	$rows   = toplist_items_to_external_json_rows( $items );

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=toplist-' . $post_id . '.json' );

	echo wp_json_encode( $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}

/**
 * Export all saved toplists as a single CSV.
 *
 * @return void
 */
function toplist_handle_export_all_csv() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to export toplists.', 'toplist' ) );
	}
	check_admin_referer( 'toplist_export_all_csv' );

	$posts = get_posts(
		array(
			'post_type'      => 'toplist_list',
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	$fields      = toplist_supported_fields();
	$csv_headers = array_merge( array( 'toplist', 'toplist_id' ), $fields );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=toplists-all.csv' );

	$out = fopen( 'php://output', 'w' );
	if ( false !== $out ) {
		fputcsv( $out, $csv_headers );

		foreach ( $posts as $post ) {
			if ( ! ( $post instanceof WP_Post ) ) {
				continue;
			}

			$parsed = toplist_parse_lines_to_items( (string) $post->post_content, array() );
			$items  = is_array( $parsed['items'] ?? null ) ? $parsed['items'] : array();

			foreach ( $items as $item ) {
				$row = array( (string) $post->post_title, (string) $post->ID );
				foreach ( $fields as $field ) {
					$value = $item[ $field ] ?? '';
					if ( in_array( $field, array( 'bullets', 'payments', 'games', 'withdrawals' ), true ) ) {
						$value = is_array( $value ) ? implode( ';', $value ) : toplist_clean_text( is_scalar( $value ) ? (string) $value : '' );
					}
					$row[] = is_scalar( $value ) ? (string) $value : '';
				}
				fputcsv( $out, $row );
			}
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing an output stream handle created for CSV export.
	}
	exit;
}

/**
 * Download a CSV template for bulk import.
 *
 * @return void
 */
function toplist_handle_export_bulk_template_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to export the bulk template.', 'toplist' ) );
	}
	check_admin_referer( 'toplist_export_bulk_template_csv' );

	$fields  = toplist_supported_fields();
	$headers = array_merge( array( 'toplist', 'toplist_id' ), $fields );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=toplist-bulk-import-template.csv' );

	$out = fopen( 'php://output', 'w' );
	if ( false !== $out ) {
		fputcsv( $out, $headers );

		$sample     = array_fill( 0, count( $headers ), '' );
		$sample_map = array(
			'toplist'        => 'Example Toplist',
			'operator'       => 'Operator Name',
			'product'        => 'Casino Brand',
			'offer'          => '100% Bonus + 50 Free Spins',
			'href'           => 'https://example.com',
			'logo'           => 'https://example.com/logo.png',
			'year'           => '2026',
			'ctaText'        => 'Visit Casino',
			'terms'          => '18+ T&Cs apply',
			'bullets'        => 'Fast payouts;Welcome bonus',
			'payout'         => 'Instant',
			'code'           => 'WELCOME50',
			'rating'         => '4.8',
			'regulator'      => 'MGA',
			'payments'       => 'Visa;Mastercard;PayPal',
			'games'          => 'Slots;Live Casino',
			'liveGames'      => 'Yes',
			'smallPrint'     => 'Wagering requirements apply',
			'readReviewHref' => 'https://example.com/review',
			'readReviewText' => 'Read Review',
			'withdrawals'    => 'Bank Transfer;Skrill',
		);

		foreach ( $headers as $i => $header ) {
			$sample[ $i ] = (string) ( $sample_map[ $header ] ?? '' );
		}
		fputcsv( $out, $sample );
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing an output stream handle created for CSV export.
	}
	exit;
}
