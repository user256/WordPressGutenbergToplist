<?php

require dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__DIR__) . '/wordpress/');
}

if (!defined('TOPLIST_BLOCK_PATH')) {
	define('TOPLIST_BLOCK_PATH', dirname(__DIR__) . '/toplist-block/');
}

if (!defined('TOPLIST_BLOCK_VERSION')) {
	define('TOPLIST_BLOCK_VERSION', '0.0.0-test');
}

if (!function_exists('__')) {
	function __(string $text, string $domain = 'default'): string {
		return $text;
	}
}

if (!function_exists('esc_html__')) {
	function esc_html__(string $text, string $domain = 'default'): string {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('esc_html')) {
	function esc_html(string $text): string {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('esc_attr')) {
	function esc_attr(string $text): string {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('esc_url')) {
	function esc_url(string $url): string {
		return $url;
	}
}

if (!function_exists('esc_textarea')) {
	function esc_textarea(string $text): string {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('wp_strip_all_tags')) {
	function wp_strip_all_tags(string $text): string {
		return strip_tags($text);
	}
}

if (!function_exists('sanitize_text_field')) {
	function sanitize_text_field(string $text): string {
		return trim($text);
	}
}

if (!function_exists('wp_json_encode')) {
	function wp_json_encode($data, int $options = 0): string {
		return json_encode($data, $options) ?: 'null';
	}
}

if (!function_exists('add_action')) {
	function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): void {
	}
}

if (!function_exists('add_filter')) {
	function add_filter(string $hook, $callback, int $priority = 10, int $accepted_args = 1) {
		return true;
	}
}

if (!function_exists('apply_filters')) {
	function apply_filters(string $hook, $value) {
		return $value;
	}
}

if (!function_exists('get_option')) {
	function get_option(string $key, $default = false) {
		return $default;
	}
}

if (!function_exists('update_option')) {
	function update_option(string $key, $value): bool {
		return true;
	}
}

if (!function_exists('delete_option')) {
	function delete_option(string $key): bool {
		return true;
	}
}

if (!function_exists('delete_site_transient')) {
	function delete_site_transient(string $key): bool {
		return true;
	}
}

if (!function_exists('get_site_transient')) {
	function get_site_transient(string $key) {
		return false;
	}
}

if (!function_exists('set_site_transient')) {
	function set_site_transient(string $key, $value, int $expiration): bool {
		return true;
	}
}

if (!function_exists('wp_next_scheduled')) {
	function wp_next_scheduled(string $hook) {
		return false;
	}
}

if (!function_exists('wp_schedule_single_event')) {
	function wp_schedule_single_event(int $timestamp, string $hook): void {
	}
}

if (!function_exists('wp_unschedule_event')) {
	function wp_unschedule_event(int $timestamp, string $hook): void {
	}
}

if (!function_exists('wp_salt')) {
	function wp_salt(string $scheme = 'auth'): string {
		return 'test-salt-' . $scheme;
	}
}

if (!function_exists('home_url')) {
	function home_url(string $path = ''): string {
		return 'https://example.test' . $path;
	}
}

if (!function_exists('wp_parse_url')) {
	function wp_parse_url(string $url, int $component = -1) {
		return parse_url($url, $component);
	}
}

if (!function_exists('trailingslashit')) {
	function trailingslashit(string $string): string {
		return rtrim($string, '/\\') . '/';
	}
}

if (!function_exists('plugin_basename')) {
	function plugin_basename(string $file): string {
		return basename(dirname($file)) . '/' . basename($file);
	}
}

if (!function_exists('load_plugin_textdomain')) {
	function load_plugin_textdomain(string $domain, bool $deprecated = false, string $path = ''): bool {
		return true;
	}
}

if (!function_exists('register_activation_hook')) {
	function register_activation_hook(string $file, $callback): void {
	}
}

if (!function_exists('plugins_url')) {
	function plugins_url(string $path, string $plugin_file): string {
		return 'https://example.test/wp-content/plugins/' . ltrim($path, '/');
	}
}

if (!function_exists('wp_enqueue_script')) {
	function wp_enqueue_script(string $handle, string $src = '', array $deps = array(), $ver = false, bool $in_footer = false): void {
	}
}

if (!function_exists('wp_add_inline_script')) {
	function wp_add_inline_script(string $handle, string $data, string $position = 'after'): void {
	}
}

if (!function_exists('deactivate_plugins')) {
	function deactivate_plugins(string $plugins, bool $silent = false): void {
	}
}

if (!function_exists('is_plugin_active')) {
	function is_plugin_active(string $plugin): bool {
		return false;
	}
}

if (!function_exists('set_transient')) {
	function set_transient(string $key, $value, int $expiration): bool {
		return true;
	}
}

if (!function_exists('get_transient')) {
	function get_transient(string $key) {
		return false;
	}
}

if (!function_exists('delete_transient')) {
	function delete_transient(string $key): bool {
		return true;
	}
}

if (!function_exists('current_user_can')) {
	function current_user_can(string $capability): bool {
		return true;
	}
}

if (!function_exists('is_admin')) {
	function is_admin(): bool {
		return false;
	}
}

if (!function_exists('admin_url')) {
	function admin_url(string $path = ''): string {
		return 'https://example.test/wp-admin/' . ltrim($path, '/');
	}
}

if (!function_exists('wp_remote_post')) {
	function wp_remote_post(string $url, array $args = array()) {
		return new WP_Error('http_stub', 'HTTP stub');
	}
}

if (!function_exists('is_wp_error')) {
	function is_wp_error($thing): bool {
		return $thing instanceof WP_Error;
	}
}

if (!function_exists('wp_remote_retrieve_response_code')) {
	function wp_remote_retrieve_response_code($response): int {
		return 0;
	}
}

if (!function_exists('wp_remote_retrieve_body')) {
	function wp_remote_retrieve_body($response): string {
		return '';
	}
}

if (!class_exists('WP_Error')) {
	class WP_Error {
		private string $message;

		public function __construct(string $code = '', string $message = '') {
			$this->message = $message;
		}

		public function get_error_message(): string {
			return $this->message;
		}
	}
}

require_once TOPLIST_BLOCK_PATH . 'toplist-block.php';

require_once __DIR__ . '/IntegrationTestCase.php';
