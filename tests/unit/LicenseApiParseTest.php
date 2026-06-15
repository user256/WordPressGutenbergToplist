<?php

use PHPUnit\Framework\TestCase;

final class LicenseApiParseTest extends TestCase {
	public function test_api_success_data_standard_shape(): void {
		$decoded = array(
			'success' => true,
			'data' => array(
				'valid' => true,
				'status' => 'active',
			),
		);

		$data = Toplist_Block_License::api_success_data($decoded);

		$this->assertIsArray($data);
		$this->assertSame('active', $data['status']);
	}

	public function test_api_success_data_legacy_error_false_shape(): void {
		$decoded = array(
			'error' => false,
			'data' => array(
				'status' => 'grace',
			),
		);

		$data = Toplist_Block_License::api_success_data($decoded);

		$this->assertIsArray($data);
		$this->assertSame('grace', $data['status']);
	}

	public function test_api_success_data_returns_null_for_error_payload(): void {
		$decoded = array(
			'success' => false,
			'error' => array(
				'code' => 'license_invalid',
				'message' => 'No license found.',
			),
		);

		$this->assertNull(Toplist_Block_License::api_success_data($decoded));
	}
}
