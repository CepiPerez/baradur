<?php

Class RouteItem 
{
    public $method;
    public $domain;
    public $url;
    //public $full_url;
    public $middleware;
    public $name;
    public $scope_bindings;
    public $controller;
    public $func;
    public $view;
    public $viewparams;
    public $with_trashed = false;
    public $wheres = array();
    public $regex;
    public $parametros;
    public $domain_parametros;
    public $url_parametros;

    /**
     * Assign a name to route\
     * 
     * @param string $name
     * @param string $controller
     */
    public function name($name)
    {
        $this->name .= $name;
        return $this;
    }

    public function middleware($middleware)
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function can($action, $param=null)
    {
        $this->middleware[] = 'can:'.$action.($param? ','.$param : '');
        return $this;
    }

    public function scopeBindings()
    {
        $this->scope_bindings = true;
        return $this;
    }

    public function withoutScopeBindings()
    {
        $this->scope_bindings = false;
        return $this;
    }

    public function withoutMiddleware($middleware)
    {
        $middleware = is_array($middleware) ? $middleware : array($middleware);

        foreach ($middleware as $m) {
            unset($this->middleware[$m]);
        }

        return $this;
    }

    public function withTrashed()
    {
        $this->with_trashed = true;
        return $this;
    }

    public function named()
    {
        if (is_null($this->name)) {
            return false;
        }

        foreach (func_get_args() as $pattern) {
            if (Str::is($pattern, $this->name)) {
                return true;
            }
        }

        return false;
    }

    public function where($name, $expression = null)
    {
        foreach ($this->parseWhere($name, $expression) as $name => $expression) {
            $this->wheres[$name] = $expression;
        }

        return $this;
    }

    protected function parseWhere($name, $expression)
    {
        return is_array($name) ? $name : array($name => $expression);
    }

    protected function assignExpressionToParameters($parameters, $expression)
    {
        $parameters = is_array($parameters)? $parameters : array($parameters);

        foreach ($parameters as $param) {
            $this->where($param, $expression);
        }

        return $this;
    }

    public function whereAlpha($parameters)
    {
        return $this->assignExpressionToParameters($parameters, '[a-zA-Z]+');
    }

    public function whereAlphaNumeric($parameters)
    {
        return $this->assignExpressionToParameters($parameters, '[a-zA-Z0-9]+');
    }

    public function whereNumber($parameters)
    {
        return $this->assignExpressionToParameters($parameters, '[0-9]+');
    }

    public function whereUlid($parameters)
    {
        return $this->assignExpressionToParameters($parameters, '[0-7][0-9a-hjkmnp-tv-zA-HJKMNP-TV-Z]{25}');
    }

    public function whereUuid($parameters)
    {
        return $this->assignExpressionToParameters($parameters, '[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}');
    }

    public function whereIn($parameters, $values)
    {
        return $this->assignExpressionToParameters($parameters, implode('|', $values));
    }

}