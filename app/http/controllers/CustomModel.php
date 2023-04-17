<?php

class CustomModel
{

    public function __call($name, $arguments)
    {
        // Note: value of $name is case sensitive.
        dd("Calling object method '$name' ");
    }

    public static function __callStatic($name, $arguments)
    {
        dd("HOLA");
        dd($name);
    }

    public static function instance() {
        $model = get_called_class();
        return new $model;
    }

    public static function select($test) {
        return self::instance();
    }

}