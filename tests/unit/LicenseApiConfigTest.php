<?php

use PHPUnit\Framework\TestCase;

final class LicenseApiConfigTest extends TestCase {
	protected function setUp(): void {
		delete_option(Toplist_Block_License::OPTION_API_URL);
		delete_option(Toplist_Block_License::OPTION_API_KEY);
	}

	protected function tearDown(): void {
		delete_option(Toplist_Block_License::OPTION_API_URL);
		delete_option(Toplist_Block_License::OPTION_API_KEY);
	}

	public function test_api_url_reads_stored_option(): void {
		update_option(
			Toplist_Block_License::OPTION_API_URL,
			'http://127.0.0.1:9080/api/v1/toplist-block/validate'
		);

		$this->assertSame(
			'http://127.0.0.1:9080/api/v1/toplist-block/validate',
			Toplist_Block_License::api_url()
		);
	}

	public function test_api_key_round_trips_encrypted_option(): void {
		Toplist_Block_License::save_api_settings('', 'tbl_dev_test_key_12345');

		$this->assertSame('tbl_dev_test_key_12345', Toplist_Block_License::api_key());
	}

	public function test_save_api_settings_preserves_key_when_blank(): void {
		Toplist_Block_License::save_api_settings('', 'tbl_dev_keep_me');
		Toplist_Block_License::save_api_settings('http://example.test/validate', '');

		$this->assertSame('http://example.test/validate', Toplist_Block_License::api_url());
		$this->assertSame('tbl_dev_keep_me', Toplist_Block_License::api_key());
	}
}
