<?php

abstract Class ServiceProvider
{
    public static $regitered_policies = array();

    protected $listen = array();
    protected $policies = array();
    protected $observers = array();
    public $app;
    
    public function __construct()
    {
        global $app;
        $this->app = $app; 

        global $observers;
        foreach ($this->observers as $model => $class) {
            if (!isset($observers[$model])) {
                $observers[$model] = $class;
            }
        }

        if (count($this->listen) > 0) {
            global $listeners;
            $listeners = $this->listen;
        }
    }
    
    public function register() { }
    
    public function boot() { }

    private function checkCachedRoutes()
    {
        if (file_exists(_DIR_.'bootstrap/cache/routes.php')) {
            $content = file_get_contents(_DIR_.'bootstrap/cache/routes.php');
            $content = unserialize($content);
            $routes = Route::getInstance();
            $routes->_collection = collect($content);

            return true;
        }

        return false;
    }

    protected function routes($param)
    {
        if ($this->checkCachedRoutes()) {
            return;
        }

        list($c, $m, $p) = getCallbackFromString($param);
        executeCallback($c, $m, $p, $this);
    }

    public function registerPolicies()
    {
        foreach ($this->policies as $key => $val) {
            self::$regitered_policies[$key] = $val;
        }
    }

}