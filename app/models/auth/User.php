<?php

class User extends Model
{
    protected $table = 'users';

    public function getIsAdminAttribute()
    {
        return $this->roles->pluck('name')->contains('Administrador');
    }

    public function getImageAttribute() 
    {
        if (Storage::exists('users/'.$this->username.'.jpg'))
            return asset('storage/users/' . $this->username . '.jpg');

        return asset('storage/users/nopicture.png');
    }

    
    public function roles()
    {
        return $this->belongsToMany('Role');
    }
    
}

