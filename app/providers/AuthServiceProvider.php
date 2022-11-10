<?php

class AuthServiceProvider extends ServiceProvider
{


    public function boot()
    {
        //Gate::define('admin-product', [ProductPolicy::class, 'adminProduct']);

        //Gate::define('test', function (User $user, $role) {
        //    return $user->roles->pluck('name')->contains($role);
        //});

    }

}
