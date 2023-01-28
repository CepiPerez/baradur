<?php

Class UploadedFile
{
    public $name;
    public $type;
    public $path;
    public $error;
    public $size; 

    public function __construct($fileinfo)
    {
        $this->name = $fileinfo['name'];
        $this->type = $fileinfo['type'];
        $this->path = $fileinfo['path'];
        $this->error = $fileinfo['error'];
        $this->size = $fileinfo['size'];
    }

    public function isValid()
    {
        return $this->error == 0;
    }

    public function path()
    {
        return $this->path;
    }

    public function extension()
    {
        $res = pathinfo($this->name);
        return $res['extension'];
    }

    public function name()
    {
        $res = pathinfo($this->name);
        return $res['basename'];
    }

    public function store($path)
    {
        $this->checkPath($path);
        return Storage::put($path.'/'.$this->name, file_get_contents($this->path));
        
    }

    public function storeAs($path, $name)
    {
        $this->checkPath($path);
        return Storage::put($path.'/'.$name, file_get_contents($this->path));
    }

    private function checkPath($path)
    {
        if (!file_exists(Storage::$path . '/' . $path))
            @mkdir(Storage::$path . '/' . $path, 0777, true);
    }

}