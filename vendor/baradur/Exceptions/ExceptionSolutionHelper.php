<?php

Class ExceptionSolutionHelper
{
    public static $solutions = array(
        'MissingAppKeyException',
        'QueryException',
        'AccessDeniedHttpException',
        'RouteNotFoundException',
        'MissingClassException',
        'BadMethodCallException'
    );

    public static $exception;

    public static function getSolution($exception)
    {
        if (!in_array(get_class($exception), self::$solutions))  {
            return null;
        }

        self::$exception = $exception;

        $method = lcfirst(get_class($exception));

        return self::$method();
    }

    public static function getSuggestedRoute()
    {
        $routes = Route::getRoutes()->where('method', $_SERVER['REQUEST_METHOD'])->pluck('url');
        $current = $_SERVER['REQUEST_URI'];
        if ($current!=='/') {
            $current = ltrim($current, '/');
        }

        $suggested = find_similar($current, $routes);

        # Deep search, exploding route folders
        if (!$suggested)
        {
            $folders = explode ('/', $current);

            $results = array();

            foreach ($routes as $route)
            {
                $route_folders = explode('/', $route);
                if (count($route_folders) === count($folders))
                {
                    $result = array();

                    for ($i=0; $i < count($route_folders); $i++)
                    {
                        $res = null;
                        if (Str::startsWith($route_folders[$i], '{') && Str::endsWith($route_folders[$i], '}')) {
                            $res = $folders[$i];
                        } else {
                            $res = find_similar($folders[$i], array($route_folders[$i]));
                        }

                        if ($res===null) {
                            break;
                        }

                        $result[] = $res;
                    }

                    if (count($result) == count($folders)) {
                        $results[] = implode('/', $result);
                    }
                }
            }

            if (count($results)==1) {
                $suggested = $results[0];
            } elseif (count($results)>1) {
                $suggested = find_similar($current, $results);
            }
        }
        
        return $suggested 
            ? 'Did you mean <a class="font-normal" href="/' . ltrim($suggested, '/') . '">/' . ltrim($suggested, '/') . '</a>?' 
            : 'Are you sure that the route is defined?';
    }

    public static function findClosestMatch($strings, $input, $sensitivity = 4)
    {
        $closestDistance = -1;

        $closestMatch = null;

        foreach ($strings as $string) {
            $levenshteinDistance = levenshtein($input, $string);

            if ($levenshteinDistance === 0) {
                $closestMatch = $string;
                $closestDistance = 0;

                break;
            }

            if ($levenshteinDistance <= $closestDistance || $closestDistance < 0) {
                $closestMatch = $string;
                $closestDistance = $levenshteinDistance;
            }
        }

        if ($closestDistance <= $sensitivity) {
            return $closestMatch;
        }

        return null;
    }

    public static function getSuggestedMethod()
    {
        $method = self::$exception->getMessage();
        
        $arr = explode(' ', $method);
        $method = $arr[1];
        
        $class = self::$exception->getFile();
        $class = pathinfo($class);
        $class = $class['basename'];
        $class = str_ireplace('.php', '', $class);
        
        $methods = get_class_methods($class);

        $suggested = find_similar($method, $methods);

        return $suggested 
            ? 'Did you mean ' . $class . ':' . $suggested . '() ?' 
            : 'Check if you misspelled the method';
    }
    
    public static function missingAppKeyException()
    {
        return  array(
            'title'       => 'Your app key is missing',
            'description' => 'Generate your application encryption key using <b>`php artisan key:generate`</b>.',
            'button'      => 'Generate app key',
            'run'         => 'Artisan|keyGenerate'
        );
    }
    
    public static function queryException()
    {
        return  array(
            'title'       => 'You might have forgotten to run your database migrations.',
            'description' => 'You can try to run your migrations using <b>`php artisan migrate`</b>.',
            'button'      => 'Run migrations',
            'run'         => 'Artisan|migrate'
        );
    }
    
    public static function accessDeniedHttpException()
    {
        return  array(
            'title'       => "You don't have access to this resource",
            'description' => Auth::check() 
                ? 'You may not have permission to access this resource'
                : 'You may need to log in',
            'button'      => null,
            'run'         => null
        );
    }
    
    public static function routeNotFoundException()
    {
        return array(
            'title'       => "The route was not defined",
            'description' => self::getSuggestedRoute(),
            'button'      => null,
            'run'         => null
        );
    }

    public static function missingClassException()
    {
        return array(
            'title'       => "The class was not found in your project",
            'description' => 'You can try reloading cache files using <b>`php artisan optimize:clear`</b>.',
            'button'      => null,
            'run'         => null
        );
    }

    public static function badMethodCallException()
    {
        return array(
            'title'       => "The method doesn't exists",
            'description' => self::getSuggestedMethod(),
            'button'      => null,
            'run'         => null
        );
    }


}