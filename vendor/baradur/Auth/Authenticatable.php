<?php

Class Authenticatable extends Model
{
    protected $rememberTokenName = 'token';

    public function hasVerifiedEmail()
    {
        return $this->attributes['validation']===null;
    }

    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    public function getAuthIdentifierName()
    {
        return $this->getKeyName();
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        $key = $this->getRememberTokenName();
        if (isset($key) || $key) {
            return (string) $this->$key;
        }
    }

    public function setRememberToken($value)
    {
        $key = $this->getRememberTokenName();
        if (isset($key) || $key) {
            $this->$key = $value;
        }
    }

    public function getRememberTokenName()
    {
        return $this->rememberTokenName;
    }

    public function can($function, $param)
    {
        return Gate::authorize($function, $param);
    }

}