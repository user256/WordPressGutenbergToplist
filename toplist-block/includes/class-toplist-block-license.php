<?php
/**
 * Remote license validation and premium feature gating.
 *
 * Premium-only: excluded from the lite build.
 *
 * @package Toplist_Block
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Validates portal license keys and caches signed entitlement state.
 */
class Toplist_Block_License {

	const OPTION_KEY   = 'toplist_block_license_key';
	const OPTION_API_URL = 'toplist_block_license_api_url';
	const OPTION_API_KEY = 'toplist_block_license_api_key';
	const CACHE_OPTION = 'toplist_block_license_cache_signed';
	const CRON_HOOK    = 'toplist_block_license_recheck';
	const PRODUCT_SLUG = 'toplist-block-pro';
	const RETRY_HOURS  = 6;

	/** @var bool|null */
	private static $cache_trusted = null;

	/**
	 * Licensing API URL (portal licence-manager validate or core /api/v1/validate).
	 *
	 * @return string
	 */
	public static function api_url(): string {
		$from_const = Toplist_Block_Util::configured_constant('TOPLIST_BLOCK_LICENSE_API_URL');
		if ($from_const !== '') {
			return $from_const;
		}
		$stored = self::get_stored_api_url();
		if ($stored !== '') {
			return $stored;
		}
		$filtered = apply_filters('toplist_block_license_api_url', '');
		return is_string($filtered) ? $filtered : '';
	}

	/**
	 * Optional Bearer / X-Portal-Module-Key for module dispatch routes.
	 *
	 * @return string
	 */
	public static function api_key(): string {
		$from_const = Toplist_Block_Util::configured_constant('TOPLIST_BLOCK_LICENSE_API_KEY');
		if ($from_const !== '') {
			return $from_const;
		}
		$stored = self::get_stored_api_key();
		if ($stored !== '') {
			return $stored;
		}
		$filtered = apply_filters('toplist_block_license_api_key', '');
		return is_string($filtered) ? $filtered : '';
	}

	/**
	 * Portal validate endpoint saved in plugin settings (not wp-config).
	 *
	 * @return string
	 */
	public static function get_stored_api_url(): string {
		$stored = get_option(self::OPTION_API_URL, '');
		return is_string($stored) ? $stored : '';
	}

	/**
	 * Module API key saved in plugin settings (not wp-config).
	 *
	 * @return string
	 */
	public static function get_stored_api_key(): string {
		$stored = get_option(self::OPTION_API_KEY, '');
		return self::decrypt_secret(is_string($stored) ? $stored : '');
	}

	/**
	 * Whether API URL/key come from wp-config constants (read-only in admin).
	 *
	 * @return bool
	 */
	public static function api_config_locked_by_constant(): bool {
		return Toplist_Block_Util::configured_constant('TOPLIST_BLOCK_LICENSE_API_URL') !== ''
			|| Toplist_Block_Util::configured_constant('TOPLIST_BLOCK_LICENSE_API_KEY') !== '';
	}

	/**
	 * @param string $url     Validate endpoint URL.
	 * @param string $api_key Module API key; empty keeps existing stored key.
	 * @return void
	 */
	public static function save_api_settings(string $url, string $api_key): void {
		if (self::api_config_locked_by_constant()) {
			return;
		}
		$url = esc_url_raw(trim($url));
		update_option(self::OPTION_API_URL, $url, false);
		if ($api_key !== '') {
			update_option(self::OPTION_API_KEY, self::encrypt_secret(sanitize_text_field(trim($api_key))), false);
		}
		delete_site_transient('toplist_block_update_info');
	}

	/**
	 * OTA update-check endpoint (defaults from validate URL).
	 *
	 * @return string
	 */
	public static function update_check_api_url(): string {
		$from_const = Toplist_Block_Util::configured_constant('TOPLIST_BLOCK_UPDATE_API_URL');
		if ($from_const !== '') {
			return $from_const;
		}
		$filtered = apply_filters('toplist_block_update_api_url', '');
		if (is_string($filtered) && $filtered !== '') {
			return $filtered;
		}
		$validate = self::api_url();
		if ($validate === '') {
			return '';
		}
		if (preg_match('#/validate/?$#i', $validate)) {
			return (string) preg_replace('#/validate/?$#i', '/update-check', $validate);
		}
		return rtrim($validate, '/') . '/update-check';
	}

