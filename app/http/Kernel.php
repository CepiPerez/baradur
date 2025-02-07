<?php

class Kernel extends HttpKernel
{
    # The application's global HTTP middleware stack.
    protected $middleware = [
        PreventRequestsDuringMaintenance::class,
    ];

    # The application's route middleware groups.
    protected $middlewareGroups = [
        'web' => [
            VerifyCsrfToken::class,
            SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            SubstituteBindings::class,
        ],
    ];

    # The application's route middleware.
    protected $middlewareAliases = [
        'auth' => Authenticate::class,
        'guest' => RedirectIfAuthenticated::class,
        'throttle' => ThrottleRequests::class,
        'signed' => ValidateSignature::class,
        'can' => Authorize::class,
        'verified' => EnsureEmailIsVerified::class,
        'features' => EnsureFeaturesAreActive::class
    ];
}
