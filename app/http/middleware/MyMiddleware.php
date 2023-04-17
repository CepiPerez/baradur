<?php

class MyMiddleware extends Middleware
{

    public function handle($request, $next, $role=null)
    {
        echo "Hello from middleware --".($role? $role : 'nothing')."--<br>";
        
        /* if ($request->path()=='productos' && !Auth::check())
        {
            abort(403);
        } */
        $res =$next($request);
        return $res;
    }


}

?>