	/**
	 * @return array<string, string>
	 */
	public static function api_request_headers(): array {
		$headers = array('Content-Type' => 'application/json; charset=utf-8');
		$api_key = self::api_key();
		if ($api_key !== '') {
			$headers['Authorization'] = 'Bearer ' . $api_key;
		}
		return $headers;
	}

	/**
	 * @return string
	 */
	public static function get_key(): string {
		$stored = get_option(self::OPTION_KEY, '');
		return self::decrypt_secret(is_string($stored) ? $stored : '');
	}

	/**
	 * @param string $key License key.
	 * @return void
	 */
	public static function save_key(string $key): void {
		update_option(self::OPTION_KEY, self::encrypt_secret(sanitize_text_field(trim($key))));
	}

	/**
	 * @param string $plain Plain secret.
	 * @return string
	 */
	private static function encrypt_secret(string $plain): string {
		if ($plain === '') {
			return '';
		}
		if (!function_exists('openssl_encrypt')) {
			return base64_encode($plain);
		}
		$iv = openssl_random_pseudo_bytes(16);
		if (!is_string($iv) || $iv === '') {
			return base64_encode($plain);
		}
		$encrypted = openssl_encrypt($plain, 'AES-256-CBC', hash('sha256', wp_salt('auth'), true), OPENSSL_RAW_DATA, $iv);
		if (!is_string($encrypted)) {
			return base64_encode($plain);
		}
		return base64_encode($iv . $encrypted);
	}

	/**
	 * @param string $packed Packed secret.
	 * @return string
	 */
	private static function decrypt_secret(string $packed): string {
		if ($packed === '') {
			return '';
		}
		$raw = base64_decode($packed, true);
		if ($raw === false) {
			return '';
		}
		if (!function_exists('openssl_decrypt') || strlen($raw) <= 16) {
			return $raw;
		}
		$iv = substr($raw, 0, 16);
		$ciphertext = substr($raw, 16);
		$plain = openssl_decrypt($ciphertext, 'AES-256-CBC', hash('sha256', wp_salt('auth'), true), OPENSSL_RAW_DATA, $iv);
		return is_string($plain) ? $plain : '';
	}

	/**
	 * @return string
	 */
	public static function get_cache_path(): string {
		$upload = wp_upload_dir();
		return trailingslashit($upload['basedir']) . 'toplist-block/license-cache.json';
	}

