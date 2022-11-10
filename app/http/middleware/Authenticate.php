<?php

class Authenticate extends Middleware
{

    public function handle($request, $next)
    {
        if (!Auth::check())
        {
            $history = isset($_SESSION['url_history']) ? $_SESSION['url_history'] : array();
            array_unshift($history, $request->fullUrl());
            $_SESSION['url_history'] = $history;
            
            $_SESSION['_requestedRoute'] = $request->fullUrl();
            return redirect(HOME.'/login');
        }

        return $next($request);
    }

}
