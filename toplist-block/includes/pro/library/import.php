<?php
/**
 * Premium library: CSV/JSON import handlers, upload validation, status notices.
 *
 * Extracted from import-export.php (ticket: library split).
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize CSV header to canonical field key.
 *
 * @param string $value Header cell.
 * @return string
 */
function toplist_normalize_csv_header( $value ) {
	$normalized = strtolower( trim( (string) $value ) );
	$normalized = preg_replace( '/^\xEF\xBB\xBF/u', '', $normalized );
	$lookup     = array();
	foreach ( toplist_supported_fields() as $field ) {
		$lookup[ strtolower( $field ) ] = $field;
	}
	return $lookup[ $normalized ] ?? '';
}

/**
 * Whether an upload filename matches the expected extension and MIME map.
 *
 * @param string $filename Original upload name.
 * @param string $extension Expected extension without dot (json|csv).
 * @return bool
 */
function toplist_upload_filename_matches_extension( string $filename, string $extension ): bool {
	$extension = strtolower( $extension );
	if ( '' === $filename || ! in_array( $extension, array( 'json', 'csv' ), true ) ) {
		return false;
	}

	$ext = strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) );
	if ( $ext !== $extension ) {
		return false;
	}

	$mimes   = 'json' === $extension
		? array( 'json' => 'application/json' )
		: array(
			'csv' => 'text/csv',
			'txt' => 'text/plain',
		);
	$checked = wp_check_filetype( $filename, $mimes );
	if ( ! empty( $checked['ext'] ) && $checked['ext'] === $ext ) {
		return true;
	}

	// Excel and some hosts label CSV uploads as text/plain.
	if ( 'csv' === $extension ) {
		$plain = wp_check_filetype( $filename, array( 'csv' => 'text/plain' ) );
		return ! empty( $plain['ext'] ) && 'csv' === $plain['ext'];
	}

	return false;
}

/**
 * Read sanitized upload metadata for import handlers after caller nonce checks.
 *
 * @param string $files_key $_FILES key.
 * @return array{name:string,tmp_name:string,error:int}
 */
