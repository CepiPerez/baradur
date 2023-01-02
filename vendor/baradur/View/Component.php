<?php

Class Component
{
    protected $_attributes = null;
    protected $_component = null;
    public $slot = null;
    public $attributes = null;

    public function render()
    {
        return view('components.'.$this->_component);
    }

    public function setComponent($component)
    {
        $this->_component = $component;
    }

    public function setAttributes($attributes)
    {
        $this->_attributes = $attributes;
    }

    public function attributes($attrs = null)
    {
        if (!isset($attrs))
            $attrs = $this->attributes;

        $res = array();
        foreach ($attrs as $key => $val)
            $res[] = $key.'="'.$val.'"';
        return implode(' ', $res);
    }

    public function merge($attributes)
    {
        $attributes = explode('=>', str_replace("'","", str_replace('"', '', $attributes)));

        $attrs = array($attributes[0] => $attributes[1]);

        $attrs[$attributes[0]] .= ' '.$this->attributes[$attributes[0]];

        return $this->attributes($attrs);
    }
    
    public function __get($name)
    {
        //echo "Called ".$name."::"; var_dump($this->$name); echo "<br>";
        return $this->$name;
    }

    /* function __call($name, $arguments)
    {
        echo "Call blade: ".$name;    
    }
 */
    public function __toString()
    {
        return $this->val;
    }


}