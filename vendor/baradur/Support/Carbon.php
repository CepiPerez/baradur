<?php

class Carbon
{
    /**
     * Diff wording options(expressed in octal).
     */
    const NO_ZERO_DIFF = 01;
    const JUST_NOW = 02;
    const ONE_DAY_WORDS = 04;
    const TWO_DAY_WORDS = 010;
    const SEQUENTIAL_PARTS_ONLY = 020;
    const ROUND = 040;
    const FLOOR = 0100;
    const CEIL = 0200;

    /**
     * Diff syntax options.
     */
    const DIFF_ABSOLUTE = 1; // backward compatibility with true
    const DIFF_RELATIVE_AUTO = 0; // backward compatibility with false
    const DIFF_RELATIVE_TO_NOW = 2;
    const DIFF_RELATIVE_TO_OTHER = 3;

    /**
     * Translate string options.
     */
    const TRANSLATE_MONTHS = 1;
    const TRANSLATE_DAYS = 2;
    const TRANSLATE_UNITS = 4;
    const TRANSLATE_MERIDIEM = 8;
    const TRANSLATE_DIFF = 0x10;
    //const TRANSLATE_ALL = self::TRANSLATE_MONTHS | self::TRANSLATE_DAYS | self::TRANSLATE_UNITS | self::TRANSLATE_MERIDIEM | self::TRANSLATE_DIFF;

    /**
     * The day constants.
     */
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;

    /**
     * The month constants.
     * These aren't used by Carbon itself but exist for
     * convenience sake alone.
     */
    const JANUARY = 1;
    const FEBRUARY = 2;
    const MARCH = 3;
    const APRIL = 4;
    const MAY = 5;
    const JUNE = 6;
    const JULY = 7;
    const AUGUST = 8;
    const SEPTEMBER = 9;
    const OCTOBER = 10;
    const NOVEMBER = 11;
    const DECEMBER = 12;

    /**
     * Number of X in Y.
     */
    const YEARS_PER_MILLENNIUM = 1000;
    const YEARS_PER_CENTURY = 100;
    const YEARS_PER_DECADE = 10;
    const MONTHS_PER_YEAR = 12;
    const MONTHS_PER_QUARTER = 3;
    const QUARTERS_PER_YEAR = 4;
    const WEEKS_PER_YEAR = 52;
    const WEEKS_PER_MONTH = 4;
    const DAYS_PER_YEAR = 365;
    const DAYS_PER_WEEK = 7;
    const HOURS_PER_DAY = 24;
    const MINUTES_PER_HOUR = 60;
    const SECONDS_PER_MINUTE = 60;
    const MILLISECONDS_PER_SECOND = 1000;
    const MICROSECONDS_PER_MILLISECOND = 1000;
    const MICROSECONDS_PER_SECOND = 1000000;

    /**
     * Special settings to get the start of week from current locale culture.
     */
    const WEEK_DAY_AUTO = 'auto';

    /**
     * RFC7231 DateTime format.
     *
     * @var string
     */
    const RFC7231_FORMAT = 'D, d M Y H:i:s \G\M\T';

    /**
     * Default format to use for __toString method when type juggling occurs.
     *
     * @var string
     */
    const DEFAULT_TO_STRING_FORMAT = 'Y-m-d H:i:s';

    /**
     * Format for converting mocked time, includes microseconds.
     *
     * @var string
     */
    const MOCK_DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * Pattern detection for ->isoFormat and ::createFromIsoFormat.
     *
     * @var string
     */
    const ISO_FORMAT_REGEXP = '(O[YMDHhms]|[Hh]mm(ss)?|Mo|MM?M?M?|Do|DDDo|DD?D?D?|ddd?d?|do?|w[o|w]?|W[o|W]?|Qo?|YYYYYY|YYYYY|YYYY|YY?|g{1,5}|G{1,5}|e|E|a|A|hh?|HH?|kk?|mm?|ss?|S{1,9}|x|X|zz?|ZZ?)';


    private $date = null;
    private $localToStringFormat = null;
    private $localSerializer = null;

    private $overflow = true;


    public function __construct($time = null, $tz = null)
    {
        $parse = $time;

        if ($parse instanceof Carbon)
            $parse = $parse->toDateTimeString();

        if (!$parse){
            $parse = 'now';
        }

        if (is_string($parse) && !is_numeric($parse)) {
            $parse = strtotime($parse);
        }

        if (!$parse) {
            throw new Exception('Invalid argument ['.$time.'] for Carbon');
        }

        $this->date = intval($parse); //? strtotime($time) : time();
    }
    
    private static function instance($time = null)
    {
        return new Carbon($time);
    }

    public function copy()
    {
        return new Carbon($this->timestamp);
    }

    public function resolveCarbon($time)
    {
        return new Carbon($time);
    }

    public function __toString()
    {
        return date(
            $this->localToStringFormat? $this->localToStringFormat : self::DEFAULT_TO_STRING_FORMAT,
            $this->date
        );
    }

    public function settings($settings)
    {
        $this->localToStringFormat = $settings['toStringFormat'] ? $settings['toStringFormat'] : null;
        $this->localSerializer = $settings['toJsonFormat'] ? $settings['toJsonFormat'] : null;

        return $this;
    }

    private function getShort($key)
    {
        if ($key=='minute') {
            return 'min';
        }

        return substr($key, 0, 1);
    }

