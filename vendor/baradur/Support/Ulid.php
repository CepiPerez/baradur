<?php

/*
 * This file is part of the ULID package.
 *
 * (c) Robin van der Vleuten <robin@webstronauts.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


class Ulid
{
    public static $ENCODING_CHARS = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    public static $ENCODING_LENGTH = 32;

    public static $TIME_MAX = 281474976710655;
    public static $TIME_LENGTH = 10;

    public static $RANDOM_LENGTH = 16;

    /**
     * @var int
     */
    private static $lastGenTime = 0;

    /**
     * @var array
     */
    private static $lastRandChars = array();

    /**
     * @var string
     */
    private $time;

    /**
     * @var string
     */
    private $randomness;

    /**
     * @var bool
     */
    private $lowercase;

    private function __construct($time, $randomness, $lowercase = true)
    {
        $this->time = $time;
        $this->randomness = $randomness;
        $this->lowercase = $lowercase;
    }

    public static function isValid($ulid)
    {
        return (bool) preg_match('{^[0123456789ABCDEFGHJKMNPQRSTVWXYZ]{26}$}Di', $ulid);
    }

    public static function fromString($value, $lowercase = true)
    {
        if (strlen($value) !== self::$TIME_LENGTH + self::$RANDOM_LENGTH) {
            throw new Exception('Invalid ULID string (wrong length): ' . $value);
        }

        // Convert to uppercase for regex. Doesn't matter for output later, that is determined by $lowercase.
        $value = strtoupper($value);

        if (!preg_match(sprintf('!^[%s]{%d}$!', self::$ENCODING_CHARS, self::$TIME_LENGTH + self::$RANDOM_LENGTH), $value)) {
            throw new Exception('Invalid ULID string (wrong characters): ' . $value);
        }

        $res = new Ulid(substr($value, 0, self::$TIME_LENGTH), substr($value, self::$TIME_LENGTH, self::$RANDOM_LENGTH), $lowercase);
        return $res->__toString();
    }

    /**
     * Create a ULID using the given timestamp.
     * @param int $milliseconds Number of milliseconds since the UNIX epoch for which to generate this ULID.
     * @param bool $lowercase True to output lowercase ULIDs.
     * @return Ulid Returns a ULID object for the given microsecond time.
     */
    public static function fromTimestamp($milliseconds, $lowercase = true)
    {
        $duplicateTime = $milliseconds === self::$lastGenTime;

        self::$lastGenTime = $milliseconds;

        $timeChars = '';
        $randChars = '';

        $encodingChars = self::$ENCODING_CHARS;

        for ($i = self::$TIME_LENGTH - 1; $i >= 0; $i--) {
            $mod = $milliseconds % self::$ENCODING_LENGTH;
            $timeChars = $encodingChars[$mod].$timeChars;
            $milliseconds = ($milliseconds - $mod) / self::$ENCODING_LENGTH;
        }

        if (!$duplicateTime) {
            for ($i = 0; $i < self::$RANDOM_LENGTH; $i++) {
                self::$lastRandChars[$i] = random_int(0, 31);
            }
        } else {
            // If the timestamp hasn't changed since last push,
            // use the same random number, except incremented by 1.
            for ($i = self::$RANDOM_LENGTH - 1; $i >= 0 && self::$lastRandChars[$i] === 31; $i--) {
                self::$lastRandChars[$i] = 0;
            }

            self::$lastRandChars[$i]++;
        }

        for ($i = 0; $i < self::$RANDOM_LENGTH; $i++) {
            $randChars .= $encodingChars[self::$lastRandChars[$i]];
        }

        $res = new Ulid($timeChars, $randChars, $lowercase);
        return $res->__toString();
    }

    public static function generate($lowercase = true)
    {
        $now = (int) (microtime(true) * 1000);
        
        return self::fromTimestamp($now, $lowercase);
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getRandomness()
    {
        return $this->randomness;
    }

    public function isLowercase()
    {
        return $this->lowercase;
    }

    public function toTimestamp()
    {
        return $this->decodeTime($this->time);
    }

    public function __toString()
    {
        return ($value = $this->time . $this->randomness) && $this->lowercase ? strtolower($value) : strtoupper($value);
    }

    private function decodeTime($time)
    {
        $timeChars = str_split(strrev($time));
        $carry = 0;

        foreach ($timeChars as $index => $char) {
            if (($encodingIndex = strripos(self::$ENCODING_CHARS, $char)) === false) {
                throw new Exception('Invalid ULID character: ' . $char);
            }

            $carry += ($encodingIndex * pow(self::$ENCODING_LENGTH, $index));
        }

        if ($carry > self::$TIME_MAX) {
            throw new Exception('Invalid ULID string: timestamp too large');
        }

        return $carry;
    }
}