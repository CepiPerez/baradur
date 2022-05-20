<?php

Class Config extends ServiceProvider
{

    /* protected $observers = [
        'Product' => 'ProductObserver',
    ]; */


    protected $routeMiddleware = [
        'auth' => 'Auth',
    ];
    
    public function boot()
    {
        # Set language for translations
        // App::setLocale('es');
        
        
        # Routes localization
        /* Route::resourceVerbs(array(
            'index' => 'inicio',
            'create' => 'crear',
            'store' => 'guardar',
            'show' => 'mostrar',
            'edit' => 'editar',
            'update' => 'modificar',
            'destroy' => 'eliminar',
        )); */
        
        
        # Policies
        //Gate::define('admin-product', 'ProductPolicy@adminProduct');
        
        # Observers
        //Product::observer('ProductOberver');

    }
    
}
