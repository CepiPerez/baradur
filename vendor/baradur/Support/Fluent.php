<?php

class Fluent
{
    protected $attributes = array();

    public function __construct($attributes = array())
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    public function get($key, $default = null)
    {
        /* if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return $default; */

        return data_get($this->attributes, $key, $default);
    }

    public function value($key, $default = null)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return value($default);
    }

    public function scope($key, $default = null)
    {
        return new Fluent(
            (array) $this->get($key, $default)
        );
    }
    
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function collect($key = null)
    {
        return new Collection($this->get($key));
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public function __call($method, $parameters)
    {
        $this->attributes[$method] = count($parameters) > 0 ? reset($parameters) : true;

        return $this;
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
}