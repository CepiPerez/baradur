<?php

class Authorize
{
    public function handle($request, $next, $function, $param)
    {
        self::verify($function, $param);

        return $request;

    }


    public static function verify($function, $param=null)
    {
        if (!Auth::user())
            abort(403);

        $cont = null;
        $func = null;

        $callable = Gate::$policies[$function];

        if (isset($callable))
        {
            //list($cont, $func) = explode('@', $callable);
            list($cont, $func, $params) = getCallbackFromString($callable);            
        }
        else
        {
            if (is_object($param))
                $cont = get_class($param).'Policy';
            else if (is_string($param))
                $cont = $param.'Policy';
            $func = $function;
        }

        $c = new $cont;
        $res = false;
        if (isset($param))
            $res = $c->$func(Auth::user(), $param);
        else 
            $res = $c->$func(Auth::user());

        if (!$res)
            abort(403);
    }

}