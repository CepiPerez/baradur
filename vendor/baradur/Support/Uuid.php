<?php

/*
 * This code is part of Symfony - Polyfill Uuid
 * https://github.com/symfony/polyfill-uuid/blob/main/Uuid.php
 *
 */

class Uuid
{
    private static $TIME_OFFSET_BIN = "\x01\xb2\x1d\xd2\x13\x81\x40\x00";
    const TIME_OFFSET_INT = 0x01B21DD213814000;
    const TIME_OFFSET_BIN = "\x01\xb2\x1d\xd2\x13\x81\x40\x00";
    const TIME_OFFSET_COM = "\xfe\x4d\xe2\x2d\xec\x7e\xc0\x00";

    private $uuid;

    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    public static function isValid($uuid)
    {
        //return (bool) preg_match('{^[0-9a-f]{8}(?:-[0-9a-f]{4}-4[0-9a-f]{3}-[0-9a-f]{4})-[0-9a-f]{12}$}Di', $uuid);
        return (bool) preg_match('{^[0-9a-f]{8}(?:-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4})-[0-9a-f]{12}$}Di', $uuid);
    }

    private static function parse($uuid)
    {
        if (!preg_match('{^([0-9a-f]{8})-([0-9a-f]{4})-([0-9a-f])([0-9a-f]{3})-([0-9a-f]{4})-([0-9a-f]{12})$}Di', $uuid, $matches)) {
            return null;
        }

        return array(
            'time' => '0'.$matches[4].$matches[2].$matches[1],
            'version' => hexdec($matches[3]),
            'clock_seq' => hexdec($matches[5]),
            'node' => $matches[6],
        );
    }

    public static function uuid_time($uuid)
    {
        if (!self::isValid($uuid)) {
            throw new Exception('Invalid Uuid');
        }

        $parsed = self::parse($uuid);
        
        if ($parsed['version']!==1 && $parsed['version']!==2) {
            throw new Exception('This function only supports Uuid versions 1 and 2');
        }

        /* if (PHP_INT_SIZE >= 8) {
            return intdiv(hexdec($parsed['time']) - self::TIME_OFFSET_INT, 10000000);
        } */

        $time = str_pad(hex2bin($parsed['time']), 8, "\0", \STR_PAD_LEFT);
        $time = self::binaryAdd($time, self::TIME_OFFSET_COM);
        $time[0] = $time[0] & "\x7F";

        //dd($time);

        return (int) substr(self::toDecimal($time), 0, -7);
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

    public static function fromString($string)
    {
        if (!self::isValid($string)) {
            throw new Exception('Invalid UUID string');
        }

        return new self($string);
    }

    public function getDateTime() {
        return self::uuid_time($this->uuid);
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

    private static function toDecimal($bytes)
    {
        $digits = '';
        $bytes = array_values(unpack('C*', $bytes));

        while ($count = \count($bytes)) {
            $quotient = array();
            $remainder = 0;

            for ($i = 0; $i !== $count; ++$i) {
                $carry = $bytes[$i] + ($remainder << 8);
                $digit = (int) ($carry / 10);
                $remainder = $carry % 10;

                if ($digit || $quotient) {
                    $quotient[] = $digit;
                }
            }

            $digits = $remainder.$digits;
            $bytes = $quotient;
        }

        return $digits;
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