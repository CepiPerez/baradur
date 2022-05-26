<?php

class Controller
{
    /**
     * Set the Token verification
     */
    protected $tokenVerification = true;

    # Token Middleware - OBSOLETE
    # -----------------------------------------------------------------
    # This function is called by Route automatically
    # Checks the token if $tokenVerification is true
    /* public function verify($ruta)
    {
        if ($this->tokenVerification)
        {
            //echo "Checking token<br>";
            $this->checkToken($ruta);
            $this->removeOldTokens();
        }

    } */

    public function authorize($function, $param=null)
    {
        if (!Auth::user())
            abort(403);

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
