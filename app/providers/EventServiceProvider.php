<?php

class EventServiceProvider extends ServiceProvider
{

    protected $observers = [
        //Category::class => CategoryObserver::class,
    ];

    public function boot()
    {
        # Observers
        // Category::observe(CategoryOberver::class);
    }

}
