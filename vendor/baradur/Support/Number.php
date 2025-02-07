<?php

class Number
{
    protected static $locale = 'en';

    //public static $localeconv = null;

    /**
     * Format the given number according to the current locale.
     *
     * @param  int|float  $number
     * @param  int|null  $precision
     * @param  int|null  $maxPrecision
     * @param  string|null  $locale
     * @return string|false
     */
    public static function format($number, $precision = null, $maxPrecision = null, $locale = null)
    {
        //self::ensureIntlExtensionIsInstalled(__METHOD__);

        $formatter = new NumberFormatter($locale ? $locale : self::$locale, NumberFormatter::DECIMAL);

        /* if (! is_null($maxPrecision)) {
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxPrecision);
        } else */if (! is_null($precision)) {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);
        }

        return $formatter->format($number);
    }

    /**
     * Spell out the given number in the given locale.
     *
     * @param  int|float  $number
     * @param  string|null  $locale
     * @param  int|null  $after
     * @param  int|null  $until
     * @return string
     */
    public static function spell($number, $locale = null, $after = null, $until = null)
    {
        /* self::ensureIntlExtensionIsInstalled(__METHOD__);

        if (! is_null($after) && $number <= $after) {
            return self::format($number, null, null, $locale);
        }

        if (! is_null($until) && $number >= $until) {
            return self::format($number, null, null, $locale);
        }

        $formatter = new NumberFormatter($locale ? $locale : self::$locale, NumberFormatter::SPELLOUT);

        return $formatter->format($number); */

        return self::convert($number, null, -1, $locale ? $locale : self::$locale);
        
    }

    private static $decimalsAsFraction = false;
	private static $templateFraction = "(%s/%s)";

    private static function convert($number, $units = null, $level = -1, $locale = 'en')
	{
        self::ensureIntlExtensionIsInstalled(__METHOD__);
        
        $filepath = _DIR_.'lang/'.(self::$locale).'/numbers.php';
        
        if (file_exists($filepath)) {
            $dict = CoreLoader::loadConfigFile($filepath);
        } else {
            $dict = CoreLoader::loadConfigFile(_DIR_.'lang/en/numbers.php');
        }

		++$level;
		$dictionary  = $dict['spell'];
		$hyphen      = $dictionary['union'];
		$conjunction = ' and ';
		$separator   = ' ';
		$negative    = 'negative ';
		$decimal     = ' point ';


		// fix common typo [,] instead of dot
		$number = str_replace(',', '.', $number);

		if (!is_numeric($number)) {
			return false;
		}

		if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
			// overflow
			throw new \Exception('Invalid number range - value must be between ' . PHP_INT_MAX . ' and ' . PHP_INT_MAX.'.');
		}

		if ($number < 0) {
			return $negative . self::convert(abs($number));
		}

		$string = $fraction = '';

		if (strpos($number, '.') !== false) {
			list($number, $fraction) = explode('.', $number);
		}

		switch (true) {
			case $number < $dictionary['one_by_one_until']:
				$dict = $dictionary[$number];
				$string = $dict;
				break;
			case $number < 100:
				$tens   = ((int) ($number / 10)) * 10;
				$units  = $number % 10;
				$string = ' '.$dictionary[$tens];
				if ($units) {
					$string .= $hyphen . $dictionary[$units];
				}
				break;
			case $number < 1000:
				$hundreds  = floor($number / 100);
				$remainder = $number % 100;
				$string = $dictionary[$hundreds] .' '. $dictionary[100];
				if ($remainder) {
					$string .= self::convert($remainder, null, $level);
				}
				break;
			default:
				$baseUnit = pow(1000, floor(log($number, 1000)));
				$numBaseUnits = (int) ($number / $baseUnit);
				$remainder = $number - ($baseUnit * $numBaseUnits);
				$append = $dictionary[$baseUnit];
				$string = self::convert($numBaseUnits, $baseUnit, $level) . ' ' . $append;
				if ($remainder) {
					$string .= $remainder < 100 ? $conjunction : $separator;
					$string .= self::convert($remainder, null, $level);
				}
				break;
		}

		if ('' !== trim($fraction) && is_numeric($fraction)) {
			if(self::$decimalsAsFraction){
				$fraction = trim($fraction); // (!) keep zeroes on left and right
				$base = pow(10, strlen($fraction));
				$string .= " ".sprintf(self::$templateFraction, intval($fraction), $base); // ie. 99/100
			}else{
				$string .= $decimal;
				if('0' !== substr($fraction, 0, 1) && intval($fraction) < 1000){
					// up to 3 decimals and not zeroes on left - full convert
					$string .= trim(self::convert($fraction));
				}else{
					// 3+ decimals or zeroes on left - spell out single digits
					$words = array();
					foreach (str_split((string) $fraction) as $number) {
						$words[] = $dictionary[$number];
					}
					$string .= implode(' ', $words);
				}
			}
		}

		return $string;
	}

    /**
     * Convert the given number to ordinal form.
     *
     * @param  int|float  $number
     * @param  string|null  $locale
     * @return string
     */
    public static function ordinal($number, $locale = null)
    {
        //self::ensureIntlExtensionIsInstalled(__METHOD__);
        //$formatter = new NumberFormatter($locale ? $locale : self::$locale, NumberFormatter::ORDINAL);
        //return $formatter->format($number);

        $filepath = _DIR_.'lang/'.($locale ? $locale : self::$locale).'/numbers.php';
        
        if (file_exists($filepath)) {
            $suffix = CoreLoader::loadConfigFile($filepath);
        } else {
            $suffix = CoreLoader::loadConfigFile(_DIR_.'lang/en/numbers.php');
        }

        $suffix = $suffix['ordinals'];
        
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            $ordinal = $number . $suffix[0];
        }
        else {
            $ordinal = $number . $suffix[$number % 10];
        }

        return $ordinal;
    }

    /**
     * Convert the given number to its percentage equivalent.
     *
     * @param  int|float  $number
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  string|null  $locale
     * @return string|false
     */
    public static function percentage($number, $precision = 0, $maxPrecision = null, $locale = null)
    {
        return self::format($number, $precision, $maxPrecision, $locale) . '%';
    }

    /**
     * Convert the given number to its currency equivalent.
     *
     * @param  int|float  $number
     * @param  string  $in
     * @param  string|null  $locale
     * @return string|false
     */
    public static function currency( $number, $in = null, $locale = null)
    {
        //self::ensureIntlExtensionIsInstalled(__METHOD__);

        $formatter = new NumberFormatter($locale ? $locale : self::$locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($number, $in);
    }

    /**
     * Convert the given number to its file size equivalent.
     *
     * @param  int|float  $bytes
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    public static function fileSize($bytes, $precision = 0, $maxPrecision = null)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        for ($i = 0; ($bytes / 1024) > 0.9 && ($i < count($units) - 1); $i++) {
            $bytes /= 1024;
        }

        return sprintf('%s %s', self::format($bytes, $precision, $maxPrecision), $units[$i]);
    }

    /**
     * Convert the number to its human-readable equivalent.
     *
     * @param  int|float  $number
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return bool|string
     */
    public static function abbreviate($number, $precision = 0, $maxPrecision = null)
    {
        return self::forHumans($number, $precision, $maxPrecision, true);
    }

    /**
     * Convert the number to its human-readable equivalent.
     *
     * @param  int|float  $number
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  bool  $abbreviate
     * @return bool|string
     */
    public static function forHumans($number, $precision = 0, $maxPrecision = null, $abbreviate = false)
    {
        return self::summarize($number, $precision, $maxPrecision, $abbreviate ? array(
            3 => 'K',
            6 => 'M',
            9 => 'B',
            12 => 'T',
            15 => 'Q'
        ) : array(
            3 => ' thousand',
            6 => ' million',
            9 => ' billion',
            12 => ' trillion',
            15 => ' quadrillion'
        ));
    }

    /**
     * Convert the number to its human-readable equivalent.
     *
     * @param  int|float  $number
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  array  $units
     * @return string|false
     */
    protected static function summarize($number, $precision = 0, $maxPrecision = null, $units = array())
    {
        if (empty($units)) {
            $units = array(
                3 => 'K',
                6 => 'M',
                9 => 'B',
                12 => 'T',
                15 => 'Q'
            );
        }

        switch (true) {
            case floatval($number) === 0.0:
                return $precision > 0 ? self::format(0, $precision, $maxPrecision) : '0';
            case $number < 0:
                return sprintf('-%s', self::summarize(abs($number), $precision, $maxPrecision, $units));
            case $number >= 1e15:
                return sprintf('%s'.end($units), self::summarize($number / 1e15, $precision, $maxPrecision, $units));
        }

        $numberExponent = floor(log10($number));
        $displayExponent = $numberExponent - ($numberExponent % 3);
        $number /= pow(10, $displayExponent);

        return trim(sprintf('%s%s', self::format($number, $precision, $maxPrecision), $units[$displayExponent] ? $units[$displayExponent] : ''));
    }

    /**
     * Clamp the given number between the given minimum and maximum.
     *
     * @param  int|float  $number
     * @param  int|float  $min
     * @param  int|float  $max
     * @return int|float
     */
    public static function clamp($number, $min, $max)
    {
        return min(max($number, $min), $max);
    }

    /**
     * Execute the given callback using the given locale.
     *
     * @param  string  $locale
     * @param  callable  $callback
     * @return mixed
     */
    /* public static function withLocale($locale, $callback)
    {
        $previousLocale = self::$locale;

        self::useLocale($locale);

        return tap($callback(), fn () => self::useLocale($previousLocale));
    } */

    /**
     * Set the default locale.
     *
     * @param  string  $locale
     * @return void
     */
    public static function useLocale($locale)
    {
        self::$locale = $locale;
    }


    /**
     * Ensure the "intl" PHP extension is installed.
     *
     * @return void
     */
    protected static function ensureIntlExtensionIsInstalled($method)
    {
        if (! extension_loaded('intl')) {
            $method = str_replace('Number::', '', $method);
            throw new RuntimeException('The "intl" PHP extension is required to use the ['.$method.'] method.');
        }
    }



}