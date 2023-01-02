<?php

class RetryHelper
{

    public static function retry($times, $callback, $sleepMilliseconds=0, $when=null)
    {
        $attempts = 0;

        $backoff = [];

        if (is_array($times)) {
            $backoff = $times;
            $times = count($times) + 1;
        }


        return self::startRetry($attempts, $backoff, $times, $callback, $sleepMilliseconds, $when);

    }

    private function getCallback($callback, $parameters)
    {
        if (strpos($callback, '@')!==false)
        {
            list($class, $method, $params) = getCallbackFromString($callback);
            return call_user_func_array(array($class, $method), $parameters);
        }
    }

    private function startRetry($attempts, $backoff, $times, $callback, $sleepMilliseconds, $when)
    {
        $attempts++;
        $times--;

        try
        {
            return self::getCallback($callback, array($attempts));
        } 
        catch (Exception $e)
        {
            if ($times < 1 || ($when && ! self::getCallback($when, array($e)))) {
                throw $e;
            }

            $sleepMilliseconds = $backoff[$attempts - 1] ? $backoff[$attempts - 1] : $sleepMilliseconds;

            if ($sleepMilliseconds) {
                usleep(self::value($sleepMilliseconds, $attempts, $e) * 1000);
            }

            return self::startRetry($attempts, $backoff, $times, $callback, $sleepMilliseconds, $when);
        }

    }

    public static function value($value, $attempts, $e)
    {
        if (is_numeric($value))
        {
            return $value;
        }

        if (strpos($value, '@')!==false)
        {
            return self::getCallback($value, array($attempts, $e));
        }

        return null;
    }



}