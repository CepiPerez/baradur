<?php

class CsrfMiddleware
{
    protected $except = array();

    public function handle($request)
    {
        foreach ($this->except as $except)
        {
            if ($except == $request->_uri) {
                return $request;
            }
            
            if (strpos($except, '*')!==false)
            {
                $special_chars = "\.+^$[]()|{}/'#";
                $special_chars = str_split($special_chars);
                $escape = array();

                foreach ($special_chars as $char) {
                    $escape[$char] = "\\$char";
                }

                $pattern = strtr($except, $escape);
                $pattern = strtr($pattern, array(
                    '*' => '.*?',
                    '?' => '.',
                ));

                if (preg_match("/$pattern/", $request->_uri)) {
                    return $request;
                }
            }
        }

        //echo "Verifying token";
        $this->_checkToken(Route::current());
        $this->_removeOldTokens();
        
        return $request;
    }

    private function _checkToken($ruta)
    {
        if ($ruta->method=='POST' || $ruta->method=='PUT' || $ruta->method=='DELETE')
        {
            if (!isset($_SESSION['tokens'][$_POST['csrf']]))
            {
                # Token doesn't exist
                abort(403);
            }

            $lifetime = config('app.http_tokens');
            $session_timestamp = $_SESSION['tokens'][$_POST['csrf']]['timestamp'];

            $date1 = Carbon::parse($session_timestamp)
                ->addMinutes($lifetime)->getTimestamp();
            
            $date2 = Carbon::now()->getTimestamp();

            //$date1 = strtotime(config('app.http_tokens'), strtotime($_SESSION['tokens'][$_POST['csrf']]['timestamp']));
            //$date2 = strtotime(date('Y-m-d H:i:s'));
            
            if ($date1 < $date2)
            {
                # Token expired
                abort(403);
            }
            else
            {
                # Access granted
                $counter = $_SESSION['tokens'][$_POST['csrf']]['counter'];
                if ($counter < 1)
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
    private function _removeOldTokens()
    {
        if (isset($_SESSION['tokens']))
        {
            foreach ($_SESSION['tokens'] as $key => $token)
            {
                $date1 = strtotime(config('app.http_tokens'), strtotime($token['timestamp']));
                $date2 = strtotime(date('Y-m-d H:i:s'));
                if ($date1 < $date2)
                    unset($_SESSION['tokens'][$key]);
                
                if ($token['counter'] >= 1)
                    unset($_SESSION['tokens'][$key]);
            }
        }
    }

}