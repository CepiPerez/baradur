<?php

class RouteServiceProvider extends ServiceProvider
{
    # The path to the "home" route for your application.
    //public const HOME = '/home';

    public function boot()
    {
        Route::resourceVerbs([
            'create' => 'crear',
            'edit' => 'editar',
        ]);


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
       
    }
}
