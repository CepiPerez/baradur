<?php

class Limit
{
    public $key;
    public $maxAttempts;
    public $decayMinutes;
    public $responseCallback;

    public function __construct($key = '', $maxAttempts = 60, $decayMinutes = 1)
    {
        $this->key = $key;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    public static function perMinute($maxAttempts)
    {
        return new Limit('', $maxAttempts);
    }

    public static function perMinutes($decayMinutes, $maxAttempts)
    {
        return new Limit('', $maxAttempts, $decayMinutes);
    }

    public static function perHour($maxAttempts, $decayHours = 1)
    {
        return new Limit('', $maxAttempts, 60 * $decayHours);
    }

    public static function perDay($maxAttempts, $decayDays = 1)
    {
        return new Limit('', $maxAttempts, 60 * 24 * $decayDays);
    }

    public static function none()
    {
        return new Unlimited;
    }

    public function by($key)
    {
        $this->key = $key;

        return $this;
    }

    public function response($callback)
    {
        $this->responseCallback = $callback;

        return $this;
    }
}