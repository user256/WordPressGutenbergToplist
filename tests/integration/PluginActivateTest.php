<?php

/**
 * Smoke test: premium plugin activates in wp-env.
 */
final class PluginActivateTest extends Toplist_Block_IntegrationTestCase {
	public function test_plugin_is_active(): void {
		$active = self::wp_cli('plugin is-active ' . self::plugin_slug() . ' && echo yes || echo no');
		$this->assertSame('yes', $active);
	}

	public function test_version_constant_is_defined(): void {
		$version = self::wp_eval('echo defined("TOPLIST_BLOCK_VERSION") ? TOPLIST_BLOCK_VERSION : "";');
		$this->assertNotSame('', $version);
	}

	public function test_block_type_is_registered(): void {
		$registered = self::wp_eval(
			<<<'PHP'
$registry = WP_Block_Type_Registry::get_instance();
echo $registry->is_registered('toplist/rankings') ? 'yes' : 'no';
PHP
		);
		$this->assertSame('yes', $registered);
	}
}
