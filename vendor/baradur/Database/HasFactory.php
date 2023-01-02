<?php

trait HasFactory
{
    protected $hasFactory = true;

    /** @return Factory */
    public static function factory()
    {
        $class = new self;
        $factory = get_class($class).'Factory';

        return new $factory;
    }

}