<?php

class Kernel extends HttpKernel
{
    # The application's global HTTP middleware stack.
    protected $middleware = [

    ];

    # The application's route middleware groups.
    protected $middlewareGroups = [
        'web' => [
            VerifyCsrfToken::class,
            //SubstituteBindings::class,
        ],

        'api' => [
            //'throttle:api',
            //SubstituteBindings::class,
        ],
    ];

    # The application's route middleware.
    protected $routeMiddleware = [
        'web' => MyMiddleware::class,
        'auth' => Auth::class,
    ];
}