function toplist_get_uploaded_import_file( string $files_key ): array {
	if ( '' === $files_key ) {
		return array(
			'name'     => '',
			'tmp_name' => '',
			'error'    => UPLOAD_ERR_NO_FILE,
		);
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Callers verify nonces before reading upload metadata.
	if ( ! isset( $_FILES[ $files_key ] ) || ! is_array( $_FILES[ $files_key ] ) ) {
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return array(
			'name'     => '',
			'tmp_name' => '',
			'error'    => UPLOAD_ERR_NO_FILE,
		);
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Callers verify nonces before reading upload metadata.
	$file = $_FILES[ $files_key ];
	// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	return array(
		'name'     => isset( $file['name'] ) ? sanitize_file_name( (string) wp_unslash( $file['name'] ) ) : '',
		'tmp_name' => isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '',
		'error'    => isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE,
	);
}

/**
 * Validate a $_FILES upload entry for import handlers.
 *
 * @param string $files_key $_FILES key.
 * @param string $extension Expected extension without dot (json|csv).
 * @return string Empty when valid; otherwise error code for redirects.
 */
function toplist_validate_uploaded_import_file( $files_key, $extension ) {
	$file = toplist_get_uploaded_import_file( $files_key );
	if ( UPLOAD_ERR_NO_FILE === $file['error'] && '' === $file['tmp_name'] && '' === $file['name'] ) {
		return 'empty';
	}

	$tmp = $file['tmp_name'];
	if ( '' === $tmp || ! is_uploaded_file( $tmp ) ) {
		return 'empty';
	}

	$error = $file['error'];
	if ( UPLOAD_ERR_INI_SIZE === $error || UPLOAD_ERR_FORM_SIZE === $error ) {
		return 'upload_too_large';
	}
	if ( UPLOAD_ERR_OK !== $error ) {
		return 'failed';
	}

	$name = $file['name'];
	if ( ! toplist_upload_filename_matches_extension( $name, $extension ) ) {
		return 'invalid_type';
	}

	return '';
}

/**
 * Import JSON (toplist.json schema) into one saved toplist.
 *
 * @return void
 */
function toplist_handle_import_json() {
	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to import into this toplist.', 'toplist' ) );
	}
	if ( ! isset( $_POST['toplist_import_json_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['toplist_import_json_nonce'] ) ), 'toplist_import_json_' . $post_id ) ) {
		wp_die( esc_html__( 'Invalid JSON import request.', 'toplist' ) );
	}
	$upload_status = toplist_validate_uploaded_import_file( 'toplist_json_file', 'json' );
	if ( '' !== $upload_status ) {
		wp_safe_redirect( add_query_arg( 'toplist_json_import', $upload_status, get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$upload = toplist_get_uploaded_import_file( 'toplist_json_file' );
	$tmp    = $upload['tmp_name'];
	$raw    = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a verified local upload temp file, not a remote URL.
	if ( false === $raw ) {
		wp_safe_redirect( add_query_arg( 'toplist_json_import', 'failed', get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$result = toplist_decode_external_toplist_json( $raw );
	if ( 'invalid' === $result['error'] ) {
		wp_safe_redirect( add_query_arg( 'toplist_json_import', 'invalid', get_edit_post_link( $post_id, '' ) ) );
		exit;
	}
	if ( 'empty' === $result['error'] ) {
		wp_safe_redirect( add_query_arg( 'toplist_json_import', 'empty', get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$items       = is_array( $result['items'] ?? null ) ? $result['items'] : array();
	$new_content = toplist_items_to_pipe_content( $items, array() );
	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $new_content,
		)
	);

	wp_safe_redirect( add_query_arg( 'toplist_json_import', 'success', get_edit_post_link( $post_id, '' ) ) );
	exit;
}

/**
 * Parse a bulk CSV file and create/update multiple toplists.
 *
 * @param string $file Path to uploaded CSV temporary file.
 * @return array{status:string,updated:int,created:int,rows:int,groups:int}
 */
function toplist_process_bulk_import_csv_file( string $file ): array {
	if ( '' === $file ) {
		return array(
			'status'  => 'empty',
			'updated' => 0,
			'created' => 0,
			'rows'    => 0,
			'groups'  => 0,
		);
	}

	$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reading a verified local CSV upload temp file.
	if ( ! $handle ) {
		return array(
			'status'  => 'failed',
			'updated' => 0,
			'created' => 0,
			'rows'    => 0,
			'groups'  => 0,
		);
	}

	$header_row = fgetcsv( $handle );
	if ( ! is_array( $header_row ) || empty( $header_row ) ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a local CSV upload handle.
		return array(
			'status'  => 'empty',
			'updated' => 0,
			'created' => 0,
			'rows'    => 0,
			'groups'  => 0,
		);
	}

	$name_index    = -1;
	$id_index      = -1;
	$field_indexes = array();
	$header_fields = array();
	foreach ( $header_row as $i => $cell ) {
		$raw = strtolower( trim( (string) $cell ) );
		$raw = preg_replace( '/^\xEF\xBB\xBF/u', '', $raw );
		if ( in_array( $raw, array( 'toplist', 'toplist_name', 'name', 'list' ), true ) ) {
			$name_index = (int) $i;
			continue;
		}
		if ( in_array( $raw, array( 'toplist_id', 'list_id', 'id' ), true ) ) {
			$id_index = (int) $i;
			continue;
		}
		$field = toplist_normalize_csv_header( (string) $cell );
		if ( '' === $field ) {
			continue;
		}
		$field_indexes[ (int) $i ] = $field;
		if ( ! in_array( $field, $header_fields, true ) ) {
			$header_fields[] = $field;
		}
	}

	if ( ( $name_index < 0 && $id_index < 0 ) || empty( $header_fields ) ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a local CSV upload handle.
		return array(
			'status'  => 'bad_header',
			'updated' => 0,
			'created' => 0,
			'rows'    => 0,
			'groups'  => 0,
		);
	}

	$groups    = array();
	$rows_read = 0;
	$csv_row   = fgetcsv( $handle );
	while ( false !== $csv_row ) {
		++$rows_read;
		$name    = $name_index >= 0 ? trim( (string) ( $csv_row[ $name_index ] ?? '' ) ) : '';
		$raw_id  = $id_index >= 0 ? trim( (string) ( $csv_row[ $id_index ] ?? '' ) ) : '';
		$post_id = '' !== $raw_id ? (int) $raw_id : 0;

		// Fallback for exports where name/id headers are present but not recognized as expected.
		if ( '' === $name ) {
			$fallback_name = trim( (string) ( $csv_row[0] ?? '' ) );
			if ( '' !== $fallback_name && ! is_numeric( $fallback_name ) ) {
				$name = $fallback_name;
			}
		}
		if ( $post_id <= 0 && '' === $raw_id ) {
			$fallback_id = trim( (string) ( $csv_row[1] ?? '' ) );
			if ( '' !== $fallback_id && ctype_digit( $fallback_id ) ) {
				$post_id = (int) $fallback_id;
			}
		}

		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			if ( ! $post || 'toplist_list' !== $post->post_type ) {
				$post_id = 0;
			}
		}

		if ( $post_id <= 0 && '' === $name ) {
			continue;
		}

		$key = $post_id > 0 ? 'id:' . $post_id : 'name:' . strtolower( $name );
		if ( ! isset( $groups[ $key ] ) ) {
			$groups[ $key ] = array(
				'post_id' => $post_id,
				'name'    => $name,
				'rows'    => array(),
			);
		}

		$row_values = array();
		foreach ( $field_indexes as $index => $field ) {
			$row_values[ $field ] = trim( (string) ( $csv_row[ $index ] ?? '' ) );
		}

		$line_parts = array();
		foreach ( $header_fields as $field ) {
			$line_parts[] = $row_values[ $field ] ?? '';
		}
		$line = implode( '|', $line_parts );
		if ( trim( $line ) !== '' ) {
			$groups[ $key ]['rows'][] = $line;
		}
		$csv_row = fgetcsv( $handle );
	}
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a local CSV upload handle.

	if ( empty( $groups ) ) {
		return array(
			'status'  => 'empty',
			'updated' => 0,
			'created' => 0,
			'rows'    => $rows_read,
			'groups'  => 0,
		);
	}

	$updated = 0;
	$created = 0;
	foreach ( $groups as $group ) {
		if ( empty( $group['rows'] ) ) {
			continue;
		}

		$lines   = array( implode( '|', $header_fields ) );
		$lines   = array_merge( $lines, $group['rows'] );
		$content = implode( "\n", $lines );

		$post_id = (int) $group['post_id'];
		$name    = trim( (string) $group['name'] );

		if ( $post_id > 0 ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $content,
				)
			);
			++$updated;
			continue;
		}

		$existing = array();
		if ( '' !== $name ) {
			$existing = get_posts(
				array(
					'post_type'      => 'toplist_list',
					'post_status'    => array( 'publish', 'private', 'draft' ),
					'title'          => $name,
					'posts_per_page' => 1,
					'no_found_rows'  => true,
				)
			);
		}

		if ( ! empty( $existing ) && $existing[0] instanceof WP_Post ) {
			wp_update_post(
				array(
					'ID'           => (int) $existing[0]->ID,
					'post_content' => $content,
				)
			);
			++$updated;
			continue;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'toplist_list',
				'post_title'   => '' !== $name ? $name : __( 'Imported Toplist', 'toplist' ),
				'post_content' => $content,
				'post_status'  => 'publish',
			),
			true
		);

		if ( ! is_wp_error( $new_id ) && is_int( $new_id ) ) {
			++$created;
		}
	}

	return array(
		'status'  => ( $updated + $created ) > 0 ? 'success' : 'no_changes',
		'updated' => $updated,
		'created' => $created,
		'rows'    => $rows_read,
		'groups'  => count( $groups ),
	);
}

/**
 * Import a single CSV and create/update multiple toplists.
 *
 * Expected CSV columns:
 * - `toplist` (or `name`) and/or `toplist_id`
 * - standard toplist fields (operator, product, offer, etc.)
 *
 * @return void
 */
function toplist_handle_import_all_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to bulk import toplists.', 'toplist' ) );
	}

	check_admin_referer( 'toplist_import_all_csv', 'toplist_import_all_csv_nonce' );

	$redirect_url  = admin_url( 'options-general.php?page=toplist-settings' );
	$upload_status = toplist_validate_uploaded_import_file( 'toplist_bulk_csv_file', 'csv' );
	$upload        = toplist_get_uploaded_import_file( 'toplist_bulk_csv_file' );
	if ( '' !== $upload_status ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'toplist_bulk_import'       => $upload_status,
					'toplist_bulk_updated'      => 0,
					'toplist_bulk_created'      => 0,
					'toplist_bulk_rows'         => 0,
					'toplist_bulk_groups'       => 0,
					'toplist_bulk_upload_error' => $upload['error'],
				),
				$redirect_url
			)
		);
		exit;
	}

	$result       = toplist_process_bulk_import_csv_file( $upload['tmp_name'] );
	$upload_error = $upload['error'];
	wp_safe_redirect(
		add_query_arg(
			array(
				'toplist_bulk_import'       => $result['status'],
				'toplist_bulk_updated'      => (int) $result['updated'],
				'toplist_bulk_created'      => (int) $result['created'],
				'toplist_bulk_rows'         => (int) $result['rows'],
				'toplist_bulk_groups'       => (int) $result['groups'],
				'toplist_bulk_upload_error' => $upload_error,
			),
			$redirect_url
		)
	);
	exit;
}

