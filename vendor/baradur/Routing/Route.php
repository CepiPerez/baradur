<?php

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
    public $_scope_bindings = true;
    
    public $_collection;
    public $current;
    public $GET;
    public $PUT;
    public $POST;
    public $DELETE;

    public function __construct()
    {
        $this->current = null;
        $this->GET = array();
        $this->PUT = array();
        $this->POST = array();
        $this->DELETE = array();
        $this->_collection = new Collection(); //collectWithParent(null, 'Route');

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

    /**
     * Get the underlying route collection.
     *
     * @return Collection
     */
    public static function getRoutes()
    {
        $res = self::getInstance();
        return $res->_collection;
    }

    /**
     * Redirects to specific route
     * 
     * @param string $redirect_from
     * @param string $redirect_to
     */
    public static function redirect($redirect_from, $redirect_to, $code=302)
    {
        $res = self::getInstance();
        $res->_redirections[$redirect_from] = array('to' => $redirect_to, 'code' => $code);
    }
    
    /**
     * Redirects to specific route
     * 
     * @param string $redirect_from
     * @param string $redirect_to
     */
    public static function permanentRedirect($redirect_from, $redirect_to)
    {
        $res = self::getInstance();
        $res->_redirections[$redirect_from] = array('to' => $redirect_to, 'code' => 301);
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
     * @param string|array|Closure $callback
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
     * @param string|array|Closure $callback
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
     * @param string|array|Closure $callback
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
     * @param string|array|Closure $callback
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
     * Enable scope bindings to routes
     *  
     * @return RouteGroup
     */
    public static function scopeBindings()
    {
        $instance = self::getInstance();
        $res = new RouteGroup($instance);
        $res->scope_bindings = true;
        return $res;
    }

    /**
     * Disable scope bindings to routes
     *  
     * @param boolean $scope_bindings
     * @return RouteGroup
     */
    public static function withoutScopedBindings()
    {
        $instance = self::getInstance();
        $res = new RouteGroup($instance);
        $res->scope_bindings = false;
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
        if (is_string($routes) && file_exists($routes))
        {
            CoreLoader::loadClass($routes, false);
        }        
        elseif (is_string($routes) && is_closure($routes))
        {
            list($c, $m, $p) = getCallbackFromString($routes);
            executeCallback($c, $m, $p);
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
    public static function view($url, $view, $params=array())
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

        elseif (is_string($callback) && !is_closure($callback))
            $callback = array('', $callback);

        return self::addRoute($method, $url, $callback[0], $callback[1]);
        
    }

    # Adds a route
    # Private - Creates the routes list (array)
    private static function addRoute($method, $url, $controller, $func, $view=false, $viewparams=null)
    {
        $res = self::getInstance();

        $method = strtoupper($method);
        $url = ltrim($url, "/");

        if ($func==null) $func = '';

        $route = new RouteItem;
        $route->method = $method;
        $route->url = ($res->_prefix? $res->_prefix.'/' : '') . ($url=='/' ? '' : $url);
        $route->middleware = $res->_middleware;
        $route->name = $res->_name!=''? $res->_name : null ;
        $route->scope_bindings = $res->_scope_bindings;
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
        $records = self::getRoutes()->where('method', $method);

        $record = $records->where('url', '==', $val);

        if ($record->count() == 1) {
            return $record->first();
        }

        $records = $records->where('url', '!==', '');

        $dictionary = $records->getDictionary();

        foreach (array_keys($dictionary) as $url)
        {
            $replaced = $url;
            $alternative = $url;
            preg_match('/\{.*?\}/x', $replaced, $matches);

            foreach ($matches as $par) {
                if (strpos($replaced, '?}')!==false) {
                    $alternative = preg_replace('/\/\{.*?\}/x', '', $alternative);
                    $replaced = preg_replace('/\{.*?\}/x', '.*', $replaced);
                } else {
                    $alternative = preg_replace('/\{.*?\}/x', '.*', $alternative);
                    $replaced = preg_replace('/\{.*?\}/x', '.*', $replaced);
                }
            }

            $replaced = str_replace('/', '\\/', $replaced);
            $alternative = str_replace('/', '\\/', $alternative);

            //echo "$replaced :: $alternative :: $val <br>";

            if (preg_match('/^'.$replaced.'$/x', $val, $matches))
            {
                //dump($url);
                $record = ($dictionary[$url]);
                break;
            }

            if (preg_match('/^'.$alternative.'$/x', $val, $matches))
            {
                //dump($url);
                $record = ($dictionary[$url]);
                break;
            }
        }

        if (!$record) {
            return null;
        }

        $val = ltrim(rtrim($val, '/'), '/');
        $urls = explode('/', $val);
        $carpetas = explode('/', ltrim(rtrim($record->url, '/'), '/'));
        $nuevaruta = '';
        
        $parametros = array();

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
        return self::current()->name == $name;
    }


    /**
     * Get the current route from its name
     * 
     * @param string $name
     * @return string
     */
    public static function getRoute($params)
    {
        if (is_string($params)) $params = array($params);
        
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
     * @return RouteItem
     */
    public static function current()
    {
        return self::getInstance()->_currentRoute;
    }

    /**
     * Get the current route
     * 
     * @return RouteItem
     */
    public static function getCurrentRoute()
    {
        return self::current();
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
        {
            $to = $res->_redirections[$route]['to'];
            $code = $res->_redirections[$route]['code'];

            $reason = HttpResponse::$reason_phrases[$code];
            header("HTTP/1.1 $code $reason");
            echo header('Location: '.$to);
        }

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
        $current = (isset($_GET['ruta']) ? $_GET['ruta'] :  '/');
        $ruta = self::findRoute($_SERVER['REQUEST_METHOD'], rtrim($current,'/'));

        # Return 404 if route doesn't exists
        if (!isset($ruta->controller) && !isset($ruta->view))
        {
            abort(404);
        }
        
        foreach ($ruta->wheres as $key => $constraint)
        {
            if (!isset($ruta->parametros[$key])) {
                abort(404);
            }

            preg_match("/".$constraint."/x", $ruta->parametros[$key]['value'], $matches);
            
            if(count($matches)==0) {
                abort(404);
            }
        }
        
        # Constructing Request
        $request = app('request');
        $request->generate($ruta);
        
        self::setCurrentRoute($ruta);

        $list = HttpKernel::getMiddlewareList($request->route->middleware);

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
        if ($res instanceof ResourceCollection) {
            $res = response( $res->getResult(), 200);
        }
        elseif ($res instanceof JsonResource) {
            $res = response( (array)$res, 200);
        }
        elseif ($res instanceof Model || $res instanceof Collection) {
            $res = response( $res->toArray(), 200);
        }
        elseif ($res instanceof FinalView) {
            $res = response( (string)$res, 200);
        }
        elseif (!($res instanceof Response)) {
            $res = response( (string)$res, 200);
        }

        if ($res instanceof Response)
        {
            CoreLoader::processResponse($res);
        }

        throw new Exception("Invalid response [".get_class($res)."]");

        /* if (is_object($res) && !method_exists(get_class($res), 'showFinalResult'))
            response($res)->json()->showFinalResult();
        elseif (is_string($res))
            echo $res;
        elseif (is_array($res))
            response()->json($res)->showFinalResult();
        elseif (isset($res))
            $res->showFinalResult(); */

    }

}
