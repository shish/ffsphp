<?php

declare(strict_types=1);

namespace FFSPHP;

class VLQ
{
    public static function encode_vlq(int $value)
    {
        $res = "";
        $vlq = ($value < 0) ? ((-$value) << 1) + 1 : ($value << 1) + 0;
        do {
            $digit = $vlq & 0x1f;
            $vlq >>= 5;
            if ($vlq > 0) {
                $digit |= 0x20;
            }
            $res .= "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/"[$digit];
        } while ($vlq > 0);
        return $res;
    }

    public static function encode_vlq_array(array $values)
    {
        $res = "";
        foreach ($values as $value) {
            $res .= self::encode_vlq($value);
        }
        return $res;
    }

    public static function decode_vlq(string $vlq)
    {
        $res = 0;
        $shift = 0;
        for ($i = 0; $i < strlen($vlq); $i++) {
            $digit = strpos("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", $vlq[$i]);
            $res += ($digit & 0x1f) << $shift;
            $shift += 5;
            if (($digit & 0x20) == 0) {
                break;
            }
        }
        return ($res & 1) ? -($res >> 1) : ($res >> 1);
    }

    public static function decode_vlq_array(string $vlq)
    {
        $res = [];
        $shift = 0;
        $value = 0;
        for ($i = 0; $i < strlen($vlq); $i++) {
            $digit = strpos("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", $vlq[$i]);
            $value += ($digit & 0x1f) << $shift;
            $shift += 5;
            if (($digit & 0x20) == 0) {
                $res[] = ($value & 1) ? -($value >> 1) : ($value >> 1);
                $shift = 0;
                $value = 0;
            }
        }
        return $res;
    }
}
