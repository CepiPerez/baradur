<?php

class FieldFactoryMinute extends FieldFactoryAbstract
{
    protected $rangeStart = 0;
    protected $rangeEnd = 59;

    public function isSatisfiedBy($date, $value)
    {
        return $this->isSatisfied($date->format('i'), $value);
    }

    public function increment($date, $invert = false, $parts = null)
    {
        if (is_null($parts)) {
            if ($invert) {
                $date->subMinute(); // modify('-1 minute');
            } else {
                $date->addMinute(); // modify('+1 minute');
            }
            return $this;
        }

        $parts = strpos($parts, ',') !== false ? explode(',', $parts) : array($parts);
        $minutes = array();
        foreach ($parts as $part) {
            $minutes = array_merge($minutes, $this->getRangeForExpression($part, 59));
        }

        $current_minute = $date->format('i');
        $position = $invert ? count($minutes) - 1 : 0;
        if (count($minutes) > 1) {
            for ($i = 0; $i < count($minutes) - 1; $i++) {
                if ((!$invert && $current_minute >= $minutes[$i] && $current_minute < $minutes[$i + 1]) ||
                    ($invert && $current_minute > $minutes[$i] && $current_minute <= $minutes[$i + 1])) {
                    $position = $invert ? $i : $i + 1;
                    break;
                }
            }
        }

        if ((!$invert && $current_minute >= $minutes[$position]) || ($invert && $current_minute <= $minutes[$position])) {
            //$date->modify(($invert ? '-' : '+') . '1 hour');
            if ($invert) $date->subHour(); else $date->addHour();
            $date->setTime($date->format('H'), $invert ? 59 : 0, $date->second);
        }
        else {
            $date->setTime($date->format('H'), $minutes[$position], $date->second);
        }

        return $this;
    }
}