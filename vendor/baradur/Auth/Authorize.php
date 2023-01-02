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
        preg_match_all('/\{[^}]*\}/', request()->route->url, $matches);
        
        if (count($matches)>0 && count($matches[0])>0)
        {
            for($i=0; $i<count($matches[0]); $i++)
            {
                if (is_string($param) && '{'.$param.'}'==$matches[0][$i]) {
                    $param = request()->route->parametros[$i];
                }
            }
        }

        if (!Auth::user())
            abort(403);

        $cont = null;
        $func = null;

        $callable = Gate::$policies[$function];

        if (isset($callable))
        {
            if (!is_array($callable))
                list($cont, $func, $params) = getCallbackFromString($callable);
            else {
                $cont = $callable[0];
                $func = $callable[1];
            }
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