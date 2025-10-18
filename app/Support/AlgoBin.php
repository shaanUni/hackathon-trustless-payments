<?php
namespace App\Support;

use ParagonIE\ConstantTime\Base32;

class AlgoBin
{
    public static function addrToBytes(string $addr): string
    {
        $raw = Base32::decodeUpper($addr); // 32 pk + 4 checksum
        return substr($raw, 0, 32);
    }
}
