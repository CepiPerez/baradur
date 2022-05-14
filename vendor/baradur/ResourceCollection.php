<?php

class ResourceCollection
{
    public $collection;

    public function __construct($resource)
    {
        //parent::__construct($resource);
        //$this->resource = $this->collectResource($resource);

        $this->collection = $resource;

        $res = $this->toArray($resource);

        unset($this->collection);

        foreach ($res as $key => $val)
            $this->$key = $val;

    }

    public function toArray($data)
    {
        return (array)$this->collection;
    }

    public function __call($name, $arguments)
    {
        return $this->collection->$name();
    }
}