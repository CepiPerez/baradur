<?php

class CsrfMiddleware
{
    protected $except = array();

    public function handle($request)
    {
        $uri = $request->path();

        foreach ($this->except as $except) {

            if ($except == $uri) {
                return $request;
            }
            
            if (strpos($except, '*')!==false) {

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

                if (preg_match("/$pattern/", $uri)) {
                    return $request;
                }
            }
        }

        //echo "Verifying token";
        $this->_checkToken(Route::current());
        $this->_removeOldTokens(Route::current());
        
        return $request;
    }

    private function _checkToken($ruta)
    {
        
        if ($ruta->method=='POST' || $ruta->method=='PUT' || $ruta->method=='DELETE') {
            
            if (!isset($_SESSION['_token']) ||  $_SESSION['_token']!==$_POST['_token']) {
                # Token doesn't exist or is invalid
                abort(403);
            }

            /* $lifetime = config('app.http_tokens');
            $session_timestamp = $_SESSION['tokens'][$_POST['_token']]['timestamp'];

            $date1 = Carbon::parse($session_timestamp)->addMinutes($lifetime)->getTimestamp();
            
            $date2 = Carbon::now()->getTimestamp();
            
            if ($date1 < $date2) {
                # Token expired
                abort(403);
            } */
        }

    }

    # Remove old tokens based on .env settings
    private function _removeOldTokens($ruta)
    {
        if ($ruta->method=='POST' || $ruta->method=='PUT' || $ruta->method=='DELETE') {

            if (isset($_SESSION['tokens'])) {

                foreach ($_SESSION['tokens'] as $key => $token) {

                    $lifetime = config('app.http_tokens');
                    $session_timestamp = $_SESSION['tokens'][$_POST['_token']]['timestamp'];

                    $date1 = Carbon::parse($session_timestamp)->addMinutes($lifetime)->getTimestamp();
                    
                    $date2 = Carbon::now()->getTimestamp();

                    if ($date1 < $date2) {
                        unset($_SESSION['tokens'][$key]);
                    }
                    
                    if ($token['counter'] >= 1) {
                        unset($_SESSION['tokens'][$key]);
                    }
                }
            }
        }
    }

}