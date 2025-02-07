<?php

/*
 * Original file is part of the Symfony package.
 */

class NumberFormatter
{
    /* Format style constants */
    const PATTERN_DECIMAL = 0;
    const DECIMAL = 1;
    const CURRENCY = 2;
    const PERCENT = 3;
    const SCIENTIFIC = 4;
    const SPELLOUT = 5;
    const ORDINAL = 6;
    const DURATION = 7;
    const PATTERN_RULEBASED = 9;
    const IGNORE = 0;
    const DEFAULT_STYLE = 1;

    /* Format type constants */
    const TYPE_DEFAULT = 0;
    const TYPE_INT32 = 1;
    const TYPE_INT64 = 2;
    const TYPE_DOUBLE = 3;
    const TYPE_CURRENCY = 4;

    /* Numeric attribute constants */
    const PARSE_INT_ONLY = 0;
    const GROUPING_USED = 1;
    const DECIMAL_ALWAYS_SHOWN = 2;
    const MAX_INTEGER_DIGITS = 3;
    const MIN_INTEGER_DIGITS = 4;
    const INTEGER_DIGITS = 5;
    const MAX_FRACTION_DIGITS = 6;
    const MIN_FRACTION_DIGITS = 7;
    const FRACTION_DIGITS = 8;
    const MULTIPLIER = 9;
    const GROUPING_SIZE = 10;
    const ROUNDING_MODE = 11;
    const ROUNDING_INCREMENT = 12;
    const FORMAT_WIDTH = 13;
    const PADDING_POSITION = 14;
    const SECONDARY_GROUPING_SIZE = 15;
    const SIGNIFICANT_DIGITS_USED = 16;
    const MIN_SIGNIFICANT_DIGITS = 17;
    const MAX_SIGNIFICANT_DIGITS = 18;
    const LENIENT_PARSE = 19;

    /* Text attribute constants */
    const POSITIVE_PREFIX = 0;
    const POSITIVE_SUFFIX = 1;
    const NEGATIVE_PREFIX = 2;
    const NEGATIVE_SUFFIX = 3;
    const PADDING_CHARACTER = 4;
    const CURRENCY_CODE = 5;
    const DEFAULT_RULESET = 6;
    const PUBLIC_RULESETS = 7;

    /* Format symbol constants */
    const DECIMAL_SEPARATOR_SYMBOL = 0;
    const GROUPING_SEPARATOR_SYMBOL = 1;
    const PATTERN_SEPARATOR_SYMBOL = 2;
    const PERCENT_SYMBOL = 3;
    const ZERO_DIGIT_SYMBOL = 4;
    const DIGIT_SYMBOL = 5;
    const MINUS_SIGN_SYMBOL = 6;
    const PLUS_SIGN_SYMBOL = 7;
    const CURRENCY_SYMBOL = 8;
    const INTL_CURRENCY_SYMBOL = 9;
    const MONETARY_SEPARATOR_SYMBOL = 10;
    const EXPONENTIAL_SYMBOL = 11;
    const PERMILL_SYMBOL = 12;
    const PAD_ESCAPE_SYMBOL = 13;
    const INFINITY_SYMBOL = 14;
    const NAN_SYMBOL = 15;
    const SIGNIFICANT_DIGIT_SYMBOL = 16;
    const MONETARY_GROUPING_SEPARATOR_SYMBOL = 17;

    /* Rounding mode values used by NumberFormatter::setAttribute() with NumberFormatter::ROUNDING_MODE attribute */
    const ROUND_CEILING = 0;
    const ROUND_FLOOR = 1;
    const ROUND_DOWN = 2;
    const ROUND_UP = 3;
    const ROUND_HALFEVEN = 4;
    const ROUND_HALFDOWN = 5;
    const ROUND_HALFUP = 6;

    /* Pad position values used by NumberFormatter::setAttribute() with NumberFormatter::PADDING_POSITION attribute */
    const PAD_BEFORE_PREFIX = 0;
    const PAD_AFTER_PREFIX = 1;
    const PAD_BEFORE_SUFFIX = 2;
    const PAD_AFTER_SUFFIX = 3;

    protected $errorCode = 0; //Icu::U_ZERO_ERROR;
    protected $errorMessage = 'U_ZERO_ERROR';

    private $style;

    private $attributes = array(
        self::FRACTION_DIGITS => 2,
        self::GROUPING_USED => 0,
        self::ROUNDING_MODE => self::ROUND_HALFEVEN,
    );

    private $initializedAttributes = array();

    private static $supportedStyles = array(
        'CURRENCY' => self::CURRENCY,
        'DECIMAL' => self::DECIMAL,
    );

