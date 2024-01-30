<?php

Class Folio
{
    protected static $path;
    protected static $uri;
    protected static $middleware;

    public static function getPath()
    {
        return self::$path;
    }

    public static function route($path = null, $uri = '/', $middleware = array())
    {
        self::$path = $path;
        self::$uri = $uri;
        self::$middleware = $middleware;

        global $config;
        $config['view']['paths'][0] = self::$path;

        FolioRouter::loadRoutes($path);
    }

    public function processRoute()
    {
        $page = $this->getCurrentRoutePage();

        $page['page'] = str_replace(self::$path, '', $page['page']);
        $page['page'] = str_replace('.', '/', $page['page']);
        
        return view($page['page'], $page['params']);
    }

    private function getCurrentRoutePage()
    {
        $route = request()->route;

        $params = array();

        foreach ($route->parametros as $key => $val) {
            if (ctype_upper(substr($key, 0, 1))) {
                global $_model_list;
                if (in_array($key, $_model_list)) {
                    $value = Model::instance($key);
                    if ($route->with_trashed) $value = $value->withTrashed();
                    $value = $value->findOrFail($val);
                    $params[Str::camel($key)] = $value;
                }
            }
        }

        return array(
            'page' => self::$path . '/' . $this->switchBrackets($route->url),
            'params' => $params
        );
    }

    private function switchBrackets($string)
    {
        if (str_contains($string, '{')) {
            return str_replace('{', '[', str_replace('}', ']', $string));
        }

        return str_replace('[', '{', str_replace(']', '}', $string));
    }


}