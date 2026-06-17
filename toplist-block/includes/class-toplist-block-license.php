<?php
/**
 * Remote license validation and premium feature gating.
 *
 * Premium-only: excluded from the lite build.
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates portal license keys and caches signed entitlement state.
 */
class Toplist_Block_License {

	const OPTION_KEY     = 'toplist_block_license_key';
	const OPTION_API_URL = 'toplist_block_license_api_url';
	const OPTION_API_KEY = 'toplist_block_license_api_key';
	const CACHE_OPTION   = 'toplist_block_license_cache_signed';
	const CRON_HOOK      = 'toplist_block_license_recheck';
	const PRODUCT_SLUG   = 'toplist-block-pro';
	const RETRY_HOURS    = 6;

		/**
		 * Whether the cached trust state has been verified.
		 *
		 * @var bool|null
		 */
	private static $cache_trusted = null;

	/**
	 * Licensing API URL (portal licence-manager validate or core /api/v1/validate).
	 *
	 * @return string
	 */
	public static function api_url(): string {
		$from_const = Toplist_Block_Util::configured_constant( 'TOPLIST_BLOCK_LICENSE_API_URL' );
		if ( '' !== $from_const ) {
			return $from_const;
		}
		$stored = self::get_stored_api_url();
		if ( '' !== $stored ) {
			return $stored;
		}
		$filtered = apply_filters( 'toplist_block_license_api_url', '' );
		return is_string( $filtered ) ? $filtered : '';
	}

	/**
	 * Optional Bearer / X-Portal-Module-Key for module dispatch routes.
	 *
	 * @return string
	 */
	public static function api_key(): string {
		$from_const = Toplist_Block_Util::configured_constant( 'TOPLIST_BLOCK_LICENSE_API_KEY' );
		if ( '' !== $from_const ) {
			return $from_const;
		}
		$stored = self::get_stored_api_key();
		if ( '' !== $stored ) {
			return $stored;
		}
		$filtered = apply_filters( 'toplist_block_license_api_key', '' );
		return is_string( $filtered ) ? $filtered : '';
	}

	/**
	 * Portal validate endpoint saved in plugin settings (not wp-config).
	 *
	 * @return string
	 */
	public static function get_stored_api_url(): string {
		$stored = get_option( self::OPTION_API_URL, '' );
		return is_string( $stored ) ? $stored : '';
	}

	/**
	 * Module API key saved in plugin settings (not wp-config).
	 *
	 * @return string
	 */
	public static function get_stored_api_key(): string {
		$stored = get_option( self::OPTION_API_KEY, '' );
		return self::decrypt_secret( is_string( $stored ) ? $stored : '' );
	}

	/**
	 * Whether API URL/key come from wp-config constants (read-only in admin).
	 *
	 * @return bool
	 */
	public static function api_config_locked_by_constant(): bool {
		return Toplist_Block_Util::configured_constant( 'TOPLIST_BLOCK_LICENSE_API_URL' ) !== ''
			|| Toplist_Block_Util::configured_constant( 'TOPLIST_BLOCK_LICENSE_API_KEY' ) !== '';
	}

	/**
	 * Short description.
	 *
	 * @param string $url     Validate endpoint URL.
	 * @param string $api_key Module API key; empty keeps existing stored key.
	 * @return void
	 */
	public static function save_api_settings( string $url, string $api_key ): void {
		if ( self::api_config_locked_by_constant() ) {
			return;
		}
		$url = esc_url_raw( trim( $url ) );
		update_option( self::OPTION_API_URL, $url, false );
		if ( '' !== $api_key ) {
			update_option( self::OPTION_API_KEY, self::encrypt_secret( sanitize_text_field( trim( $api_key ) ) ), false );
		}
		delete_site_transient( 'toplist_block_update_info' );
	}

