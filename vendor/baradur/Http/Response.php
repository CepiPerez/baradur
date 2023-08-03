<?php

class Response
{
    protected static $_macros = array();

    protected $response;
    protected $decoded;
    public $cookies;
    public $transferStats;

    public $error_code;
    public $error_string;
    public $info;

    public $filename;
    public $inline;
    public $custom_name;

    public function __construct($response, $filename=null, $inline=false)
    {
        $this->response = $response;
        $this->filename = $filename;
        $this->inline = $inline;
    }

    public function body()
    {
        return (string) $this->response->getBody();
    }

    public static function json($data=null, $code=null)
    {
        return response($data, $code);

        /* if ($data) {
            $this->response->setBody(json_encode($data));
        }

        if ($code) {
            $this->response->setStatusCode($code);
        }
        
        return $this; */
    }

    public function with($key, $value)
    {
        $_SESSION['messages'][$key] = $value;
        return $this;
    }

    public function withErrors($errors)
    {
        foreach ($errors as $key => $val) {
            $_SESSION['errors'][$key] = $val;
        }

        return $this;
    }

    public function route()
    {
        $params = func_get_args();
        $this->response->setHeader('Location', Route::getRoute($params));

        return $this;
    }

    public function addHeaders($headers)
    {
        $headers = is_array($headers) ? $headers : array($headers);

        foreach ($headers as $key => $value) {
            $this->response->setHeader($key, $value);
        }

        return $this;
    }

    public function withHeader($key, $value)
    {
        $this->response->setHeader($key, $value);
        
        return $this;
    }


    public function file($path, $headers=array())
    {
        $this->filename = $path;
        $this->inline = true;
        
        foreach ($headers as $key => $val){
            $this->response->setHeader($key, $val);
        }

        $this->response->setHeader('Content-Type', mime_type($path));
        $this->response->setHeader('Content-disposition', 'inline; filename="'.basename($path).'"');

        return $this;
    }

    public function download($path, $name=null, $headers=array())
    {
        $this->filename = $path;
        $this->inline = true;
        $this->custom_name = $name? $name : basename($path);

        foreach ($headers as $key => $val) {
            $this->response->setHeader($key, $val);
        }

        $this->response->setHeader('Content-Type', mime_type($path));
        $this->response->setHeader('Content-disposition', 'download; filename="'.$this->custom_name.'"');

        return $this;
    }

    public function object()
    {
        return json_decode($this->body(), false);
    }

    public function collect($key = null)
    {
        return collect($this->json($key));
    }

    public function header($header)
    {
        return $this->response->getHeaderLine($header);
    }

    public function headers()
    {
        return $this->response->getHeaders();
    }

    public function protocol()
    {
        return (int) $this->response->getProtocolVersion();
    }

    public function status()
    {
        return (int) $this->response->getStatusCode();
    }

    public function reason()
    {
        return $this->response->getReasonPhrase();
    }

    public function effectiveUri()
    {
        return $this->info['url'];
    }

    public function successful()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    public function ok()
    {
        return $this->status() === 200;
    }

    public function redirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    public function unauthorized()
    {
        return $this->status() === 401;
    }

    public function forbidden()
    {
        return $this->status() === 403;
    }

    /* public function failed()
    {
        return $this->error_code || $this->error_string;
    } */

    public function clientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    public function serverError()
    {
        return $this->status() >= 500;
    }

    public function notFound()
    {
        return $this->status() === 404;
    }

    /* public function onError(callable $callback)
    {
        if ($this->failed()) {
            $callback($this);
        }

        return $this;
    } */

    public function cookies()
    {
        return $this->cookies;
    }

    /* public function handlerStats()
    {
        return $this->transferStats?->getHandlerStats() ?? [];
    } */

    /* public function close()
    {
        $this->response->getBody()->close();

        return $this;
    } */

    public function toPsrResponse()
    {
        return $this->response->getRawResponse();
    }


