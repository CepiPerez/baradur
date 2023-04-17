<?php

class RedirectIfAuthenticated
{
    public function handle($request, $next, $guard=null)
    {
        if (Auth::guard($guard)->check()) {
            return redirect(RouteServiceProvider::HOME);
        }

        return $next($request);
    }
}