	/**
	 * OTA update-check endpoint (defaults from validate URL).
	 *
	 * @return string
	 */
	public static function update_check_api_url(): string {
		$from_const = Toplist_Block_Util::configured_constant( 'TOPLIST_BLOCK_UPDATE_API_URL' );
		if ( '' !== $from_const ) {
			return $from_const;
		}
		$filtered = apply_filters( 'toplist_block_update_api_url', '' );
		if ( is_string( $filtered ) && '' !== $filtered ) {
			return $filtered;
		}
		$validate = self::api_url();
		if ( '' === $validate ) {
			return '';
		}
		if ( preg_match( '#/validate/?$#i', $validate ) ) {
			return (string) preg_replace( '#/validate/?$#i', '/update-check', $validate );
		}
		return rtrim( $validate, '/' ) . '/update-check';
	}

	/**
	 * Short description.
	 *
	 * @return array<string, string>
	 */
	public static function api_request_headers(): array {
		$headers = array( 'Content-Type' => 'application/json; charset=utf-8' );
		$api_key = self::api_key();
		if ( '' !== $api_key ) {
			$headers['Authorization'] = 'Bearer ' . $api_key;
		}
		return $headers;
	}

	/**
	 * Short description.
	 *
	 * @return string
	 */
	public static function get_key(): string {
		$stored = get_option( self::OPTION_KEY, '' );
		return self::decrypt_secret( is_string( $stored ) ? $stored : '' );
	}

	/**
	 * Short description.
	 *
	 * @param string $key License key.
	 * @return void
	 */
	public static function save_key( string $key ): void {
		update_option( self::OPTION_KEY, self::encrypt_secret( sanitize_text_field( trim( $key ) ) ) );
	}

