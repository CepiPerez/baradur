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
        list($cont, $func) = explode('@', self::$policies[$function]);

        if (isset($param)) $params = array(Auth::user(), $param);
        else $params = array(Auth::user());

        return call_user_func_array(array($cont, $func), $params);
    }

    public static function denies($function, $param=null)
    {
        list($cont, $func) = explode('@', self::$policies[$function]);

        if (isset($param)) $params = array(Auth::user(), $param);
        else $params = array(Auth::user());

        return !call_user_func_array(array($cont, $func), $param);
    }

    public static function authorize($function, $param=null)
    {
        list($cont, $func) = explode('@', self::$policies[$function]);

        if (isset($param)) $params = array(Auth::user(), $param);
        else $params = array(Auth::user());

        if (!call_user_func_array(array($cont, $func), $params))
            abort(403);
    }

    



}