    private function getIsoUnits()
    {
        static $units = null;

        /* if ($units === null) {
            $units = [
                'OD' => ['getAltNumber', ['day']],
                'OM' => ['getAltNumber', ['month']],
                'OY' => ['getAltNumber', ['year']],
                'OH' => ['getAltNumber', ['hour']],
                'Oh' => ['getAltNumber', ['h']],
                'Om' => ['getAltNumber', ['minute']],
                'Os' => ['getAltNumber', ['second']],
                'D' => 'day',
                'DD' => ['rawFormat', ['d']],
                'Do' => ['ordinal', ['day', 'D']],
                'd' => 'dayOfWeek',
                'dd' => function (Carbon $date, $originalFormat = null) {
                    return $date->getTranslatedMinDayName($originalFormat);
                },
                'ddd' => function (Carbon $date, $originalFormat = null) {
                    return $date->getTranslatedShortDayName($originalFormat);
                },
                'dddd' => function (Carbon $date, $originalFormat = null) {
                    return $date->getTranslatedDayName($originalFormat);
                },
                'DDD' => 'dayOfYear',
                'DDDD' => ['getPaddedUnit', ['dayOfYear', 3]],
                'DDDo' => ['ordinal', ['dayOfYear', 'DDD']],
                'e' => ['weekday', []],
                'E' => 'dayOfWeekIso',
                'H' => ['rawFormat', ['G']],
                'HH' => ['rawFormat', ['H']],
                'h' => ['rawFormat', ['g']],
                'hh' => ['rawFormat', ['h']],
                'k' => 'noZeroHour',
                'kk' => ['getPaddedUnit', ['noZeroHour']],
                'hmm' => ['rawFormat', ['gi']],
                'hmmss' => ['rawFormat', ['gis']],
                'Hmm' => ['rawFormat', ['Gi']],
                'Hmmss' => ['rawFormat', ['Gis']],
                'm' => 'minute',
                'mm' => ['rawFormat', ['i']],
                'a' => 'meridiem',
                'A' => 'upperMeridiem',
                's' => 'second',
                'ss' => ['getPaddedUnit', ['second']],
                'S' => function (Carbon $date) {
                    return (string) floor($date->micro / 100000);
                },
                'SS' => function (Carbon $date) {
                    return str_pad((string) floor($date->micro / 10000), 2, '0', STR_PAD_LEFT);
                },
                'SSS' => function (Carbon $date) {
                    return str_pad((string) floor($date->micro / 1000), 3, '0', STR_PAD_LEFT);
                },
                'SSSS' => function (Carbon $date) {
                    return str_pad((string) floor($date->micro / 100), 4, '0', STR_PAD_LEFT);
                },
                'SSSSS' => function (Carbon $date) {
                    return str_pad((string) floor($date->micro / 10), 5, '0', STR_PAD_LEFT);
                },
                'SSSSSS' => ['getPaddedUnit', ['micro', 6]],
                'SSSSSSS' => function (Carbon $date) {
                    return str_pad((string) floor($date->micro * 10), 7, '0', STR_PAD_LEFT);
                },
                'SSSSSSSS' => function (Carbon $date) {
                    return str_pad((string) floor($date->micro * 100), 8, '0', STR_PAD_LEFT);
                },
                'SSSSSSSSS' => function (Carbon $date) {
                    return str_pad((string) floor($date->micro * 1000), 9, '0', STR_PAD_LEFT);
                },
                'M' => 'month',
                'MM' => ['rawFormat', ['m']],
                'MMM' => function (Carbon $date, $originalFormat = null) {
                    $month = $date->getTranslatedShortMonthName($originalFormat);
                    $suffix = $date->getTranslationMessage('mmm_suffix');
                    if ($suffix && $month !== $date->monthName) {
                        $month .= $suffix;
                    }

                    return $month;
                },
                'MMMM' => function (Carbon $date, $originalFormat = null) {
                    return $date->getTranslatedMonthName($originalFormat);
                },
                'Mo' => ['ordinal', ['month', 'M']],
                'Q' => 'quarter',
                'Qo' => ['ordinal', ['quarter', 'M']],
                'G' => 'isoWeekYear',
                'GG' => ['getPaddedUnit', ['isoWeekYear']],
                'GGG' => ['getPaddedUnit', ['isoWeekYear', 3]],
                'GGGG' => ['getPaddedUnit', ['isoWeekYear', 4]],
                'GGGGG' => ['getPaddedUnit', ['isoWeekYear', 5]],
                'g' => 'weekYear',
                'gg' => ['getPaddedUnit', ['weekYear']],
                'ggg' => ['getPaddedUnit', ['weekYear', 3]],
                'gggg' => ['getPaddedUnit', ['weekYear', 4]],
                'ggggg' => ['getPaddedUnit', ['weekYear', 5]],
                'W' => 'isoWeek',
                'WW' => ['getPaddedUnit', ['isoWeek']],
                'Wo' => ['ordinal', ['isoWeek', 'W']],
                'w' => 'week',
                'ww' => ['getPaddedUnit', ['week']],
                'wo' => ['ordinal', ['week', 'w']],
                'x' => ['valueOf', []],
                'X' => 'timestamp',
                'Y' => 'year',
                'YY' => ['rawFormat', ['y']],
                'YYYY' => ['getPaddedUnit', ['year', 4]],
                'YYYYY' => ['getPaddedUnit', ['year', 5]],
                'YYYYYY' => function (Carbon $date) {
                    return ($date->year < 0 ? '' : '+').$date->getPaddedUnit('year', 6);
                },
                'z' => ['rawFormat', ['T']],
                'zz' => 'tzName',
                'Z' => ['getOffsetString', []],
                'ZZ' => ['getOffsetString', ['']],
            ];
        } */

        return $units;
    }

    public function __get($name)
    {
        switch ($name)
        {
            case 'year': 
                return date('Y', $this->date);
            case 'month': 
                return date('n', $this->date);
            case 'day': 
                return date('j', $this->date);
            case 'hour': 
                return date('H', $this->date);
            case 'minute': 
                return (int)date('i', $this->date);
            case 'second': 
                return (int)date('s', $this->date);
            case 'daysInMonth': 
                return date('t', $this->date);
            case 'dayOfWeek': 
                return date('w', $this->date);
            case 'firstWeekDay': 
                return 0;
            case 'lastWeekDay': 
                return 6;
            case 'timestamp': 
                return $this->date;
            case 'quarter': 
                return (int) ceil($this->month / self::MONTHS_PER_QUARTER);
            case 'decade': 
                return (int) ceil($this->year / self::YEARS_PER_DECADE);
            case 'age': 
                return $this->diffInYears();
    
            default:
                return null;
        }

    }

