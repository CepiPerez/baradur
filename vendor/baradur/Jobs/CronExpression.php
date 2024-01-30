<?php

/**
 * CRON expression parser that can determine whether or not a CRON expression is
 * due to run, the next run date and previous run date of a CRON expression.
 * The determinations made by this class are accurate if checked run once per
 * minute (seconds are dropped from date time comparisons).
 *
 * Schedule parts must map to:
 * minute [0-59], hour [0-23], day of month, month [1-12|JAN-DEC], day of week
 * [1-7|MON-SUN], and an optional year.
 *
 * @link http://en.wikipedia.org/wiki/Cron
 */

class CronExpression
{
    const MINUTE = 0;
    const HOUR = 1;
    const DAY = 2;
    const MONTH = 3;
    const WEEKDAY = 4;
    const YEAR = 5;

    private $fieldFactory;
    private $cronParts;
    private $maxIterationCount = 1000;
    private static $order = array(self::YEAR, self::MONTH, self::DAY, self::WEEKDAY, self::HOUR, self::MINUTE);

    public static function factory($expression, $fieldFactory = null)
    {
        $mappings = array(
            '@yearly' => '0 0 1 1 *',
            '@annually' => '0 0 1 1 *',
            '@monthly' => '0 0 1 * *',
            '@weekly' => '0 0 * * 0',
            '@daily' => '0 0 * * *',
            '@hourly' => '0 * * * *'
        );

        if (isset($mappings[$expression])) {
            $expression = $mappings[$expression];
        }

        return new CronExpression($expression, $fieldFactory ? $fieldFactory : new FieldFactory());
    }

    public static function isValidExpression($expression)
    {
        try {
            self::factory($expression);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }
    
    public function __construct($expression, $fieldFactory = null)
    {
        $this->fieldFactory = $fieldFactory ? $fieldFactory : new FieldFactory();
        $this->setExpression($expression);
    }

    public function setExpression($value)
    {
        $this->cronParts = preg_split('/\s/', $value, -1, PREG_SPLIT_NO_EMPTY);

        if (count($this->cronParts) < 5) {
            throw new InvalidArgumentException(
                $value . ' is not a valid CRON expression'
            );
        }

        foreach ($this->cronParts as $position => $part) {
            $this->setPart($position, $part);
        }

        return $this;
    }

    public function setPart($position, $value)
    {
        //dump($this->fieldFactory->getField($position));
        if (!$this->fieldFactory->getField($position)->validate($value)) {
            throw new InvalidArgumentException(
                'Invalid CRON field value ' . $value . ' at position ' . $position
            );
        }

        $this->cronParts[$position] = $value;

        return $this;
    }

    public function setMaxIterationCount($maxIterationCount)
    {
        $this->maxIterationCount = $maxIterationCount;
        
        return $this;
    }

    public function getNextRunDate($currentTime = 'now', $nth = 0, $allowCurrentDate = false)
    {
        return $this->getRunDate($currentTime, $nth, false, $allowCurrentDate);
    }

    public function getPreviousRunDate($currentTime = 'now', $nth = 0, $allowCurrentDate = false)
    {
        return $this->getRunDate($currentTime, $nth, true, $allowCurrentDate);
    }

    public function getMultipleRunDates($total, $currentTime = 'now', $invert = false, $allowCurrentDate = false)
    {
        $matches = array();
        for ($i = 0; $i < max(0, $total); $i++) {
            try {
                $matches[] = $this->getRunDate($currentTime, $i, $invert, $allowCurrentDate);
            } catch (RuntimeException $e) {
                break;
            }
        }

        return $matches;
    }

    public function getExpression($part = null)
    {
        if (null === $part) {
            return implode(' ', $this->cronParts);
        } elseif (array_key_exists($part, $this->cronParts)) {
            return $this->cronParts[$part];
        }

        return null;
    }

    public function __toString()
    {
        return $this->getExpression();
    }

    public function isDue($currentTime = 'now')
    {
        /* dump($currentTime, "DUE");
        if ('now' === $currentTime) {
            $currentDate = date('Y-m-d H:i');
            $currentTime = strtotime($currentDate);
        } elseif ($currentTime instanceof Carbon) {
            $currentDate = $currentTime->format('Y-m-d H:i');
            $currentTime = strtotime($currentDate);
        }  */
        $currentDate = Carbon::parse($currentTime);
        $currentTime = $currentDate->startOfMinute()->timestamp;

        try {
            return $this->getNextRunDate($currentDate, 0, true)->startOfMinute()->timestamp == $currentTime;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function getRunDate($currentTime = null, $nth = 0, $invert = false, $allowCurrentDate = false)
    {        
        if ($currentTime instanceof Carbon) {
            $currentDate = $currentTime->copy();
        } else {
            $currentDate = Carbon::now();
        }

        $currentDate->setTime($currentDate->format('H'), $currentDate->format('i'), 0);
        $nextRun = clone $currentDate;
        $nth = (int) $nth;

        // We don't have to satisfy * or null fields
        $parts = array();
        $fields = array();
        foreach (self::$order as $position) {
            $part = $this->getExpression($position);
            if (null === $part || '*' === $part) {
                continue;
            }
            $parts[$position] = $part;
            $fields[$position] = $this->fieldFactory->getField($position);
        }

        // Set a hard limit to bail on an impossible date
        for ($i = 0; $i < $this->maxIterationCount; $i++) {

            foreach ($parts as $position => $part) {
                $satisfied = false;
                // Get the field object used to validate this part
                $field = $fields[$position];
                // Check if this is singular or a list
                if (strpos($part, ',') === false) {
                    $satisfied = $field->isSatisfiedBy($nextRun, $part);
                } else {
                    foreach (array_map('trim', explode(',', $part)) as $listPart) {
                        if ($field->isSatisfiedBy($nextRun, $listPart)) {
                            $satisfied = true;
                            break;
                        }
                    }
                }

                // If the field is not satisfied, then start over
                if (!$satisfied) {
                    $field->increment($nextRun, $invert, $part);
                    continue 2;
                }
            }

            // Skip this match if needed
            if ((!$allowCurrentDate && $nextRun == $currentDate) || --$nth > -1) {
                $this->fieldFactory->getField(0)->increment($nextRun, $invert, isset($parts[0]) ? $parts[0] : null);
                continue;
            }

            return $nextRun;
        }

        // @codeCoverageIgnoreStart
        throw new RuntimeException('Impossible CRON expression');
        // @codeCoverageIgnoreEnd
    }
}