	/**
	 * @param string $payload Payload JSON.
	 * @return string
	 */
	private static function sign_payload(string $payload): string {
		return hash_hmac('sha256', $payload, wp_salt('auth'));
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_cache(): array {
		$option = get_option(self::CACHE_OPTION, false);
		if (is_array($option) && isset($option['payload'], $option['sig'])) {
			$payload = Toplist_Block_Util::as_string($option['payload']);
			$sig = Toplist_Block_Util::as_string($option['sig']);
			if (hash_equals(self::sign_payload($payload), $sig)) {
				$data = json_decode($payload, true);
				self::$cache_trusted = true;
				return is_array($data) ? $data : array();
			}
		}

		self::$cache_trusted = false;
		$path = self::get_cache_path();
		if (!file_exists($path)) {
			return array();
		}
		$raw = @file_get_contents($path);
		if ($raw === false) {
			return array();
		}
		$data = json_decode($raw, true);
		return is_array($data) ? $data : array();
	}

	/**
	 * @return bool
	 */
	public static function is_cache_trusted(): bool {
		if (self::$cache_trusted === null) {
			self::get_cache();
		}
		return self::$cache_trusted === true;
	}

	/**
	 * @param array<string, mixed> $data Cache payload.
	 * @return void
	 */
	private static function save_cache(array $data): void {
		$json = wp_json_encode($data, JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			return;
		}

		update_option(
			self::CACHE_OPTION,
			array(
				'payload' => $json,
				'sig' => self::sign_payload($json),
			),
			false
		);
		self::$cache_trusted = true;

		$path = self::get_cache_path();
		$dir = dirname($path);
		if (!is_dir($dir)) {
			wp_mkdir_p($dir);
		}
		$pretty = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if ($pretty !== false) {
			file_put_contents($path, $pretty);
		}
	}

	/**
	 * @return void
	 */
	private static function clear_cache(): void {
		delete_option(self::CACHE_OPTION);
		self::$cache_trusted = null;
		$path = self::get_cache_path();
		if (file_exists($path)) {
			wp_delete_file($path);
		}
	}

	/**
	 * @param array<string, mixed> $cache Cache.
	 * @return void
	 */
	private static function maybe_kick_stale_recheck(array $cache): void {
		static $kicked = false;
		if ($kicked) {
			return;
		}
		$kicked = true;

		$recheck_after = $cache['recheck_after'] ?? null;
		if (!is_string($recheck_after) || $recheck_after === '') {
			return;
		}
		$due = strtotime($recheck_after);
		if ($due === false || $due >= time()) {
			return;
		}

		$next = wp_next_scheduled(self::CRON_HOOK);
		if ($next && $next <= time() + 60) {
			return;
		}

		wp_schedule_single_event(time(), self::CRON_HOOK);
	}

	/**
	 * @param array<string, mixed> $cache Cache.
	 * @return int
	 */
	private static function grace_seconds(array $cache): int {
		$grace_hours = $cache['grace_hours'] ?? null;
		if (is_numeric($grace_hours) && (int) $grace_hours > 0) {
			return (int) $grace_hours * HOUR_IN_SECONDS;
		}

		$billing = strtolower(trim(Toplist_Block_Util::array_string($cache, 'billing_period')));
		if ($billing === 'monthly') {
			return 3 * DAY_IN_SECONDS;
		}
		if ($billing === 'yearly' || $billing === 'lifetime') {
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
		if (self::get_key() === '') {
			return false;
		}

		$cache = self::get_cache();

		if (!self::is_cache_trusted()) {
			if (!wp_next_scheduled(self::CRON_HOOK)) {
				wp_schedule_single_event(time(), self::CRON_HOOK);
			}
			return false;
		}

		self::maybe_kick_stale_recheck($cache);

		$status = Toplist_Block_Util::array_string($cache, 'status');

		if (in_array($status, array('active', 'grace'), true)) {
			$expires = $cache['expires_at'] ?? null;
			if ($expires !== null && $expires !== '') {
				$t = strtotime(Toplist_Block_Util::as_string($expires));
				if ($t !== false && $t < time()) {
					return false;
				}
			}

			$allowed = self::get_allowed_domains();
			if ($allowed !== array()) {
				$current = self::current_install_domain();
				if (!in_array($current, $allowed, true)) {
					if (!wp_next_scheduled(self::CRON_HOOK)) {
						wp_schedule_single_event(time(), self::CRON_HOOK);
					}
					return false;
				}
			}

			return true;
		}

		if ($status === 'revoked') {
			return false;
		}

		if ($status === 'unreachable') {
			$last_valid = Toplist_Block_Util::array_string($cache, 'last_valid_at');
			if ($last_valid !== '') {
				$lv = strtotime($last_valid);
				if ($lv !== false && (time() - $lv) < self::grace_seconds($cache)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return string
	 */
	public static function current_install_domain(): string {
		$host = wp_parse_url(home_url('/'), PHP_URL_HOST);
		$host = is_string($host) ? $host : '';
		return strtolower((string) preg_replace('#^www\.#', '', $host));
	}

	/**
	 * @return list<string>
	 */
	public static function get_allowed_domains(): array {
		$c = self::get_cache();
		if (isset($c['allowed_domains']) && is_array($c['allowed_domains'])) {
			return array_values(array_map('strval', $c['allowed_domains']));
		}
		$primary = Toplist_Block_Util::array_string($c, 'primary_domain');
		$extras = isset($c['extra_domains']) && is_array($c['extra_domains'])
			? array_values(array_map('strval', $c['extra_domains']))
			: array();
		if ($primary === '' && $extras === array()) {
			return array();
		}
		return array_values(array_unique(array_merge($primary !== '' ? array($primary) : array(), $extras)));
	}

	/**
	 * Save key, validate against portal, schedule recheck.
	 *
	 * @param string $key License key.
	 * @return array<string, mixed>
	 */
	public static function activate(string $key): array {
		self::save_key($key);
		delete_site_transient('toplist_block_update_info');
		$result = self::validate($key);
		self::schedule_from_cache();
		return $result;
	}

	/**
	 * @param array<string, mixed> $decoded API JSON.
	 * @return array<string, mixed>|null
	 */
	public static function api_success_data(array $decoded): ?array {
		if (!empty($decoded['success']) && isset($decoded['data']) && is_array($decoded['data'])) {
			return $decoded['data'];
		}
		if (isset($decoded['error']) && $decoded['error'] === false && isset($decoded['data']) && is_array($decoded['data'])) {
			return $decoded['data'];
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $decoded API JSON.
	 * @return array{code: string, message: string}
	 */
	private static function api_error_fields(array $decoded): array {
		if (isset($decoded['error']) && is_array($decoded['error'])) {
			return array(
				'code' => Toplist_Block_Util::array_string($decoded['error'], 'code') !== '' ? Toplist_Block_Util::array_string($decoded['error'], 'code') : 'unknown',
				'message' => Toplist_Block_Util::array_string($decoded['error'], 'message') !== '' ? Toplist_Block_Util::array_string($decoded['error'], 'message') : __('License check failed.', 'toplist'),
			);
		}
		if (!empty($decoded['error']) && isset($decoded['code'])) {
			return array(
				'code' => Toplist_Block_Util::as_string($decoded['code']),
				'message' => Toplist_Block_Util::array_string($decoded, 'message') !== '' ? Toplist_Block_Util::array_string($decoded, 'message') : __('License check failed.', 'toplist'),
			);
		}
		return array(
			'code' => 'unknown',
			'message' => __('License check failed.', 'toplist'),
		);
	}

	/**
	 * @param string|null $key Optional key override.
	 * @return array<string, mixed>
	 */
	public static function validate(?string $key = null): array {
		$key = $key ?? self::get_key();
		$now = gmdate('c');

		if ($key === '') {
			return array(
				'status' => 'unconfigured',
				'message' => __('No license key configured.', 'toplist'),
			);
		}

		$api_url = self::api_url();
		if ($api_url === '') {
			return array(
				'status' => 'unreachable',
				'message' => __('License API URL is not configured.', 'toplist'),
			);
		}

		$domain = self::current_install_domain();
		$body = array(
			'domain' => $domain,
			'license_key' => $key,
			'auth_key' => $key,
			'product_slug' => self::PRODUCT_SLUG,
			'plugin_version' => defined('TOPLIST_BLOCK_VERSION') ? TOPLIST_BLOCK_VERSION : '',
		);

		$headers = self::api_request_headers();

		$response = wp_remote_post(
			$api_url,
			array(
				'timeout' => 15,
				'headers' => $headers,
				'body' => Toplist_Block_Util::json_encode_body($body),
			)
		);

		if (is_wp_error($response)) {
			$existing = self::get_cache();
			$existing['status'] = 'unreachable';
			$existing['checked_at'] = $now;
			$existing['error_message'] = $response->get_error_message();
			self::save_cache($existing);
			return array(
				'status' => 'unreachable',
				'message' => $response->get_error_message(),
			);
		}

		$http_code = (int) wp_remote_retrieve_response_code($response);
		$decoded = json_decode(wp_remote_retrieve_body($response), true);

		if (!is_array($decoded)) {
			$msg = sprintf(
				/* translators: %d: HTTP status code */
				__('Non-JSON response from licensing server (HTTP %d).', 'toplist'),
				$http_code
			);
			$existing = self::get_cache();
			$existing['status'] = 'unreachable';
			$existing['checked_at'] = $now;
			$existing['error_message'] = $msg;
			self::save_cache($existing);
			return array(
				'status' => 'unreachable',
				'message' => $msg,
			);
		}

		$data = self::api_success_data($decoded);
		if (is_array($data)) {
			$api_status = strtolower(Toplist_Block_Util::array_string($data, 'status'));
			if ($api_status === '') {
				$api_status = 'active';
			}
			$local_status = $api_status === 'grace' ? 'grace' : 'active';

			$extras = isset($data['extra_domains']) && is_array($data['extra_domains'])
				? array_values(array_map(array(Toplist_Block_Util::class, 'as_string'), $data['extra_domains']))
				: array();
			$allowed = isset($data['allowed_domains']) && is_array($data['allowed_domains'])
				? array_values(array_map(array(Toplist_Block_Util::class, 'as_string'), $data['allowed_domains']))
				: array();
			if ($allowed === array()) {
				$primary = Toplist_Block_Util::array_string($data, 'primary_domain');
				$allowed = array_values(array_unique(array_merge($primary !== '' ? array($primary) : array(), $extras)));
			}

			$recheck_after = $data['recheck_after'] ?? null;
			$grace_hours = $data['grace_hours'] ?? null;

			$cache = array(
				'status' => $local_status,
				'checked_at' => $now,
				'last_valid_at' => $now,
				'expires_at' => $data['expires_at'] ?? null,
				'recheck_after' => is_string($recheck_after) ? $recheck_after : null,
				'grace_hours' => is_numeric($grace_hours) ? (int) $grace_hours : null,
				'key_last4' => Toplist_Block_Util::array_string($data, 'license_key_last4'),
				'billing_period' => Toplist_Block_Util::array_string($data, 'billing_period'),
				'approved_at' => Toplist_Block_Util::array_string($data, 'approved_at'),
				'bearer_token' => Toplist_Block_Util::array_string($data, 'bearer_token'),
				'license_scope' => Toplist_Block_Util::array_string($data, 'license_scope') !== '' ? Toplist_Block_Util::array_string($data, 'license_scope') : 'single',
				'primary_domain' => Toplist_Block_Util::array_string($data, 'primary_domain'),
				'extra_domains' => $extras,
				'allowed_domains' => $allowed,
			);
			self::save_cache($cache);
			return array(
				'status' => $local_status,
				'data' => $data,
			);
		}

		$err = self::api_error_fields($decoded);
		$error_code = $err['code'];
		$error_msg = $err['message'];
		$local_status = in_array($error_code, array('license_revoked', 'license_expired'), true)
			? ltrim($error_code, 'license_')
			: 'invalid';

		$existing = self::get_cache();
		$cache = array(
			'status' => $local_status,
			'checked_at' => $now,
			'last_valid_at' => Toplist_Block_Util::array_string($existing, 'last_valid_at'),
			'billing_period' => Toplist_Block_Util::array_string($existing, 'billing_period'),
			'grace_hours' => isset($existing['grace_hours']) && is_numeric($existing['grace_hours']) ? (int) $existing['grace_hours'] : null,
			'error_code' => $error_code,
			'error_message' => $error_msg,
		);
		self::save_cache($cache);

		return array(
			'status' => $local_status,
			'error_code' => $error_code,
			'message' => $error_msg,
		);
	}

	/**
	 * @return void
	 */
	public static function schedule_from_cache(): void {
		$cache = self::get_cache();
		$status = Toplist_Block_Util::array_string($cache, 'status');
		$billing = Toplist_Block_Util::array_string($cache, 'billing_period');
		$recheck_after = $cache['recheck_after'] ?? null;

		if (in_array($status, array('active', 'grace'), true) && $recheck_after !== null && $recheck_after !== '') {
			$at = strtotime(Toplist_Block_Util::as_string($recheck_after));
			if ($at !== false && $at > time()) {
				wp_clear_scheduled_hook(self::CRON_HOOK);
				wp_schedule_single_event($at, self::CRON_HOOK);
				return;
			}
		}

		if ($status === 'active' && $billing === 'lifetime') {
			wp_clear_scheduled_hook(self::CRON_HOOK);
			wp_schedule_single_event(time() + 30 * DAY_IN_SECONDS, self::CRON_HOOK);
			return;
		}

		if ($status === 'unreachable' || $status === 'expired') {
			wp_clear_scheduled_hook(self::CRON_HOOK);
			wp_schedule_single_event(time() + self::RETRY_HOURS * HOUR_IN_SECONDS, self::CRON_HOOK);
			return;
		}

		if ($status === 'invalid') {
			$retry_raw = $cache['invalid_retry_count'] ?? 0;
			$tries = is_numeric($retry_raw) ? (int) $retry_raw : 0;
			if ($tries < 1) {
				$cache['invalid_retry_count'] = $tries + 1;
				self::save_cache($cache);
				wp_clear_scheduled_hook(self::CRON_HOOK);
				wp_schedule_single_event(time() + DAY_IN_SECONDS, self::CRON_HOOK);
				return;
			}
		}

		self::cancel_cron();
	}

	/**
	 * @return void
	 */
	public static function cancel_cron(): void {
		wp_clear_scheduled_hook(self::CRON_HOOK);
	}

	/**
	 * @return void
	 */
	public static function do_recheck(): void {
		self::validate();
		self::schedule_from_cache();
	}

	/**
	 * @return void
	 */
	public static function clear(): void {
		delete_option(self::OPTION_KEY);
		delete_site_transient('toplist_block_update_info');
		self::clear_cache();
		self::cancel_cron();
	}
}
