<?php

use PHPUnit\Framework\TestCase;

final class ApiSyncTest extends TestCase {
	public function test_items_to_pipe_content_round_trip(): void {
		$items = array(
			array(
				'operator' => 'Acme',
				'product' => 'Casino',
				'offer' => '100% bonus',
				'href' => 'https://example.com',
			),
		);
		$content = toplist_items_to_pipe_content($items, array());
		$parsed = toplist_parse_lines_to_items($content, array());
		$this->assertCount(1, $parsed['items']);
		$this->assertSame('Acme', $parsed['items'][0]['operator']);
	}
}
