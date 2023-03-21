<?php

trait Dispatchable
{
    private static function instance($params = null)
    {
        return new self($params);
    }

    public static function dispatch($object)
    {
        $instance = self::instance($object);
        return event($instance);
    }

    public static function dispatchIf($boolean, $object)
    {
        if ($boolean) {
            $instance = self::instance($object);
            return event($instance);
        }
    }

    public static function dispatchUnless($boolean, $object)
    {
        if (! $boolean) {
            $instance = self::instance($object);
            return event($instance);
        }
    }

    /* public static function broadcast()
    {
        return broadcast(new static(...func_get_args()));
    } */
}