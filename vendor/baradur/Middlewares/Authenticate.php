<?php

class Authenticate
{

    public function handle($request, $next, $param=null)
    {
        if ($param=='api')
        {
            return $this->handleApi($request, $next);
        }

        if (!Auth::check())
        {
            $history = isset($_SESSION['url_history']) ? $_SESSION['url_history'] : array();
            array_unshift($history, $request->fullUrl());
            $_SESSION['url_history'] = $history;
            
            $_SESSION['_requestedRoute'] = $request->fullUrl();
            return redirect(HOME.'/login');
        }

        return $request;
    }


    private function handleApi($request, $next)
    {
        //$this->removeOldTokens();
        $this->checkToken($request);

        return $request;
    }

    private function deny($reason)
    {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');
        echo json_encode(array("error" => $reason));
        exit();
    }

    private function checkToken(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            $this->deny("Access denied. Missing token in request");
        }

        $user = DB::table('users')->where('token', $token)->first();

        if (!$user) {
            $this->deny("Access denied. Unexistent token");
        }

        $lifetime = env('API_TOKENS')? env('API_TOKENS') : 60;

        $date1 = Carbon::parse($user->token_timestamp)->addMinutes($lifetime)->getTimestamp();
        $date2 = Carbon::now()->getTimestamp();

        if ($date1 < $date2) {
            $this->deny("Access denied. Token expired");
        }

        return true;

    }

    # Remove old tokens based on API_TOKENS from .env file
    /* private function removeOldTokens()
    {
        $timestamp = new DateTime();
        $timestamp->modify(str_replace('+', '-', env('API_TOKENS')));
        DB::statement('DELETE FROM api_tokens WHERE `timestamp` < "' . $timestamp->format('Y-m-d H:i:s') . '"');
    } */

}
