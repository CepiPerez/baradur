<?php

class EnsureEmailIsVerified
{

    public function handle($request, $next, $redirectToRoute = null)
    {
        if (! $request->user() || ! $request->user()->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : redirect($redirectToRoute ? $redirectToRoute : 'email_verify');
        }

        return $request;
    }
}
