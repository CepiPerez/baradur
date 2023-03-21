<?php

class RouteServiceProvider extends ServiceProvider
{
    # The path to the "home" route for your application.
    public const HOME = '/';

    public function boot()
    {
        //$this->configureRateLimiting();

        $this->routes(function () {

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    
    protected function configureRateLimiting()
    {
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

    }
}
