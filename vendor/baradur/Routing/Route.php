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
        $this->name = $name;
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
        $this->middleware = $middleware;
        return $this;
    }

    public function scopeBindings()
    {
        $this->scope_binding = true;
        return $this;
    }

}

Class RouteGroup 
{
    public $controller = null;
    public $middleware = null;
    public $prefix = null;


    /**
     * Assign controller to certain routes
     * 
     * @param string $controller
     * @return RouteGroup
     */
    public function controller($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * Assign middleware to certain routes
     * 
     * @param string $middleware
     * @return RouteGroup
     */
    public function middleware($middleware)
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * Assign prefix to certain routes
     * 
     * @param string $prefix
     * @return RouteGroup
     */
    public function prefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Adds all given routes
     * Callback should be Controller@function
     * 
     * @param string $url
     * @param string $callback
     */
    public function group()
    {
        $res = Route::getInstance();
        $this->added = array();

        $to_load = null;

        $var = func_get_args();

        if (is_string($var[0]) && is_file($var[0]))
        {
            if (file_exists($var[0]))
            {
                return array(
                    'middleware' => $this->middleware,
                    'prefix' => $this->prefix,
                    'controller' => $this->controller,
                    'file' => $var[0]
                );
            }
        }
        else
        {
            $to_load = func_get_args();
        }

        //dd($to_load); exit();

        foreach ($to_load as $r)
        {
            //echo "Revisando ruta: "; var_dump($r); echo ":".$this->prefix."<br>";
            if (is_array($r))
            {
                foreach ($r as $route)
                {
                    $route->url = ltrim($route->url, '/');
                    if ($this->controller) $route->controller = $this->controller;
                    if ($this->middleware) $route->middleware = $this->middleware;
                    if ($this->prefix) $route->url = $this->prefix . '/' . $route->url;
                    if ($this->prefix && isset($route->name)) $route->name = $this->prefix . '.' . $route->name;
                }

            }
            else if (isset($r->method))
            {
                //dd($r); dd($this->controller);
                $r->url = ltrim($r->url, '/');
                if ($this->controller) $r->controller = $this->controller;
                if ($this->middleware) $r->middleware = $this->middleware;
                if ($this->prefix) $r->url = $this->prefix . '/' . $r->url;
                if ($this->prefix && isset($r->name)) $r->name = $this->prefix . '.' . $r->name;

                if (!$res->_collection->where('method', $r->method)->where('url', $r->url)->first())
                    $res->_collection->put($r);
                    
                $this->added[] = $r;
            }
            else
            {
                foreach ($r->added as $rs)
                {
                    $rs->url = ltrim($rs->url, '/');
                    if ($this->controller) $rs->controller = $this->controller;
                    if ($this->middleware) $rs->middleware = $this->middleware;
                    if ($this->prefix) $rs->url = $this->prefix . '/' . $rs->url;
                    if ($this->prefix && isset($rs->name)) $rs->name = $this->prefix . '.' . $rs->name;

                    //if (!$res->_collection->where('method', $rs->method)->where('url', $rs->url)->first())
                    //    $res->_collection->put($rs);

                    $this->added[] = $rs;

                }
            }
        }
        //dd($this);
        return $this;

    } 


    public function except($except)
    {        
        foreach ($except as $ex)
        {
            $name = Route::getVerbName($ex);
            foreach ($this->added as $route)
            {
                if (substr($route->name, - (strlen($name)+1) ) == '.'.$name)
                    Route::getInstance()->_collection->pull('name', $route->name);
            }
        }
    }

    public function only($only)
    {
        $excluded = array();

        foreach ($only as $ex)
        {
            $name = Route::getVerbName($ex);
            foreach ($this->added as $route)
            {
                if (substr($route->name, - (strlen($name)+1) ) == '.'.$name)
                    $excluded[] = $route->name;
            }
        }

        foreach ($this->added as $route)
        {
            if (!in_array($route->name, $excluded))
                Route::getInstance()->_collection->pull('name', $route->name);
        }

    }


}


class Route
{
    private static $_instance;
    public static $_strings;
    protected $_current;
    protected $_controller = null;
    protected $_middleware = null;
    protected $_prefix = null;
    protected $_currentRoute;


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
    

    
    /**
     * Define resources localization
     * 
     * @param array $strings
     */
    public static function resourceVerbs($strings)
    {
        self::$_strings = $strings;
    }


    public static function group()
    {
        $res = Route::getInstance();

        $routes = func_get_args();

        if (is_string($routes[0]) && is_file($routes[0]))
        {
            if (file_exists($routes[0]))
            {
                return array(
                    'middleware' => null,
                    'prefix' => null,
                    'controller' => null,
                    'file' => $routes[0]
                );
            }
        }

        if (is_array($routes[0])) // && !is_object($routes[0][0]))
        {
            $attributes = array_shift($routes);
            //dd($attributes);
            foreach ($attributes as $key => $val)
            {
                if ($key == 'middleware') $res->_middleware = $val;
                else if ($key == 'controller') $res->_controller = $val;
                else if ($key == 'prefix') $res->_prefix = $val;
            }
        }

        foreach ($routes as $r)
        {
            //dd($r);
            if (!is_array($r))
            {
                $r->url = ltrim($r->url, '/');
                if (isset($res->_controller)) $r->controller = $res->_controller;
                if (isset($res->_middleware)) $r->middleware = $res->_middleware;
                if (isset($res->_prefix)) $r->url = $res->_prefix . '/' . $r->url;
                if (isset($res->_prefix) && isset($r->name)) $r->name = $res->_prefix . '.' . $r->name;
                //$res->_collection->put($r);
            }
            else
            {
                foreach ($r as $route)
                {
                    if (isset($res->_controller)) $route->controller = $res->_controller;
                    if (isset($res->_middleware)) $route->middleware = $res->_middleware;
                    if (isset($res->_prefix)) $route->url = $res->_prefix . '/' . $route->url;
                    if (isset($res->_prefix) && isset($route->name)) $route->name = $res->_prefix . '.' . $route->name;
                }
            }
        }
        $res->_middleware = null;
        $res->_controller = null;
        $res->_prefix = null;
    } 

    /**
     * Add a new route for GET method\
     * Callback should be Controller@function\ 
     * Example: get('/products/info', 'ProductsController@showinfo')
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
     * Add a new route for POST method\
     * Callback should be Controller@function\ 
     * Example: post('/products/info', 'ProductsController@showinfo')
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
     * Add a new route for PUT method\
     * Callback should be Controller@function\ 
     * Example: put('/products/info', 'ProductsController@showinfo')
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
     * Add a new route for DELETE method\
     * Callback should be Controller@function\ 
     * Example: delete('/products/info', 'ProductsController@showinfo')
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
     * Assign controller to routes\
     * It can be used to group routes using group()
     * 
     * @param string $controller
     * @return RouteGroup
     */
    public static function controller($controller)
    {
        $res = new RouteGroup;
        $res->controller = $controller;
        return $res;
    }

    /**
     * Assign middleware to routes\
     * It can be used to group routes using group()
     * 
     * @param string $middleware
     * @return RouteGroup
     */
    public static function middleware($middleware)
    {
        $res = new RouteGroup;
        $res->middleware = $middleware;
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
        $res = new RouteGroup;
        $res->prefix = $prefix;
        return $res;
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
        $arr = new RouteGroup;
        $item = Helpers::getSingular($url);

        $arr->group(
            self::addRoute('GET', $url, $controller, 'index')->name($url.'.index'),
            self::addRoute('GET', $url.'/'.Route::getVerbName('create'), $controller, 'create')->name($url.'.create'),
            self::addRoute('POST', $url, $controller, 'store')->name($url.'.store'),
            self::addRoute('GET', $url.'/{'.$item.'}', $controller, 'show')->name($url.'.show'),
            self::addRoute('GET', $url.'/{'.$item.'}/'.Route::getVerbName('edit'), $controller, 'edit')->name($url.'.edit'),
            self::addRoute('PUT', $url.'/{'.$item.'}', $controller, 'update')->name($url.'.update'),
            self::addRoute('DELETE', $url.'/{'.$item.'}', $controller, 'destroy')->name($url.'.destroy')
        );
        return $arr;
    }


    /**
     * Creates a controller's resources for APIs\
     * Example: apiResource('products', 'ProductsController')
     * 
     * @param string $url
     * @param string $controller
     */
    public static function apiResource($url, $controller)
    {
        $arr = array();
        $item = Helpers::getSingular($url);

        $arr[] = self::addRoute('GET', $url, $controller, 'index')->name($url.'.index');
        $arr[] = self::addRoute('GET', $url.'/{'.$item.'}', $controller, 'show')->name($url.'.show');
        $arr[] = self::addRoute('POST', $url, $controller, 'store')->name($url.'.store');
        $arr[] = self::addRoute('PUT', $url.'/{'.$item.'}', $controller, 'update')->name($url.'.update');
        $arr[] = self::addRoute('DELETE', $url.'/{'.$item.'}', $controller, 'destroy')->name($url.'.destroy');

        return $arr;
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
    private static function getOrAppend($method, $url, $destination)
    {
        if (is_string($destination))
        {
            if (strpos($destination, '@')!=false)
            {
                list($controller, $func) = explode('@', $destination);
                return self::addRoute($method, $url, $controller, $func);
            }
            /* elseif (is_array($destination) && count($destination)==2)
            {
                return self::addRoute($method, $url, $destination[0], $destination[1]);
            } */
            else
            {
                //echo "Returning route: " . $url."<br>";
                $arr = new RouteItem;
                $arr->method = $method;
                $arr->url = $url=='/'?'':$url;
                $arr->func = $destination;
                //$res->_temp[] = $arr;
                //$res->_current = $arr;
                return $arr;
            }
        }
        elseif (is_array($destination) && count($destination)==2)
        {
            return self::addRoute($method, $url, $destination[0], $destination[1]);
        }
        
        # Este paso genera una ruta con closures
        # Solamente valido para PHP => 5.3
        else
        {
            return self::addRoute($method, $url, $destination, null);
        }
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
        #printf("adding:".$url."\n");
        $method = strtoupper($method);
        $url = ltrim($url, "/");

        $arr = new RouteItem;
        $arr->method = $method;
        $arr->url = $url=='/' ? '' : $url;
        $arr->controller = $controller;
        $arr->func = $func;
        $arr->view = $view;
        $arr->viewparams = $viewparams;
        
        $res = self::getInstance();

        $res->_collection->put($arr);

        return $arr;
    }

    # Route filter
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

            if (count($urls) == count($carpetas))
            {
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
                        if (isset($index)) $parametros[$key]['index'] = $index;
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
            }
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
        foreach ($args as $value)
        {
            if (is_object($value))
            {
                $val = $value->getInstance()->getRouteKeyName();
                return preg_replace('/\{[^}]*\}/', $value->$val, $route, 1);
            }
            else
            {
                $route = preg_replace('/\{[^}]*\}/', $value, $route, 1);
                if (strpos($route, "{")==false) break;
            }
        }
        return $route;
    }

    /* private static function convertCodesFromApp($route, $args)
    {
        foreach ($args as $key => $value)
        {
            if (is_array($value))
            {
                $route = self::convertCodesFromApp($route, $value);
            }
            else if (is_object($value))
            {
                $route = self::convertCodesFromApp($route, $value);
            }
            else
            {
                $route = str_replace('{'.$key.'}', $value, $route);
                if (strpos($route, "{")==false) break;
            }
        }
        return $route;
    } */

    /**
     * Get the current route
     * 
     * @return Route
     */
    public static function getCurrentRoute()
    {
        return self::getInstance()->_currentRoute;
    }

    # Sets the actual route
    private static function setCurrentRoute($ruta)
    {
        self::getInstance()->_currentRoute = $ruta;
    }


    private function getParamArray( $item ){
        return array(
            'param' => $item->getName(), 
            'class' => ($item->getClass()!=null)? $item->getClass()->getName() : null
        );
    }


    /**
     * Starts the Application\
     * Verifies if the current url is in routes list\
     * If true it calls the assigned controller@function\
     * Otherwise it returns error 404
     */
    public static function start()
    {        
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


        # If route has middleware then call it
        $continue = false;
        if (isset($ruta->middleware))
        {
            $res = self::checkMiddleware($request);

            if (is_bool($res))
                $continue = $res;
        }

        if ($continue)
        {            
            # Save URL history
            self::saveHistory();
            
            # Callback - Calls the assigned function in assigned controller
            if (is_string($request->route->controller) && isset($request->route->func))
            {
                $res = CoreLoader::invokeClass($request->route);
            }
            
            # Route returns a view directly
            elseif (isset($request->route->view))
            {
                $res = CoreLoader::invokeView($request->route);
            }
            
        }

        # Show results
        if (is_object($res) && !method_exists(get_class($res), 'showFinalResult'))
            response($res)->showFinalResult();
        elseif (is_string($res))
            echo $res;
        elseif (isset($res))
            $res->showFinalResult();

    }

    private static function checkMiddleware($request)
    {
        //dd(Route::routeList());exit();

        MiddlewareHelper::bootKernel();
        $middlewares = MiddlewareHelper::getMiddlewaresList();
        $middleware_groups = MiddlewareHelper::getMiddlewareGroup();

        $result = true;

        foreach ($request->route->middleware as $midd)
        {
            if (isset($middlewares[$midd]))
            {
                if (!class_exists($midd))
                {
                    list($midd, $params) = explode(':', $midd);
                    $midd = $middlewares[$midd];
                }
                //dd($middlewares);
                //echo "HOLA:: $middlewares[$midd]<br>";
                $result = self::invokeMiddleware($midd, $request);

                if (!is_bool($result) || $result==false)
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

                    if (!is_bool($result) || $result==false)
                        return $result;
                }
            }

            /* $controller = new $middleware;
            $result = $controller->handle($request, $result);

            if (!is_bool($result) || $result==false);
                return $result; */
        }


        return $result;

    }

    private static function invokeMiddleware($middleware, $request)
    {
        //echo "Calling middleware: $middleware<br>";
        $controller = new $middleware;
        return $controller->handle($request, true);
    }

}
