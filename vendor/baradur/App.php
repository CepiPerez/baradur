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

    protected $basePath;
    protected $databasePath;
    protected $storagePath;
    protected $environmentPath;
    protected $environmentFile = '.env';

    public static function start()
    { 
        # Autologin
        if (!isset($_SESSION['guard'])) {
            if (isset($_COOKIE[config('app.name').'_token']) && !Auth::user() && Route::has('login')) {
                Auth::autoLogin($_COOKIE[config('app.name').'_token']);
            }
        }

        //echo Route::start();
        ob_start();
        $content = Route::start();
        //ob_end_clean();

        if ($content !== null) {
            CoreLoader::processResponse($content);
        }
        
        __exit();
    }

    public static function version()
    {
        return Application::VERSION;
    }

    public function isLocal()
    {
        return config('app.env') == 'local';
    }

    public function inProduction()
    {
        return config('app.env') == 'production';
    }

    public function isDownForMaintenance()
    {
        return file_exists(_DIR_.'/storage/framework/down');
    }

    public function maintenanceMode()
    {
        return file_exists(_DIR_.'storage/framework/down');
    }

    public static function getError($error)
    {
        global $errors; 
        return $errors->$error;
    }

    public static function getRequestToken()
    {
        if ( config('app.key') === null ) {
            throw new MissingAppKeyException('No application encryption key has been specified.');
        }

        if (!isset($_SESSION['_token'])) {
            session()->regenerateToken();
        }

        return session()->token();
    }

    public static function getLocale()
    {
        return config('app.locale');
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

        if (!isset($name)) {
            return $app;
        }

        if (isset($app->binds[$name])) {

            if (!$app->binds[$name]['shared']) {
                $class = $app->binds[$name]['concrete'];
                return new $class;
            }

            if (!isset($app->binds[$name]['instance'])) {
                $class = $app->binds[$name]['concrete'];
                $app->binds[$name]['instance'] = new $class;
            }

            return $app->binds[$name]['instance'];
        }

        foreach (array_keys($app->binds) as $key) {
            if ($app->binds[$key]['concrete'] == $name) {

                if (!isset($app->binds[$key]['instance'])) {
                    $class = $app->binds[$key]['concrete'];
                    $app->binds[$key]['instance'] = new $class;
                }

                return $app->binds[$key]['instance'];
            }
        }

        return new $name;
    }

}