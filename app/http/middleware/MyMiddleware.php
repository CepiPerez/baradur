<?php

class MyMiddleware extends Middleware
{

    public function handle($request, $next)
    {
        //echo "Hello from middleware --".$request->path()."--<br>";
        
        /* if ($request->path()=='productos' && !Auth::check())
        {
            abort(403);
        } */
        return $next;
        
    }


}

?>