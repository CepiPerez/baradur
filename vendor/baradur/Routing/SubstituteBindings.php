<?php

class SubstituteBindings
{
    private function getItemClass($item)
    {
        return $item->getClass()!=null ? $item->getClass()->getName() : null;
    }

    public function handle($request, $next)
    {
        if (!isset($request->route->controller))
            return $request;

        $class = $request->route->controller;
        $method = $request->route->func;
        $bindings = $request->route->scope_binding==1;
        $trashed = isset($request->route->with_trashed);

        $reflectionClass = new \ReflectionClass($class);

        # Creating Controller
        # Inject dependencies if needed
        $constructor = $reflectionClass->getConstructor();
        $parameters = $constructor? $constructor->getParameters() : null;

        if ($constructor && $parameters)
        {
            $iargs = array();
            foreach ($parameters as $inject)
            {
                $iclass = $this->getItemClass($inject);
                if (!isset($iclass)) $iclass = isset(app()->binds[$inject->name])? app()->binds[$inject->name] : null;

                if (isset($iclass))
                {
                    $iargs[] = app($iclass);
                }
                
                else if (class_exists($inject['class']))
                {
                    $iargs[] = new $inject['class'];
                }
            }

            $instance = $reflectionClass->newInstanceArgs($iargs);
        }
        else
        {
            $instance = new $class;
        }
        
        $parametros = isset($request->route->parametros)? $request->route->parametros : array();
        

        $reflectionMethod = new \ReflectionMethod($class, $method);
        $paramNames = $reflectionMethod->getParameters();

        $scope_bindings = array();
        $final_params = array();


        # Binding parameters

        if (count($paramNames)>0)
        {
            $formRequest = null;
            $record = null;

            for ($i=0; $i<count($paramNames); $i++)
            {
                $inject = $paramNames[$i];
                $iclass = $this->getItemClass($inject);

                if (!isset($iclass)) 
                    $iclass = isset(app()->binds[$inject->name])? app()->binds[$inject->name] : null;
                
                
                if (class_exists($iclass) && is_subclass_of($iclass, 'FormRequest'))
                {
                    $formRequest = new $iclass();
                    $formRequest->generate($request->route);
                    $final_params[] = $formRequest;
                    unset($parametros[$paramNames[$i]->name]);
                }
                
                elseif (class_exists($iclass) && $iclass!='Request' && isset($parametros[$paramNames[$i]->name]))
                {
                    

                    if (is_subclass_of($iclass, 'Model'))
                    {
                        $data = $parametros[$paramNames[$i]->name];
                        //$key = isset($data['index']) ? $data['index'] : $model->getRouteKeyName();
                        $val = $data['value'];

                        if (count($scope_bindings)==0 || !$bindings)
                        {
                            $model = new $iclass;
                            $key = isset($data['index']) ? $data['index'] : $model->getRouteKeyName();
                            $query = Model::instance($iclass)->where($key, $val);
                            $record = $trashed? $query->withTrashed()->first() : $query->first();
                        }
                        else
                        {
                            $last = $scope_bindings[count($scope_bindings)-1];
                            $arrkeys = array_keys($parametros);
                            $relation = Str::plural($arrkeys[0]);
                            $relation = $last->$relation();
                            $record = $relation->where($relation->_primary[0], $val)->first();
                            $last->setQuery(null);
                        }

                        if (!$record) abort(404);

                        if ($bindings)
                            $scope_bindings[] = $record;
                        
                        $final_params[] = $record;

                        unset($parametros[$paramNames[$i]->name]);
                    }

                }
                elseif (class_exists($iclass) && $iclass=='Request')
                {
                    $final_params[] = request();
                    unset($parametros[$paramNames[$i]->name]);
                }
            }
    
            # If it's FormRequest, check authorization and validate
            if (isset($formRequest) && isset($record))
            {
                $formRequest->authorize($record);
                $formRequest->validateRules($formRequest->rules());
            }

        }

        if (count($parametros) > 0)
        {
            foreach ($parametros as $p)
                $final_params[] = $p['value'];
        }

        $request->route->instance = $instance;
        $request->route->parametros = $final_params;

        return $request;
    }

}