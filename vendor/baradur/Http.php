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

    public static function ssl($ssl)
    {
        $instance = self::instance();
        $instance->ssl($ssl);
    }

    public static function get($url, $json=true)
    {
        $instance = self::instance();
        $instance->simple_get($url);

        if (!$json)
            return $instance->last_response;

        return response(json_decode($instance->last_response), $instance->info['http_code']);
    }

    public static function post($url, $data=array(), $json=true)
    {
        $instance = self::instance();
        $instance->simple_post($url, $data);

        if (!$json)
            return $instance->last_response;

        return response(json_decode($instance->last_response), $instance->info['http_code']);
    }

    public static function put($url, $data=array(), $json=true)
    {
        $instance = self::instance();
        $instance->simple_put($url, $data);

        if (!$json)
            return $instance->last_response;

        return response(json_decode($instance->last_response), $instance->info['http_code']);
    }

    public static function delete($url, $data=array(), $json=true)
    {
        $instance = self::instance();
        $instance->simple_delete($url, $data);

        if (!$json)
            return $instance->last_response;

        return response(json_decode($instance->last_response), $instance->info['http_code']);
    }



}