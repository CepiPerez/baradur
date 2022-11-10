<?php

class ViewServiceProvider extends ServiceProvider
{

    public function register()
    {

    }

    public function boot()
    {
        //View::share('data', Category::orderBy('id')->paginate(15));

        /* View::composer(['categories'], function ($view) {
            $view->with('data', Category::orderBy('id')->paginate(15));
        }); */

        //View::composer(['categories'], CategoryComposer::class);

    }

}