    private static $supportedAttributes = array(
        'FRACTION_DIGITS' => self::FRACTION_DIGITS,
        'GROUPING_USED' => self::GROUPING_USED,
        'ROUNDING_MODE' => self::ROUNDING_MODE,
    );

    private static $roundingModes = array(
        'ROUND_HALFEVEN' => self::ROUND_HALFEVEN,
        'ROUND_HALFDOWN' => self::ROUND_HALFDOWN,
        'ROUND_HALFUP' => self::ROUND_HALFUP,
        'ROUND_CEILING' => self::ROUND_CEILING,
        'ROUND_FLOOR' => self::ROUND_FLOOR,
        'ROUND_DOWN' => self::ROUND_DOWN,
        'ROUND_UP' => self::ROUND_UP,
    );

    private static $phpRoundingMap = array(
        self::ROUND_HALFDOWN => 2,
        self::ROUND_HALFEVEN => 3,
        self::ROUND_HALFUP => 1,
    );

    private static $customRoundingList = array(
        self::ROUND_CEILING => true,
        self::ROUND_FLOOR => true,
        self::ROUND_DOWN => true,
        self::ROUND_UP => true,
    );

    private static $int32Max = 2147483647;
    private static $int64Max = 9223372036854775807;

    private static $enSymbols = array(
        self::DECIMAL => array('.', ',', ';', '%', '0', '#', '-', '+', '¤', '¤¤', '.', 'E', '‰', '*', '∞', 'NaN', '@', ','),
        self::CURRENCY => array('.', ',', ';', '%', '0', '#', '-', '+', '¤', '¤¤', '.', 'E', '‰', '*', '∞', 'NaN', '@', ',')
    );

    private static $enTextAttributes = array(
        self::DECIMAL => array('', '', '-', '', ' ', 'XXX', ''),
        self::CURRENCY => array('¤', '', '-¤', '', ' ', 'XXX')
    );

    private $localeconv = null;

    
    public function __construct($locale = 'en', $style = null, $pattern = null)
    {
        /* if ('en' !== $locale && null !== $locale) {
            throw new MethodArgumentValueNotImplementedException(__METHOD__, 'locale', $locale, 'Only the locale "en" is supported');
        } */

        $filepath = _DIR_.'lang/'.$locale.'/numbers.php';
        
        if (file_exists($filepath)) {
            $this->localeconv = CoreLoader::loadConfigFile($filepath);
        } else {
            $this->localeconv = CoreLoader::loadConfigFile(_DIR_.'lang/en/numbers.php');
        }

        if (!in_array($style, self::$supportedStyles)) {
            $message = 'The available styles are: ' . implode(', ', array_keys(self::$supportedStyles));
            throw new MethodArgumentValueNotImplementedException(__METHOD__, 'style', $style, $message);
        }

        if (null !== $pattern) {
            throw new MethodArgumentNotImplementedException(__METHOD__, 'pattern');
        }

        $this->style = $style;
    }

    
    /* public static function create($locale = 'en', $style = null, $pattern = null)
    {
        return new GlobalNumberFormatter($locale, $style, $pattern);
    } */


    public function getAttribute($attribute)
    {
        //dd($attribute, $this->attributes[$attribute]);

        return $this->attributes[$attribute] ? $this->attributes[$attribute] : null;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function getLocale($type = Locale::ACTUAL_LOCALE)
    {
        return 'en';
    }

    public function getPattern()
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    public function getSymbol($symbol)
    {
        return array_key_exists($this->style, self::$enSymbols) 
            && array_key_exists($symbol, self::$enSymbols[$this->style]) 
                ? self::$enSymbols[$this->style][$symbol] : false;
    }

    public function getTextAttribute($attribute)
    {
        return array_key_exists($this->style, self::$enTextAttributes) 
            && array_key_exists($attribute, self::$enTextAttributes[$this->style]) 
                ? self::$enTextAttributes[$this->style][$attribute] : false;
    }

    private function isInitializedAttribute($attr)
    {
        return isset($this->initializedAttributes[$attr]);
    }

    private function isInvalidRoundingMode(int $value)
    {
        if (in_array($value, self::$roundingModes, true)) {
            return false;
        }

        return true;
    }

    private function normalizeGroupingUsedValue($value)
    {
        return (int) (bool) (int) $value;
    }

    private function normalizeFractionDigitsValue($value)
    {
        return (int) $value;
    }

    public function setAttribute($attribute, $value)
    {
        
        if (!in_array($attribute, self::$supportedAttributes)) {
            $message = "The available attributes are: " . implode(', ', array_keys(self::$supportedAttributes));
            throw new MethodArgumentValueNotImplementedException(__METHOD__, 'attribute', $value, $message);
        }

        if (self::$supportedAttributes['ROUNDING_MODE'] === $attribute && $this->isInvalidRoundingMode($value)) {
            $message = "The supported values for ROUNDING_MODE are: " . implode(', ', array_keys(self::$roundingModes));

            throw new MethodArgumentValueNotImplementedException(__METHOD__, 'attribute', $value, $message);
        }

        if (self::$supportedAttributes['GROUPING_USED'] === $attribute) {
            $value = $this->normalizeGroupingUsedValue($value);
        }


        if (self::$supportedAttributes['FRACTION_DIGITS'] === $attribute) {
            $value = $this->normalizeFractionDigitsValue($value);
            if ($value < 0) {
                // ignore negative values but do not raise an error
                return true;
            }
        }

        $this->attributes[$attribute] = $value;
        $this->initializedAttributes[$attribute] = true;

        return true;
    }

    public function setPattern(string $pattern)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    public function setSymbol(int $symbol, string $value)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    public function setTextAttribute(int $attribute, string $value)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }
    
