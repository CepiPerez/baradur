<?php

class CustomCasterServiceProvider extends ServiceProvider
{

    public function register()
    {

    }

    public function boot()
    {
        CustomCaster::for(Collection::class)
            ->only(['items']);

        CustomCaster::for(Paginator::class)
            ->only(['pagination', 'items'])
            ->virtual('pagination', fn($q) => $q->pagination());

        CustomCaster::for(Model::class)
            ->only(['attributes', 'relations'])
            ->filter();

        CustomCaster::for(Builder::class)
            ->only(['sql', 'bindings', '_eagerLoad'])
            ->virtual('sql', fn($q) => $q->toSql())
            ->virtual('bindings', fn($q) => $q->getBindings());

        CustomCaster::for(Carbon::class)
            ->only(['date'])
            ->virtual('date', fn($prod) => $prod->toDateTimeString());

    }

}
