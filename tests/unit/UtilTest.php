<?php

use PHPUnit\Framework\TestCase;

final class UtilTest extends TestCase {
	public function test_as_string_handles_scalars(): void {
		$this->assertSame('hello', Toplist_Block_Util::as_string('hello'));
		$this->assertSame('42', Toplist_Block_Util::as_string(42));
		$this->assertSame('1', Toplist_Block_Util::as_string(true));
		$this->assertSame('', Toplist_Block_Util::as_string(null));
		$this->assertSame('', Toplist_Block_Util::as_string(array('x')));
	}

	public function test_array_string_reads_key(): void {
		$data = array('status' => 'active', 'count' => 3);
		$this->assertSame('active', Toplist_Block_Util::array_string($data, 'status'));
		$this->assertSame('3', Toplist_Block_Util::array_string($data, 'count'));
		$this->assertSame('', Toplist_Block_Util::array_string($data, 'missing'));
	}

	public function test_json_encode_body_never_returns_empty(): void {
		$body = Toplist_Block_Util::json_encode_body(array('domain' => '127.0.0.1'));
		$this->assertNotSame('', $body);
		$this->assertStringContainsString('127.0.0.1', $body);
	}
}
