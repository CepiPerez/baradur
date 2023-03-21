<?php

namespace Baradur;

class Attribute
{
    # Dummy class

    public $get;
    public $set;

    public function __construct($get = null, $set = null)
    {
        $this->get = $get;
        $this->set = $set;
    }

    public static function make(callable $get = null, callable $set = null)
    {
        return new Attribute($get, $set);
    }

}