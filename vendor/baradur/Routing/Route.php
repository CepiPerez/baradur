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
    public $_domain = '';
    public $_prefix = '';
    public $_name = '';
    public $_scope_bindings = true;
    
    public $_collection;
    public $current;
    public $GET;
    public $PUT;
    public $POST;
    public $DELETE;

    protected static $binds = array();


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
     * Add a new route parameter binder.
     *
     * @return void
     */
    public static function bind($key, $binder)
    {
        self::$binds[$key] = array('class' => null, 'callback' => $binder);
    }

    /**
     * Register a model binder for a wildcard.
     *
     * @return void
     */
    public static function model($key, $class, $callback = null)
    {
        self::$binds[$key] = array('class' => $class, 'callback' => $callback);
    }

    public static function __getBinders()
    {
        return self::$binds;
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
        $res->prefix = ($instance->_prefix? $instance->_prefix.'/' : '') . $prefix;
        return $res;
    }

    /**
     * Assign domain to routes
     * 
     * @param string $domain
     * @return RouteGroup
     */
    public static function domain($domain)
    {
        $instance = self::getInstance();
        $res = new RouteGroup($instance);
        $res->domain = ($instance->_domain? $instance->_domain.'.' : null) . $domain;
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
     * @param array $parameters
     */
    public static function view($url, $view, $parameters=array())
    {
        return self::addRoute('GET', $url, null, null, $view, $parameters);
    }


    # Add route (previous phase) (private)
    # Checks if the give route has the controller's name 
    # If it's true then it adds the route, otherwise it
    # returns an array for group() function
    private static function getOrAppend($method, $url, $callback)
    {
        global $_class_list;

        if (is_closure($callback)) {
            $arr = explode('|', $callback);
            $callback = $arr[0] . '@' . $arr[1];
        }

        if (is_string($callback) && strpos($callback, '@')!==false)
            $callback = explode('@', $callback);

        elseif (is_string($callback) && isset($_class_list[$callback]))
            $callback = array($callback, '__invoke');

        elseif (is_string($callback))// && !is_closure($callback))
            $callback = array('', $callback);

        return self::addRoute($method, $url, $callback[0], $callback[1]);
        
    }

    # Adds a route
    # Private - Creates the routes list (array)
    private static function addRoute($method, $url, $controller, $func, $view=false, $viewparams=null)
    {
        $res = self::getInstance();

        $domain = $res->_domain;

        if (!$domain) {
            $parse = parse_url(config('app.url'));
            $domain = $parse['host'];
            $path = isset($parse['path']) ? $parse['path'] : '';
        }

        $domain = $domain . $path;

        $method = strtoupper($method);
        $url = ltrim($url, "/");
        $url = /* $domain . '/' . */ ($res->_prefix? $res->_prefix.'/' : '') . ($url=='/' ? '' : $url);

        if ($url == '') {
            $url = '/';
        }

        if ($domain.'/' == $url) {
            $url = $domain;
        }

        if ($func==null) {
            $func = '';
        }

        $route = new RouteItem;
        $route->method = $method;
        $route->domain = $domain;
        $route->url = $url;
        $route->full_url = str_replace('//', '/', $domain . '/' . $url);
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

        $domain = $_SERVER['HTTP_HOST'];
        
        $val = str_replace($domain, str_replace('.', '/', $domain), $val);
        $val = ltrim($val, '/');
        
        if ($val=='') $val = '/';
        
        $records = self::getRoutes()->where('method', $method)->where('full_url', '==', $val);
        //dd($method, $val, $_SERVER, self::getRoutes());
        
        if ($records->count() == 1) {
            return $records->first();
        }

        $records = self::getRoutes()->where('method', $method)->where('full_url', '!==', '');

        $dictionary = $records->getDictionary();

        $record = null;

        foreach ($dictionary as $item)
        {
            $full = str_replace('.', '/', $item->domain) . '/' . $item->url;

            $replaced = $full;
            $alternative = $full;

            preg_match_all('/\{.*?\}/x', $replaced, $matches);

            $matches = count($matches[0])>0 ? $matches[0] : array();

            //dump($matches, $val);

            foreach ($matches as $par) {
                //dump($par);
                if (strpos($par, '?}')!==false) {
                    $alternative = preg_replace('/\/\{.*?\}/x', '', $alternative, 1);
                    $replaced = preg_replace('/\{.*?\}/x', '.*', $replaced, 1);
                } else {
                    $alternative = preg_replace('/\{.*?\}/x', '.*', $alternative, 1);
                    $replaced = preg_replace('/\{.*?\}/x', '.*', $replaced, 1);
                }
            }

            $replaced = str_replace('/', '\\/', $replaced);
            $alternative = str_replace('/', '\\/', $alternative);

            //echo "$replaced :: $alternative :: $val <br>";

            if ((preg_match('/^'.$replaced.'$/x', $val, $matches)) 
                /* && (count(explode('/', $val))==count(explode('/', $replaced))) */)
            {
                //dump("RECORD", $url);
                $record = ($item);
                break;
            }

            if ((preg_match('/^'.$alternative.'$/x', $val, $matches)) 
                /* && (count(explode('/', $val))==count(explode('/', $alternative  ))) */)
            {
                //dump("ALTERNATIVE", $url);
                $record = ($item);
                break;
            }
        }
        
        //dd($record);

        if (!$record) {
            return null;
        }


        $val = ltrim(rtrim($val, '/'), '/');
        $urls = explode('/', $val);
        $full = str_replace('.', '/', $record->domain) . '/' . $record->url;
        $carpetas = explode('/', ltrim(rtrim($full, '/'), '/'));
        $nuevaruta = '';
        
        $parametros = array();

        for ($i=0; $i<count($carpetas); $i++) {

            if ($carpetas[$i]!=$urls[$i] && strpos($carpetas[$i], '}')==false) {
                break;
            }

            if ($carpetas[$i]==$record->domain && strpos($carpetas[$i], '}')!=false) {
                $key = substr($carpetas[$i], 1, strpos($carpetas[$i], '}')-1);
                $garbage = str_replace('{'.$key.'}', '', $carpetas[$i]);
                $parametros[$key]['value'] = str_replace($garbage, '', $urls[$i]);
                $nuevaruta .= $urls[$i].'/';
            }

            elseif (strpos($carpetas[$i], '}')!=false) {                        
                $nuevaruta .= $urls[$i].'/';

                $key = substr($carpetas[$i], 1, strpos($carpetas[$i], '}')-1);

                $index = null;
                if (strpos($key, ':')>0) {
                    list($key, $index) = explode(':', $key);
                }

                if ((str_ends_with($key, '?') && $urls[$i]!==null) || !str_ends_with($key, '?')) {
                    $parametros[$key]['value'] = $urls[$i];
                } else {
                    $parametros[$key]['value'] = 'baradur_null_parameter';
                }

                if (isset($index)) {
                    $parametros[$key]['index'] = $index;
                    if (!isset($record->scope_binding)) {
                        $record->scope_binding = 1;
                    }
                }
            }
            
            else {
                $nuevaruta .= $carpetas[$i].'/';
            }
        }

        if (rtrim($nuevaruta, '/')==$val)
        {
            $record->parametros = $parametros;
            //dd($record);
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

            //unset($_GET['ruta']);
            //$current = isset($_GET['ruta']) ? $_GET['ruta'] :  '/';

            $current = ltrim($_SERVER['REDIRECT_URL'], '/');

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

        $parse = parse_url(config('app.url'));
        //$domain = $parse['host'];
        //$route = ltrim($route, $domain);
        $route = /* rtrim(config('app.url'), '/')  */ config('app.url') . '/' . $route;

        return self::convertCodesFromParams($route, $params);
        //return self::convertCodesFromApp($route, $app->arguments);;

    }

    private static function convertCodesFromParams($route, $args)
    {
        //ddd($args);
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

        $parse = parse_url(config('app.url'));
        $domain = $parse['host'];
        $path = $parse['path'];
        
        $route = str_replace($domain.$path, '', $route);
        $route = ltrim($route, '/');
        
        if ($route=='') $route = '/';

        if (isset($res->_redirections[$route]))
        {
            $to = config('app.url') . '/' . $res->_redirections[$route]['to'];
            $code = $res->_redirections[$route]['code'];

            $reason = HttpResponse::$reason_phrases[$code];
            header("HTTP/1.1 $code $reason");
            echo header('Location: '.$to);
            __exit();
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
        $current = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $parse = parse_url($current);
        $current = $parse['path'];

        # Check redirections first
        self::checkRedirections($current);

        //dd(self::getRoutes());

        # Convert GET/POST into PUT/DELETE if necessary
        if (isset($_GET['_method']) || isset($_POST['_method']))
        {
            $method = isset($_GET['_method'])? $_GET['_method'] : $_POST['_method'];
            $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        }

        # Filter requested url
        //$current = (isset($_GET['ruta']) ? $_GET['ruta'] :  '/');
        $ruta = self::findRoute($_SERVER['REQUEST_METHOD'], $current);

        //dd($ruta);

        # Return 404 if route doesn't exists
        if (!isset($ruta->controller) && !isset($ruta->view))
        {
            global $app;
            if ($app->maintenanceMode()) {

                $secret = str_replace($_SERVER['HTTP_HOST'].'/', '', $current);

                if (filled($secret)) {
                    MaintenanceMiddleware::checkSecret($secret);

                    if ($_SESSION['bypass']['stored']==$_SESSION['bypass']['secret'] && $_SESSION['bypass']['stored']!==null) {
                        return redirect('/');
                    }
                }
            }

            throw new RouteNotFoundException(
                'Missing route ['.$_SERVER['REQUEST_METHOD'].'] /' . $current .'.', 
                404
            );
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


        //dd("RES", $res);

        # Show results
        if (!$res) {
            __exit();
        }

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
            $res = response( $res->__toString(), 200);
        }
        elseif (!($res instanceof Response) && is_string($res)) {
            $res = response( $res, 200);
        }

        if ($res instanceof Response)
        {
            return $res;
        }

        throw new RuntimeException("Invalid response [".get_class($res)."]");

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
