<?php

class BaseController
{
    public $middleware = array();

    /** @return RouteMiddleware **/
    public function middleware($middleware)
    {
        $new = new RouteMiddleware;
        $new->middleware = $middleware;
        $this->middleware[] = $new;

        return $new;
    }
    
    public function authorize($function, $param=null)
    {
        //call_user_func_array(array('Authorize', 'verify'), array($function, $param));
        Gate::authorize($function, $param);
    }

}
