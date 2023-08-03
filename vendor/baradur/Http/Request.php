<?php

Class Request
{
    //private $get = array();
    //private $post = array();
    private $files = array();

    private $session;

    public $route = null;
    private $method = null;
    private $host = null;
    private $uri = null;
    private $ip = null;
    private $query = null;
    private $input = null;
    private $headers = array();
    private $server = array();

    protected $validated = array();

    public function generate($route)
    {
        $this->clear();

        $this->route = $route;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = config('app.url').$_SERVER['REQUEST_URI'];
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->host = $_SERVER['HTTP_HOST'];
        //$this->input = $_REQUEST;
        //$this->query = $_GET;
        $this->headers = getallheaders();
        $this->server = $_SERVER;

        //unset($this->input['_token']);
        //unset($this->input['_method']);
        //unset($this->input['Baradur_token']);
        //unset($this->input['PHPSESSID']);

        # Adding GET values into Request
        if (isset($_GET)) {
            foreach ($_GET as $key => $val) {
                if ($key!='_method' && $key!='_token' && $key!='ruta') {
                    $this->query[$key] = $val;
                    $this->input[$key] = $val;
                }
            }
        }

        # Adding POST values into Request
        if (isset($_POST)) {
            foreach ($_POST as $key => $val) {
                if ($key!='_method' && $key!='_token' && $key!='ruta') {
                    $this->input[$key] = $val;
                }
            }
        }

        # Adding PUT values into Request
        if ($_SERVER['REQUEST_METHOD']=='PUT' || $_SERVER['REQUEST_METHOD']=='PATCH') {
            parse_str(file_get_contents("php://input"), $data);

            foreach ($data as $key => $val) {
                if ($key!='_method' && $key!='_token' && $key!='ruta') {
                    $this->input[$key] = $val;
                }
            }
        }

        # Adding files into Request
        if (isset($_FILES)) {

            foreach ($_FILES as $key => $val) {

                if (is_array($val['name'])) {
                    for ($i=0; $i<count($val['name']); ++$i) {
                        $fileinfo = array();
                        $fileinfo['name'] = $val['name'][$i];
                        $fileinfo['type'] = $val['type'][$i];
                        $fileinfo['path'] = $val['tmp_name'][$i];
                        $fileinfo['error'] = $val['error'][$i];
                        $fileinfo['size'] = $val['size'][$i];

                        if ($fileinfo['name'] && $fileinfo['type'] && $fileinfo['error']==0) {
                            $this->files[$fileinfo['name']] = new UploadedFile($fileinfo);
                        }
                    }
                } else {
                    //$this->files[$key] = new UploadedFile($val);
                    $fileinfo = array();
                    $fileinfo['name'] = $val['name'];
                    $fileinfo['type'] = $val['type'];
                    $fileinfo['path'] = $val['tmp_name'];
                    $fileinfo['error'] = $val['error'];
                    $fileinfo['size'] = $val['size'];

                    if ($fileinfo['name'] && $fileinfo['type'] && $fileinfo['error']==0) {
                        $this->files[$key] = new UploadedFile($fileinfo);
                    }
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

    public function accepts($content_type)
    {
        $acceptable = $this->getAcceptableContentTypes();

        return Str::contains($acceptable, array($content_type));
    }

    public function getAcceptableContentTypes()
    {
        $acceptable = array(); 

        foreach ($this->headers as $key => $val) {
            if ($key=='Accept') {
                $acceptable[] = $val;
            }
        } 

        return implode(', ', $acceptable);
    }

    public function expectsJson()
    {
        return ($this->ajax() && ! $this->pjax() && $this->acceptsAnyContentType()) || $this->wantsJson();
    }

    public function wantsJson()
    {
        //return $this->header('Accept') == 'application/json';
        $acceptable = $this->getAcceptableContentTypes();

        return Str::contains(strtolower($acceptable), array('/json', '+json'));
    }

    public function acceptsAnyContentType()
    {
        $acceptable = $this->getAcceptableContentTypes();

        return strlen($acceptable)===0 || Str::contains($acceptable, array('*/*', '*'));
    }

    public function ajax()
    {
        return $this->isXmlHttpRequest();
    }

    public function pjax()
    {
        return isset($this->headers['X-PJAX']);
    }

    public function isXmlHttpRequest()
    {
        return isset($this->headers['X-Requested-With']) && $this->headers['X-Requested-With']=='XMLHttpRequest';
    }

    public function bearerToken()
    {
        $token = $this->header('Authorization');
        
        if (!$token) {
            return null;
        }
        
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
        $this->method = null;
        $this->ip = null;
        $this->host = null;
        $this->headers = getallheaders();
        $this->server = $_SERVER;
        $this->route = null;
        $this->uri = null;
        $this->query = array();
        $this->input = array();
        $this->files = array();
        $this->validated = array();
        $this->session = app('session');;
    }

    public function session($value = null, $default = null)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $this->session->put($key, $val);
            }

            return;
        }

        if ($value) {
            return $this->session->get($value, $default);
        }

        return $this->session;
    }
    
    public function attributes()
    {
        return array();
    }

    public function messages()
    {
        return array();
    }

    /** @return array */
    public function validated()
    {
        return $this->validated;
    }

    public function validate($arguments)
    {
        $validator = new Validator($this->all(), $arguments, $this->messages(), $this->attributes());

        $result = $validator->validate();

        $this->validated = $result->validated();

        if (!$result->passes()) {
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

    public function fullUrlWithQuery($query = array())
    {
        parse_str($this->query, $result);
        
        foreach ($query as $key => $value) {
            $result[$key] = $value;
        }

        $res = $this->url();

        if (count($result)> 0) {
            $res .= '?' . http_build_query($result);
        }

        return $res;
    }

    public function fullUrlWithoutQuery($query = array())
    {
        parse_str($this->query, $result);
        
        foreach ($query as $key) {
            unset($result[$key]);
        }

        $res = $this->url();
        if (count($result)> 0) {
            $res .= '?' . http_build_query($result);
        }

        return $res;
    }

    public function method()
    {
        return $this->method;
    }

    public function host()
    {
        return $this->host;
    }

    public function route($param = null)
    {
        if (!$param) {
            return $this->route;
        }

        return $this->route->url_parametros[$param];
    }

    public function routeIs($name)
    {
        return $this->route->named($name);
    }

    public function isJson()
    {
        if (!$this->hasHeader('Content-Type')) {
            return false;
        }

        $header = $this->header('Content-Type');
        $header = is_array($header) ? $header : array($header);

        foreach ($header as $val) {
            if (str_contains($val, 'json')) {
                return true;
            }
        }

        return false;
    }

    public function keys()
    {
        return array_merge(array_keys($this->input), array_keys($this->files));
    }

    public function all()
    {
        $array = array();

        foreach ($this->input as $key => $val) {
            $array[$key] = $val;
        }

        foreach ($this->files as $key => $val) {
            $array[$key] = $val;
        }

        return $array;
    }

    public function only()
    {
        $keys = func_get_args();

        $array = array();

        foreach ($this->all() as $key => $val) {
            if (in_array($key, $keys)) {
                $array[$key] = $val;
            }
        }
        
        return $array;
    }

    public function except()
    {
        $keys = func_get_args();

        $array = array();

        foreach ($this->all() as $key => $val) {
            if (!in_array($key, $keys)) {
                $array[$key] = $val;
            }
        }
        
        return $array;
    }
    
    public function query($key=null, $default=null)
    {
        if ($key) {
            return array_key_exists($key, $this->query) 
                ? $this->query[$key] 
                : $default;
        }

        $res = $this->query;
        unset($res['ruta']);

        return $res;
    }

    public function missing($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        return ! $this->has($keys);
    }

    public function exists($key)
    {
        return $this->has($key);
    }

    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        $input = $this->all();

        foreach ($keys as $value) {
            if (! Arr::has($input, $value)) {
                return false;
            }
        }

        return true;
    }

    public function hasAny($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $input = $this->all();

        return Arr::hasAny($input, $keys);
    }

    public function filled($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if ($this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    public function isNotFilled($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if (! $this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    public function anyFilled($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if ($this->filled($key)) {
                return true;
            }
        }

        return false;
    }

    protected function isEmptyString($key)
    {
        $value = $this->input($key);

        return !is_bool($value) && !is_array($value) && trim((string) $value)==='';
    }


    public function user()
    {
        return Auth::user();
    }

    public function hasValidSignature($absolute = true)
    {
        return URL::hasValidSignature($this, $absolute);
    }

    public function hasValidRelativeSignature()
    {
        return URL::hasValidSignature($this, false);
    }

    public function hasValidSignatureWhileIgnoring($ignoreQuery = array(), $absolute = true)
    {
        return URL::hasValidSignature($this, $absolute, $ignoreQuery);
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

    public function input($key=null, $default=null)
    {
        $array = $this->input; //array_merge($this->get, $this->post, $this->files);

        if (!$key) {
            return $array;
        }

        return array_key_exists($key, $array) ? $array[$key] : $default;
    }

    public function get($key, $default = null)
    {
        $array = $this->all(); //array_merge($this->get, $this->post, $this->files);

        return array_key_exists($key, $array) ? $array[$key] : $default;
    }

    public function collect($key)
    {
        return collect($this->input($key, array()));
    }

    public function ip()
    {
        return $this->ip;
    }

    public function serialize()
    {
        return serialize((array)$this->all());
    }

    public function replace($parameters = array())
    {
        $this->validated = $parameters;
    }

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->input)) {
            $this->input[$name] = $value;
        } elseif (array_key_exists($name, $this->query)) {
            $this->query[$name] = $value;
        } elseif (array_key_exists($name, $this->files)) {
            $this->files[$name] = $value;
        }
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->input)) {
            return $this->input[$key];
        }

        if (array_key_exists($key, $this->query)) {
            return $this->query[$key];
        }

        if (array_key_exists($key, $this->files)) {
            return $this->files[$key];
        }

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
        $value = $this->input($name);

        return isset($value)? Str::of($value)->toBoolean() : null;
    }

    /** @return Stringable|null */
    public function string($key, $default = null)
    {
        return str($this->input($key, $default));
    }

    /** @return Stringable|null */
    public function str($name)
    {
        return $this->string($name);
    }

    /** @return Carbon|null */
    public function date($name)
    {
        $value = $this->input($name);
        
        return $value? Carbon::parse($value) : null;
    }

    /** @return int|null */
    public function integer($key, $default = 0)
    {
        return intval($this->input($key, $default));
    }

    /** @return float|null */
    public function float($key, $default = 0.0)
    {
        return floatval($this->input($key, $default));
    }
}
