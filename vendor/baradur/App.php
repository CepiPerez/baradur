<?php

Class App {

    public $result;
    public $action;
    public $code = 200;
    public $type;
    public $filename;
    public $arguments;
    public $inline;
    public $headers;
    public static $localization = null;
    public $binds = array();

    public static function start() { 

        # Autologin
        if (!isset($_SESSION['user']))
        {
            if (isset($_COOKIE[env('APP_NAME').'_token']) && !Auth::user() && Route::has('login'))
            {
                Auth::autoLogin($_COOKIE[env('APP_NAME').'_token']);
            }
        }

        return Route::start();
    }

    public function inProduction()
    {
        return env('APP_ENV')=='production';
    }

    public function __call($method, $parameters)
    {
        if (! Str::startsWith($method, 'with')) {
            throw new Exception("Method [$method] does not exist on view.");
        }

        return $this->with(Str::camel(substr($method, 4)), $parameters[0]);
    }

    public static function getError($error)
    {
        global $errors; 
        return $errors->$error;
    }

    public static function generateToken()
    {
        $timestamp = date('Y-m-d H:i:s');
        $csrf = hash_hmac('sha256', Route::current()->url, $_SESSION['key']);
        $_SESSION['tokens'][$csrf]['timestamp'] = $timestamp;
        $_SESSION['tokens'][$csrf]['counter'] = 1;
        return $csrf;
    }

    public static function getLocale()
    {
        global $locale;
        return $locale;
    }

    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->binds[$abstract] = array(
            'concrete' => $concrete, 
            'shared' => $shared
        );
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }
    
    public static function instance($name = null)
    {
        global $app;

        if (!isset($name))
        {
            return $app;
        }

        if (isset($app->binds[$name]))
        {
            if (!$app->binds[$name]['shared'])
            {
                $class = $app->binds[$name]['concrete'];
                return new $class;
            }

            if (!isset($app->binds[$name]['instance']))
            {
                $class = $app->binds[$name]['concrete'];
                $app->binds[$name]['instance'] = new $class;
            }

            return $app->binds[$name]['instance'];
        }

        foreach ($app->binds as $key => $val)
        {
            if ($app->binds[$key]['concrete'] == $name)
            {
                if (!isset($app->binds[$key]['instance']))
                {
                    $class = $app->binds[$key]['concrete'];
                    $app->binds[$key]['instance'] = new $class;
                }

                return $app->binds[$key]['instance'];
            }
        }

        return new $name;
    }

}