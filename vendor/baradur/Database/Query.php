<?php

Class Query extends Model
{

    public static function where($column, $condition='', $value='')
    {
        return parent::instance('DB')->where($column, $condition, $value);
    }

    public static function relation($relation)
    {
        return parent::instance('DB')->_has($relation);
    }
    
}