/**
 * Import CSV into one saved toplist.
 *
 * @return void
 */
function toplist_handle_import_csv() {
	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to import into this toplist.', 'toplist' ) );
	}
	if ( ! isset( $_POST['toplist_import_csv_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['toplist_import_csv_nonce'] ) ), 'toplist_import_csv_' . $post_id ) ) {
		wp_die( esc_html__( 'Invalid CSV import request.', 'toplist' ) );
	}
	$upload_status = toplist_validate_uploaded_import_file( 'toplist_csv_file', 'csv' );
	if ( '' !== $upload_status ) {
		wp_safe_redirect( add_query_arg( 'toplist_import', $upload_status, get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$upload = toplist_get_uploaded_import_file( 'toplist_csv_file' );
	$file   = $upload['tmp_name'];
	$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reading a verified local CSV upload temp file.
	if ( ! $handle ) {
		wp_safe_redirect( add_query_arg( 'toplist_import', 'failed', get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$header_row = fgetcsv( $handle );
	if ( ! is_array( $header_row ) ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a local CSV upload handle.
		wp_safe_redirect( add_query_arg( 'toplist_import', 'empty', get_edit_post_link( $post_id, '' ) ) );
		exit;
	}

	$normalized_headers = array();
	foreach ( $header_row as $header_cell ) {
		$normalized_headers[] = toplist_normalize_csv_header( $header_cell );
	}

	$recognized_headers = array_values( array_filter( $normalized_headers ) );
	$use_index_mapping  = count( $recognized_headers ) < 3;
	$fields             = toplist_supported_fields();
	$columns            = $use_index_mapping ? $fields : $normalized_headers;
	$header_for_lines   = $use_index_mapping ? $fields : array_values( array_filter( $normalized_headers ) );

	$lines = array();
	if ( ! empty( $header_for_lines ) ) {
		$lines[] = implode( '|', $header_for_lines );
	}

	$csv_row = fgetcsv( $handle );
	while ( false !== $csv_row ) {
		$values_by_field = array();
		foreach ( $fields as $field ) {
			$values_by_field[ $field ] = '';
		}

		$i_count = count( $columns );
		for ( $i = 0; $i < $i_count; ++$i ) {
			$field = $columns[ $i ] ?? '';
			if ( '' === $field || ! in_array( $field, $fields, true ) ) {
				continue;
			}
			$values_by_field[ $field ] = trim( (string) ( $csv_row[ $i ] ?? '' ) );
		}

		$line_parts = array();
		foreach ( $header_for_lines as $field ) {
			$line_parts[] = $values_by_field[ $field ] ?? '';
		}
		$line = implode( '|', $line_parts );
		if ( trim( $line ) !== '' ) {
			$lines[] = $line;
		}
		$csv_row = fgetcsv( $handle );
	}
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a local CSV upload handle.

	$new_content = implode( "\n", $lines );
	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $new_content,
		)
	);

	wp_safe_redirect( add_query_arg( 'toplist_import', 'success', get_edit_post_link( $post_id, '' ) ) );
	exit;
}

/**
 * Show import status notices on toplist edit screen.
 *
 * @return void
 */
function toplist_import_admin_notice() {
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->base || 'toplist_list' !== $screen->post_type ) {
		return;
	}
	$status_param = filter_input( INPUT_GET, 'toplist_import', FILTER_UNSAFE_RAW );
	$status       = is_string( $status_param ) ? sanitize_key( $status_param ) : '';

	if ( '' !== $status ) {
		$message = '';
		$class   = 'notice notice-info';
		if ( 'success' === $status ) {
			$message = __( 'CSV imported successfully.', 'toplist' );
			$class   = 'notice notice-success';
		} elseif ( 'empty' === $status ) {
			$message = __( 'CSV import failed: file was empty or invalid.', 'toplist' );
			$class   = 'notice notice-warning';
		} elseif ( 'failed' === $status ) {
			$message = __( 'CSV import failed: unable to read the uploaded file.', 'toplist' );
			$class   = 'notice notice-error';
		} elseif ( 'invalid_type' === $status ) {
			$message = __( 'CSV import failed: upload must be a .csv file.', 'toplist' );
			$class   = 'notice notice-error';
		} elseif ( 'upload_too_large' === $status ) {
			$message = __( 'CSV import failed: uploaded file is too large for current PHP upload limits.', 'toplist' );
			$class   = 'notice notice-error';
		}

		if ( '' !== $message ) {
			echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	$json_status_param = filter_input( INPUT_GET, 'toplist_json_import', FILTER_UNSAFE_RAW );
	$json_status       = is_string( $json_status_param ) ? sanitize_key( $json_status_param ) : '';
	if ( '' !== $json_status ) {
		$json_message = '';
		$json_class   = 'notice notice-info';
		if ( 'success' === $json_status ) {
			$json_message = __( 'JSON imported successfully.', 'toplist' );
			$json_class   = 'notice notice-success';
		} elseif ( 'empty' === $json_status ) {
			$json_message = __( 'JSON import failed: file was empty or no valid rows.', 'toplist' );
			$json_class   = 'notice notice-warning';
		} elseif ( 'failed' === $json_status ) {
			$json_message = __( 'JSON import failed: unable to read the uploaded file.', 'toplist' );
			$json_class   = 'notice notice-error';
		} elseif ( 'invalid' === $json_status ) {
			$json_message = __( 'JSON import failed: invalid JSON (expected a JSON array).', 'toplist' );
			$json_class   = 'notice notice-error';
		} elseif ( 'invalid_type' === $json_status ) {
			$json_message = __( 'JSON import failed: upload must be a .json file.', 'toplist' );
			$json_class   = 'notice notice-error';
		} elseif ( 'upload_too_large' === $json_status ) {
			$json_message = __( 'JSON import failed: uploaded file is too large for current PHP upload limits.', 'toplist' );
			$json_class   = 'notice notice-error';
		}

		if ( '' !== $json_message ) {
			echo '<div class="' . esc_attr( $json_class ) . '"><p>' . esc_html( $json_message ) . '</p></div>';
		}
	}
}
