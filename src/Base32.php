<?php

declare(strict_types=1);

namespace CUK;

class Base32
{
    private const CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function decode(string $data): string
    {
        $data = strtoupper($data);
        $data = str_replace('=', '', $data);
        $result = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $val = strpos(self::CHARS, $data[$i]);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $result;
    }

    public function encode(string $data): string
    {
        $result = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $buffer = ($buffer << 8) | ord($data[$i]);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $result .= self::CHARS[($buffer >> $bitsLeft) & 0x1F];
            }
        }

        if ($bitsLeft > 0) {
            $result .= self::CHARS[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $result;
    }
}
