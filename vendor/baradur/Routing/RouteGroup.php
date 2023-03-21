<?php

Class RouteGroup 
{
    public $domain = null;
    public $prefix = '';
    public $middleware = array();
    public $name = '';
    public $controller = '';
    public $scope_bindings = true;

    public $added;

    public function __construct($parent)
    {
        $this->prefix = $parent->_prefix;
        $this->controller = $parent->_controller;
        $this->name = $parent->_name;
        $this->middleware = $parent->_middleware;
        $this->scope_bindings = $parent->_scope_bindings;
    }

    public function except($except)
    {
        foreach ($this->added as $route)
        {
            if (in_array($route->func, $except))
                Route::getInstance()->_collection->pull('name', $route->name);
        }

        return $this;
    }

    
    public function only($only)
    {
        foreach ($this->added as $route)
        {
            if (!in_array($route->func, $only))
                Route::getInstance()->_collection->pull('name', $route->name);
        }

        return $this;
    }

    private function backupRouteOptions($route)
    {
        $result = array();

        $result['middleware'] = $route->_middleware;
        $result['controller'] = $route->_controller;
        $result['domain'] = $route->_domain;
        $result['prefix'] = $route->_prefix;
        $result['name'] = $route->_name;
        $result['scope_bindings'] = $route->_scope_bindings;

        return $result;
    }

    private function restoreRouteOptions($route, $options)
    {
        $route->_middleware = $options['middleware'];
        $route->_controller = $options['controller'];
        $route->_domain = $options['domain'];
        $route->_prefix = $options['prefix'];
        $route->_name = $options['name'];
        $route->_scope_bindings = $options['scope_bindings'];
    }

    private function swicthRouteOptions($route, $source)
    {
        $route->_middleware = $source->middleware;
        $route->_controller = $source->controller;
        $route->_domain = $source->domain;
        $route->_prefix = $source->prefix;
        $route->_name = $source->name;
        $route->_scope_bindings = $source->scope_bindings;

    }

    public function group($routes)
    {        
        $instance = Route::getInstance();

        $backup = $this->backupRouteOptions($instance);

        $this->swicthRouteOptions($instance, $this);

        Route::group($routes);

        $this->restoreRouteOptions($instance, $backup);

        return $this;
    } 

    public function prefix($prefix)
    {
        $this->prefix .= $prefix;
        return $this;
    }

    public function controller($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    public function name($name)
    {
        $this->name .= $name;
        return $this;
    }

    public function middleware($middleware)
    {
        if (is_string($middleware))
            $middleware = array($middleware);

        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function withTrashed()
    {
        foreach ($this->added as $route)
        {
            $route->with_trashed = true;
        }
        return $this;
    }

}