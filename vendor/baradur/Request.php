<?php

Class Request
{
    private $get = array();
    private $post = array();
    private $files = array();

    public $route = null;
    private $method = null;
    private $uri = array();

    private $validated = array();

    public function generate($route)
    {
        $this->clear();

        $this->route = $route;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = env('HOME').$_SERVER['REQUEST_URI'];

        # Adding GET values into Request
        if (isset($_GET))
        {
            foreach ($_GET as $key => $val)
            {
                if ($key!='_method' && $key!='csrf')
                    $this->get[$key] = $val;
            }
        }

        # Adding POST values into Request
        if (isset($_POST))
        {
            foreach ($_POST as $key => $val)
            {
                if ($key!='_method' && $key!='csrf')
                    $this->post[$key] = $val;
            }
        }

        # Adding PUT values into Request
        if ($_SERVER['REQUEST_METHOD']=='PUT')
        {
            parse_str(file_get_contents("php://input"), $data);
            foreach ($data as $key => $val)
            {
                if ($key!='_method' && $key!='csrf')
                    $this->post[$key] = $val;
            }
        }

        # Adding files into Request
        if (isset($_FILES))
        {
            foreach ($_FILES as $key => $val)
            {
                if (is_array($val['name']))
                {
                    for ($i=0; $i<count($val['name']); ++$i)
                    {
                        $new = new stdClass;
                        $new->name = $val['name'][$i];
                        $new->type = $val['type'][$i];
                        $new->tmp_name = $val['tmp_name'][$i];
                        $new->error = $val['error'][$i];
                        $new->size = $val['size'][$i];

                        if ($new->name && $new->type && $new->error==0)
                            $this->files[$key][] = new StorageFile($new);
                    }
                }
                else
                {
                    $this->files[$key] = new StorageFile($val);
                }
            }
        }

    }

    public function setUri($val)
    {
        $this->uri = $$val;
    }

    public function setMethod($val)
    {
        $this->method = $$val;
    }

    public function setRoute($val)
    {
        $this->route = $$val;
    }

    public function clear()
    {
        $this->route = null;
        $this->uri = null;
        $this->get = array();
        $this->post = array();
        $this->files = array();
        $this->validated = array();
    }

    public function validated()
    {
        return $this->validated;
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
                    if ( !isset($this->post[$key]) || (is_string($this->post[$key]) && strlen($this->post[$key])==0) )
                    {
                        $pass = false;
                        $errors[$key] = __("validation.required", array('attribute' => $key));
                    }
                }

                else if ($arg=='max') 
                {
                    if ( isset($this->post[$key]) && is_string($this->post[$key]) && strlen($this->post[$key])<=$values) continue;
                    elseif ( isset($this->post[$key]) && $this->post[$key]<=$values) continue;
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

                    $value = $this->post[$key];

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

            $this->validated[$key] = $this->post[$key];

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
        return $this->route->url;
    }

    public function url()
    {
        return rtrim(preg_replace('/\?.*/', '', $this->uri), '/');
    }

    public function fullUrl()
    {
        return $this->uri;
    }


    public function routeIs($name)
    {
        return $this->route->name == $name;
    }

    public function all()
    {
        $array = array();

        //dd($this->method);

        if($this->method=='GET')
        {
            foreach ($this->get as $key => $val)
                $array[$key] = $val;
        }

        elseif($this->method=='POST' || $this->method=='PUT')
        {
            foreach ($this->post as $key => $val)
                $array[$key] = $val;

            foreach ($this->files as $key => $val)
                $array[$key] = $val;
        }

        return $array;
    }

    public function only()
    {
        $array = array();
        foreach ($this->post as $key => $val)
        {
            if (in_array($key, func_get_args()))
                $array[$key] = $val;
        }

        foreach ($this->files as $key => $val)
        {
            if (in_array($key, func_get_args()))
                $array[$key] = $val;
        }
            
        return $array;
    }
    
    public function query()
    {
        return $this->get;
    }

    /**
     * Gets a file in array by key
     * 
     * @param string $key
     * @return StorageFile|array
     */
    public function file($key)
    {
        return $this->files[$key];
    }

    public function input($key)
    {
        return isset($this->post[$key]) ? $this->post[$key] : null;
    }

    public function get($key)
    {
        return isset($this->get[$key]) ? $this->get[$key] : null;
    }

    public function serialize()
    {
        return serialize((array)$this);
    }

    public function __get($key)
    {
        if (isset($this->post[$key]))
            return $this->post[$key];

        if (isset($this->get[$key]))
            return $this->get[$key];

        if (isset($this->files[$key]))
            return $this->files[$key];

        return null;
    }

    public function hasFile($name)
    {
        return isset($this->files[$name]) && !empty($this->files[$name]);
    }


}
