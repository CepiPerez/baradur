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

    protected $stopOnFirstFailure = false;


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
        //$result = Validator::validate($this->all(), $arguments);
        $validator = new Validator($this->all(), $arguments);

        if ($this->stopOnFirstFailure)
        {
            $validator->stopOnFirstFailure();
        }


        if ($validator->fails())
        {
            back()->withErrors($validator->errors())->showFinalResult();
            exit();
        } 
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
