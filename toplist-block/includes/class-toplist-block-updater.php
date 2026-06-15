<?php
/**
 * Premium OTA update checks via portal API.
 *
 * Premium-only: excluded from the lite build.
 *
 * @package Toplist_Block
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Injects portal-backed plugin updates into WordPress core update UI.
 */
class Toplist_Block_Updater {

	const CACHE_KEY   = 'toplist_block_update_info';
	const CACHE_TTL   = 12 * HOUR_IN_SECONDS;
	const PLUGIN_SLUG = 'toplist-block';

	/**
	 * @return void
	 */
	public static function init(): void {
		add_filter('pre_set_site_transient_update_plugins', array(__CLASS__, 'inject_update'));
		add_filter('plugins_api', array(__CLASS__, 'plugin_info'), 10, 3);
	}

	/**
	 * @return string
	 */
	public static function plugin_file(): string {
		return plugin_basename(TOPLIST_BLOCK_PATH . '/toplist-block.php');
	}

	/**
	 * @param mixed $transient Update transient.
	 * @return mixed
	 */
	public static function inject_update($transient) {
		if (!is_object($transient)) {
			return $transient;
		}

		$plugin_file = self::plugin_file();
		if (!isset($transient->checked) || !is_array($transient->checked)) {
			return $transient;
		}
		if (!isset($transient->checked[$plugin_file])) {
			return $transient;
		}

		if (!Toplist_Block_License::is_valid()) {
			return $transient;
		}

		$remote = self::get_remote_info();
		if (!is_array($remote) || empty($remote['update_available'])) {
			return $transient;
		}

		$new_version = Toplist_Block_Util::array_string($remote, 'new_version');
		if ($new_version === '') {
			$new_version = Toplist_Block_Util::array_string($remote, 'version');
		}
		$package = Toplist_Block_Util::array_string($remote, 'package');
		if ($package === '') {
			$package = Toplist_Block_Util::array_string($remote, 'download_url');
		}
		if ($new_version === '' || $package === '') {
			return $transient;
		}

		if (!version_compare(Toplist_Block_Util::as_string($transient->checked[$plugin_file]), $new_version, '<')) {
			return $transient;
		}

		$response_map = Toplist_Block_Util::transient_response_map($transient);
		$response_map[$plugin_file] = (object) array(
			'slug'         => Toplist_Block_Util::array_string($remote, 'slug') !== '' ? Toplist_Block_Util::array_string($remote, 'slug') : self::PLUGIN_SLUG,
			'plugin'       => $plugin_file,
			'new_version'  => $new_version,
			'url'          => Toplist_Block_Util::array_string($remote, 'homepage'),
			'package'      => $package,
			'tested'       => Toplist_Block_Util::array_string($remote, 'tested'),
			'requires_php' => Toplist_Block_Util::array_string($remote, 'requires_php') !== '' ? Toplist_Block_Util::array_string($remote, 'requires_php') : '7.4',
		);
		Toplist_Block_Util::set_transient_response_map($transient, $response_map);

		return $transient;
	}

	/**
	 * @param false|object|array<string, mixed> $result Plugin info result.
	 * @param string                            $action API action.
	 * @param object                            $args   Request args.
	 * @return false|object|array<string, mixed>
	 */
	public static function plugin_info($result, $action, $args) {
		if ($action !== 'plugin_information' || !is_object($args)) {
			return $result;
		}

		$slug = isset($args->slug) ? sanitize_key((string) $args->slug) : '';
		if ($slug !== self::PLUGIN_SLUG) {
			return $result;
		}

		if (!Toplist_Block_License::is_valid()) {
			return $result;
		}

		$remote = self::get_remote_info();
		if (!is_array($remote)) {
			return $result;
		}

		$sections = isset($remote['sections']) && is_array($remote['sections']) ? $remote['sections'] : array();
		$homepage = Toplist_Block_Util::array_string($remote, 'homepage');
		$new_version = Toplist_Block_Util::array_string($remote, 'new_version');
		if ($new_version === '') {
			$new_version = Toplist_Block_Util::array_string($remote, 'version');
		}
		if ($new_version === '') {
			$new_version = TOPLIST_BLOCK_VERSION;
		}
		$info = (object) array(
			'name'          => Toplist_Block_Util::array_string($remote, 'name') !== '' ? Toplist_Block_Util::array_string($remote, 'name') : 'Toplist Block Pro',
			'slug'          => self::PLUGIN_SLUG,
			'version'       => $new_version,
			'author'        => '<a href="' . esc_url($homepage) . '">Toplist Block Pro</a>',
			'homepage'      => $homepage,
			'download_link' => Toplist_Block_Util::array_string($remote, 'package') !== '' ? Toplist_Block_Util::array_string($remote, 'package') : Toplist_Block_Util::array_string($remote, 'download_url'),
			'requires'      => Toplist_Block_Util::array_string($remote, 'requires') !== '' ? Toplist_Block_Util::array_string($remote, 'requires') : '6.0',
			'tested'        => Toplist_Block_Util::array_string($remote, 'tested'),
			'requires_php'  => Toplist_Block_Util::array_string($remote, 'requires_php') !== '' ? Toplist_Block_Util::array_string($remote, 'requires_php') : '7.4',
			'last_updated'  => Toplist_Block_Util::array_string($remote, 'last_updated') !== '' ? Toplist_Block_Util::array_string($remote, 'last_updated') : gmdate('Y-m-d'),
			'sections'      => array(
				'description' => Toplist_Block_Util::array_string($sections, 'description') !== '' ? Toplist_Block_Util::array_string($sections, 'description') : '<p>Toplist Block Pro premium plugin.</p>',
				'changelog'   => Toplist_Block_Util::array_string($sections, 'changelog') !== '' ? Toplist_Block_Util::array_string($sections, 'changelog') : '<p>See portal account for release notes.</p>',
			),
		);

		return $info;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function get_remote_info(): ?array {
		$cached = get_site_transient(self::CACHE_KEY);
		if (is_array($cached)) {
			return $cached;
		}

		$api_url = Toplist_Block_License::update_check_api_url();
		if ($api_url === '' || Toplist_Block_License::get_key() === '') {
			return null;
		}

		$body = array(
			'domain'         => Toplist_Block_License::current_install_domain(),
			'license_key'    => Toplist_Block_License::get_key(),
			'auth_key'       => Toplist_Block_License::get_key(),
			'product_slug'   => Toplist_Block_License::PRODUCT_SLUG,
			'plugin_version' => defined('TOPLIST_BLOCK_VERSION') ? TOPLIST_BLOCK_VERSION : '',
		);

		$response = wp_remote_post(
			$api_url,
			array(
				'timeout' => 15,
				'headers' => Toplist_Block_License::api_request_headers(),
				'body'    => Toplist_Block_Util::json_encode_body($body),
			)
		);

		if (is_wp_error($response)) {
			return null;
		}

		$decoded = json_decode(wp_remote_retrieve_body($response), true);
		if (!is_array($decoded)) {
			return null;
		}

		$data = Toplist_Block_License::api_success_data($decoded);
		if (!is_array($data)) {
			return null;
		}

		set_site_transient(self::CACHE_KEY, $data, self::CACHE_TTL);

		return $data;
	}
}
