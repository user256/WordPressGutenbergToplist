<?php

use PHPUnit\Framework\TestCase;

/**
 * Base class for wp-env integration tests (ticket 603).
 */
abstract class Toplist_Block_IntegrationTestCase extends TestCase {
	private const PLUGIN_SLUG = 'toplist-block';

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if (!self::command_exists('node') || !self::command_exists('docker')) {
			self::markTestSkipped('Node and Docker are required for wp-env integration tests.');
		}

		if (!self::site_is_reachable()) {
			self::markTestSkipped('wp-env is not running. Start it with `npm run test:integration:setup`.');
		}

		self::wait_for_wp_ready();

		$installed = self::wp_cli('plugin list --field=name 2>&1 || true');
		if (strpos($installed, self::PLUGIN_SLUG) === false) {
			self::markTestSkipped(
				"Plugin '" . self::PLUGIN_SLUG . "' is not installed in wp-env. Run `npm run test:integration:setup`."
			);
		}

		self::wp_cli('plugin activate ' . self::PLUGIN_SLUG . ' || true');

		$active = self::wp_cli(
			'plugin is-active ' . self::PLUGIN_SLUG . ' && echo TB_ACTIVE || echo TB_INACTIVE'
		);
		if (strpos($active, 'TB_ACTIVE') === false) {
			self::markTestSkipped(
				"Plugin '" . self::PLUGIN_SLUG . "' could not be activated in wp-env; skipping integration run."
			);
		}
	}

	private static function wait_for_wp_ready(): void {
		for ($i = 0; $i < 15; $i++) {
			$out = self::wp_cli('option get siteurl 2>&1 || true');
			if (strpos($out, 'http') !== false && stripos($out, 'error') === false) {
				return;
			}
			usleep(1000000);
		}
	}

	protected static function site_url(): string {
		$value = getenv('TOPLIST_WP_ENV_URL');
		return is_string($value) && $value !== '' ? rtrim($value, '/') : 'http://localhost:8888';
	}

	protected static function plugin_slug(): string {
		return self::PLUGIN_SLUG;
	}

	protected static function wp_eval(string $php): string {
		return self::wp_cli('eval ' . escapeshellarg($php));
	}

	protected static function wp_cli(string $command): string {
		$cmd = sprintf(
			'%s run cli wp %s 2>&1',
			escapeshellcmd(self::wp_env_bin()),
			$command
		);
		exec($cmd, $output, $code);
		$text = implode("\n", $output);
		if ($code !== 0) {
			throw new RuntimeException("wp-env command failed:\n" . $text);
		}
		return self::clean_wp_env_output($output);
	}

	private static function wp_env_bin(): string {
		$value = getenv('TOPLIST_WP_ENV_BIN');
		return is_string($value) && $value !== '' ? $value : 'npx wp-env';
	}

	private static function site_is_reachable(): bool {
		$ctx = stream_context_create(
			array(
				'http' => array('timeout' => 1.5, 'ignore_errors' => true),
			)
		);
		$headers = @get_headers(self::site_url(), false, $ctx);
		return is_array($headers) && $headers !== array();
	}

	private static function command_exists(string $command): bool {
		$output = array();
		$code   = 0;
		exec('command -v ' . escapeshellarg($command) . ' >/dev/null 2>&1', $output, $code);
		return $code === 0;
	}

	/**
	 * @param array<int, string> $output
	 */
	private static function clean_wp_env_output(array $output): string {
		$text = implode("\n", $output);
		$text = preg_replace('/ℹ Starting.*$/m', '', $text);
		$text = preg_replace('/✔ Ran.*$/m', '', $text);

		$filtered = array();
		foreach (preg_split('/\R/', (string) $text) as $line) {
			$trimmed = trim($line);
			if ($trimmed === '') {
				continue;
			}
			if (
				str_starts_with($trimmed, 'Notice:')
				|| str_starts_with($trimmed, 'Warning:')
				|| str_starts_with($trimmed, 'Please see ')
				|| str_starts_with($trimmed, '(This message was added')
			) {
				continue;
			}
			$filtered[] = $trimmed;
		}

		return trim(implode("\n", $filtered));
	}
}
