<?php

class User extends Model
{
    public $timestamps = false;

    protected $table = 'users';

    protected $hidden = ['password', 'token', 'validation', 'token_timestamp'];

    /* public function isAdmin() : Attribute
    {
        return new Attribute(
            get: fn() => $this->roles->pluck('name')->contains('Administrador')
        );
    } */

    /* public function getImageAttribute() 
    {
        if (Storage::exists('users/'.$this->username.'.jpg'))
            return asset('storage/users/' . $this->username . '.jpg');

        return asset('storage/users/default.png');
    } */
    
    /* public function roles()
    {
        return $this->belongsToMany(Role::class);
    } */
    
}

