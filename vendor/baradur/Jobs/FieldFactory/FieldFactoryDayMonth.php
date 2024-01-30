<?php

class FieldFactoryDayMonth extends FieldFactoryAbstract
{
    protected $rangeStart = 1;
    protected $rangeEnd = 31;

    /**
     * Get the nearest day of the week for a given day in a month
     *
     * @param int $currentYear  Current year
     * @param int $currentMonth Current month
     * @param int $targetDay    Target day of the month
     *
     * @return \DateTime Returns the nearest date
     */
    private static function getNearestWeekday($currentYear, $currentMonth, $targetDay)
    {
        $tday = str_pad($targetDay, 2, '0', STR_PAD_LEFT);
        $target = Carbon::instance("$currentYear-$currentMonth-$tday");
        $currentWeekday = (int) $target->format('N');

        if ($currentWeekday < 6) {
            return $target;
        }

        $lastDayOfMonth = $target->format('t');

        foreach (array(-1, 1, -2, 2) as $i) {
            $adjusted = $targetDay + $i;
            if ($adjusted > 0 && $adjusted <= $lastDayOfMonth) {
                $target->setDate($currentYear, $currentMonth, $adjusted);
                if ($target->format('N') < 6 && $target->format('m') == $currentMonth) {
                    return $target;
                }
            }
        }
    }

    public function isSatisfiedBy(DateTime $date, $value)
    {
        // ? states that the field value is to be skipped
        if ($value == '?') {
            return true;
        }

        $fieldValue = $date->format('d');

        // Check to see if this is the last day of the month
        if ($value == 'L') {
            return $fieldValue == $date->format('t');
        }

        // Check to see if this is the nearest weekday to a particular value
        if (strpos($value, 'W')) {
            // Parse the target day
            $targetDay = substr($value, 0, strpos($value, 'W'));
            // Find out if the current day is the nearest day of the week
            return $date->format('j') == self::getNearestWeekday(
                $date->format('Y'),
                $date->format('m'),
                $targetDay
            )->format('j');
        }

        return $this->isSatisfied($date->format('d'), $value);
    }

    public function increment(DateTime $date, $invert = false)
    {
        if ($invert) {
            $date->modify('previous day');
            $date->setTime(23, 59);
        } else {
            $date->modify('next day');
            $date->setTime(0, 0);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function validate($value)
    {
        $basicChecks = parent::validate($value);

        // Validate that a list don't have W or L
        if (strpos($value, ',') !== false && (strpos($value, 'W') !== false || strpos($value, 'L') !== false)) {
            return false;
        }

        if (!$basicChecks) {

            if ($value === 'L') {
                return true;
            }

            if (preg_match('/^(.*)W$/', $value, $matches)) {
                return $this->validate($matches[1]);
            }

            return false;
        }

        return $basicChecks;
    }
}