    /* public function offsetExists($offset)
    {
        $json = $this->json();
        return isset($json[$offset]);
    } */

    /* public function offsetGet($offset)
    {
        $json = $this->json();
        return $json[$offset];
    } */

    /* public function offsetSet($offset, $value): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    } */

    /* public function offsetUnset($offset): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    } */
    
    public function failed()
    {
        return $this->serverError() || $this->clientError() || $this->error_code || $this->error_string;
    }

    public function toException()
    {
        //if ($this->failed()) {
            return new RequestException($this);
        //}
    }

    public function __throw()
    {
        $callback = func_get_args();
        $callback = isset($callback[0]) ? $callback[0] : null;

        if ($this->failed()) {
            //throw tap($this->toException(), function ($exception) use ($callback) {
            //    if ($callback && is_closure($callback)) {
            //        //$callback($this, $exception);
            //        list($class, $method) = getCallbackFromString($callback);
            //        call_user_func_array(array($class, $method), array($this, $exception));
            //    }
            //});

            $exception = $this->toException();

            if ($callback && is_closure($callback)) {
                list($class, $method) = getCallbackFromString($callback);
                call_user_func_array(array($class, $method), array($this, $exception));
            }

            throw $exception;
        }

        return $this;
    }

    public function throwIf($condition)
    {
        $callback = func_get_args();
        $callback = isset($callback[1]) ? $callback[1] : null;

        return value($condition, $this) ? $this->__throw($callback) : $this;
    }

    public function throwIfStatus($statusCode)
    {
        if (is_closure($statusCode)) { //&& $statusCode($this->status(), $this)) {

            list($class, $method) = getCallbackFromString($statusCode);
            $result = call_user_func_array(array($class, $method), array($this->status(), $this));

            if ($result) {
                return $this->__throw();
            }
        }

        return $this->status() === $statusCode ? $this->__throw() : $this;
    }

    public function throwUnlessStatus($statusCode)
    {
        //if (is_callable($statusCode) &&
        //    ! $statusCode($this->status(), $this)) {
        //    return $this->throw();
        //}

        if (is_closure($statusCode)) { //&& $statusCode($this->status(), $this)) {

            list($class, $method) = getCallbackFromString($statusCode);
            $result = call_user_func_array(array($class, $method), array($this->status(), $this));

            if ($result) {
                return $this->__throw();
            }
        }

        return $this->status() === $statusCode ? $this : $this->__throw();
    }

    public function throwIfClientError()
    {
        return $this->clientError() ? $this->__throw() : $this;
    }

    public function throwIfServerError()
    {
        return $this->serverError() ? $this->__throw() : $this;
    }

    public function __toString()
    {
        return $this->body();
    }

    public function view($template, $parameters, $code=200)
    {
        $result = loadView($template, $parameters);

        $this->response->setBody($result);
        $this->response->setStatusCode($code);

        return $this;
    }

    /* public function __call($method, $parameters)
    {
        return static::hasMacro($method)
                    ? $this->macroCall($method, $parameters)
                    : $this->response->{$method}(...$parameters);
    } */

    public static function macro($name, $function)
    {
        self::$_macros[$name] = $function;
    }

    public static function hasMacro($name)
    {
        return array_key_exists($name, self::$_macros);
    }

    public static function getMacros()
    {
        return self::$_macros;
    }

    public function __call($method, $parameters)
    {
        global $_class_list;

        if (isset(self::$_macros[$method])) {

            $class = self::$_macros[$method];
            $params = array();

            if (is_closure($class)) {
                list($c, $m, $params) = getCallbackFromString($class);
                $class = new $c();
            } elseif (isset($_class_list[$class])) {
                $class = new $class;
                $m = '__invoke';
            }

            for ($i=0; $i<count($params); $i++) {
                if (count($parameters)>=$i) {
                    $params[$i] = $parameters[$i];
                }
            }
            
            return executeCallback($class, $m, $params, $class, false);
        }

        throw new BadMethodCallException("Method $method does not exist");
    }
}