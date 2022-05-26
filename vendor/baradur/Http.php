<?php

class Http
{
    private static $_instance;

    /**
     * Get Route instance
     * 
     * @return Curl
     */
    public static function instance()
    {
        if (!self::$_instance)
            self::$_instance = new Curl;

        return self::$_instance;
    }


    public static function get($url)
    {
        $instance = self::instance();
        $instance->simple_get($url);


        return response($instance->last_response, $instance->info['http_code']);
    }


}