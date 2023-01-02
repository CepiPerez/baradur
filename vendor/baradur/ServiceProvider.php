<?php

abstract Class ServiceProvider
{
    protected $observers = array();

    public $app;

    public function __construct()
    {
        global $app;
        $this->app = $app; 

        global $observers;
        foreach ($this->observers as $model => $class)
        {
            if (!isset($observers[$model]))
                $observers[$model] = $class;
        }

    }

    public function register()
    {

    }

    public function boot()
    {
        
    }


    public function routes($param)
    {

        list($c, $m, $p) = getCallbackFromString($param);
        executeCallback($c, $m, $p, $this);
        //call_user_func_array(array($c, $m), $p);
    }




}