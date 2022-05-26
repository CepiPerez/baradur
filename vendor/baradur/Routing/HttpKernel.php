<?php

Class HttpKernel
{
    protected $routeMiddleware = array();

    public function getMiddlewareList()
    {
 
        $middlewares = array();
        foreach ($this->routeMiddleware as $name => $class)
        {
            if (!isset($middlewares[$name]))
                $middlewares[$name] = $class;
        }

        return $middlewares;

    }

    public function getMiddlewareGroup()
    {
 
        $groups = array();
        foreach ($this->middlewareGroups as $name => $class)
        {
            if (!isset($groups[$name]))
                $groups[$name] = $class;
        }

        return $groups;

    }




}