    protected function resetError()
    {
        //Icu::setError(Icu::U_ZERO_ERROR);
        $this->errorCode = 0; //Icu::getErrorCode();
        $this->errorMessage = ""; //Icu::getErrorMessage();
    }


    public function format($num, $type = self::TYPE_DEFAULT)
    {
        // The original NumberFormatter does not support this format type
        if (self::TYPE_CURRENCY === $type) {
            if (\PHP_VERSION_ID >= 80000) {
                throw new ValueError("The format type must be a NumberFormatter::TYPE_* constant (".$type." given)");
            }

            trigger_error(__METHOD__.'(): Unsupported format type '.$type, \E_USER_WARNING);

            return false;
        }

        if (self::CURRENCY === $this->style) {
            throw new NotImplementedException(__METHOD__ . "() method does not support the formatting of currencies 
                (instance with CURRENCY style). " . NotImplementedException::INTL_INSTALL_MESSAGE);
        }

        // Only the default type is supported.
        if (self::TYPE_DEFAULT !== $type) {
            throw new MethodArgumentValueNotImplementedException(__METHOD__, 'type', $type, 'Only TYPE_DEFAULT is supported');
        }

        $fractionDigits = $this->getAttribute(self::FRACTION_DIGITS);

        $num = $this->round($num, $fractionDigits);
        $num = $this->formatNumber($num, $fractionDigits);

        // behave like the intl extension
        $this->resetError();

        return $num;
    }

    private function round($value, $precision)
    {
        $precision = $this->getUninitializedPrecision($value, $precision);

        $roundingModeAttribute = $this->getAttribute(self::ROUNDING_MODE);

        if (isset(self::$phpRoundingMap[$roundingModeAttribute])) {
            //$value = round($value, $precision, self::$phpRoundingMap[$roundingModeAttribute]);
            $value = round($value, $precision);
        }
        elseif (isset(self::$customRoundingList[$roundingModeAttribute])) {
            $roundingCoef = pow(10, $precision);
            $value *= $roundingCoef;
            $value = (float) (string) $value;

            switch ($roundingModeAttribute) {
                case self::ROUND_CEILING:
                    $value = ceil($value);
                    break;
                case self::ROUND_FLOOR:
                    $value = floor($value);
                    break;
                case self::ROUND_UP:
                    $value = $value > 0 ? ceil($value) : floor($value);
                    break;
                case self::ROUND_DOWN:
                    $value = $value > 0 ? floor($value) : ceil($value);
                    break;
            }

            $value /= $roundingCoef;
        }

        return $value;
    }
    
    private function formatNumber($value, $precision)
    {
        $precision = $this->getUninitializedPrecision($value, $precision);

        $decimal_point = $this->localeconv['decimal_point'] ? $this->localeconv['decimal_point'] : '.';
        $thousands_sep = $this->localeconv['thousands_sep'] ? $this->localeconv['thousands_sep'] : '';

        return number_format($value, $precision, $decimal_point, $thousands_sep);
    }

    private function getUninitializedPrecision($value, $precision)
    {
        if (self::CURRENCY === $this->style) {
            return $precision;
        }

        if (!$this->isInitializedAttribute(self::FRACTION_DIGITS)) {
            preg_match('/.*\.(.*)/', (string) $value, $digits);
            
            if (isset($digits[1])) {
                $precision = strlen($digits[1]);
            } else {
                $precision = 0;
            }
        }

        return $precision;
    }

    public function formatCurrency($amount, $currency)
    {
        if ($currency == null) {
            $currency = $this->localeconv['int_curr_symbol'];
        }

        if (self::DECIMAL === $this->style) {
            return $this->format($amount);
        }

        if (null === $symbol = Currencies::getSymbol($currency)) {
            return false;
        }
        
        //$symbol = $this->localeconv['currency_symbol'] ? $this->localeconv['currency_symbol'] : '$';

        $fractionDigits = Currencies::getFractionDigits($currency);
        
        $amount = $this->roundCurrency($amount, $currency);

        $negative = false;
        if (0 > $amount) {
            $negative = true;
            $amount *= -1;
        }

        $decimal_point = $this->localeconv['mon_decimal_point'] ? $this->localeconv['mon_decimal_point'] : '.';
        $thousands_sep = $this->localeconv['mon_thousands_sep'] ? $this->localeconv['mon_thousands_sep'] : '';

        $amount = number_format($amount, $fractionDigits, $decimal_point, $thousands_sep);

        // There's a non-breaking space after the currency code (i.e. CRC 100), but not if the currency has a symbol (i.e. £100).
        $ret = $symbol.(strlen($symbol) > 2 ? ' ' : '').$amount;

        return $negative ? '-'.$ret : $ret;
    }

    public function parseCurrency($string, &$currency, &$offset = null)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    public function parse($string, $type = self::TYPE_DOUBLE, &$offset = null)
    {
        if (self::TYPE_DEFAULT === $type || self::TYPE_CURRENCY === $type) {
            if (\PHP_VERSION_ID >= 80000) {
                throw new ValueError('The format type must be a NumberFormatter::TYPE_* constant (' . $type . 'given).');
            }

            trigger_error(__METHOD__.'(): Unsupported format type '.$type, \E_USER_WARNING);

            return false;
        }

        // Any invalid number at the end of the string is removed.
        // Only numbers and the fraction separator is expected in the string.
        // If grouping is used, grouping separator also becomes a valid character.
        $groupingMatch = $this->getAttribute(self::GROUPING_USED) ? '|(?P<grouping>\d++(,{1}\d+)++(\.\d*+)?)' : '';
        if (preg_match("/^-?(?:\.\d++{$groupingMatch}|\d++(\.\d*+)?)/", $string, $matches)) {
            $string = $matches[0];
            $offset = \strlen($string);
            // value is not valid if grouping is used, but digits are not grouped in groups of three
            if ($error = isset($matches['grouping']) && !preg_match('/^-?(?:\d{1,3}+)?(?:(?:,\d{3})++|\d*+)(?:\.\d*+)?$/', $string)) {
                // the position on error is 0 for positive and 1 for negative numbers
                $offset = 0 === strpos($string, '-') ? 1 : 0;
            }
        } else {
            $error = true;
            $offset = 0;
        }

        if ($error) {
            /* Icu::setError(Icu::U_PARSE_ERROR, 'Number parsing failed');
            $this->errorCode = Icu::getErrorCode();
            $this->errorMessage = Icu::getErrorMessage(); */
            $this->errorCode = 0; //Icu::getErrorCode();
            $this->errorMessage = ""; //Icu::getErrorMessage();

            return false;
        }

        $string = str_replace(',', '', $string);
        $string = $this->convertValueDataType($string, $type);

        // behave like the intl extension
        $this->resetError();

        return $string;
    }

    private function roundCurrency($value, $currency)
    {
        $fractionDigits = Currencies::getFractionDigits($currency);
        $roundingIncrement = Currencies::getRoundingIncrement($currency);

        // Round with the formatter rounding mode
        $value = $this->round($value, $fractionDigits);

        // Swiss rounding
        if (0 < $roundingIncrement && 0 < $fractionDigits) {
            $roundingFactor = $roundingIncrement / pow(10, $fractionDigits);
            $value = round($value / $roundingFactor) * $roundingFactor;
        }

        return $value;
    }

    private function convertValueDataType($value, $type)
    {
        if (self::TYPE_DOUBLE === $type) {
            $value = (float) $value;
        } elseif (self::TYPE_INT32 === $type) {
            $value = $this->getInt32Value($value);
        } elseif (self::TYPE_INT64 === $type) {
            $value = $this->getInt64Value($value);
        }

        return $value;
    }

    private function getInt32Value($value)
    {
        if ($value > self::$int32Max || $value < -self::$int32Max - 1) {
            return false;
        }

        return (int) $value;
    }

    private function getInt64Value($value)
    {
        if ($value > self::$int64Max || $value < -self::$int64Max - 1) {
            return false;
        }

        if (\PHP_INT_SIZE !== 8 && ($value > self::$int32Max || $value < -self::$int32Max - 1)) {
            return (float) $value;
        }

        return (int) $value;
    }
}