<?php

Class EnsureFrontendRequestsAreStateful
{
    public function handle($request, $next, $redirectToRoute = null)
    {
        $list = self::fromFrontend($request) 
            ? array("VerifyCsrfToken")
            : array();

        $request = Pipeline::send($request)
            ->through($list)
            ->thenReturn();

        return $request;
    }

    public static function fromFrontend($request)
    {
        $domain = $request->headers['Referer']
            ? $request->headers['Referer']
            : $request->headers['Origin'];

        if (is_null($domain)) {
            return false;
        }

        return true;

    }
}