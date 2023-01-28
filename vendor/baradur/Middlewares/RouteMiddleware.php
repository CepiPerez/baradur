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


}