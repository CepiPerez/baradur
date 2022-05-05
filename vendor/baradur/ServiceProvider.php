<?php

Class ServiceProvider
{
    protected $observers = array();

    protected $routeMiddleware = array();

    public function __construct()
    {
        global $observers;
        foreach ($this->observers as $model => $class)
        {
            if (!isset($observers[$model]))
                $observers[$model] = $class;
        }

        global $middlewares;
        foreach ($this->routeMiddleware as $model => $class)
        {
            if (!isset($middlewares[$model]))
                $middlewares[$model] = $class;
        }

    }




}