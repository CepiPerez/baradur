<?php

class MyMiddleware
{

    # Middleware
    # -----------------------------------------------------------------
    # We can verify the route access from here
    # If we need to block we just need to call abort() 
    #
    # $route has the following parameters wich
    # can be used to check
    #
    # $route->method       => method (GET, POST, PUT, DELETE)
    # $route->url          => url assigned to route
    # $route->controller   => assigned controller
    # $route->func         => function to call from controller
    # $route->name         => assigned name to route (may be null)
    #
    # Example:
    #
    # if ($route->url=='products' && !isset($_SESSION['user']))
    # {
    #     abort(403);
    # }

    public static function check($route)
    {
        //echo "Hello from middleware<br>";
        
        
    }


}

?>