    private function getIsoUnit($value)
    {
        switch ($value)
        {
            case 'D':
                return $this->day;
            case 'DD';
                return $this->rawFormat('d');
            case 'd':
                return $this->dayOfWeek;
            case 'dd':
                $temp =__('carbon.weekdays_min');
                return $temp[$this->dayOfWeek];
            case 'ddd':
                $temp =__('carbon.weekdays_short');
                return $temp[$this->dayOfWeek];
            case 'dddd':
                $temp =__('carbon.weekdays');
                return $temp[$this->dayOfWeek];
            case 'M':
                return $this->month;
            case 'MM':
                return $this->rawFormat('m');
            case 'MMM':
                $temp =__('carbon.months_short');
                return $temp[$this->month].__('carbon.mmm_suffix');
            case 'MMMM':
                $temp =__('carbon.months');
                return $temp[$this->month];
            case 'Y':
                return $this->year;
            case 'YY':
                return $this->rawFormat('y');
            case 'YYYY':
                return $this->getPaddedUnit($this->year, 4);
            case 'YYYYY':
                return $this->getPaddedUnit($this->year, 5);
            case 'HH':
                return $this->hour;
            case 'H':
                return $this->rawFormat('G');
            case 'hh':
                return $this->rawFormat('h');
            case 'h':
                return $this->rawFormat('g');
            case 'm':
                return $this->minute;
            case 'mm':
                return $this->rawFormat('i');
            case 's':
                return $this->second;
            case 'ss':
                return $this->rawFormat('s');

            default:
                return $value;
        }
    }

    public function getPaddedUnit($unit, $length = 2, $padString = '0', $padType = STR_PAD_LEFT)
    {
        return ($unit < 0 ? '-' : '') . str_pad( (string) abs($unit), $length, $padString, $padType);
    }

    private function callbackReplaceFormat($match)
    {
        return $this->getIsoUnit($match[0]);
    }

    public function isoFormat($format)
    {
        $formats = $this->getIsoFormats();

        $format = $formats[$format];

        $format = preg_replace_callback('/[^\W]+(?:[^\W]+)*/x', array($this, 'callbackReplaceFormat'), $format);

        return $format;
    }

    public function getTimestamp()
    {
        return $this->date;
    }

    public function getIsoFormats()
    {
        return array(
            'LT' => __('carbon.formats.LT') != 'LT' ? __('carbon.formats.LT') : 'h:mm A',
            'LTS' => __('carbon.formats.LTS') != 'LTS' ? __('carbon.formats.LTS') : 'h:mm:ss A',
            'L' => __('carbon.formats.L') != 'L' ? __('carbon.formats.L') : 'MM/DD/YYYY',
            'LL' => __('carbon.formats.LL') != 'LL' ? __('carbon.formats.LL') : 'MMMM D, YYYY',
            'LLL' => __('carbon.formats.LLL') != 'LLL' ? __('carbon.formats.LLL') : 'MMMM D, YYYY h:mm A',
            'LLLL' => __('carbon.formats.LLLL') != 'LLLL' ? __('carbon.formats.LLLL') : 'dddd, MMMM D, YYYY h:mm A',
            'l' => __('carbon.formats.l') != 'l' ? __('carbon.formats.l') : null,
            'll' => __('carbon.formats.ll') != 'll' ? __('carbon.formats.ll') : null,
            'lll' => __('carbon.formats.lll') != 'lll' ? __('carbon.formats.lll') : null,
            'llll' => __('carbon.formats.llll') != 'llll' ? __('carbon.formats.llll') : null
        );
    }

    private function _date_range_limit($start, $end, $adj, $a, $b, &$result)
    {
        if ($result[$a] < $start) {
            $result[$b] -= intval(($start - $result[$a] - 1) / $adj) + 1;
            $result[$a] += $adj * intval(($start - $result[$a] - 1) / $adj + 1);
        }

        if ($result[$a] >= $end) {
            $result[$b] += intval($result[$a] / $adj);
            $result[$a] -= $adj * intval($result[$a] / $adj);
        }

        return $result;
    }

