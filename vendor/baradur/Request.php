<?php

Class Request
{
    public $_get = array();
    public $_post = array();
    public $_route = null;
    public $_url = null;

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
                        $errors[$key] = 'The field '.$key.' is required';
                    }
                }

                else if ($arg=='max') 
                {
                    if ( isset($this->_post[$key]) && is_string($this->_post[$key]) && strlen($this->_post[$key])<=$values) continue;
                    elseif ( isset($this->_post[$key]) && $this->_post[$key]<=$values) continue;
                    else
                    {
                        $pass = false;
                        $errors[$key] = $key.' is too long';
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
                        $errors[$key] = 'The '.$key.' has already been taken';
                    }
                }

                if ($stopOnFirstFail && !$pass) break;
    
            }

            if ($stopOnFirstFail && !$pass) break;


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
            
        return $array;
    }
    
    public function query()
    {
        return $this->_get;
    }

    public function input($key)
    {
        return isset($this->_post[$key]) ? $this->_post[$key] : null;
    }

    public function __get($key)
    {
        $res = isset($this->_post[$key]) ? $this->_post[$key] : null;
        if (!isset($res))
            $res = isset($this->_get[$key]) ? $this->_get[$key] : null;

        return $res;
    }

}
