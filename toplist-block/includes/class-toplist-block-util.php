<?php
/**
 * Shared helpers for premium classes (PHPStan-friendly).
 *
 * @package Toplist_Block
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Small typed utilities for license/updater code paths.
 */
final class Toplist_Block_Util {

	/**
	 * @param mixed $value Raw value.
	 */
	public static function as_string($value): string {
		if (is_string($value)) {
			return $value;
		}
		if (is_int($value) || is_float($value) || is_bool($value)) {
			return (string) $value;
		}
		return '';
	}

	/**
	 * @param array<string, mixed> $data Source array.
	 */
	public static function array_string(array $data, string $key): string {
		return self::as_string($data[$key] ?? '');
	}

	/**
	 * Read a wp-config constant when defined and non-empty.
	 */
	public static function configured_constant(string $name): string {
		if (!defined($name)) {
			return '';
		}
		$value = constant($name);
		return is_string($value) && $value !== '' ? $value : '';
	}

	/**
	 * @param array<string, mixed> $data Payload for wp_remote_post body.
	 */
	public static function json_encode_body(array $data): string {
		$json = wp_json_encode($data);
		return is_string($json) && $json !== '' ? $json : '{}';
	}

	/**
	 * Read plugin update transient response map (WordPress stdClass).
	 *
	 * @param object $transient Update transient object.
	 * @return array<string, object>
	 */
	public static function transient_response_map($transient): array {
		$vars = get_object_vars($transient);
		if (!isset($vars['response']) || !is_array($vars['response'])) {
			return array();
		}
		/** @var array<string, object> $map */
		$map = $vars['response'];
		return $map;
	}

	/**
	 * Write plugin update transient response map (WordPress stdClass).
	 *
	 * @param object               $transient Update transient object.
	 * @param array<string, object> $map      Response entries.
	 */
	public static function set_transient_response_map($transient, array $map): void {
		$transient->response = $map; // @phpstan-ignore-line WP update transient uses dynamic stdClass properties.
	}
}
