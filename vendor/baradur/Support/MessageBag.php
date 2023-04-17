<?php

Class MessageBag
{
    public $errorBag = array();

    public function __construct($errors = null)
    {
        if (isset($errors)) {
            foreach ($errors as $key => $val) {
                $this->errorBag[$key] = $val;
            }
        }
    }

    public function any()
    {
        return count($this->errorBag) > 0;
    } 

    public function all()
    {
        return $this->errorBag;
    }

    public function __get($name)
    {
        return isset($this->errorBag[$name]) ? $this->errorBag[$name] : null;
    }


}