	/**
	 * Short description.
	 *
	 * @param string $plain Plain secret.
	 * @return string
	 */
	private static function encrypt_secret( string $plain ): string {
		if ( '' === $plain ) {
			return '';
		}
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return base64_encode( $plain ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}
		$iv = openssl_random_pseudo_bytes( 16 );
		if ( ! is_string( $iv ) || '' === $iv ) {
			return base64_encode( $plain ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}
		$encrypted = openssl_encrypt( $plain, 'AES-256-CBC', hash( 'sha256', wp_salt( 'auth' ), true ), OPENSSL_RAW_DATA, $iv );
		if ( ! is_string( $encrypted ) ) {
			return base64_encode( $plain ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}
		return base64_encode( $iv . $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Short description.
	 *
	 * @param string $packed Packed secret.
	 * @return string
	 */
	private static function decrypt_secret( string $packed ): string {
		if ( '' === $packed ) {
			return '';
		}
		$raw = base64_decode( $packed, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $raw ) {
			return '';
		}
		if ( ! function_exists( 'openssl_decrypt' ) || strlen( $raw ) <= 16 ) {
			return $raw;
		}
		$iv         = substr( $raw, 0, 16 );
		$ciphertext = substr( $raw, 16 );
		$plain      = openssl_decrypt( $ciphertext, 'AES-256-CBC', hash( 'sha256', wp_salt( 'auth' ), true ), OPENSSL_RAW_DATA, $iv );
		return is_string( $plain ) ? $plain : '';
	}

	/**
	 * Short description.
	 *
	 * @return string
	 */
	public static function get_cache_path(): string {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['basedir'] ) . 'toplist-block/license-cache.json';
	}

	/**
	 * Short description.
	 *
	 * @param string $payload Payload JSON.
	 * @return string
	 */
	private static function sign_payload( string $payload ): string {
		return hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
	}

	/**
	 * Short description.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_cache(): array {
		$option = get_option( self::CACHE_OPTION, false );
		if ( is_array( $option ) && isset( $option['payload'], $option['sig'] ) ) {
			$payload = Toplist_Block_Util::as_string( $option['payload'] );
			$sig     = Toplist_Block_Util::as_string( $option['sig'] );
			if ( hash_equals( self::sign_payload( $payload ), $sig ) ) {
				$data                = json_decode( $payload, true );
				self::$cache_trusted = true;
				return is_array( $data ) ? $data : array();
			}
		}

		self::$cache_trusted = false;
		$path                = self::get_cache_path();
		if ( ! file_exists( $path ) ) {
			return array();
		}
		$raw = @file_get_contents( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw ) {
			return array();
		}
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Short description.
	 *
	 * @return bool
	 */
	public static function is_cache_trusted(): bool {
		if ( null === self::$cache_trusted ) {
			self::get_cache();
		}
		return true === self::$cache_trusted;
	}

	/**
	 * Short description.
	 *
	 * @param array<string, mixed> $data Cache payload.
	 * @return void
	 */
	private static function save_cache( array $data ): void {
		$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return;
		}

		update_option(
			self::CACHE_OPTION,
			array(
				'payload' => $json,
				'sig'     => self::sign_payload( $json ),
			),
			false
		);
		self::$cache_trusted = true;

		$path = self::get_cache_path();
		$dir  = dirname( $path );
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$pretty = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( false !== $pretty ) {
			file_put_contents( $path, $pretty ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Persisting a local cache file alongside the signed option cache.
		}
	}

	/**
	 * Short description.
	 *
	 * @return void
	 */
	private static function clear_cache(): void {
		delete_option( self::CACHE_OPTION );
		self::$cache_trusted = null;
		$path                = self::get_cache_path();
		if ( file_exists( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * Short description.
	 *
	 * @param array<string, mixed> $cache Cache.
	 * @return void
	 */
	private static function maybe_kick_stale_recheck( array $cache ): void {
		static $kicked = false;
		if ( $kicked ) {
			return;
		}
		$kicked = true;

		$recheck_after = $cache['recheck_after'] ?? null;
		if ( ! is_string( $recheck_after ) || '' === $recheck_after ) {
			return;
		}
		$due = strtotime( $recheck_after );
		if ( false === $due || $due >= time() ) {
			return;
		}

		$next = wp_next_scheduled( self::CRON_HOOK );
		if ( $next && $next <= time() + 60 ) {
			return;
		}

		wp_schedule_single_event( time(), self::CRON_HOOK );
	}

	/**
	 * Short description.
	 *
	 * @param array<string, mixed> $cache Cache.
	 * @return int
	 */
	private static function grace_seconds( array $cache ): int {
		$grace_hours = $cache['grace_hours'] ?? null;
		if ( is_numeric( $grace_hours ) && (int) $grace_hours > 0 ) {
			return (int) $grace_hours * HOUR_IN_SECONDS;
		}

		$billing = strtolower( trim( Toplist_Block_Util::array_string( $cache, 'billing_period' ) ) );
		if ( 'monthly' === $billing ) {
			return 3 * DAY_IN_SECONDS;
		}
		if ( 'yearly' === $billing || 'lifetime' === $billing ) {
			return 14 * DAY_IN_SECONDS;
		}

		return 7 * DAY_IN_SECONDS;
	}

	/**
	 * Whether premium library/import features may run.
	 *
	 * @return bool
	 */
	public static function is_valid(): bool {
		if ( '' === self::get_key() ) {
			return false;
		}

		$cache = self::get_cache();

		if ( ! self::is_cache_trusted() ) {
			if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_single_event( time(), self::CRON_HOOK );
			}
			return false;
		}

		self::maybe_kick_stale_recheck( $cache );

		$status = Toplist_Block_Util::array_string( $cache, 'status' );

		if ( in_array( $status, array( 'active', 'grace' ), true ) ) {
			$expires = $cache['expires_at'] ?? null;
			if ( null !== $expires && '' !== $expires ) {
				$t = strtotime( Toplist_Block_Util::as_string( $expires ) );
				if ( false !== $t && $t < time() ) {
					return false;
				}
			}

			$allowed = self::get_allowed_domains();
			if ( array() !== $allowed ) {
				$current = self::current_install_domain();
				if ( ! in_array( $current, $allowed, true ) ) {
					if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
						wp_schedule_single_event( time(), self::CRON_HOOK );
					}
					return false;
				}
			}

			return true;
		}

		if ( 'revoked' === $status ) {
			return false;
		}

		if ( 'unreachable' === $status ) {
			$last_valid = Toplist_Block_Util::array_string( $cache, 'last_valid_at' );
			if ( '' !== $last_valid ) {
				$lv = strtotime( $last_valid );
				if ( false !== $lv && ( time() - $lv ) < self::grace_seconds( $cache ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Short description.
	 *
	 * @return string
	 */
	public static function current_install_domain(): string {
		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$host = is_string( $host ) ? $host : '';
		return Toplist_Block_Util::normalize_domain( $host );
	}

	/**
	 * Short description.
	 *
	 * @return list<string>
	 */
	public static function get_allowed_domains(): array {
		$c = self::get_cache();
		if ( isset( $c['allowed_domains'] ) && is_array( $c['allowed_domains'] ) ) {
			return array_values( array_map( 'strval', $c['allowed_domains'] ) );
		}
		$primary = Toplist_Block_Util::array_string( $c, 'primary_domain' );
		$extras  = isset( $c['extra_domains'] ) && is_array( $c['extra_domains'] )
			? array_values( array_map( 'strval', $c['extra_domains'] ) )
			: array();
		if ( '' === $primary && array() === $extras ) {
			return array();
		}
		return array_values( array_unique( array_merge( '' !== $primary ? array( $primary ) : array(), $extras ) ) );
	}

	/**
	 * Save key, validate against portal, schedule recheck.
	 *
	 * @param string $key License key.
	 * @return array<string, mixed>
	 */
	public static function activate( string $key ): array {
		self::save_key( $key );
		delete_site_transient( 'toplist_block_update_info' );
		$result = self::validate( $key );
		self::schedule_from_cache();
		return $result;
	}

	/**
	 * Short description.
	 *
	 * @param array<string, mixed> $decoded API JSON.
	 * @return array<string, mixed>|null
	 */
	public static function api_success_data( array $decoded ): ?array {
		if ( ! empty( $decoded['success'] ) && isset( $decoded['data'] ) && is_array( $decoded['data'] ) ) {
			return $decoded['data'];
		}
		if ( isset( $decoded['error'] ) && false === $decoded['error'] && isset( $decoded['data'] ) && is_array( $decoded['data'] ) ) {
			return $decoded['data'];
		}
		return null;
	}

	/**
	 * Short description.
	 *
	 * @param array<string, mixed> $decoded API JSON.
	 * @return array{code: string, message: string}
	 */
	private static function api_error_fields( array $decoded ): array {
		if ( isset( $decoded['error'] ) && is_array( $decoded['error'] ) ) {
			return array(
				'code'    => Toplist_Block_Util::array_string( $decoded['error'], 'code' ) !== '' ? Toplist_Block_Util::array_string( $decoded['error'], 'code' ) : 'unknown',
				'message' => Toplist_Block_Util::array_string( $decoded['error'], 'message' ) !== '' ? Toplist_Block_Util::array_string( $decoded['error'], 'message' ) : __( 'License check failed.', 'toplist' ),
			);
		}
		if ( ! empty( $decoded['error'] ) && isset( $decoded['code'] ) ) {
			return array(
				'code'    => Toplist_Block_Util::as_string( $decoded['code'] ),
				'message' => Toplist_Block_Util::array_string( $decoded, 'message' ) !== '' ? Toplist_Block_Util::array_string( $decoded, 'message' ) : __( 'License check failed.', 'toplist' ),
			);
		}
		return array(
			'code'    => 'unknown',
			'message' => __( 'License check failed.', 'toplist' ),
		);
	}

	/**
	 * Short description.
	 *
	 * @param string|null $key Optional key override.
	 * @return array<string, mixed>
	 */
	public static function validate( ?string $key = null ): array {
		$key = $key ?? self::get_key();
		$now = gmdate( 'c' );

		if ( '' === $key ) {
			return array(
				'status'  => 'unconfigured',
				'message' => __( 'No license key configured.', 'toplist' ),
			);
		}

		$api_url = self::api_url();
		if ( '' === $api_url ) {
			return array(
				'status'  => 'unreachable',
				'message' => __( 'License API URL is not configured.', 'toplist' ),
			);
		}

		$domain = self::current_install_domain();
		$body   = array(
			'domain'         => $domain,
			'license_key'    => $key,
			'auth_key'       => $key,
			'product_slug'   => self::PRODUCT_SLUG,
			'plugin_version' => defined( 'TOPLIST_BLOCK_VERSION' ) ? TOPLIST_BLOCK_VERSION : '',
		);

		$headers = self::api_request_headers();

		$response = wp_remote_post(
			$api_url,
			array(
				'timeout' => 15,
				'headers' => $headers,
				'body'    => Toplist_Block_Util::json_encode_body( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			$existing                  = self::get_cache();
			$existing['status']        = 'unreachable';
			$existing['checked_at']    = $now;
			$existing['error_message'] = $response->get_error_message();
			self::save_cache( $existing );
			return array(
				'status'  => 'unreachable',
				'message' => $response->get_error_message(),
			);
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		$decoded   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $decoded ) ) {
			$msg = sprintf(
				/* translators: %d: HTTP status code */
				__( 'Non-JSON response from licensing server (HTTP %d).', 'toplist' ),
				$http_code
			);
			$existing                  = self::get_cache();
			$existing['status']        = 'unreachable';
			$existing['checked_at']    = $now;
			$existing['error_message'] = $msg;
			self::save_cache( $existing );
			return array(
				'status'  => 'unreachable',
				'message' => $msg,
			);
		}

		$data = self::api_success_data( $decoded );
		if ( is_array( $data ) ) {
			$api_status = strtolower( Toplist_Block_Util::array_string( $data, 'status' ) );
			if ( '' === $api_status ) {
				$api_status = 'active';
			}
			$local_status = 'grace' === $api_status ? 'grace' : 'active';

			$extras  = isset( $data['extra_domains'] ) && is_array( $data['extra_domains'] )
				? array_values( array_map( array( Toplist_Block_Util::class, 'as_string' ), $data['extra_domains'] ) )
				: array();
			$allowed = isset( $data['allowed_domains'] ) && is_array( $data['allowed_domains'] )
				? array_values( array_map( array( Toplist_Block_Util::class, 'as_string' ), $data['allowed_domains'] ) )
				: array();
			if ( array() === $allowed ) {
				$primary = Toplist_Block_Util::array_string( $data, 'primary_domain' );
				$allowed = array_values( array_unique( array_merge( '' !== $primary ? array( $primary ) : array(), $extras ) ) );
			}

			$recheck_after = $data['recheck_after'] ?? null;
			$grace_hours   = $data['grace_hours'] ?? null;

			$cache = array(
				'status'          => $local_status,
				'checked_at'      => $now,
				'last_valid_at'   => $now,
				'expires_at'      => $data['expires_at'] ?? null,
				'recheck_after'   => is_string( $recheck_after ) ? $recheck_after : null,
				'grace_hours'     => is_numeric( $grace_hours ) ? (int) $grace_hours : null,
				'key_last4'       => Toplist_Block_Util::array_string( $data, 'license_key_last4' ),
				'billing_period'  => Toplist_Block_Util::array_string( $data, 'billing_period' ),
				'approved_at'     => Toplist_Block_Util::array_string( $data, 'approved_at' ),
				'bearer_token'    => Toplist_Block_Util::array_string( $data, 'bearer_token' ),
				'license_scope'   => Toplist_Block_Util::array_string( $data, 'license_scope' ) !== '' ? Toplist_Block_Util::array_string( $data, 'license_scope' ) : 'single',
				'primary_domain'  => Toplist_Block_Util::array_string( $data, 'primary_domain' ),
				'extra_domains'   => $extras,
				'allowed_domains' => $allowed,
			);
			self::save_cache( $cache );
			return array(
				'status' => $local_status,
				'data'   => $data,
			);
		}

		$err          = self::api_error_fields( $decoded );
		$error_code   = $err['code'];
		$error_msg    = $err['message'];
		$local_status = in_array( $error_code, array( 'license_revoked', 'license_expired' ), true )
			? ltrim( $error_code, 'license_' )
			: 'invalid';

		$existing = self::get_cache();
		$cache    = array(
			'status'         => $local_status,
			'checked_at'     => $now,
			'last_valid_at'  => Toplist_Block_Util::array_string( $existing, 'last_valid_at' ),
			'billing_period' => Toplist_Block_Util::array_string( $existing, 'billing_period' ),
			'grace_hours'    => isset( $existing['grace_hours'] ) && is_numeric( $existing['grace_hours'] ) ? (int) $existing['grace_hours'] : null,
			'error_code'     => $error_code,
			'error_message'  => $error_msg,
		);
		self::save_cache( $cache );

		return array(
			'status'     => $local_status,
			'error_code' => $error_code,
			'message'    => $error_msg,
		);
	}

	/**
	 * Short description.
	 *
	 * @return void
	 */
	public static function schedule_from_cache(): void {
		$cache         = self::get_cache();
		$status        = Toplist_Block_Util::array_string( $cache, 'status' );
		$billing       = Toplist_Block_Util::array_string( $cache, 'billing_period' );
		$recheck_after = $cache['recheck_after'] ?? null;

		if ( in_array( $status, array( 'active', 'grace' ), true ) && null !== $recheck_after && '' !== $recheck_after ) {
			$at = strtotime( Toplist_Block_Util::as_string( $recheck_after ) );
			if ( false !== $at && $at > time() ) {
				wp_clear_scheduled_hook( self::CRON_HOOK );
				wp_schedule_single_event( $at, self::CRON_HOOK );
				return;
			}
		}

		if ( 'active' === $status && 'lifetime' === $billing ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
			wp_schedule_single_event( time() + 30 * DAY_IN_SECONDS, self::CRON_HOOK );
			return;
		}

		if ( 'unreachable' === $status || 'expired' === $status ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
			wp_schedule_single_event( time() + self::RETRY_HOURS * HOUR_IN_SECONDS, self::CRON_HOOK );
			return;
		}

		if ( 'invalid' === $status ) {
			$retry_raw = $cache['invalid_retry_count'] ?? 0;
			$tries     = is_numeric( $retry_raw ) ? (int) $retry_raw : 0;
			if ( $tries < 1 ) {
				$cache['invalid_retry_count'] = $tries + 1;
				self::save_cache( $cache );
				wp_clear_scheduled_hook( self::CRON_HOOK );
				wp_schedule_single_event( time() + DAY_IN_SECONDS, self::CRON_HOOK );
				return;
			}
		}

		self::cancel_cron();
	}

	/**
	 * Short description.
	 *
	 * @return void
	 */
	public static function cancel_cron(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Short description.
	 *
	 * @return void
	 */
	public static function do_recheck(): void {
		self::validate();
		self::schedule_from_cache();
	}

	/**
	 * Short description.
	 *
	 * @return void
	 */
	public static function clear(): void {
		delete_option( self::OPTION_KEY );
		delete_site_transient( 'toplist_block_update_info' );
		self::clear_cache();
		self::cancel_cron();
	}
}
