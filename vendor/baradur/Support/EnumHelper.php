<?php

class EnumItem
{
    protected $name;
    protected $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /* public function __toString()
    {
        return $this->value;
    } */

    public function __get($name)
    {
        return $this->$name;
    }

}

class EnumHelper
{
    public static function instance($parent)
    {
        return new $parent;
    }

    public function __get($name)
    {
        return new EnumItem($name, $this->$name);
    }

    public function cases()
    {
        $arr = array();
        foreach ($this as $k => $v)
        {
            $arr[] = get_class($this)."::".$k;
        }
        return $arr;
    }

    
}