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
}
