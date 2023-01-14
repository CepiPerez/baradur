<?php

Class SmartCache
{
    private static $instance = null;

    private static function getInstance($group=null)
    {
        $cache = _DIR_.'storage/framework/cache';

        if (!isset(self::$instance))
        {
            $files = new Filesystem();
            self::$instance = new FileStore($files, $cache, 0777);
        }

        if (isset($group))
        {
            self::$instance->setDirectory( $cache . '/' . $group );
        }

        return self::$instance;
    }

    public static function has($group, $key)
    {
        if (is_array($key)) $key = md5(serialize($key));
        else if (is_object($key)) $key = md5(serialize((array)$key));

        return self::getInstance($group)->has($key);
    }

    public static function get($group, $key)
    {
        if (is_array($key)) $key = md5(serialize($key));
        else if (is_object($key)) $key = md5(serialize((array)$key));

        return unserialize(self::getInstance($group)->get($key));
    }

    public static function put($group, $key, $value)
    {
        if (is_array($key)) $key = md5(serialize($key));
        else if (is_object($key)) $key = md5(serialize((array)$key));

        $value = serialize($value);

        return self::getInstance($group)->put($key, $value, 86400);
    }

    public static function forget($group, $key)
    {
        if (is_array($key)) $key = md5(serialize($key));
        else if (is_object($key)) $key = md5(serialize((array)$key));

        return self::getInstance($group)->forget($key);
    }

    public static function flush($group)
    {
        return self::getInstance($group)->flush();
    }



}