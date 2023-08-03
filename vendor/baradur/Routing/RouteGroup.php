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
        $except = is_array($except) ? $except : array($except);

        foreach ($this->added as $route) {
            if (in_array($route->func, $except)) {
                Route::getInstance()->_collection->pull('name', $route->name);
            }
        }

        return $this;
    }

    
    public function only($only)
    {
        foreach ($this->added as $route) {
            if (!in_array($route->func, $only)) {
                Route::getInstance()->_collection->pull('name', $route->name);
            }
        }

        return $this;
    }

    public static function backupRouteOptions($route)
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

    public static function applyRouteOptions($route, $options)
    {
        if (is_array($options['middleware'])) {
            foreach($options['middleware'] as $m) {
                if (!in_array($m, $route->_middleware)) {
                    $route->_middleware[] = $m;
                }
            }
        } else {
            if (!in_array($options['middleware'], $route->_middleware)) {
                $route->_middleware[] = $options['middleware'];
            }
        }

        //$route->_middleware = array_merge($route->_middleware, $middlewares);
        $route->_controller = $options['controller'];
        $route->_domain = $options['domain'];
        $route->_prefix = $options['prefix'];
        $route->_name = $options['name'];
        $route->_scope_bindings = $options['scope_bindings'];
    }

    public static function restoreRouteOptions($route, $options)
    {
        $route->_middleware = $options['middleware'];
        $route->_controller = $options['controller'];
        $route->_domain = $options['domain'];
        $route->_prefix = $options['prefix'];
        $route->_name = $options['name'];
        $route->_scope_bindings = $options['scope_bindings'];
    }

    public static function swicthRouteOptions($route, $source)
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

        //$backup = self::backupRouteOptions($instance);

        //self::swicthRouteOptions($instance, $this);

        $attributes = array(
            'domain' => $this->domain,
            'prefix' => $this->prefix,
            'middleware' => $this->middleware,
            'name' => $this->name,
            'controller' => $this->controller,
            'scope_bindings' => $this->scope_bindings
        );

        Route::group($attributes, $routes);

        //self::applyRouteOptions($instance, $backup);

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
        $middleware = is_string($middleware) ? array($middleware) : $middleware;
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function withTrashed()
    {
        foreach ($this->added as $route) {
            $route->with_trashed = true;
        }

        return $this;
    }

}