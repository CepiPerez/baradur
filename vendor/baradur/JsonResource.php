<?php


class JsonResource
{

    public function __construct($resource)
    {
        foreach ($resource as $key => $val)
            $this->$key = $val;

        $request = $this;
        
        $res = $this->toArray($request);

        foreach ($this as $key => $val)
            unset($this->$key);

        foreach ($res as $key => $val)
            $this->$key = $val;
    }

    public function toArray($data)
    {
        return (array)$data;
    }
}