<?php

class BaseController
{
    public $middleware = array();

    /** @return ControllerMiddleware **/
    public function middleware($middleware)
    {
        $new = new ControllerMiddleware;
        $new->middleware = $middleware;
        $this->middleware[] = $new;

        return $new;
    }
    

    public function authorize($function, $param=null)
    {
        call_user_func_array(array('Authorize', 'verify'), array($function, $param));
    }


}
