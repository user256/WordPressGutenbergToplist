<?php

use PHPUnit\Framework\TestCase;

final class UploadValidationTest extends TestCase {
	public function test_json_extension_requires_json_suffix(): void {
		$this->assertTrue(toplist_upload_filename_matches_extension('data.json', 'json'));
		$this->assertFalse(toplist_upload_filename_matches_extension('data.csv', 'json'));
		$this->assertFalse(toplist_upload_filename_matches_extension('data.json.exe', 'json'));
	}

	public function test_csv_extension_accepts_csv_mime_map(): void {
		$this->assertTrue(toplist_upload_filename_matches_extension('bulk.csv', 'csv'));
		$this->assertFalse(toplist_upload_filename_matches_extension('bulk.json', 'csv'));
	}

	public function test_pipe_content_survives_kses_post_stub(): void {
		$line = '1|Operator|<b>Bold</b>|<a href="https://example.com">Go</a>|https://example.com';
		$sanitized = wp_kses_post($line . "\n<script>alert(1)</script>");
		$this->assertStringContainsString('Operator', $sanitized);
		$this->assertStringContainsString('https://example.com', $sanitized);
		$this->assertStringNotContainsString('<script>', $sanitized);
	}
}
