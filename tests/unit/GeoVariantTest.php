<?php

use PHPUnit\Framework\TestCase;

final class GeoVariantTest extends TestCase {
	public function test_picks_rows_matching_visitor_country(): void {
		$items = array(
			array('operator' => 'A', 'geo' => 'GB'),
			array('operator' => 'B', 'geo' => 'IE'),
			array('operator' => 'C', 'geo' => ''),
		);
		$result = toplist_geo_pick_items($items, 'GB', '');
		$this->assertCount(2, $result);
		$this->assertSame('A', $result[0]['operator']);
		$this->assertSame('C', $result[1]['operator']);
	}

	public function test_returns_all_when_no_geo_column_used(): void {
		$items = array(
			array('operator' => 'A'),
			array('operator' => 'B'),
		);
		$this->assertSame($items, toplist_geo_pick_items($items, 'GB', 'IE'));
	}
}
