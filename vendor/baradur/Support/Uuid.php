<?php

/*
 * This code is part of Symfony - Polyfill Uuid
 * https://github.com/symfony/polyfill-uuid/blob/main/Uuid.php
 *
 */

class Uuid
{
    private static $TIME_OFFSET_BIN = "\x01\xb2\x1d\xd2\x13\x81\x40\x00";


    public static function isValid($uuid)
    {
        return (bool) preg_match('{^[0-9a-f]{8}(?:-[0-9a-f]{4}-4[0-9a-f]{3}-[0-9a-f]{4})-[0-9a-f]{12}$}Di', $uuid);
    }


    public static function uuid_generate_random()
    {
        $uuid = bin2hex(random_bytes(16));

        return sprintf('%08s-%04s-4%03s-%04x-%012s',
            // 32 bits for "time_low"
            substr($uuid, 0, 8),
            // 16 bits for "time_mid"
            substr($uuid, 8, 4),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            substr($uuid, 13, 3),
            // 16 bits:
            // * 8 bits for "clk_seq_hi_res",
            // * 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            hexdec(substr($uuid, 16, 4)) & 0x3FFF | 0x8000,
            // 48 bits for "node"
            substr($uuid, 20, 12)
        );
    }

    public static function uuid_generate_time()
    {
        $time = microtime(false);
        $time = substr($time, 11).substr($time, 2, 7);

            $time = str_pad(self::toBinary($time), 8, "\0", \STR_PAD_LEFT);
            $time = self::binaryAdd($time, self::$TIME_OFFSET_BIN);
            $time = bin2hex($time);

        // https://tools.ietf.org/html/rfc4122#section-4.1.5
        // We are using a random data for the sake of simplicity: since we are
        // not able to get a super precise timeOfDay as a unique sequence
        $clockSeq = random_int(0, 0x3FFF);

        static $node;
        if (null === $node) {
            $node = sprintf('%06x%06x',
                random_int(0, 0xFFFFFF) | 0x010000,
                random_int(0, 0xFFFFFF)
            );
        }

        return sprintf('%08s-%04s-1%03s-%04x-%012s',
            // 32 bits for "time_low"
            substr($time, -8),

            // 16 bits for "time_mid"
            substr($time, -12, 4),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 1
            substr($time, -15, 3),

            // 16 bits:
            // * 8 bits for "clk_seq_hi_res",
            // * 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            $clockSeq | 0x8000,

            // 48 bits for "node"
            $node
        );
    }

    private static function toBinary($digits)
    {
        $bytes = '';
        $count = \strlen($digits);

        while ($count) {
            $quotient = array();
            $remainder = 0;

            for ($i = 0; $i !== $count; ++$i) {
                $carry = $digits[$i] + $remainder * 10;
                $digit = $carry >> 8;
                $remainder = $carry & 0xFF;

                if ($digit || $quotient) {
                    $quotient[] = $digit;
                }
            }

            $bytes = \chr($remainder).$bytes;
            $count = \count($digits = $quotient);
        }

        return $bytes;
    }

    private static function binaryAdd($a, $b)
    {
        $sum = 0;
        for ($i = 7; 0 <= $i; --$i) {
            $sum += \ord($a[$i]) + \ord($b[$i]);
            $a[$i] = \chr($sum & 0xFF);
            $sum >>= 8;
        }

        return $a;
    }

}