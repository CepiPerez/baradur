<?php

Class FolioRouter
{
    public static function loadRoutes($path)
    {
        $routes = self::getRoutes($path);

        foreach ($routes as $route) {
            Route::get($route['route'], 'Folio@processRoute')
                ->withTrashed($route['trashed']? true : false)
                ->middleware($route['middleware'])
                ->name($route['name']);
        }

        //dd(Route::getRoutes());
    }

    public static function getRoutes($path)
    {
        if (file_exists(_DIR_.'storage/framework/config/folio.php')) {
            return unserialize(file_get_contents(_DIR_.'storage/framework/config/folio.php'));
        }

        $folders = array();

        $recursive = $path;
        while ($dirs = glob($recursive . '/*', GLOB_ONLYDIR)) {
            $recursive .= '/*';
            $folders = array_merge($folders, $dirs);
        }

        $files = array();

        foreach (glob($path . '/*.blade.php') as $file) {
            if (Str::endsWith($file, '.blade.php')) {
                $files[] = $file;
            }
        }

        foreach ($folders as $folder) {
            foreach (glob($folder . '/*.blade.php') as $file) {
                if (Str::endsWith($file, '.blade.php')) {
                    $files[] = $file;
                }
            }
        }

        $routes = array();

        foreach ($files as $file) {
            $data = file_get_contents($file);

            // Checking PHP Tag (Folio exclusive)
            preg_match( '/<\?[=|php]?[\s\S]*?\?>/is', $data, $blocks);
            $exclusive_tag = $blocks[0];

            $name = null;
            $trashed = null;
            $list = array();

            // If tag exists then check functions
            if ($exclusive_tag) {
                $trashed = preg_match('/^[\s]*withTrashed\(\);/m', $exclusive_tag);
    
                preg_match('/^[\s]*middleware\([^\)]*\);/m', $exclusive_tag, $middleware);
                
                $list = array();
                if (count($middleware) > 0) {
                    $middleware = trim(str_replace('middleware(', '', $middleware[0]));
                    $middleware = rtrim($middleware, ');');
                    $middleware = str_replace('[', '', str_replace(']', '', $middleware));
                    $middleware = str_replace("'", '', $middleware);
                    foreach (explode(',', $middleware) as $m) {
                        $list[] = trim($m);
                    }
                }
    
                preg_match('/^[\s]*name\([^\)]*\);/m', $exclusive_tag, $names);
    
                $name = null;
                if (count($names) > 0) {
                    $name = trim(str_replace('name(', '', $names[0]));
                    $name = rtrim($name, ');');
                    $name = str_replace("'", '', $name);
                }
            }

            $file = str_replace($path, '', str_replace('.blade.php', '', $file));
            $file = str_replace('index', '', $file);
            $file = strlen($file)>1 ? rtrim($file, "/") : $file;

            $routes[] = array(
                'route' => str_replace('[', '{', str_replace(']', '}', $file)),
                'trashed' => $trashed,
                'middleware' => $list,
                'name' => $name
            );
        }

        file_put_contents(_DIR_.'storage/framework/config/folio.php', serialize($routes));

        //dd($routes);

        return $routes;
    }



}