<?php

Class RouteItem 
{
        /**
     * Assign a name to route\
     * 
     * @param string $name
     * @param string $controller
     */
    public function name($name)
    {
        $this->name .= $name;
        return $this;
    }

    /**
     * Assign middleware to route
     * 
     * @param string $middleware
     * @return Route
     */
    public function middleware($middleware)
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function scopeBindings()
    {
        $this->scope_binding = true;
        return $this;
    }

    public function withoutScopeBindings()
    {
        $this->scope_binding = false;
        return $this;
    }

    public function withTrashed()
    {
        $this->with_trashed = true;
        return $this;
    }

    public function named()
    {
        if (is_null($this->name)) {
            return false;
        }

        foreach (func_get_args() as $pattern) {
            if (Str::is($pattern, $this->name)) {
                return true;
            }
        }

        return false;
    }

}

Class RouteGroup 
{
    public $prefix = '';
    public $middleware = array();
    public $name = '';
    public $controller = '';

    public function __construct($parent)
    {
        $this->prefix = $parent->_prefix;
        $this->controller = $parent->_controller;
        $this->name = $parent->_name;
        $this->middleware = $parent->_middleware;
    }

    public function except($except)
    {
        foreach ($this->added as $route)
        {
            if (in_array($route->func, $except))
                Route::getInstance()->_collection->pull('name', $route->name);
        }

        return $this;
    }

    
    public function only($only)
    {
        foreach ($this->added as $route)
        {
            if (!in_array($route->func, $only))
                Route::getInstance()->_collection->pull('name', $route->name);
        }

        return $this;
    }

    public function group($routes)
    {        
        $res = Route::getInstance();

        $_last_middleware = $res->_middleware;
        $_last_controller = $res->_controller;
        $_last_prefix = $res->_prefix;
        $_last_name = $res->_name;

        $res->_middleware = $this->middleware;
        $res->_controller = $this->controller;
        $res->_prefix = $this->prefix;
        $res->_name = $this->name;

        Route::group($routes);

        $res->_middleware = $_last_middleware;
        $res->_controller = $_last_controller;
        $res->_prefix = $_last_prefix;
        $res->_name = $_last_name;

        return $this;
    } 

    public function prefix($prefix)
    {
        $this->prefix .= $prefix;
        return $this;
    }

    public function controller($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    public function name($name)
    {
        $this->name .= $name;
        return $this;
    }

    public function middleware($middleware)
    {
        if (is_string($middleware))
            $middleware = array($middleware);

        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function withTrashed()
    {
        foreach ($this->added as $route)
        {
            $route->with_trashed = true;
        }
        return $this;
    }

}


class Route
{
    private static $_instance;
    public static $_strings;
    protected $_current;
    protected $_currentRoute;
    protected $_redirections = array();

    public $_controller = null;
    public $_middleware = array();
    public $_prefix = '';
    public $_name = '';
    
    protected $_last_controller = null;
    protected $_last_middleware = array();
    protected $_last_prefix = '';
    protected $_last_name = '';


    public function __construct()
    {
        $this->current = null;
        $this->GET = array();
        $this->PUT = array();
        $this->POST = array();
        $this->DELETE = array();
        $this->_collection = new Collection('Route');
    }

    /**
     * Get Route instance
     * 
     * @return Route
     */
    public static function getInstance()
    {
        if (!self::$_instance)
            self::$_instance = new Route();

        return self::$_instance;
    }


    public static function getVerbName($verb)
    {
        $res = Route::$_strings;
        return isset($res[$verb]) ? $res[$verb] : $verb;
    }

    public static function routeList()
    {
        $res = self::getInstance();
        return $res->_collection;
    }

    public static function setRouteList($routes)
    {
        $res = self::getInstance();
        //dd($res->_collection);
        //exit();
        $res->_collection = new Collection('Route');
        $res->_collection->collect($routes, 'Route');
    }

    public static function addRouteList($routes)
    {
        $res = self::getInstance();
        $res->_collection->collect($routes, 'Route');
    }

    public static function getRedirections()
    {
        $res = self::getInstance();
        return $res->_redirections;
    }

    public static function setRedirections($redirections)
    {
        $res = self::getInstance();
        $res->_redirections = $redirections;
    }
    
    /**
     * Redirects to specific route
     * 
     * @param string $redirect_from
     * @param string $redirect_to
     */
    public static function redirect($redirect_from, $redirect_to)
    {
        $res = self::getInstance();
        $res->_redirections[$redirect_from] = $redirect_to;
    }
    
    /**
     * Define resources localization
     * 
     * @param array $strings
     */
    public static function resourceVerbs($strings)
    {
        self::$_strings = $strings;
    }

    /**
     * Add a new route for GET method
     * 
     * @param string $url
     * @param string $callback
     * @return RouteItem
     */
    public static function get($url, $callback)
    {
        return self::getOrAppend('GET', $url, $callback);
    }

    /**
     * Add a new route for POST method
     * 
     * @param string $url
     * @param string $callback
     * @return RouteItem
     */
    public static function post($url, $callback)
    {
        return self::getOrAppend('POST', $url, $callback);
    }

    /**
     * Add a new route for PUT method
     * 
     * @param string $url
     * @param string $callback
     * @return RouteItem
     */
    public static function put($url, $callback)
    {
        return self::getOrAppend('PUT', $url, $callback);
    }

    /**
     * Add a new route for DELETE method
     * 
     * @param string $url
     * @param string $callback
     * @return RouteItem
     */
    public static function delete($url, $callback)
    {
        return self::getOrAppend('DELETE', $url, $callback);
    }

    /**
     * Assign name to routes
     * 
     * @param string $controller
     * @return RouteGroup
     */
    public static function name($name)
    {
        $instance = self::getInstance();
        $res = new RouteGroup($instance);
        $res->name = $name;
        return $res;
    }


    /**
     * Assign controller to routes
     * 
     * @param string $controller
     * @return RouteGroup
     */
    public static function controller($controller)
    {
        $instance = self::getInstance();
        $res = new RouteGroup($instance);
        $res->controller = $controller;
        return $res;
    }

    /**
     * Assign middleware to routes
     *  
     * @param string $middleware
     * @return RouteGroup
     */
    public static function middleware($middleware)
    {
        if (is_string($middleware))
            $middleware = array($middleware);

        $instance = self::getInstance();
        $res = new RouteGroup($instance);
        $res->middleware = array_merge($middleware, $instance->_middleware);
        return $res;
    }

    /**
     * Assign prefix to routes\
     * It can be used to group routes using group()
     * 
     * @param string $prefix
     * @return RouteGroup
     */
    public static function prefix($prefix)
    {
        $instance = self::getInstance();
        $res = new RouteGroup($instance);
        $res->prefix = ($instance->_prefix? $instance->_prefix.'/':'') . $prefix;
        return $res;
    }

    public static function group($routes)
    {        
        //$res = Route::getInstance();

        if (is_string($routes) && file_exists($routes))
        {
            CoreLoader::loadClass($routes, false);
        }        
        elseif (is_string($routes) && strpos($routes, '@')!=false)
        {
            list($c, $m, $p) = getCallbackFromString($routes);
            call_user_func_array(array($c, $m), $p);
        }

    } 

    /**
     * Creates a controller's resources\
     * Example: resources('products', 'ProductsController')
     * 
     * @param string $url
     * @param string $controller
     */
    public static function resource($url, $controller)
    {
        $item = Helpers::getSingular($url);

        $instance = self::getInstance();
        $group = new RouteGroup($instance);
        $group->added = array();

        $group->added[] = self::addRoute('GET', $url, $controller, 'index')->name($url.'.index');
        $group->added[] = self::addRoute('GET', $url.'/'.Route::getVerbName('create'), $controller, 'create')->name($url.'.create');
        $group->added[] = self::addRoute('POST', $url, $controller, 'store')->name($url.'.store');
        $group->added[] = self::addRoute('GET', $url.'/{'.$item.'}', $controller, 'show')->name($url.'.show');
        $group->added[] = self::addRoute('GET', $url.'/{'.$item.'}/'.Route::getVerbName('edit'), $controller, 'edit')->name($url.'.edit');
        $group->added[] = self::addRoute('PUT', $url.'/{'.$item.'}', $controller, 'update')->name($url.'.update');
        $group->added[] = self::addRoute('DELETE', $url.'/{'.$item.'}', $controller, 'destroy')->name($url.'.destroy');

        return $group;
    }


    /**
     * Creates a controller's resources for APIs\
     * Example: apiResource('products', 'ApiProductsController')
     * 
     * @param string $name
     * @param string $controller
     */
    public static function apiResource($name, $controller)
    {
        $item = Helpers::getSingular($name);

        $instance = self::getInstance();
        $group = new RouteGroup($instance);
        $group->added = array();

        $group->added[] =  self::addRoute('GET', $name, $controller, 'index')->name($name.'.index');
        $group->added[] =  self::addRoute('GET', $name.'/{'.$item.'}', $controller, 'show')->name($name.'.show');
        $group->added[] =  self::addRoute('POST', $name, $controller, 'store')->name($name.'.store');
        $group->added[] =  self::addRoute('PUT', $name.'/{'.$item.'}', $controller, 'update')->name($name.'.update');
        $group->added[] =  self::addRoute('DELETE', $name.'/{'.$item.'}', $controller, 'destroy')->name($name.'.destroy');

        return $group;
    }


    /**
     * Route a singleton resource to a controller.\
     * Example: resources('profile', 'ProfileController')
     * 
     * @param string $name
     * @param string $controller
     */
    public static function singleton($name, $controller)
    {
        $instance = self::getInstance();
        $group = new RouteGroup($instance);
        $group->added = array();

        if (strpos($name, '.')!==false)
        {
            $array = explode('.', $name);
            $parent = array_shift($array);
            $name = array_pop($array);
            $item = Helpers::getSingular($parent);

            $group->added[] = self::addRoute('GET', $parent.'/{'.$item.'}/'.$name, $controller, 'show')->name($parent.'.'.$name.'.show');
            $group->added[] = self::addRoute('GET', $parent.'/{'.$item.'}/'.$name.'/'.Route::getVerbName('edit'), $controller, 'edit')->name($parent.'.'.$name.'.edit');
            $group->added[] = self::addRoute('PUT', $parent.'/{'.$item.'}/'.$name, $controller, 'update')->name($parent.'.'.$name.'.update');
            $group->added[] = self::addRoute('DELETE', $parent.'/{'.$item.'}/'.$name, $controller, 'destroy')->name($parent.'.'.$name.'.destroy');
    
            return $group;
        }

        $group->added[] = self::addRoute('GET', $name, $controller, 'show')->name($name.'.show');
        $group->added[] = self::addRoute('GET', $name.'/'.Route::getVerbName('edit'), $controller, 'edit')->name($name.'.edit');
        $group->added[] = self::addRoute('PUT', $name, $controller, 'update')->name($name.'.update');
        $group->added[] = self::addRoute('DELETE', $name, $controller, 'destroy')->name($name.'.destroy');

        return $group;
    }


    /**
     * Creates a route that directly returns a view
     * Example: view('products', 'productos_template')
     * 
     * @param string $url
     * @param string $view
     */
    public static function view($url, $view, $params=null)
    {
        return self::addRoute('GET', $url, null, null, $view, $params);
    }


    # Add route (previous phase) (private)
    # Checks if the give route has the controller's name 
    # If it's true then it adds the route, otherwise it
    # returns an array for group() function
    private static function getOrAppend($method, $url, $callback)
    {

        if (is_string($callback) && strpos($callback, '@')!=false)
            $callback = explode('@', $callback);

        elseif (is_string($callback) && class_exists($callback))
            $callback = array($callback, '__invoke');

        elseif (is_string($callback) && strpos($callback, '@')===false)
            $callback = array('', $callback);

        return self::addRoute($method, $url, $callback[0], $callback[1]);
        
    }

    # Add a route
    # Private - Creates the routes list (array)
    # ------------------------------------------------------------
    # Parameters:
    # 1- method (GET, POST, PUT, DELETE)
    # 2- url assigned to route
    # 3- controller assigned for callback
    # 4- function in the controller
    # 5- middleware (optional) >> REMOVED (maybe it will be added back)
    private static function addRoute($method, $url, $controller, $func, $view=false, $viewparams=null)
    {
        $res = self::getInstance();

        //dump(func_get_args());

        //if ($res->_controller==null && $controller=='')
        //    throw new Exception('Controller not defined in $method > $url route');

        #printf("adding:".$url."\n");
        $method = strtoupper($method);
        $url = ltrim($url, "/");

        if ($func==null) $func = '';

        $route = new RouteItem;
        $route->method = $method;
        $route->url = ($res->_prefix? $res->_prefix.'/' : '') . ($url=='/' ? '' : $url);
        $route->middleware = $res->_middleware;
        $route->name = $res->_name!=''? $res->_name : null ;
        $route->controller = $res->_controller ? $res->_controller : $controller;
        $route->func = strpos($func, '(')===false? $func : substr($func, 0, strpos($func, '('));
        $route->view = $view;
        $route->viewparams = $viewparams;
        
        $res->_collection->put($route);

        return $route;
    }

    # Route filter
    /** @return Collection */
    public static function filter($method, $val)
    {
        $records = self::getInstance()->_collection->where('method', $method);

        if ($val=='*')
            return $records;
        else
            return $records->where('url', $val);
    }

    # Route finder
    # This function also check variables between '{}' in routes
    # and replace them with url values to send as parameters
    private static function findRoute($method, $val = '/')
    {

        $records = self::filter($method, $val);

        if ($records->count()==1)
            return $records->first();

        $records = self::getInstance()->_collection->where('method', $method);

        foreach ($records as $record)
        {
            $val = ltrim(rtrim($val, '/'), '/');
            $urls = explode('/', $val);
            $carpetas = explode('/', ltrim(rtrim($record->url, '/'), '/'));
            $nuevaruta = '';
            
            $parametros = array();

            //if (count($urls) == count($carpetas))
            //{
                for ($i=0; $i<count($carpetas); $i++)
                {
                    if ($carpetas[$i]!=$urls[$i] && strpos($carpetas[$i], '}')==false)
                        break;

                    if (strpos($carpetas[$i], '}')!=false)
                    {                        
                        $nuevaruta .= $urls[$i].'/';

                        $key = str_replace('{', '', str_replace('}', '', $carpetas[$i]));

                        $index = null;
                        if (strpos($key, ':')>0)
                            list($key, $index) = explode(':', $key);

                        $parametros[$key]['value'] = $urls[$i];
                        if (isset($index)) {
                            $parametros[$key]['index'] = $index;
                            if (!isset($record->scope_binding))
                                $record->scope_binding = 1;
                        }
                    }
                    else
                    {
                        $nuevaruta .= $carpetas[$i].'/';
                    }
                }

                if (rtrim($nuevaruta, '/')==$val)
                {
                    $record->parametros = $parametros;
                    return $record;
                }
                else
                {
                    $parametros = array();
                }
            //}
        }
        return null;

    }

    # Saves route history
    private static function saveHistory()
    {
        global $home;

        if ($_SERVER['REQUEST_METHOD']=='GET')
        {
            //$referer = $this->request->headers->get('referer');

            unset($_GET['ruta']);
    
            $current = isset($_GET['ruta']) ? $_GET['ruta'] :  '/';

            if (count($_GET)>0)
                $ruta = $current.'?'.http_build_query($_GET,'','&');
            else
                $ruta = $current;

            $history = isset($_SESSION['url_history']) ? $_SESSION['url_history'] : array();

            $newurl = rtrim($home, '/') .'/'. ltrim($ruta, '/');
            
            if ($history[0]!=$newurl)
            {
                array_unshift($history, $newurl);
            }
            
            while (count($history)>10)
                array_pop($history);
            
            $_SESSION['url_history'] = $history;

        }
    }


    /**
     * Check if route exists
     * 
     * @param string $name
     * @return bool
     */
    public static function has($name)
    {
        return self::getInstance()->_collection->where('name', $name)->count() > 0;
    }

    /**
     * Check if current route name equals value
     * 
     * @param string $name
     * @return bool
     */
    public static function is($name)
    {
        return self::getCurrentRoute()->name == $name;
    }


    /**
     * Get the current route from its name
     * 
     * @param string $name
     * @return string
     */
    public static function getRoute($params)
    {
        $name = array_shift($params);

        $res = self::getInstance()->_collection->where('name', $name)->first();
        $route = $res->url;
        $route = rtrim(env('APP_URL'), '/') . '/' . $route;
        
        return self::convertCodesFromParams($route, $params);
        //return self::convertCodesFromApp($route, $app->arguments);;

    }

    private static function convertCodesFromParams($route, $args)
    {
        /* if (is_array($args))
        {
            $args = $args[0];
            preg_match_all('/\{[^}]*\}/', $route, $matches);
            foreach ($matches[0] as $match) {
                $clean = ltrim(rtrim($match, '}'), '{');
                $arg = $args[$clean];
                unset($args[$clean]);
                $route = str_replace($match, $arg, $route);
            }
            if (count($args)>0) {
                $params = array();
                foreach ($args as $key => $val) {
                    $params[] = "$key=$val";
                }
                $route .= '?' . implode('&', $params);
            }
            return $route;
        } */

        foreach ($args as $value)
        {
            if (is_object($value))
            {
                $m = new $value;
                $val = $m->getRouteKeyName();
                return preg_replace('/\{[^}]*\}/', $value->$val, $route, 1);
            }
            else
            {
                $route = preg_replace('/\{[^}]*\}/', $value, $route, 1);
                if (strpos($route, "{")==false) break;
            }
        }
        return rtrim(preg_replace('/\{[^}]*\}/', '', $route), '/');
    }

    
    /**
     * Get the current route
     * 
     * @return Route
     */
    public static function getCurrentRoute()
    {
        return self::getInstance()->_currentRoute;
    }

    /**
     * Get the current route name
     * 
     * @return Route
     */
    public static function currentRouteName()
    {
        return self::getInstance()->_currentRoute->name;
    }

    # Sets the actual route
    private static function setCurrentRoute($ruta)
    {
        self::getInstance()->_currentRoute = $ruta;
    }

    private static function checkRedirections($route)
    {
        $res = self::getInstance();
        if (isset($res->_redirections[$route]))
            header('Location: '.$res->_redirections[$route]);

    }


    /**
     * Starts the Application\
     * Verifies if the current url is in routes list\
     * If true it calls the assigned controller@function\
     * Otherwise it returns error 404
     */
    public static function start()
    {
        # Check redirections first
        self::checkRedirections(isset($_GET['ruta']) ? $_GET['ruta'] :  '/');


        # Convert GET/POST into PUT/DELETE if necessary
        if (isset($_GET['_method']) || isset($_POST['_method']))
        {
            $method = isset($_GET['_method'])? $_GET['_method'] : $_POST['_method'];
            $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        }


        # Filter requested url
        $current = (env('PUBLIC_FOLDER')?env('PUBLIC_FOLDER').'/':'') . (isset($_GET['ruta']) ? $_GET['ruta'] :  '/');
        $ruta = self::findRoute($_SERVER['REQUEST_METHOD'], rtrim($current,'/'));

        # Return 404 if route doesn't exists
        if (!isset($ruta->controller) && !isset($ruta->view))
        {
            abort(404);
        }
        # Constructing Request
        $request = app('request');
        $request->generate($ruta);
        
        self::setCurrentRoute($ruta);

        /* if (isset($ruta->middleware))
        {
            $res = self::checkMiddleware($request);

        } */

        $list = MiddlewareHelper::getMiddlewareList($request->route->middleware);

        $res = app('Pipeline')
            ->send($request)
            ->through($list)
            ->thenReturn();
        
        if ($res instanceof Request)
        {
            # Save URL history
            self::saveHistory();

            # Callback - Calls the assigned function in assigned controller
            if (is_string($res->route->controller) && isset($res->route->func))
            {
                $res = CoreLoader::invokeClassMethod($res->route->controller,
                    $res->route->func, $res->route->parametros, $res->route->instance);
            }
            
            # Route returns a view directly
            elseif (isset($res->route->view))
            {
                $res = CoreLoader::invokeView($res->route);
            }
        }
        
        # Show results
        if (is_object($res) && !method_exists(get_class($res), 'showFinalResult'))
            response($res)->json()->showFinalResult();
        elseif (is_string($res))
            echo $res;
        elseif (is_array($res))
            response()->json($res)->showFinalResult();
        elseif (isset($res))
            $res->showFinalResult();

    }


    /* private static function checkMiddleware($request)
    {
        //dd(Route::routeList());exit();

        MiddlewareHelper::bootKernel();
        $middlewares = MiddlewareHelper::getMiddlewaresList();
        $middleware_groups = MiddlewareHelper::getMiddlewareGroup();

        $result = true;

        foreach ($request->route->middleware as $midd)
        {
            list($midd, $params) = explode(':', $midd);

            if (isset($middlewares[$midd]))
            {
                $result = self::invokeMiddleware($middlewares[$midd], $request);

                if (!($result instanceof Request))
                    return $result;
            }
            elseif (isset($middleware_groups[$midd]))
            {
                foreach ($middleware_groups[$midd] as $midd)
                {
                    if (!class_exists($midd))
                    {
                        list($midd, $params) = explode(':', $midd);
                        $midd = $middlewares[$midd];
                    }

                    $result = self::invokeMiddleware($midd, $request);

                    if (!($result instanceof Request))
                        return $result;
                }
            }
            else
            {
                try {
                    $result = self::invokeMiddleware($midd, $request);

                    if (!($result instanceof Request))
                        return $result;
                }
                catch (Exception $e)
                {
                    throw new Exception("Error: Middleware $midd not found");
                }
            }

        }

        return $result;

    } */

    /* private static function invokeMiddleware($middleware, $request)
    {
        //echo "Calling middleware: $middleware<br>";
        $controller = new $middleware;
        return $controller->handle($request, true);
    } */

}
