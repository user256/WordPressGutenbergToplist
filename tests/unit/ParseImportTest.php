<?php

use PHPUnit\Framework\TestCase;

final class ParseImportTest extends TestCase {
	private string $root;

	protected function setUp(): void {
		$this->root = dirname(__DIR__, 2);
	}

	public function test_decode_external_toplist_json_fixture_row_count(): void {
		$raw = (string) file_get_contents($this->root . '/toplist.json');
		$result = toplist_decode_external_toplist_json($raw);

		$this->assertSame('', $result['error']);
		$this->assertCount(10, $result['items']);
	}

	public function test_external_json_round_trip_preserves_key_fields(): void {
		$raw = (string) file_get_contents($this->root . '/toplist.json');
		$decoded = toplist_decode_external_toplist_json($raw);
		$source = $decoded['items'][0];
		$rows = toplist_items_to_external_json_rows($decoded['items']);
		$first = $rows[0];

		$this->assertNotEmpty($first['name'] ?? '');
		$this->assertSame(toplist_clean_text($source['offer'] ?? ''), $first['bonus'] ?? '');
		$this->assertNotEmpty($first['bonus_link'] ?? $first['visit_link'] ?? '');
	}

	public function test_csv_fixture_matches_json_row_count(): void {
		$json = toplist_decode_external_toplist_json((string) file_get_contents($this->root . '/toplist-229.json'));
		$csv_items = $this->parse_csv_fixture_to_items($this->root . '/toplist-229.csv');

		$this->assertCount(count($json['items']), $csv_items);
		$this->assertCount(10, $csv_items);
	}

	public function test_wide_pipe_row_does_not_truncate_columns(): void {
		$fields = toplist_supported_fields();
		$parts = array();
		foreach ($fields as $i => $field) {
			$parts[] = $field . '-value-' . $i;
		}
		$line = implode('|', $parts);
		$parsed = toplist_parse_lines_to_items($line, array());

		$this->assertCount(1, $parsed['items']);
		$item = $parsed['items'][0];
		$this->assertSame('operator-value-0', $item['operator']);
		$this->assertSame('withdrawals-value-19', $item['withdrawals'][0] ?? $item['withdrawals']);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function parse_csv_fixture_to_items(string $path): array {
		$handle = fopen($path, 'r');
		$this->assertIsResource($handle);

		$header_row = fgetcsv($handle);
		$this->assertIsArray($header_row);

		$normalized_headers = array();
		foreach ($header_row as $header_cell) {
			$normalized_headers[] = toplist_normalize_csv_header($header_cell);
		}

		$fields = toplist_supported_fields();
		$columns = $normalized_headers;
		$header_for_lines = array_values(array_filter($normalized_headers));
		$lines = array(implode('|', $header_for_lines));

		while (($csv_row = fgetcsv($handle)) !== false) {
			$values_by_field = array_fill_keys($fields, '');
			for ($i = 0; $i < count($columns); $i += 1) {
				$field = $columns[$i] ?? '';
				if ($field === '' || !in_array($field, $fields, true)) {
					continue;
				}
				$values_by_field[$field] = trim((string) ($csv_row[$i] ?? ''));
			}
			$line_parts = array();
			foreach ($header_for_lines as $field) {
				$line_parts[] = $values_by_field[$field] ?? '';
			}
			$lines[] = implode('|', $line_parts);
		}
		fclose($handle);

		$content = implode("\n", $lines);
		$parsed = toplist_parse_lines_to_items($content, array());

		return $parsed['items'];
	}
}
