<?php

abstract Class ServiceProvider
{
    protected $observers = array();

    public $app;

    public function __construct()
    {
        global $app;
        $this->app = $app; 

        global $observers;
        foreach ($this->observers as $model => $class)
        {
            if (!isset($observers[$model]))
                $observers[$model] = $class;
        }

    }

    public function register()
    {

    }

    public function boot()
    {
        
    }


    public function routes()
    {
        $final_routes = array();
        foreach (func_get_args() as $group)
        {
            //dd($group);
            $file = str_replace(_DIR_, '', str_replace('/../..', '', $group['file']));

            if (file_exists(dirname(__FILE__).'/../../storage/framework'.$file) && env('APP_DEBUG')==0)
            {
                //echo "Cached routes: "._DIR_.'/../../storage/framework'.$file."<br>";
                Route::addRouteList(unserialize(file_get_contents(dirname(__FILE__).'/../../storage/framework'.$file)));
                //dd(Route::routeList());
            }
            else
            {
                //dd($group);
                processRoutes(dirname(__FILE__).'/../..', $file);
                $routes = Route::routeList();
                foreach ($routes as $route)
                {
                    
                    if (isset($group['middleware']))
                    {
                        $mid = array();
                        if (isset($route->middleware))
                        {
                            if (is_array($route->middleware))
                            {
                                foreach ($route->middleware as $m)
                                    $mid[] = $m;
                            }
                            else
                                $mid[] = $route->middleware;

                        }
                        if (is_array($group['middleware']))
                        {
                            foreach ($group['middleware'] as $m)
                                $mid[] = $m;
                        }
                        else
                            $mid[] = $group['middleware'];

                        $route->middleware = $mid;

                    }
                    if (isset($group['prefix']))
                    {
                        $route->url = $group['prefix'] . '/' . $route->url;
                    }
                    if (isset($group['prefix']) && isset($route->name))
                    {
                        $route->name = $group['prefix'] . '.' . $route->name;
                    }
                    //dd($route);
                }
                Route::setRouteList(array());

                $final_routes = array_merge($final_routes, $routes->toArray());


                //printf(dirname(__FILE__).'/../../storage/framework/routes'.$file);exit();

                Cache::store('file')->setDirectory(dirname(__FILE__).'/../../storage/framework/routes')
                    ->plainPut(dirname(__FILE__).'/../../storage/framework/routes'.$file, serialize((array)$routes));
                
            }
        }
        Route::setRouteList($final_routes);

    }




}