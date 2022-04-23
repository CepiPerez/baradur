<?php

class Controller
{
    /**
     * Set the Token verification
     */
    protected $tokenVerification = true;

    # Token Middleware
    # -----------------------------------------------------------------
    # This function is called by Route automatically
    # Checks the token if $tokenVerification is true
    public function check($ruta)
    {
        if ($this->tokenVerification)
        {
            //echo "Checking token<br>";
            $this->checkToken($ruta);
            $this->removeOldTokens();
        }

    }

    public function authorize($function, $param=null)
    {
        if (isset(Gate::$policies[$function]))
            list($cont, $func) = explode('@', Gate::$policies[$function]);
        else
        {
            /* if (!isset($param))
            {
                $cont = str_replace('Controller', 'Policy', get_class($this));
            } */
            return;
        }
        
        
        if (isset($param)) $params = array(Auth::user(), $param);
        else $params = array(Auth::user());

        if (!call_user_func_array(array($cont, $func), $params))
            abort(403);
    }


    private function checkToken($ruta)
    {
        if ($ruta->method=='POST' || $ruta->method=='PUT' || $ruta->method=='DELETE')
        {
            $date1 = strtotime(env('HTTP_TOKENS'), strtotime($_SESSION['tokens'][$_POST['csrf']]['timestamp']));
            $date2 = strtotime(date('Y-m-d H:i:s'));
            if (!isset($_SESSION['tokens'][$_POST['csrf']]))
            {
                # Token doesn't exist
                abort(403);
            }
            elseif ($date1 < $date2)
            {
                # Token expired
                abort(403);
            }
            else
            {
                # Access granted
                $counter = $_SESSION['tokens'][$_POST['csrf']]['counter'];
                if ($counter < env('HTTP_TOKENS_MAX_USE'))
                {
                    $_SESSION['tokens'][$_POST['csrf']]['counter'] = $counter+1;
                }
                else
                {
                    unset($_SESSION['tokens'][$_POST['csrf']]);
                }
            }
        }

    }

    # Remove old tokens based on .env settings
    private function removeOldTokens()
    {
        if (isset($_SESSION['tokens']))
        {
            foreach ($_SESSION['tokens'] as $key => $token)
            {
                $date1 = strtotime(env('HTTP_TOKENS'), strtotime($token['timestamp']));
                $date2 = strtotime(date('Y-m-d H:i:s'));
                if ($date1 < $date2)
                    unset($_SESSION['tokens'][$key]);
                
                if ($token['counter'] >= env('HTTP_TOKENS_MAX_USE'))
                    unset($_SESSION['tokens'][$key]);
            }
        }
    }


}
