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
        $result = true;
        if ($this->tokenVerification)
        {
            //echo "Checking token<br>";
            self::removeOldTokens();
            $result = self::checkToken($ruta);
        }

    }


    function checkToken($ruta)
    {
        if ($ruta->method=='POST' || $ruta->method=='PUT' || $ruta->method=='DELETE')
        {
            $date1 = strtotime(HTTP_TOKENS, strtotime($_SESSION['tokens'][$_POST['csrf']]['timestamp']));
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
                if ($counter < HTTP_TOKENS_MAX_USE)
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
    function removeOldTokens()
    {
        if (isset($_SESSION['tokens']))
        {
            foreach ($_SESSION['tokens'] as $key => $token)
            {
                $date1 = strtotime(HTTP_TOKENS, strtotime($token['timestamp']));
                $date2 = strtotime(date('Y-m-d H:i:s'));
                if ($date1 < $date2)
                    unset($_SESSION['tokens'][$key]);
                
                if ($token['counter'] >= HTTP_TOKENS_MAX_USE)
                    unset($_SESSION['tokens'][$key]);
            }
        }
    }


}
