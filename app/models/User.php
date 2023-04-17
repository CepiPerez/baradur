<?php

class User extends Authenticatable
{
    use HasFeatures, Notifiable;

    protected $table = 'users';

    public $timestamps = false;

    protected $hidden = ['password', 'token', 'validation', 'token_timestamp'];

    public function isAdmin() : Attribute
    {
        return new Attribute(
            get: fn() => $this->roles->pluck('name')->contains('Administrador')
        );
    }

    public function getImageAttribute() 
    {
        if (Storage::exists('users/'.$this->username.'.jpg'))
            return asset('storage/users/' . $this->username . '.jpg');

        return asset('storage/users/default.png');
    }

    /* public function getEmailAttribute($value)
    {
        return Str::mask($value, '*', 1, '@');
    } */

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function grupos()
    {
        return $this->belongsToMany(Grupo::class);
    }
    
}

