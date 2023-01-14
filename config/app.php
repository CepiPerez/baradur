<?php

return [

    'locale' => 'es',

    'fallback_locale' => 'en',

    'timezone' => 'America/Argentina/Buenos_Aires',
    
    'providers' => [
        app\providers\AppServiceProvider::class,
        app\providers\AuthServiceProvider::class,
        app\providers\EventServiceProvider::class,
        app\providers\RouteServiceProvider::class
    ]

];
