<?php

#function can($function, $param) { return Gates::can($function, $param); }
#function denies($function, $param) { return Gates::denies($function, $param); }

Class Gate {

    private static $policies;

    public static function define($function, $callback)
    {
        if (!isset(self::$policies[$function]))
            self::$policies[$function] = $callback;
    }

    public static function allows($function, $param)
    {
        list($cont, $func) = explode('@', self::$policies[$function]);
        //return $cont::$func($param);
        return call_user_func_array(array($cont, $func), array($param));
    }

    public static function denies($function, $param)
    {
        list($cont, $func) = explode('@', self::$policies[$function]);
        //return !$cont::$func($param);
        return !call_user_func_array(array($cont, $func), array($param));
    }

    public static function authorize($function, $param)
    {
        list($cont, $func) = explode('@', self::$policies[$function]);
        //if (!$cont::$func($param))
        if (!call_user_func_array(array($cont, $func), array($param)))
            abort(403);
    }

    



}