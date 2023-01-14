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


    public function __construct($time = null, $tz = null)
    {
        if ($time instanceof Carbon)
            $time = $time->toDateTimeString();

        $this->date = $time? strtotime($time) : time();
    }
    
    private static function instance($time = null)
    {
        return new Carbon($time);
    }

    public function __toString()
    {
        return date($this->localToStringFormat? $this->localToStringFormat : self::DEFAULT_TO_STRING_FORMAT,
            $this->date);
    }

    public function settings($settings)
    {
        $this->localToStringFormat = $settings['toStringFormat'] ? $settings['toStringFormat'] : null;
        $this->localSerializer = $settings['toJsonFormat'] ? $settings['toJsonFormat'] : null;

        return $this;
    }

    private function getShort($key)
    {
        if ($key=='minute') return 'min';
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
                'dd' => function (CarbonInterface $date, $originalFormat = null) {
                    return $date->getTranslatedMinDayName($originalFormat);
                },
                'ddd' => function (CarbonInterface $date, $originalFormat = null) {
                    return $date->getTranslatedShortDayName($originalFormat);
                },
                'dddd' => function (CarbonInterface $date, $originalFormat = null) {
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
                'S' => function (CarbonInterface $date) {
                    return (string) floor($date->micro / 100000);
                },
                'SS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro / 10000), 2, '0', STR_PAD_LEFT);
                },
                'SSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro / 1000), 3, '0', STR_PAD_LEFT);
                },
                'SSSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro / 100), 4, '0', STR_PAD_LEFT);
                },
                'SSSSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro / 10), 5, '0', STR_PAD_LEFT);
                },
                'SSSSSS' => ['getPaddedUnit', ['micro', 6]],
                'SSSSSSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro * 10), 7, '0', STR_PAD_LEFT);
                },
                'SSSSSSSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro * 100), 8, '0', STR_PAD_LEFT);
                },
                'SSSSSSSSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro * 1000), 9, '0', STR_PAD_LEFT);
                },
                'M' => 'month',
                'MM' => ['rawFormat', ['m']],
                'MMM' => function (CarbonInterface $date, $originalFormat = null) {
                    $month = $date->getTranslatedShortMonthName($originalFormat);
                    $suffix = $date->getTranslationMessage('mmm_suffix');
                    if ($suffix && $month !== $date->monthName) {
                        $month .= $suffix;
                    }

                    return $month;
                },
                'MMMM' => function (CarbonInterface $date, $originalFormat = null) {
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
                'YYYYYY' => function (CarbonInterface $date) {
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
        if ($name=='year') return date('Y', $this->date);
        if ($name=='month') return date('n', $this->date);
        if ($name=='day') return date('j', $this->date);
        if ($name=='hour') return date('H', $this->date);
        if ($name=='minute') return (int)date('i', $this->date);
        if ($name=='second') return (int)date('s', $this->date);
        if ($name=='daysInMonth') { return date('t', $this->date); };
        if ($name=='dayOfWeek') { return date('w', $this->date); };
        if ($name=='firstWeekDay') { return 0; };
        if ($name=='lastWeekDay') { return 6; };
        if ($name=='timestamp') { return $this->date; };
        
        return null;
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

    public function diffForHumans($other=null, $syntax=Carbon::DIFF_ABSOLUTE, $short=false, $parts=1)
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

    }

    public static function now()
    {
        return self::instance();
    }

    public static function today()
    {
        return self::instance()->startOfDay();
    }

    public static function parse($time)
    {
        return self::instance($time);
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

    public function addMonths($value = 1)
    {
        $this->date = strtotime($this->__toString() . " + $value months");
        return $this;
    }

    public function addMonth()
    {
        return $this->addMonths(1);
    }

    public function subMonths($value = 1)
    {
        $this->date = strtotime($this->__toString() . " - $value months");
        return $this;
    }

    public function subMonth()
    {
        return $this->subMonths(1);
    }

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

    /* public static function singularUnit(string $unit)
    {
        $unit = rtrim(mb_strtolower($unit), 's');

        if ($unit === 'centurie') {
            return 'century';
        }

        if ($unit === 'millennia') {
            return 'millennium';
        }

        return $unit;
    } */

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

        throw new Exception('Precision unit expected among: minute, second, millisecond and microsecond.');
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

}