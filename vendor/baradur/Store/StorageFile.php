<?php

Class StorageFile
{

    public function __construct($file)
    {
        foreach($file as $key => $val)
        {
            $this->$key = $val;
        }

    }

    public function isValid()
    {
        return $this->error == 0;
    }

    public function path()
    {
        return $this->tmp_name;
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
        return Storage::put($path.'/'.$this->name, file_get_contents($this->tmp_name));
    }

    public function storeAs($path, $name)
    {
        $this->checkPath($path);
        return Storage::put($path.'/'.$name, file_get_contents($this->tmp_name));
    }

    private function checkPath($path)
    {
        if (!file_exists(Storage::$path . '/' . $path))
            @mkdir(Storage::$path . '/' . $path, 0777, true);
    }

}