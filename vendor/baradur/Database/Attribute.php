<?php

Class Attribute
{
    # Dummy class

    public $get;
    public $set;

    public function __construct(callable $get = null, callable $set = null)
    {
        $this->get = $get;
        $this->set = $set;
    }
}