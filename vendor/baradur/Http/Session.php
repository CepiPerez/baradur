<?php

class Session
{
    public static function all()
    {
        return session()->all();
    }

    public static function exists($key)
    {
        return session()->exists($key);
    }

    public static function missing($key)
    {
        return session()->missing($key);
    }

    public static function has($key)
    {
        return session()->has($key);
    }

    public static function only($keys)
    {
        return session()->only($keys);
    }

    public static function except($keys)
    {
        return session()->except($keys);
    }
}