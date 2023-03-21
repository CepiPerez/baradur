<?php


class MaintenanceMiddleware
{
    protected $except = array();

    public function handle($request, $next)
    {
        global $app;

        if ($app->maintenanceMode()) {

            foreach ($this->except as $except)
            {
                $url = str_replace($request->route->domain.'/', '', $request->route->url);

                if ($except == $url) {
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

                    if (preg_match("/$pattern/", $url)) {
                        return $request;
                    }
                }
            }


            self::checkStored();
            if ($_SESSION['bypass']['stored']==$_SESSION['bypass']['secret'] && $_SESSION['bypass']['secret']!==null) {
                return $request;
            }

            /* if (isset($data['secret']) && $request->path() === $data['secret']) {
                return $this->bypassResponse($data['secret']);
            } */

            /* if ($this->hasValidBypassCookie($request, $data) ||
                $this->inExceptArray($request)) {
                return $next($request);
            } */


            /* if (isset($data['template'])) {
                return response(
                    $data['template'],
                    $data['status'] ?? 503,
                    $this->getHeaders($data)
                );
            } */

            abort(503);
            //throw new HttpException(503, 'Service Unavailable');
        }

        return $request;
    }

    public static function checkStored()
    {
        $stored = @file_get_contents(_DIR_.'storage/.maintenance_on');

        $_SESSION['bypass']['stored'] = $stored;
    }

    public static function checkSecret($secret)
    {
        $stored = @file_get_contents(_DIR_.'storage/.maintenance_on');

        $_SESSION['bypass']['stored'] = $stored;
        $_SESSION['bypass']['secret'] = $secret==$stored ? $secret : null;
    }


    /**
     * Determine if the incoming request has a maintenance mode bypass cookie.
     */
    /* protected function hasValidBypassCookie($request, array $data)
    {
        return isset($data['secret']) &&
                $request->cookie('laravel_maintenance') &&
                MaintenanceModeBypassCookie::isValid(
                    $request->cookie('laravel_maintenance'),
                    $data['secret']
                );
    } */

    /**
     * Determine if the request has a URI that should be accessible in maintenance mode.
     */
    /* protected function inExceptArray($request)
    {
        foreach ($this->getExcludedPaths() as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    } */

    /**
     * Redirect the user back to the root of the application with a maintenance mode bypass cookie.
     */
    /* protected function bypassResponse(string $secret)
    {
        return redirect('/')->withCookie(
            MaintenanceModeBypassCookie::create($secret)
        );
    } */

    /**
     * Get the headers that should be sent with the response.
     */
    /* protected function getHeaders($data)
    {
        $headers = isset($data['retry']) ? ['Retry-After' => $data['retry']] : [];

        if (isset($data['refresh'])) {
            $headers['Refresh'] = $data['refresh'];
        }

        return $headers;
    } */

    /**
     * Get the URIs that should be accessible even when maintenance mode is enabled.
     */
    public function getExcludedPaths()
    {
        return $this->except;
    }
}
