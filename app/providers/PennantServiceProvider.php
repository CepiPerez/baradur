<?php

class PennantServiceProvider extends ServiceProvider
{

    public function register()
    {
    }

    public function boot()
    {
        # Blade directive        
        Blade::if('feature', function ($feature, $value = null) {
            if (func_num_args() === 2) {
                return Feature::value($feature) === $value;
            }

            return Feature::active($feature);
        });

        # Custom response for Middleware
        EnsureFeaturesAreActive::whenInactive(
            function (Request $request, $features) {
                abort(403);
            }
        );

        # Features
        //Feature::define('new-api', fn (User $user) => match (true) {
        //    $user->isAdmin => true,
        //    default => Lottery::odds(1 / 3),
        //});

        //Feature::define('site-redesign', Lottery::odds(1/2));

        //Feature::define('purchase-button', fn (User $user) => Arr::random([
        //    'blue-sapphire',
        //    'seafoam-green',
        //    'tart-orange',
        //]));



    }

}
