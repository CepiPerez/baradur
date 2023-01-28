<?php

class File
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getRealPath()
    {
        return $this->path;
    }

    public function hashName()
    {
        $res = pathinfo($this->path);

        return hash('md5', $this->path) . '.' . $res['extension'];
    }


}