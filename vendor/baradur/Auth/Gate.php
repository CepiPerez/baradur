<?php

#function can($function, $param) { return Gates::can($function, $param); }
#function denies($function, $param) { return Gates::denies($function, $param); }

Class Gate {

    public static $policies;

    public static $user = null;

    public static function define($function, $callback, $func=null)
    {
        if (isset($func))
            $callback .= '@'.$func;

        if (!isset(self::$policies[$function]))
            self::$policies[$function] = $callback;
    }

    private static function getResult($function, $param=null)
    {
        return Authorize::verify($function, $param, false);
        /* $current_user = isset(self::$user)? self::$user : Auth::user();

        if (!isset($current_user)) {
            return false;
        }

        self::$user = null;

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
            return $controller->$func($current_user, $param);
        else 
            return $controller->$func($current_user); */
        
    }


    public static function allows($function, $param=null)
    {
        return self::getResult($function, $param);
    }

    public static function denies($function, $param=null)
    {
        return !self::getResult($function, $param);
    }

    public static function any($functions, $param=null)
    {
        $functions = is_array($functions)? $functions : array($functions);

        foreach ($functions as $function) {
            $res = self::getResult($function, $param);

            if ($res) return true;
        }

        return false;
    }

    public static function authorize($function, $param=null)
    {
        if (!self::getResult($function, $param)) {
            abort(403);
        }

        return true;
    }

    public static function forUser($user)
    {
        self::$user = $user;
        $gate = new Gate;
        return $gate;
    }



}