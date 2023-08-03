<?php

class SubstituteBindings
{
    private static function getClassName($item)
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

        $reflectionMethod = new ReflectionMethod($class, $method);
        $method_params = $reflectionMethod->getParameters();

        $arguments = array();

        if (count($method_params) > 0)
        {
            $arguments = self::buildClassParameters(
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
            $arguments = self::buildClassParameters($constructor, $construct_params);

            $instance = $reflectionClass->newInstanceArgs($arguments);
        }


        return $instance;
    }

    public static function buildClassParameters($class, $class_params, $route_params=array(), $bindings=false, $trashed=false)
    {
        //dd($class_params, $route_params);
        $arguments = array();
        $scope_bindings = array();

        $route_binders = Route::__getBinders();

        foreach ($class_params as $param)
        {            
            $current_callback = null;
            $class_name = self::getClassName($param);

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
            
            elseif ($class_name && is_subclass_of($class_name, 'EnumHelper'))
            {
                $p2 = $route_params[$param->name];
                $p2 = $p2['value'];
                //dump($class_name, EnumHelper::instance($class_name)->$p2, $p2);
                $arguments[] = EnumHelper::instance($class_name)->set($p2);
            }

            elseif ($class_name && !is_subclass_of($class_name, 'Model'))
            {
                $arguments[] = app($class_name);
            }

            else {
                $parametro = $route_params[$param->name];

                $param_index = is_array($parametro) ? $parametro['index'] : null;
                $param_value = is_array($parametro) ? $parametro['value'] : $parametro;
                
                if (!$class_name) {
                    $parametro = reset($route_params);
                    $param_value = is_array($parametro) ? $parametro['value'] : $parametro;

                    if ($param_value!=='baradur_null_parameter') {
                        $arguments[] = $param_value;
                    }
                    
                    array_shift($route_params);
                }

                elseif (is_subclass_of($class_name, 'Model') && $param_value!=='baradur_null_parameter')
                {
                    if(!$parametro) {
                        foreach ($route_binders as $key => $val) {
                            if ($val['class']==$class_name) {
                                $parametro = $route_params[$key];
                                $param_value = is_array($parametro) ? $parametro['value'] : $parametro;
                            }
                        }                        
                    }

                    if (!$parametro) {
                        foreach ($route_params as $key => $val) {
                            if (isset($route_binders[$key])) {
                                $parametro = $route_params[$key];
                                $param_value = is_array($parametro) ? $parametro['value'] : $parametro;
                                $current_callback = $route_binders[$key]['callback'];
                            }
                        }
                    }

                    if (count($scope_bindings)==0 || !$bindings)
                    {
                        $model = new $class_name;
                        //$key = $param_index ? $param_index : $model->getRouteKeyName();
                        //$query = $model->where($key, $param_value);
                        //if ($trashed && $query->_softDelete) $query = $query->withTrashed();
                        //$record = $query->first();

                        if ($current_callback) {
                            list($class, $method) = getCallbackFromString($current_callback);
                            $record = call_user_func_array(array($class, $method), array($param_value));
                        } else {
                            $record = $model->resolveRouteBinding($param_value, $param_index);
                        }
                    }
                    else
                    {
                        $last = $scope_bindings[count($scope_bindings)-1];
                        $arrkeys = array_keys($route_params);
                        $relation = Str::plural($arrkeys[0]);
                        $relation = $last->$relation();
                        $relation = $relation->where($relation->_primary[0], $param_value);
                        if ($trashed && $relation->_softDelete) $relation = $relation->withTrashed();
                        $record = $relation->first();
                        //dd($relation);
                        //$record = $relation->_model->resolveRouteBinding($param_value);
                        $last->setQuery(null);
                    }
    
                    if (!$record) {
                        $ex = new ModelNotFoundException;
                        $ex->setModel($relation->_parent);
                        throw $ex;
                    }
    
                    if ($bindings) {
                        $scope_bindings[] = $record;
                    }
                    
                    $arguments[] = $record;
    
                    array_shift($route_params);
                }

            }
            
        }
        
        //dump($arguments);

        return $arguments;
    }


}