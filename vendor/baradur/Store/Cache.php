<?php

Class Cache
{
    private static $drivers = null;

    private static function getDriver($driver=null)
    {
        if (!isset($driver))
            $driver = env('CACHE_DRIVER', 'file');

        if (!isset(self::$drivers[$driver]))
        {
            if ($driver == 'file')
            {
                $files = new Filesystem();
                $cache = _DIR_.'storage/framework/cache';
                self::$drivers['file'] = new FileStore($files, $cache, 0777);
            }
            elseif ($driver == 'redis')
            {
                self::$drivers['redis'] = new RedisDB;
            }

        }
        return self::$drivers[$driver];
    }


    /**
     * Assing cache driver
     * 
     * @return RedisDB|Filestore
     */
    public static function store($store=null)
    {
        return self::getDriver($store);
    }


    public static function has($key)
    {
        return self::getDriver()->has($key);
    }

    public static function get($key)
    {
        return self::getDriver()->get($key);
    }

    public static function put($key, $value)
    {
        return self::getDriver()->put($key, $value, 86400);
    }

    public static function forget($key)
    {
        return self::getDriver()->forget($key);
    }

    public static function flush()
    {
        return self::getDriver()->flush();
    }

    public static function remember($key, $seconds, $callback)
    {
        return self::getDriver()->remember($key, $seconds, $callback);
    }

    public static function pull($key)
    {
        $res = self::get($key);
        self::forget($key);
        return $res;
    }


}