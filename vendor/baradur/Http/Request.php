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

    private function setPost($post)
    {
        $this->post = $post;
    }

    public function validate($arguments)
    {

        $pass = true;
        $stopOnFirstFail = false;
        $errors = array();

        foreach ($arguments as $key => $argument)
        {
            //echo "checking $key :: ".$this->post[$key]." ::  $argument<br>";
            $validations = explode('|', $argument);
            $ok = true;

            $canbenull = in_array('nullable', $validations);
            unset($validations['nullable']);
    
            foreach ($validations as $validation)
            {
                list($arg, $values) = explode(':', $validation);

                if ($arg=='bail') 
                {
                    $stopOnFirstFail = true;
                }

                if ($arg=='required') 
                {
                    if ( !array_key_exists($key, $this->post) || (is_string($this->post[$key]) && strlen($this->post[$key])==0) )
                    {
                        $ok = false;
                        $errors[$key] = __("validation.required", array('attribute' => $key));
                    }
                }

                else if ($arg=='present') 
                {
                    if ( !array_key_exists($key, $this->post) || (!isset($this->post[$key]) && !$canbenull) )
                    {
                        $ok = false;
                        $errors[$key] = __("validation.present", array('attribute' => $key));
                    }
                }

                else if ($arg=='string') 
                {
                    if ( !array_key_exists($key, $this->post) || (!is_string($this->post[$key]) && !$canbenull) )
                    {
                        $ok = false;
                        $errors[$key] = __("validation.string", array('attribute' => $key));
                    }
                }

                else if ($arg=='numeric') 
                {
                    if ( !array_key_exists($key, $this->post) || (!is_numeric($this->post[$key]) && !$canbenull) )
                    {
                        $ok = false;
                        $errors[$key] = __("validation.email", array('attribute' => $key));
                    }
                }

                else if ($arg=='min') 
                {
                    $values = intval($values);
                    if (array_key_exists($key, $this->post))
                    {
                        if (is_string($this->post[$key]))
                        {
                            if (strlen($this->post[$key]) < $values)
                            {
                                $ok = false;
                                $errors[$key] = __("validation.min.string", array('attribute' => $key, 'min' => $values));
                            }
                        }
                        elseif (is_numeric($this->post[$key]))
                        {
                            if ($this->post[$key] < $values)
                            {
                                $ok = false;
                                $errors[$key] = __("validation.min.numeric", array('attribute' => $key, 'min' => $values));
                            }
                        }
                        elseif (is_array($this->post[$key]))
                        {
                            if (count($this->post[$key]) < $values)
                            {
                                $ok = false;
                                $errors[$key] = __("validation.min.array", array('attribute' => $key, 'min' => $values));
                            }
                        }
                        elseif (is_file($this->post[$key]))
                        {
                            if (round(filesize($this->post[$key])/1024) < $values)
                            {
                                $ok = false;
                                $errors[$key] = __("validation.min.file", array('attribute' => $key, 'min' => $values));
                            }
                        }
                    }
                }

                else if ($arg=='max') 
                {
                    $values = intval($values);
                    if (array_key_exists($key, $this->post))
                    {
                        if (is_string($this->post[$key]))
                        {
                            if (strlen($this->post[$key]) > $values)
                            {
                                $ok = false;
                                $errors[$key] = __("validation.max.string", array('attribute' => $key, 'max' => $values));
                            }
                        }
                        elseif (is_numeric($this->post[$key]))
                        {
                            if ($this->post[$key] > $values)
                            {
                                $ok = false;
                                $errors[$key] = __("validation.max.numeric", array('attribute' => $key, 'max' => $values));
                            }
                        }
                        elseif (is_array($this->post[$key]))
                        {
                            if (count($this->post[$key]) > $values)
                            {
                                $ok = false;
                                $errors[$key] = __("validation.max.array", array('attribute' => $key, 'max' => $values));
                            }
                        }
                        elseif (is_file($this->post[$key]))
                        {
                            if (round(filesize($this->post[$key])/1024) > $values)
                            {
                                $ok = false;
                                $errors[$key] = __("validation.max.file", array('attribute' => $key, 'max' => $values));
                            }
                        }
                    }
                }

                else if ($arg=='unique') 
                {
                    if (!array_key_exists($key, $this->post))
                    {
                        list($table, $column, $ignore) = explode(',', $values);
                        if (!$column) $column = $key;

                        $val = DB::table($table)->where($column, $this->post[$key])->first();

                        if ($val && $val->$column!=$ignore)
                        {
                            $ok = false;
                            $errors[$key] = __("validation.unique", array('attribute' => $key));
                        }
                    }
                }

                else if ($arg=='boolean') 
                {
                    if (!array_key_exists($key, $this->post) || !in_array($this->post[$key], array(true, false, 1, 0, "1", "0"), true))
                    {
                        $ok = false;
                        $errors[$key] = __("validation.boolean", array('attribute' => $key));
                    }
                }

                else if ($arg=='accepted') 
                {
                    if (!array_key_exists($key, $this->post) || !in_array($this->post[$key], array("yes", "on", 1, true), true))
                    {
                        $ok = false;
                        $errors[$key] = __("validation.accepted", array('attribute' => $key));
                    }
                }

                else if ($arg=='declined') 
                {
                    if (!array_key_exists($key, $this->post) || !in_array($this->post[$key], array("no", "off", 0, false), true))
                    {
                        $ok = false;
                        $errors[$key] = __("validation.declined", array('attribute' => $key));
                    }
                }

                else if ($arg=='email') 
                {
                    $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
                    if (!array_key_exists($key, $this->post) || !preg_match($regex, $this->post[$key]))
                    {
                        $ok = false;
                        $errors[$key] = __("validation.email", array('attribute' => $key));
                    }
                }
                

                if (!$ok)
                {
                    $pass = false;

                    if ($stopOnFirstFail)
                        break;
                }
            }
            
            if ($stopOnFirstFail && !$pass) break;

            if ($ok)
                $this->validated[$key] = $this->post[$key];

        }

        if (!$pass)
        {
            back()->withErrors($errors)->showFinalResult();
            exit();
        }

        //dump($ok); dump($pass);

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

    public function __set($name, $value)
    {
        $this->post[$name] = $value;
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

    /** @return bool */
    public function hasFile($name)
    {
        return isset($this->files[$name]) && !empty($this->files[$name]);
    }

    /** @return bool|null */
    public function boolean($name)
    {
        $value = $this->__get($name);

        return isset($value)? Str::of($value)->toBoolean() : null;
    }

    /** @return string|null */
    public function string($name)
    {
        $value = $this->__get($name);

        return $value? Str::of($value)->__toString() : null;
    }

    /** @return string|null */
    public function str($name)
    {
        return $this->string($name);
    }

    /** @return Carbon|null */
    public function date($name)
    {
        $value = $this->__get($name);

        return $value? Carbon::parse($value) : null;
    }
}
