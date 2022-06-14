<?php

class Middleware
{

    public function handle($request, $next)
    {
        foreach ($this->except as $except)
        {
            if ($except == $request->_uri)
                return true;
            
            if (strpos($except, '*')!==false)
            {
                $special_chars = "\.+^$[]()|{}/'#";
                $special_chars = str_split($special_chars);
                $escape = array();
                foreach ($special_chars as $char) $escape[$char] = "\\$char";
                $pattern = strtr($except, $escape);
                $pattern = strtr($pattern, array(
                    '*' => '.*?',
                    '?' => '.',
                ));
                if (preg_match("/$pattern/", $request->_uri))
                    return true;
            }
        }

        //echo "Verifying token";
        $this->checkToken(Route::getCurrentRoute());
        $this->removeOldTokens();
        return true;
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