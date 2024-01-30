<?php

class Authenticate
{

    public function handle($request, $next, $param=null)
    {
        if ($param=='api') {
            return $this->handleApi($request, $next);
        }

        if ($param=='sanctum') {
            return $this->handleSanctum($request, $next);
        }

        if (!Auth::check() && $request->route->controller!='Auth') {

            $history = isset($_SESSION['url_history']) ? $_SESSION['url_history'] : array();
            
            array_unshift($history, $request->fullUrl());
            
            $_SESSION['url_history'] = $history;
            
            $_SESSION['_requestedRoute'] = $request->fullUrl();
            
            return to_route('login');
        }

        return $request;
    }


    private function handleApi($request, $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            $this->deny("Access denied. Missing token in request");
        }

        $user = DB::table('users')->where('token', $token)->first();

        if (!$user) {
            $this->deny("Access denied. Unexistent token");
        }

        $lifetime = config('app.api_tokens');

        $date1 = Carbon::parse($user->token_timestamp)->addMinutes($lifetime)->getTimestamp();
        $date2 = Carbon::now()->getTimestamp();

        if ($date1 < $date2) {
            $this->deny("Access denied. Token expired");
        }

        return $request;
    }

    private function handleSanctum($request, $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            $this->deny("Access denied. Missing token in request");
        }

        $key = PersonalAccessToken::findToken($token);

        if (!$key) {
            $this->deny("Access denied. Unexistent token");
        }

        $date1 = Carbon::parse($key->expires_at)->getTimestamp();
        $date2 = Carbon::now()->getTimestamp();

        if ($date1 < $date2) {
            $this->deny("Access denied. Token expired");
        }

        if (!Auth::user() || Auth::user()->id!=$key->tokenable_id) {
            Auth::loginUsingId($key->tokenable_id);
        }

        Auth::user()->withAccessToken($key);

        return $request;
    }

    private function deny($reason)
    {
        abort(403, $reason);
    }

}
