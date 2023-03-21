<?php

class AppServiceProvider extends ServiceProvider
{

    public function register()
    {

    }

    public function boot()
    {
        //Model::preventLazyLoading(!$this->app->inProduction());
        //Model::preventSilentlyDiscardingAttributes(!$this->app->inProduction());
        //Model::preventAccessingMissingAttributes(!$this->app->inProduction());

        //Paginator::useBootstrapFour();

    }

}
