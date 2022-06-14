<?php

class BaseController
{
    /**
     * Set the Token verification - OBSOLETE;
     */
    //protected $tokenVerification = true;

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


    public function generateToken($user = null)
    {
        global $database;

        if (!$user) $user = "API";

        $token = hash_hmac('sha256', $user, bin2hex(random_bytes(32)));
        $date = new DateTime;
        $timestamp = $date->format('Y-m-d H:i:s');

        $database->query('CREATE TABLE IF NOT EXISTS api_tokens (`token` VARCHAR(100), 
                        `timestamp` TIMESTAMP)');

        $database->query('INSERT INTO api_tokens (token, timestamp)'
                        . ' VALUES ("' . $token . '", "' .$timestamp . '")');

        return $token;

    }


}
