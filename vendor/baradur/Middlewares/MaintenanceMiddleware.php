<?php


class MaintenanceMiddleware
{
    protected $except = array();

    public function handle($request, $next)
    {
        global $app;

        if ($app->isDownForMaintenance()) {

            foreach ($this->except as $except) {

                $url = str_replace($request->route->domain.'/', '', $request->route->url);

                if ($except == $url) {
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

                    if (preg_match("/$pattern/", $url)) {
                        return $request;
                    }
                }
            }


            self::checkStored();
            if ($_SESSION['bypass']['stored']==$_SESSION['bypass']['secret'] && $_SESSION['bypass']['secret']!==null) {
                return $request;
            }

            abort(503);
        }

        return $request;
    }

    public static function checkStored()
    {
        $stored = @file_get_contents(_DIR_.'storage/framework/down');

        $_SESSION['bypass']['stored'] = $stored;
    }

    public static function checkSecret($secret)
    {
        $stored = @file_get_contents(_DIR_.'storage/framework/down');

        $_SESSION['bypass']['stored'] = $stored;
        $_SESSION['bypass']['secret'] = $secret==$stored ? $secret : null;
    }

    /**
     * Get the URIs that should be accessible even when maintenance mode is enabled.
     */
    public function getExcludedPaths()
    {
        return $this->except;
    }
}
