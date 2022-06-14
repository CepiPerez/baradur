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

        return response(json_decode($instance->last_response), $instance->info['http_code']);
    }

    public static function post($url, $data=array())
    {
        $instance = self::instance();
        $instance->simple_post($url, $data);

        return response(json_decode($instance->last_response), $instance->info['http_code']);
    }

    public static function put($url, $data=array())
    {
        $instance = self::instance();
        $instance->simple_put($url, $data);

        return response(json_decode($instance->last_response), $instance->info['http_code']);
    }

    public static function delete($url, $data=array())
    {
        $instance = self::instance();
        $instance->simple_delete($url, $data);

        return response(json_decode($instance->last_response), $instance->info['http_code']);
    }



}