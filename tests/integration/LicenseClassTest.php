<?php

/**
 * Premium license class is present and exposes portal integration hooks.
 */
final class LicenseClassTest extends Toplist_Block_IntegrationTestCase {
	public function test_license_class_loads(): void {
		$exists = self::wp_eval('echo class_exists("Toplist_Block_License") ? "yes" : "no";');
		$this->assertSame('yes', $exists);
	}

	public function test_license_api_url_constant_can_be_defined(): void {
		update_option(
			Toplist_Block_License::OPTION_API_URL,
			'http://127.0.0.1:9080/api/v1/toplist-block/validate'
		);
		$url = self::wp_eval('echo Toplist_Block_License::api_url();');
		$this->assertStringContainsString('/toplist-block/validate', $url);
	}

	public function test_validate_without_key_returns_unconfigured(): void {
		$status = self::wp_eval(
			<<<'PHP'
delete_option('toplist_block_license_key');
$result = Toplist_Block_License::validate('');
echo (string) ($result['status'] ?? '');
PHP
		);
		$this->assertSame('unconfigured', $status);
	}
}
