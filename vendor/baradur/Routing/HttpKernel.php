<?php

Class HttpKernel
{
    protected $middleware = array();
    protected $routeMiddleware = array();
    protected $middlewareGroups = array();

    public function getMiddlewareList($parent)
    {
        $list = array();

        foreach ($this->$parent as $name => $class)
        {
            if (!isset($list[$name]))
                $list[$name] = $class;
        }

        return $list;
    }


}