<?php
/**
 * Shared helpers for premium classes (PHPStan-friendly).
 *
 * @package Toplist_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Small typed utilities for license/updater code paths.
 */
final class Toplist_Block_Util {

	/**
	 * Cast a value to string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function as_string( $value ): string {
		if ( is_string( $value ) ) {
			return $value;
		}
		if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) ) {
			return (string) $value;
		}
		return '';
	}

	/**
	 * Read a string value from an array by key.
	 *
	 * @param array<string, mixed> $data Source array.
	 * @param string               $key  Array key.
	 * @return string
	 */
	public static function array_string( array $data, string $key ): string {
		return self::as_string( $data[ $key ] ?? '' );
	}

	/**
	 * Read a wp-config constant when defined and non-empty.
	 *
	 * @param string $name Constant name.
	 * @return string
	 */
	public static function configured_constant( string $name ): string {
		if ( ! defined( $name ) ) {
			return '';
		}
		$value = constant( $name );
		return is_string( $value ) && '' !== $value ? $value : '';
	}

	/**
	 * Encode data as JSON for wp_remote_post body.
	 *
	 * @param array<string, mixed> $data Payload for wp_remote_post body.
	 * @return string
	 */
	public static function json_encode_body( array $data ): string {
		$json = wp_json_encode( $data );
		return is_string( $json ) && '' !== $json ? $json : '{}';
	}

	/**
	 * Normalize a hostname for license domain checks (lowercase, no leading www.).
	 *
	 * @param string $host Raw hostname.
	 * @return string
	 */
	public static function normalize_domain( string $host ): string {
		$host = strtolower( trim( $host ) );
		return (string) preg_replace( '#^www\\.#', '', $host );
	}

	/**
	 * Read plugin update transient response map (WordPress stdClass).
	 *
	 * @param object $transient Update transient object.
	 * @return array<string, object>
	 */
	public static function transient_response_map( $transient ): array {
		$vars = get_object_vars( $transient );
		if ( ! isset( $vars['response'] ) || ! is_array( $vars['response'] ) ) {
			return array();
		}
		/* @var array<string, object> $map */
		$map = $vars['response'];
		return $map;
	}

	/**
	 * Write plugin update transient response map (WordPress stdClass).
	 *
	 * @param object                $transient Update transient object.
	 * @param array<string, object> $map      Response entries.
	 * @return void
	 */
	public static function set_transient_response_map( $transient, array $map ): void {
		$transient->response = $map; // @phpstan-ignore-line WP update transient uses dynamic stdClass properties.
	}
}
