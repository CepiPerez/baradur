<?php

Class Authenticatable extends Model
{
    public $timestamps = false;


    public function hasVerifiedEmail()
    {
        return $this->attributes['validation']===null;
    }

}