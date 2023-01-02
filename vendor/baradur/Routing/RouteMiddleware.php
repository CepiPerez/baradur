<?php

Class RouteMiddleware
{
    public $middleware;
    public $only;
    public $except;

    public function except($method)
    {
        $this->except = $method;
    }

    public function only($method)
    {
        $this->only = $method;
    }

    /* public function findMiddlewareClass($value)
    {
        MiddlewareHelper::bootKernel();
        $middlewares = MiddlewareHelper::getMiddlewareList();
        $middleware_groups = MiddlewareHelper::getMiddlewareList();

        if (isset($middlewares[$value]))
        {
            return $middlewares[$value];
        }
        elseif (isset($middleware_groups[$value]))
        {
            foreach ($middleware_groups[$value] as $midd)
            {
                list($midd, $params) = explode(':', $midd);
                
                if (isset($middlewares[$midd]))
                {
                    return $middlewares[$midd];
                }
            }
        }
        elseif (class_exists($value))
        {
            return $value;
        }

        throw new Exception("Error: Middleware $value not found");
    } */

}