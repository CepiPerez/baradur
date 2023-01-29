<?php

class SubstituteBindings
{
    private function getClassName($item)
    {
        return $item->getClass()!=null
            ? $item->getClass()->getName() 
            : null;
    }

    public function handle($request, $next)
    {
        if (!isset($request->route->controller)) {
            return $request;
        }

        $class = $request->route->controller;
        $method = $request->route->func;
        $bindings = $request->route->scope_bindings;
        $trashed = $request->route->with_trashed;
        $params = isset($request->route->parametros)? $request->route->parametros : array();

        $instance = $this->getInstance($class);

        $reflectionMethod = new \ReflectionMethod($class, $method);
        $method_params = $reflectionMethod->getParameters();

        $arguments = array();

        if (count($method_params) > 0)
        {
            $arguments = $this->buildClassParameters(
                $reflectionMethod,
                $method_params,
                $params,
                $bindings,
                $trashed
            );
        }

        # If it's FormRequest, check authorization and validate
        $form = null;
        $model = null;
        foreach ($arguments as $arg)
        {
            if ($arg instanceof FormRequest) {
                $form = $arg;
            } elseif ($arg instanceof Model) {
                $model = $arg;
            }
        }

        if ($form) {
            if ($model) {
                $form->authorize($model);
            }
            $form->validateRules();
        }

        $request->route->instance = $instance;
        $request->route->parametros = $arguments;

        return $request;
    }

    private function getInstance($class)
    {
        $reflectionClass = new \ReflectionClass($class);
        $constructor = $reflectionClass->getConstructor();

        if (!$constructor) {
            return new $class;
        }

        $construct_params = $constructor->getParameters();

        if (count($construct_params) > 0)
        {
            $argunments = $this->buildClassParameters($constructor, $construct_params);

            $instance = $reflectionClass->newInstanceArgs($argunments);
        }

        return $instance;
    }

    private function buildClassParameters($class, $class_params, $route_params=array(), $bindings=false, $trashed=false)
    {
        $arguments = array();

        foreach ($class_params as $param)
        {
            $class_name = $this->getClassName($param);

            if (!$class_name) {
                $param = array_shift($route_params);
                $arguments[] = $param['value'];
            }

            if ($class_name && $class_name=='Request')
            {
                $arguments[] = request();
            }

            elseif ($class_name && is_subclass_of($class_name, 'FormRequest'))
            {
                $formRequest = new $class_name();
                $formRequest->generate(request()->route);
                $arguments[] = $formRequest;
            }
            
            elseif ($class_name && !is_subclass_of($class_name, 'Model'))
            {
                $arguments[] = app($class_name);
            }
            
            elseif ($class_name && is_subclass_of($class_name, 'Model'))
            {
                $model_key = reset($route_params);

                if (count($scope_bindings)==0 || !$bindings)
                {
                    $model = new $class_name;
                    $key = isset($model_key['index']) ? $model_key['index'] : $model->getRouteKeyName();
                    $query = $model->where($key, $model_key['value']);
                    if ($trashed && $query->_softDelete) $query = $query->withTrashed();
                    $record = $query->first();
                }
                else
                {
                    $last = $scope_bindings[count($scope_bindings)-1];
                    $arrkeys = array_keys($route_params);
                    $relation = Str::plural($arrkeys[0]);
                    $relation = $last->$relation();
                    $relation = $relation->where($relation->_primary[0], $model_key['value']);
                    if ($trashed && $relation->_softDelete) $relation = $relation->withTrashed();
                    $record = $relation->first();
                    $last->setQuery(null);
                }

                if (!$record) {
                    abort(404);
                }

                if ($bindings) {
                    $scope_bindings[] = $record;
                }
                
                $arguments[] = $record;

                array_shift($route_params);
            }
        }

        return $arguments;
    }


}