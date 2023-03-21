<?php

trait HasFactory
{
    protected $_hasFactory = true;

    /** @return Factory */
    public static function factory($count=null)
    {
        $class = new self;
        $factory = get_class($class).'Factory';

        $factory = new $factory;

        if ($count) {
            $factory->count = $count;
        }

        return $factory;
    }

}