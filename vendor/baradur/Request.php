<?php

Class Request
{
    public $_get = array();
    public $_post = array();
    public $_route = null;
    public $_url = null;
    public $_files = array();

    private $_validated = array();

    public function validated()
    {
        return $this->_validated;
    }

    public function validate($arguments)
    {
        $pass = true;
        $stopOnFirstFail = false;
        $errors = array();

        foreach ($arguments as $key => $argument)
        {
            $validations = explode('|', $argument);
    
            foreach ($validations as $validation)
            {

                list($arg, $values) = explode(':', $validation);

                if ($arg=='bail') 
                {
                    $stopOnFirstFail = true;
                }

                else if ($arg=='required') 
                {
                    if ( !isset($this->_post[$key]) || strlen($this->_post[$key])==0 )
                    {
                        $pass = false;
                        $errors[$key] = __("validation.required", array('attribute' => $key));
                    }
                }

                else if ($arg=='max') 
                {
                    if ( isset($this->_post[$key]) && is_string($this->_post[$key]) && strlen($this->_post[$key])<=$values) continue;
                    elseif ( isset($this->_post[$key]) && $this->_post[$key]<=$values) continue;
                    else
                    {
                        $pass = false;
                        $errors[$key] = __("validation.max.string", array('attribute' => $key, 'max' => $values));
                    }
                }

                else if ($arg=='unique') 
                {
                    list($table, $column, $ignore) = explode(',', $values);
                    if (!$column) $column = $key;

                    $value = $this->_post[$key];

                    $val = DB::table($table)->where($column, $value)->first();
                    
                    if ($val && $val->$column!=$ignore)
                    {
                        $pass = false;
                        $errors[$key] = __("validation.unique", array('attribute' => $key));
                    }
                }

                if ($stopOnFirstFail && !$pass) break;
    
            }

            if ($stopOnFirstFail && !$pass) break;

            $this->_validated[$key] = $this->_post[$key];

        }

        if (!$pass)
        {
            back()->withErrors($errors)->showFinalResult();
            exit();
        }

        return $pass;
    }

    public function path()
    {
        return $this->_route->url;
    }

    public function url()
    {
        return rtrim(preg_replace('/\?.*/', '', $this->_uri), '/');
    }

    public function fullUrl()
    {
        return $this->_uri;
    }


    public function routeIs($name)
    {
        return $this->_route->name == $name;
    }

    public function all()
    {
        $array = array();
        foreach ($this->_post as $key => $val)
            $array[$key] = $val;

        foreach ($this->_files as $key => $val)
            $array[$key] = $val;

            
        return $array;
    }
    
    public function query()
    {
        return $this->_get;
    }

    public function file($name)
    {
        return $this->_files[$name];
    }

    public function input($key)
    {
        return isset($this->_post[$key]) ? $this->_post[$key] : null;
    }

    public function serialize()
    {
        return serialize((array)$this);
    }

    public function __get($key)
    {
        $res = isset($this->_post[$key]) ? $this->_post[$key] : null;

        if (!isset($res))
            $res = isset($this->_get[$key]) ? $this->_get[$key] : null;

        if (!isset($res))
            $res = isset($this->_files[$key]) ? $this->_files[$key] : null;

        return $res;
    }

    public function addFile($name, $data)
    {
        $newfile = new StorageFile($data);
        $this->_files[$name] = $newfile;
    }

    public function hasFile($name)
    {
        return isset($this->_files[$name]);
    }


}
