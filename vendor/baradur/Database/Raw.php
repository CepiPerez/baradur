<?php

class Raw 
{
    public $query;
    public $bindings;

    public function __construct($query, $bindings=array())
    {
        $this->query = $query;
        $this->bindings = $bindings;
    }

}