    private function _date_range_limit_days(&$base, &$result)
    {
        $days_in_month_leap = array(31, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $days_in_month = array(31, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

        $this->_date_range_limit(1, 13, 12, "m", "y", $base);

        $year = $base["y"];
        $month = $base["m"];

        if (!$result["invert"]) {
            while ($result["d"] < 0) {
                $month--;
                if ($month < 1) {
                    $month += 12;
                    $year--;
                }

                $leapyear = $year % 400 == 0 || ($year % 100 != 0 && $year % 4 == 0);
                $days = $leapyear ? $days_in_month_leap[$month] : $days_in_month[$month];

                $result["d"] += $days;
                $result["m"]--;
            }
        } else {
            while ($result["d"] < 0) {
                $leapyear = $year % 400 == 0 || ($year % 100 != 0 && $year % 4 == 0);
                $days = $leapyear ? $days_in_month_leap[$month] : $days_in_month[$month];

                $result["d"] += $days;
                $result["m"]--;

                $month++;
                if ($month > 12) {
                    $month -= 12;
                    $year++;
                }
            }
        }

        return $result;
    }

    private function _date_normalize(&$base, &$result)
    {
        $result = $this->_date_range_limit(0, 60, 60, "s", "i", $result);
        $result = $this->_date_range_limit(0, 60, 60, "i", "h", $result);
        $result = $this->_date_range_limit(0, 24, 24, "h", "d", $result);
        $result = $this->_date_range_limit(0, 12, 12, "m", "y", $result);

        $result = $this->_date_range_limit_days($base, $result);

        $result = $this->_date_range_limit(0, 12, 12, "m", "y", $result);

        return $result;
    }

    /**
     * Accepts two unix timestamps.
     */
    private function _date_diff($one, $two)
    {
        $invert = false;
        if ($one > $two) {
            list($one, $two) = array($two, $one);
            $invert = true;
        }

        $key = array("y", "m", "d", "h", "i", "s");
        $a = array_combine($key, array_map("intval", explode(" ", date("Y m d H i s", $one))));
        $b = array_combine($key, array_map("intval", explode(" ", date("Y m d H i s", $two))));

        $result = array();
        $result["y"] = $b["y"] - $a["y"];
        $result["m"] = $b["m"] - $a["m"];
        $result["d"] = $b["d"] - $a["d"];
        $result["h"] = $b["h"] - $a["h"];
        $result["i"] = $b["i"] - $a["i"];
        $result["s"] = $b["s"] - $a["s"];
        $result["invert"] = $invert ? 1 : 0;
        $result["days"] = intval(abs(($one - $two)/86400));

        if ($invert) {
            $this->_date_normalize($a, $result);
        } else {
            $this->_date_normalize($b, $result);
        }

        return $result;
    }

    public function diffForHumans($other=null, $syntax=Carbon::DIFF_ABSOLUTE, $short=false, $parts=1)
    {
        $modifiers = 1;
        $date = null;
        
        if (isset($syntax) && is_numeric($syntax)) {
            $modifiers = $syntax;
        }

        if (isset($syntax['syntax'])) {
            $modifiers = $syntax['syntax'];
        }

        if ($modifiers==1 && !$date) {
            $modifiers = 2;
        }

        if (!is_array($syntax) && is_array($other)) {
            $syntax = $other;
        }

        if (isset($syntax['parts'])) {
            $parts = $syntax['parts'];
        }
        
        $join = ' ';
        
        if (isset($syntax['join'])) {
            $join = $syntax['join'];
        }

        $aUnit = $short;

        if (isset($syntax['aUnit'])) {
            $aUnit = $syntax['aUnit'];
        }

        if ($other instanceof Carbon) {
            $date = $other;
        } elseif (!is_array($other)) {
            $date = new Carbon($other);
        } elseif (!isset($other) || is_array($other)) {
            $date = new Carbon();
        }

        $res = $this->_date_diff($this->date, $date->date);

        $result = array(
            'year' => $res['y'],
            'month' => $res['m'],
            'day' => $res['d'],
            'hour' => $res['h'],
            'minute' => $res['i'],
            'second' => $res['s']
        );

        $negative = $res['invert'];

        $str = array();
        $added = 0;
        $keys = array_keys($result);

        foreach ($keys as $key)
        {
            if ($result[$key] > 0 && $added < $parts) {
                $str[] = Helpers::trans_choice(
                    'carbon.' .(!$aUnit? $key : $this->getShort($key) ),
                    array('count' => $result[$key])
                );
                $added++;
            }
        }


        if ($modifiers==0 || $modifiers==1)
            return implode($join, $str);
        elseif ($modifiers==2)
            return Helpers::trans(($negative? 'carbon.ago' : 'carbon.from_now'), array('time' => implode($join, $str)));
        elseif ($modifiers==3)
            return Helpers::trans(($negative? 'carbon.after' : 'carbon.before'), array('time' => implode($join, $str)));

    }

    /* public function diffForHumans($other=null, $syntax=Carbon::DIFF_ABSOLUTE, $short=false, $parts=1)
    {
        
        if ($other instanceof Carbon)
        {
            $date = $other;
        }
        elseif (!is_array($other))
        {
            $date = new Carbon($other);
        }
        elseif (!isset($other) || is_array($other))
        {
            $date = new Carbon();
        }

        if (isset($syntax) && is_numeric($syntax))
        {
            $modifiers = $syntax;
        }

        if (!is_array($syntax) && is_array($other))
        {
            $syntax = $other;
        }

        if (isset($syntax['parts']))
        {
            $parts = $syntax['parts'];
        }
        
        $join = ' ';
        if (isset($syntax['join']))
        {
            $join = $syntax['join'];
        }

        $aUnit = false;
        if (isset($syntax['aUnit']))
        {
            $aUnit = $syntax['aUnit'];
        }

        $result = array();
        $result['year'] = abs($this->year - $date->year);
        $result['month'] = abs($this->month - $date->month);
        $result['day'] = abs($this->day - $date->day);
        $result['hour'] = abs($this->hour - $date->hour);
        $result['minute'] = abs($this->minute - $date->minute);
        $result['second'] = abs($this->second - $date->second);

        $str = array();
        $added = 0;
        $keys = array_keys($result);

        foreach ($keys as $key)
        {
            if ($result[$key] > 0 && $added <= $parts) {
                $str[] = Helpers::trans_choice(
                    'carbon.' .($aUnit? $key : $this->getShort($key) ),
                    array('count' => $result[$key])
                );
                $added++;
            }
        }

        $negative = ($this->date - $date->date) < 0;

        if ($modifiers==1)
            return implode($join, $str);
        elseif ($modifiers==2)
            return Helpers::trans(($negative? 'carbon.ago' : 'carbon.from_now'), array('time' => implode($join, $str)));
        elseif ($modifiers==3)
            return Helpers::trans(($negative? 'carbon.after' : 'carbon.before'), array('time' => implode($join, $str)));

    } */

    public static function now()
    {
        return self::instance();
    }

    public static function today()
    {
        return self::instance()->startOfDay();
    }

    public static function yesterday()
    {
        return self::instance()->subDay()->startOfDay();
    }

    public static function tomorrow()
    {
        return self::instance()->addDay()->startOfDay();
    }

    public static function parse($time)
    {
        return self::instance($time);
    }

    public static function create($year, $month, $day, $hour=0, $minute=0, $second=0)
    {
        return self::instance("$year-$month-$day $hour:$minute:$second");
    }

    public static function createFromDate($year, $month, $day)
    {
        return self::instance("$year-$month-$day");
    }


    public static function createFromTimestamp($timestamp)
    {
        return self::instance($timestamp);
    }

    public static function createFromTime($hour=0, $minute=0, $second=0)
    {
        $current = now();
        $year = $current->year;
        $month = $current->month;
        $day = $current->day;
        return self::instance("$year-$month-$day $hour:$minute:$second");
    }

    public static function createFromTimeString($time='00:00:00')
    {
        $current = now();
        $year = $current->year;
        $month = $current->month;
        $day = $current->day;
        $time = explode(':', $time);
        $hour = $time[0];
        $minute = count($time)>1 ? $time[1] : 0; 
        $second = count($time)>2 ? $time[2] : 0; 
        return self::instance("$year-$month-$day $hour:$minute:$second");
    }

    public static function createFromId($id)
    {
        return Ulid::isValid($id)
            ? self::createFromInterface(Ulid::fromString($id)->getDateTime())
            : self::createFromInterface(Uuid::fromString($id)->getDateTime());
    }

    public static function createFromInterface($interface)
    {
        if (strlen($interface) > 10) {
            $interface = substr($interface, 0, 10);
        }

        $res = new self;
        $res->date = $interface;
        return $res;
    }

    public static function createMidnightDate($year, $month, $day)
    {
        return self::instance("$year-$month-$day 00:00:00");
    }

    private function getDateForCompare($date)
    {
        if (!$date)
        {
            $date = new Carbon();
        }

        return $date;
    }

    private function getAbsolute($diff, $absolute)
    {
        return $absolute? abs((int)$diff) : (int)$diff;
    }

    public function diffInYears($date = null, $absolute = true)
    {
        $date = $this->getDateForCompare($date);

        $years = date('Y', $this->date) - date('Y', $date->date);
        $months = date('m', $this->date) - date('m', $date->date);
        $days = date('d', $this->date) - date('d', $date->date);

        $dateDiff = ($years*12) + ($months) + ($days<0 ? -1 : 0);

        return abs($dateDiff) < 12? 0 : $this->getAbsolute($dateDiff/12, $absolute);
    }

    public function diffInMonths($date = null, $absolute = true)
    {
        $date = $this->getDateForCompare($date);

        $years = date('Y', $this->date) - date('Y', $date->date);
        $months = date('m', $this->date) - date('m', $date->date);
        $days = date('d', $this->date) - date('d', $date->date);

        $dateDiff = ($years*12) + ($months) + ($days<0 ? -1 : 0);

        return $this->getAbsolute($dateDiff, $absolute);
    }

    public function diffInDays($date = null, $absolute = true)
    {
        $date = $this->getDateForCompare($date);

        $dateDiff = $this->date - $date->date;
        $dateDiff = $dateDiff / (60 * 60 * 24);

        return $this->getAbsolute($dateDiff, $absolute);
    }

    public function diffInHours($date = null, $absolute = true)
    {
        $date = $this->getDateForCompare($date);

        $dateDiff = $this->date - $date->date;
        $dateDiff = $dateDiff / (60 * 60);

        return $this->getAbsolute($dateDiff, $absolute);
    }

    public function diffInMinutes($date = null, $absolute = true)
    {
        $date = $this->getDateForCompare($date);

        $dateDiff = $this->date - $date->date;
        $dateDiff = $dateDiff / 60;

        return $this->getAbsolute($dateDiff, $absolute);
    }

    public function diffInSeconds($date = null, $absolute = true)
    {
        $date = $this->getDateForCompare($date);

        $dateDiff = $this->date - $date->date;
        //$dateDiff = $dateDiff / 60;

        return $this->getAbsolute($dateDiff, $absolute);
    }

    public function isSunday()
    {
        return date('w', $this->date) == self::SUNDAY;
    }

    public function isMonday()
    {
        return date('w', $this->date) == self::MONDAY;
    }

    public function isTuesday()
    {
        return date('w', $this->date) == self::TUESDAY;
    }

    public function isWednesday()
    {
        return date('w', $this->date) == self::WEDNESDAY;
    }

    public function isThursday()
    {
        return date('w', $this->date) == self::THURSDAY;
    }

    public function isFriday()
    {
        return date('w', $this->date) == self::FRIDAY;
    }

    public function isSaturday()
    {
        return date('w', $this->date) == self::SATURDAY;
    }

    public function isSameYear($compare=null)
    {
        if (!$compare) {
            $compare = time();
        }

        return date('Y', $this->date) == date('Y', $compare);
    }

    public function isCurrentYear()
    {
        return $this->isSameYear(time());
    }

    public function isNextYear()
    {
        return date('Y', $this->date) == date('Y', strtotime(' + 1 years'));
    }

    public function isLastYear()
    {
        return date('Y', $this->date) == date('Y', strtotime(' - 1 years'));
    }


    /* public function addMillenia($value = 1)
    {
        return $this->addYears(self::YEARS_PER_CENTURY * $value);
    }

    public function addMillenium()
    {
        return $this->addMillenia(1);
    }

    public function subMillenia($value = 1)
    {
        return $this->subYears(self::YEARS_PER_CENTURY * $value);
    }

    public function subMillenium()
    {
        return $this->subMillenia(1);
    }

    public function addCenturies($value = 1)
    {
        return $this->addYears(self::YEARS_PER_CENTURY * $value);
    }

    public function addCentury()
    {
        return $this->addCenturies(1);
    }

    public function subCenturies($value = 1)
    {
        return $this->subYears(self::YEARS_PER_CENTURY * $value);
    }

    public function subCentury()
    {
        return $this->subCenturies(1);
    }

    public function addDecades($value = 1)
    {
        return $this->addYears(self::YEARS_PER_DECADE * $value);
    }

    public function addDecade()
    {
        return $this->addDecades(1);
    }

    public function subDecades($value = 1)
    {
        return $this->subYears(self::YEARS_PER_DECADE * $value);
    }

    public function subDecade()
    {
        return $this->subDecades(1);
    }

    public function addYears($value = 1)
    {
        $this->date = strtotime($this->__toString() . " + $value years");
        return $this;
    }

    public function addYear()
    {
        return $this->addYears(1);
    }

    public function subYears($value = 1)
    {
        $this->date = strtotime($this->__toString() . " - $value years");
        return $this;
    }

    public function subYear()
    {
        return $this->subYears(1);
    }

    public function addQuarters($value = 1)
    {
        return $this->addMonths(self::MONTHS_PER_QUARTER * $value);
    }

    public function addQuarter()
    {
        return $this->addQuarters(1);
    }

    public function subQuarters($value = 1)
    {
        return $this->subMonths(self::MONTHS_PER_QUARTER * $value);
    }

    public function subQuarter()
    {
        return $this->subQuarters(1);
    }

    public function addMonths($value = 1)
    {
        if (!$this->overflow) {

            $day = (int) $this->day;

            $y = (int) $this->year;
            $m = (int) $this->month + $value;
            $d = 1;
            $h = (int) $this->hour;
            $i = (int) $this->minute;
            $s = (int) $this->second;

            while ($m > 12) {
                $m = $m -12;
                $y++;
            }

            $new = self::parse("$y/$m/$d $h:$i:$s");
            $lastday = (int) $new->endOfMonth()->day;

            if ($day > $lastday) {
                $d = $lastday;
            } else {
                $d = $day;
            }

            return self::parse("$y/$m/$d $h:$i:$s");
        }

        $this->date = strtotime($this->__toString() . " + $value months");
        return $this;
    }

    public function addMonth()
    {
        return $this->addMonths(1);
    }

    public function subMonths($value = 1)
    {
        if (!$this->overflow) {

            $day = (int) $this->day;

            $y = (int) $this->year;
            $m = (int) $this->month - $value;
            $d = 1;
            $h = (int) $this->hour;
            $i = (int) $this->minute;
            $s = (int) $this->second;

            while ($m < 1) {
                $m = $m +12;
                $y--;
            }

            $new = self::parse("$y/$m/$d $h:$i:$s");
            $lastday = (int) $new->endOfMonth()->day;

            if ($day > $lastday) {
                $d = $lastday;
            } else {
                $d = $day;
            }

            return self::parse("$y/$m/$d $h:$i:$s");
        }
        
        $this->date = strtotime($this->__toString() . " - $value months");
        return $this;
    }

    public function subMonth()
    {
        return $this->subMonths(1);
    } */

    public function addDays($value = 1)
    {
        $this->date = strtotime($this->__toString() . " + $value days");
        return $this;
    }

    public function addDay()
    {
        return $this->addDays(1);
    }

    public function subDays($value = 1)
    {
        $this->date = strtotime($this->__toString() . " - $value days");
        return $this;
    }

    public function subDay()
    {
        return $this->subDays(1);
    }

    public function addHours($value = 1)
    {
        $this->date = strtotime($this->__toString() . " + $value hours");
        return $this;
    }

    public function addHour()
    {
        return $this->addHours(1);
    }

    public function subHours($value = 1)
    {
        $this->date = strtotime($this->__toString() . " - $value hours");
        return $this;
    }

    public function subHour()
    {
        return $this->subHours(1);
    }

    public function addMinutes($value = 1)
    {
        $this->date = strtotime($this->__toString() . " + $value minutes");
        return $this;
    }

    public function addMinute()
    {
        return $this->addMinutes(1);
    }

    public function subMinutes($value = 1)
    {
        $this->date = strtotime($this->__toString() . " - $value minutes");
        return $this;
    }

    public function subMinute()
    {
        return $this->subMinutes(1);
    }

    public function addSeconds($value = 1)
    {
        $this->date = strtotime($this->__toString() . " + $value seconds");
        return $this;
    }

    public function addSecond()
    {
        return $this->addSeconds(1);
    }

    public function subSeconds($value = 1)
    {
        $this->date = strtotime($this->__toString() . " - $value seconds");
        return $this;
    }

    public function subSecond()
    {
        return $this->subSeconds(1);
    }

    public function setDate($year, $month, $day)
    {
        $this->date = strtotime("$year-$month-$day $this->hour:$this->minute:$this->second");
        return $this;
    }

    public function setTime($hour, $min, $sec)
    {
        $this->date = strtotime("$this->year-$this->month-$this->day $hour:$min:$sec");
        return $this;
    }


    public function startOfYear()
    {
        return $this->setDate($this->year, 1, 1)->startOfDay();
    }

    public function endOfYear()
    {
        return $this->setDate($this->year, 12, 31)->endOfDay();
    }

    public function startOfMonth()
    {
        return $this->setDate($this->year, $this->month, 1)->startOfDay();
    }

    public function endOfMonth()
    {
        return $this->setDate($this->year, $this->month, $this->daysInMonth)->endOfDay();
    }

    public function startOfDay()
    {
        return $this->setTime(0, 0, 0);
    }

    public function endOfDay()
    {
        return $this->setTime(self::HOURS_PER_DAY - 1, self::MINUTES_PER_HOUR - 1, self::SECONDS_PER_MINUTE - 1);
    }

    public function startOfDecade()
    {
        $year = $this->year - $this->year % self::YEARS_PER_DECADE;

        return $this->setDate($year, 1, 1)->startOfDay();
    }

    public function endOfDecade()
    {
        $year = $this->year - $this->year % self::YEARS_PER_DECADE + self::YEARS_PER_DECADE - 1;

        return $this->setDate($year, 12, 31)->endOfDay();
    }

    public function startOfCentury()
    {
        $year = $this->year - ($this->year - 1) % self::YEARS_PER_CENTURY;

        return $this->setDate($year, 1, 1)->startOfDay();
    }

    public function endOfCentury()
    {
        $year = $this->year - 1 - ($this->year - 1) % self::YEARS_PER_CENTURY + self::YEARS_PER_CENTURY;

        return $this->setDate($year, 12, 31)->endOfDay();
    }

    public function startOfMillennium()
    {
        $year = $this->year - ($this->year - 1) % self::YEARS_PER_MILLENNIUM;

        return $this->setDate($year, 1, 1)->startOfDay();
    }

    public function endOfMillennium()
    {
        $year = $this->year - 1 - ($this->year - 1) % self::YEARS_PER_MILLENNIUM + self::YEARS_PER_MILLENNIUM;

        return $this->setDate($year, 12, 31)->endOfDay();
    }

    public function startOfWeek($weekStartsAt = null)
    {
        return $this->subDays((7 + $this->dayOfWeek - ($weekStartsAt ? $weekStartsAt : $this->firstWeekDay)) % 7)->startOfDay();
    }

    public function endOfWeek($weekEndsAt = null)
    {
        return $this->addDays((7 - $this->dayOfWeek + ($weekEndsAt ? $weekEndsAt : $this->lastWeekDay)) % 7)->endOfDay();
    }

    public function startOfHour()
    {
        return $this->setTime($this->hour, 0, 0, 0);
    }

    public function endOfHour()
    {
        return $this->setTime($this->hour, self::MINUTES_PER_HOUR - 1, self::SECONDS_PER_MINUTE - 1);
    }

    public function startOfMinute()
    {
        return $this->setTime($this->hour, $this->minute, 0, 0);
    }

    public function endOfMinute()
    {
        return $this->setTime($this->hour, $this->minute, self::SECONDS_PER_MINUTE - 1);
    }

    public function toDateString()
    {
        return $this->rawFormat('Y-m-d');
    }

    public function toFormattedDateString()
    {
        return $this->rawFormat('M j, Y');
    }

    public function toFormattedDayDateString()
    {
        return $this->rawFormat('D, M j, Y');
    }

    public function toTimeString($unitPrecision = 'second')
    {
        return $this->rawFormat(self::getTimeFormatByPrecision($unitPrecision));
    }

    public function toDateTimeString($unitPrecision = 'second')
    {
        return $this->rawFormat('Y-m-d '.self::getTimeFormatByPrecision($unitPrecision));
    }

    public static function getTimeFormatByPrecision($unitPrecision)
    {
        switch (/* self::singularUnit( */$unitPrecision/* ) */) {
            case 'minute':
                return 'H:i';
            case 'second':
                return 'H:i:s';
            case 'm':
            case 'millisecond':
                return 'H:i:s.v';
            case 'µ':
            case 'microsecond':
                return 'H:i:s.u';
        }

        throw new RuntimeException('Precision unit expected among: minute, second, millisecond and microsecond.');
    }

    public function toDateTimeLocalString($unitPrecision = 'second')
    {
        return $this->rawFormat('Y-m-d\T'.self::getTimeFormatByPrecision($unitPrecision));
    }

    public function toDayDateTimeString()
    {
        return $this->rawFormat('D, M j, Y g:i A');
    }

    public function rawFormat($format)
    {
        return date($format, $this->date);
    }

    public function format($format)
    {
        return $this->rawFormat($format);
    }

    public function isAfter($date)
    {
        return $this->greaterThan($date);
    }

    public function gt($date)
    {
        return $this->greaterThan($date);
    }

    public function greaterThan($date)
    {
        $this->discourageNull($date);
        $this->discourageBoolean($date);

        return $this->date > $this->resolveCarbon($date)->date;
    }

    public function eq($date)
    {
        return $this->equalTo($date);
    }

    public function equalTo($date)
    {
        $this->discourageNull($date);
        $this->discourageBoolean($date);

        return $this->date == $this->resolveCarbon($date)->date;
    }

    public function ne($date)
    {
        return $this->notEqualTo($date);
    }

    public function notEqualTo($date)
    {
        return !$this->equalTo($date);
    }

    public function gte($date)
    {
        return $this->greaterThanOrEqualTo($date);
    }

    public function greaterThanOrEqualTo($date)
    {
        $this->discourageNull($date);
        $this->discourageBoolean($date);

        return $this->date >= $this->resolveCarbon($date)->date;
    }

    public function lt($date)
    {
        return $this->lessThan($date);
    }

    public function lessThan($date)
    {
        $this->discourageNull($date);
        $this->discourageBoolean($date);

        return $this->date < $this->resolveCarbon($date)->date;
    }

    public function isBefore($date)
    {
        return $this->lessThan($date);
    }

    public function lte($date)
    {
        return $this->lessThanOrEqualTo($date);
    }

    public function lessThanOrEqualTo($date)
    {
        $this->discourageNull($date);
        $this->discourageBoolean($date);

        return $this->date <= $this->resolveCarbon($date)->date;
    }

    public function between($date1, $date2, $equal = true)
    {
        $date1 = $this->resolveCarbon($date1);
        $date2 = $this->resolveCarbon($date2);

        if ($date1->greaterThan($date2)) {
            $temp1 = $date1->date;
            $temp2 = $date2->date;
            $date1->date = $temp2;
            $date2->date = $temp1;
        }

        if ($equal) {
            return $this->date >= $date1->date && $this->date <= $date2->date;
        }

        return $this->date > $date1->date && $this->date < $date2->date;
    }
    
    public function betweenIncluded($date1, $date2)
    {
        return $this->between($date1, $date2, true);
    }

    public function betweenExcluded($date1, $date2)
    {
        return $this->between($date1, $date2, false);
    }

    public function isBetween($date1, $date2, $equal = true)
    {
        return $this->between($date1, $date2, $equal);
    }

    public function isWeekday()
    {
        return !$this->isWeekend();
    }

    public function isWeekend()
    {
        return $this->dayOfWeek > 5;
    }
    
    public function isYesterday()
    {
        return $this->toDateString() === self::yesterday()->toDateString();
    }

    public function isToday()
    {
        return $this->toDateString() === self::now()->toDateString();
    }

    public function isTomorrow()
    {
        return $this->toDateString() === self::tomorrow()->toDateString();
    }

    public function isFuture()
    {
        return $this->greaterThan(self::now());
    }

    public function isPast()
    {
        return $this->lessThan(self::now());
    }

    public function isSameQuarter($date = null, $ofSameYear = true)
    {
        $date = $this->resolveCarbon($date);

        return $this->quarter === $date->quarter && (!$ofSameYear || $this->isSameYear($date));
    }
    
    public function isBirthday($date = null)
    {
        return $this->isSameAs('md', $date);
    }

    public function isSameAs($format, $date = null)
    {
        return $this->rawFormat($format) === $this->resolveCarbon($date)->rawFormat($format);
    }


    private function discourageNull($value)
    {
        if ($value === null) {
            throw new LogicException("It's deprecated to compare a date to null, you should explicitly pass 'now' or make an other check to eliminate null values.");
        }
    }

    private function discourageBoolean($value)
    {
        if (is_bool($value)) {
            throw new LogicException("Since 2.61.0, it's deprecated to compare a date to true or false, you should explicitly pass 'now' or make an other check to eliminate boolean values.");
        }
    }


    public static function singularUnit($unit)
    {
        $unit = rtrim(strtolower($unit), 's');

        if ($unit === 'centurie') {
            return 'century';
        }

        if ($unit === 'millennia') {
            return 'millennium';
        }

        return $unit;
    }

    public function __call($method, $arguments)
    {
        $unit = rtrim($method, 's');
        $action = substr($unit, 0, 3);

        if ($action === 'add' || $action === 'sub') {
            $unit = substr($unit, 3);

            if (preg_match('/^(Month|Quarter|Year|Decade|Century|Centurie|Millennium|Millennia)s?(No|With|Without|WithNo)Overflow$/', $unit, $match)) {
                $unit = str_replace($match[2], '', $match[0]);
                $unit = str_replace('Overflow', '', $unit);
                $unit = self::singularUnit($unit);

                $this->overflow = $match[2] === 'With';

                $param = count($arguments) > 0 ? reset($arguments) : 1;

                return $this->modifyUnit($action, $unit, $param);
            }

            elseif (preg_match('/^(Month|Quarter|Year|Decade|Century|Centurie|Millennium|Millennia)s?$/', $unit, $match)) {

                $param = count($arguments) > 0 ? reset($arguments) : 1;
                $unit = self::singularUnit($unit);

                return $this->modifyUnit($action, $unit, $param);
            }
        }

        elseif (preg_match('/^(short|long)(Absolute|Relative|RelativeToNow|RelativeToOther)DiffForHumans?$/', $method, $match)) {

            $diffSyntaxModes = array(
                'Absolute' => Carbon::DIFF_ABSOLUTE,
                'Relative' => Carbon::DIFF_RELATIVE_AUTO,
                'RelativeToNow' => Carbon::DIFF_RELATIVE_TO_NOW,
                'RelativeToOther' => Carbon::DIFF_RELATIVE_TO_OTHER,
            );

            $param1 = count($arguments) > 0 ? array_shift($arguments) : null;
            $param2 = count($arguments) > 0 ? reset($arguments) : 1;

            $modifier = $diffSyntaxModes[$match[2]];

            return $this->diffForHumans($param1, $modifier, $match[1]=='short', $param2);
        }

        throw new BadMethodCallException("Method $method does not exist");
    }

    public function modifyUnit($action, $unit, $value)
    {
        if ($unit=='millenium') {
            $value = $value * self::YEARS_PER_MILLENNIUM * self::MONTHS_PER_YEAR;
        } elseif ($unit=='century') {
            $value = $value * self::YEARS_PER_CENTURY * self::MONTHS_PER_YEAR;
        } elseif ($unit=='decade') {
            $value = $value * self::YEARS_PER_DECADE * self::MONTHS_PER_YEAR;
        } elseif ($unit=='year') {
            $value = $value * self::MONTHS_PER_YEAR;
        } elseif ($unit=='quarter') {
            $value = $value * self::MONTHS_PER_QUARTER;
        }

        if (!$this->overflow) {

            $day = (int) $this->day;

            $y = (int) $this->year;

            if ($action=='sub') {
                $m = (int) $this->month - $value;
            } else {
                $m = (int) $this->month + $value;
            }

            $d = 1;
            $h = (int) $this->hour;
            $i = (int) $this->minute;
            $s = (int) $this->second;

            while ($m < 1) {
                $m = $m +12;
                $y--;
            }

            while ($m > 12) {
                $m = $m -12;
                $y++;
            }

            $new = self::parse("$y/$m/$d $h:$i:$s");
            $lastday = (int) $new->endOfMonth()->day;

            if ($day > $lastday) {
                $d = $lastday;
            } else {
                $d = $day;
            }

            return self::parse("$y/$m/$d $h:$i:$s");
        }
        
        if ($action=='sub') {
            $this->date = strtotime($this->__toString() . " - $value months");
        } else {
            $this->date = strtotime($this->__toString() . " + $value months");
        }

        return $this;
    }


}