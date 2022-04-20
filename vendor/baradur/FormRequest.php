<?php

Class FormRequest extends Request
{
    public $route;


    public function __construct($req)
    {
        foreach ($req as $key => $val)
            $this->$key = $val;

    }

    
}