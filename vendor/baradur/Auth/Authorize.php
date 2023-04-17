<?php

class Authorize
{
    public function handle($request, $next, $function, $param)
    {
        self::verify($function, $param);

        return $request;
    }

    public static function verify($function, $param=null, $abort=true)
    {
        if (!Auth::user()) {
            if ($abort) {
                abort(403);
            } else {
                return false;
            }
        }

        if (is_string($param)) {

            preg_match_all('/\{[^}]*\}/', request()->route->url, $matches);
            
            $param_value = null;

            if (count($matches)>0 && count($matches[0])>0) {
                for($i=0; $i<count($matches[0]); $i++) {
                    if (is_string($param) && '{'.$param.'}'==$matches[0][$i]) {
                        $param_value = request()->route->parametros[$param]
                            ? request()->route->parametros[$param]['value']
                            : request()->route->parametros[$i];
                    }
                }
            }
        }

        $cont = null;
        $func = null;

        $registered = is_object($param) ? get_class($param) : ucfirst($param);

        if (isset(ServiceProvider::$regitered_policies[$registered])) {
            $cont = ServiceProvider::$regitered_policies[$registered];
            $func = Str::camel($function);
        }

        if (!$cont) {
            
            $callable = Gate::$policies[$function];

            if (isset($callable)) {
                if (is_closure($callable)) {
                    list($cont, $func) = getCallbackFromString($callable);
                } elseif (is_string($callable) && strpos($callable, '@')!==false) {
                    list($cont, $func) = explode('@', $callable);
                } else {
                    $cont = $callable[0];
                    $func = $callable[1];
                }
            } else {
                if (is_object($param)) {
                    $cont = get_class($param).'Policy';
                } elseif (is_string($param)) {
                    $cont = ucfirst($param).'Policy';
                }
                $func = $function;
            }
        }

        $model = null;


        if ($param_value && !is_object($param_value) && is_string($param)) {
            $model = str_replace('Policy', '', ucfirst($param));
            $model = Model::instance($model)->find($param_value);
        } elseif (is_object($param)) {
            $model = $param;
        } elseif ($param_value instanceof Model) {
            $model = $param_value;
        }

        $c = new $cont;
        $res = false;

        if ($model) {
            $res = $c->$func(Auth::user(), $model);
        } else {
            $res = $c->$func(Auth::user());
        } 

        if ($res instanceof AuthResponse && $res->denied()) {
            if ($abort) {
                abort($res->status());
            } else {
                return false;
            }
        }

        if ($res) {
            return true;
        }

        if ($abort) {
            abort(403);
        } 

        return false;
    }

}