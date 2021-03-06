<?php

#function can($function, $param) { return Gates::can($function, $param); }
#function denies($function, $param) { return Gates::denies($function, $param); }

Class Gate {

    public static $policies;

    public static function define($function, $callback, $func=null)
    {
        if (isset($func))
            $callback .= '@'.$func;

        if (!isset(self::$policies[$function]))
            self::$policies[$function] = $callback;
    }

    private static function getResult($function, $param=null)
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


    public static function allows($function, $param=null)
    {
        return self::getResult($function, $param);
    }

    public static function denies($function, $param=null)
    {
        return !self::getResult($function, $param);
    }

    public static function authorize($function, $param=null)
    {
        if (!self::getResult($function, $param))
            abort(403);
    }

    



}