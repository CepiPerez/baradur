<?php

#function can($function, $param) { return Gates::can($function, $param); }
#function denies($function, $param) { return Gates::denies($function, $param); }

Class Gate {

    public static $policies;

    public static function define($function, $callback)
    {
        if (!isset(self::$policies[$function]))
            self::$policies[$function] = $callback;
    }

    public static function allows($function, $param=null)
    {
        if (!Auth::user()) return false;

        $cont = null;
        $func = null;
        if (isset(Gate::$policies[$function]))
            list($cont, $func) = explode('@', Gate::$policies[$function]);
        else
        {
            if (is_object($param))
                $cont = get_class($param).'Policy';
            else if (is_string($param))
                $cont = $param.'Policy';
            $func = $function;
        }

        $controller = new $cont;
        
        if (isset($param))
            return $controller->$func(Auth::user(), $param);
        else 
            return $controller->$func(Auth::user());

    }

    public static function denies($function, $param=null)
    {
        if (!Auth::user()) return false;

        $cont = null;
        $func = null;
        if (isset(Gate::$policies[$function]))
            list($cont, $func) = explode('@', Gate::$policies[$function]);
        else
        {
            if (is_object($param))
                $cont = get_class($param).'Policy';
            else if (is_string($param))
                $cont = $param.'Policy';
            $func = $function;
        }

        $controller = new $cont;
        
        if (isset($param))
            return !$controller->$func(Auth::user(), $param);
        else 
            return !$controller->$func(Auth::user());
    }

    public static function authorize($function, $param=null)
    {
        if (!Auth::user()) abort(403);

        $cont = null;
        $func = null;
        if (isset(Gate::$policies[$function]))
            list($cont, $func) = explode('@', Gate::$policies[$function]);
        else
        {
            if (is_object($param))
                $cont = get_class($param).'Policy';
            else if (is_string($param))
                $cont = $param.'Policy';
            $func = $function;
        }

        $controller = new $cont;
        if (isset($param))
            $res = $controller->$func(Auth::user(), $param);
        else 
            $res = $controller->$func(Auth::user());


        if (!$res)
            abort(403);
    }

    



}