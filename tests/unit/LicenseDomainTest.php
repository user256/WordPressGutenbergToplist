<?php

use PHPUnit\Framework\TestCase;

final class LicenseDomainTest extends TestCase {
	public function test_normalize_domain_strips_www(): void {
		$this->assertSame('example.com', Toplist_Block_Util::normalize_domain('www.Example.COM'));
	}

	public function test_normalize_domain_handles_subdomains(): void {
		$this->assertSame('staging.example.com', Toplist_Block_Util::normalize_domain('staging.example.com'));
	}

	public function test_allowed_domains_from_signed_cache(): void {
		$data = array(
			'status' => 'active',
			'allowed_domains' => array('127.0.0.1', 'staging.example.com'),
		);
		$json = wp_json_encode($data);
		$this->assertIsString($json);
		update_option(
			Toplist_Block_License::CACHE_OPTION,
			array(
				'payload' => $json,
				'sig' => hash_hmac('sha256', $json, wp_salt('auth')),
			)
		);

		$allowed = Toplist_Block_License::get_allowed_domains();
		$this->assertContains('127.0.0.1', $allowed);
		$this->assertContains('staging.example.com', $allowed);
	}
}
