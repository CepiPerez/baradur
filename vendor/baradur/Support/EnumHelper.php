<?php

/* class EnumItem
{
    protected $name;
    protected $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

} */

class EnumHelper
{
    public $name = null;
    public $value = null;

    /** @return EnumHelper */
    public static function instance($parent)
    {
        return new $parent;
    }

    public function set($name)
    {
        foreach ($this as $key => $val) {
            if ($key!='name' && $key!='value') {
                if ($key==$name || $val==$name) {
                    $this->name = $key;
                    $this->value = $val;
                }
            }
        }

        return $this;
    }

    public function __get($name)
    {
        if ($name=='name' && isset($this->name)) {
            return $this->name;
        }
        
        if ($name=='value' && isset($this->value)) {
            return $this->value;
        }

        foreach ($this as $key => $val) {
            if ($key!='name' && $key!='value') {
                if ($key==$name || $val==$name) {
                    //dump("KEY", $key, $val);
                    $this->name = $key;
                    $this->value = $val;
                }
            }
        }
        
        return $this;
    }

    public function value()
    {
        return $this->value;
    }


}