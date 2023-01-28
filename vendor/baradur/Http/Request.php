<?php

Class Request
{
    private $get = array();
    private $post = array();
    private $files = array();

    private $session;

    public $route = null;
    private $method = null;
    private $uri = array();
    private $ip = null;
    private $headers = array();
    private $server = array();

    private $validated = array();

    public function generate($route)
    {
        $this->clear();

        $this->route = $route;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = env('HOME').$_SERVER['REQUEST_URI'];
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->headers = getallheaders();
        $this->server = $_SERVER;

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
                        $fileinfo = array();
                        $fileinfo['name'] = $val['name'][$i];
                        $fileinfo['type'] = $val['type'][$i];
                        $fileinfo['path'] = $val['tmp_name'][$i];
                        $fileinfo['error'] = $val['error'][$i];
                        $fileinfo['size'] = $val['size'][$i];

                        if ($fileinfo['name'] && $fileinfo['type'] && $fileinfo['error']==0)
                            $this->files[$fileinfo['name']] = new UploadedFile($fileinfo);
                    }
                }
                else
                {
                    $this->files[$key] = new UploadedFile($val);
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

    public function addHeaders($headers)
    {
        foreach ($headers as $key => $val) {
            $this->headers[$key] = $val;
        }
    }


    public function headers()
    {
        return $this->headers;
    }

    public function header($key)
    {
        return isset($this->headers[$key]) ? $this->headers[$key] : null;
    }

    public function hasHeader($key)
    {
        return isset($this->headers[$key]);
    }

    public function bearerToken()
    {
        $token = $this->header('Authorization');
        return str_replace('Bearer ', '', $token);
    }

    public function decodedPath()
    {
        return rawurldecode($this->path());
    }

    public function is($pattern)
    {
        $path = $this->decodedPath();
        
        foreach (func_get_args() as $pattern) {
            if (Str::is($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    public function clear()
    {
        $this->route = null;
        $this->uri = null;
        $this->get = array();
        $this->post = array();
        $this->files = array();
        $this->validated = array();
        $this->session = new RequestSession;
    }

    public function session($value = null, $default = null)
    {
        return $this->session->get($value, $default);
    }

    /** @return array */
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
        $validator = new Validator($this->all(), $arguments);

        $result = $validator->validate();

        $this->validated = $result->validated();

        if (!$result->passes())
        {
            $res = back()->withErrors($result->errors());
            CoreLoader::processResponse($res);
        }

        return true;
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
        return $this->route->named($name);
    }

    public function all()
    {
        $array = array();

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
    
    public function query($key=null)
    {
        if ($key)
            return $this->get[$key];

        $res = $this->get;
        unset($res['ruta']);
        return $res;
    }

    public function hasValidSignature()
    {
        return URL::hasValidSignature($this);
    }

    /**
     * Gets a file in array by key
     * 
     * @param string $key
     * @return UploadedFile|array
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

    public function ip()
    {
        return $this->ip;
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
