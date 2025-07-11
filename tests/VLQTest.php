<?php

declare(strict_types=1);

require "vendor/autoload.php";

use FFSPHP\VLQ;
use PHPUnit\Framework\TestCase;

class VLQTest extends TestCase
{
    public function test_decode_vlq_array(): void
    {
        $this->assertEquals(VLQ::decode_vlq_array("IAAM"), [4,0,0,6]);
    }

    public function test_encode_vlq_array(): void
    {
        $this->assertEquals(VLQ::encode_vlq_array([4,0,0,6]), "IAAM");
    }

    public function test_roundtrip_string(): void
    {
        $original = 12345;
        $encoded = VLQ::encode_vlq($original);
        $decoded = VLQ::decode_vlq($encoded);
        $this->assertEquals($original, $decoded);
    }

    public function test_roundtrip_array(): void
    {
        $original = [4, 0, 0, 6];
        $encoded = VLQ::encode_vlq_array($original);
        $decoded = VLQ::decode_vlq_array($encoded);
        $this->assertEquals($original, $decoded);
    }
}
