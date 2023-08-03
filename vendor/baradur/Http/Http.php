<?php

class Http
{
    private static $globalMiddleware = array();
    private static $_instance;

    private $url = null;
    private $useSsl = true;
    private $headers = array();
    private $timeout = 30;
    private $tries = 1;
    private $sleep = 1;
    private $to = null;

    public $result = null;
    public $code = null;

    public function __construct()
    {
        //$this->curl == new Curl();
    }

    /**
     * Get Route instance
     * 
     * @return Http
     */
    public static function instance()
    {
        if (!self::$_instance)
            self::$_instance = new Http;

        return self::$_instance;
    }

    /* public static function globalMiddleware($middleware)
    {
        self::$globalMiddleware['global'] = $middleware;
    } */

    public static function globalRequestMiddleware($middleware)
    {
        self::$globalMiddleware['request'] = $middleware;
    }

    public static function globalResponseMiddleware($middleware)
    {
        self::$globalMiddleware['response'] = $middleware;
    }

    public function __toString()
    {
        return $this->result;
    }

    public function getResults($method, $url, $data=null, $fullResponse=false)
    {
        $this->url = $url;

        $curl = new Curl;
        $result = null;

        if (self::$globalMiddleware['request']) {
            list($c, $m) = getCallbackFromString(self::$globalMiddleware['request']);
            call_user_func_array(array($c, $m), array($this));
        }

        if (!$this->useSsl) {
            $curl->secure(false, false);
        }

        $curl->option(CURLOPT_TIMEOUT, $this->timeout);

        foreach ($this->headers as $key => $value) {
            $curl->httpHeaders($key, $value);
        }
        
        $tries = 1;
        while ($tries <= $this->tries) {
            $result = $curl->sendRequest($method, $this->url, $data);

            if ($result) {
                break;
            }

            $tries++;
            sleep($this->sleep);
        }

        if ($fullResponse) {
            return $result;
        }

        $response = new Response($result);

        if ($result && isset($result->error_code)) {
            $response->error_code = $result->error_code;
            $response->error_string = $result->error_string;
        }

        if (self::$globalMiddleware['response']) {
            list($c, $m) = getCallbackFromString(self::$globalMiddleware['response']);
            call_user_func_array(array($c, $m), array($response));
        }

        return $response;
    }

    public static function baseUrl($url)
    {
        $instance = self::instance();
        $instance->url = $url;
        return $instance;
    }

    public static function timeout($seconds)
    {
        $instance = self::instance();
        $instance->timeout = $seconds;
        return $instance;
    }

    public static function withoutVerifying()
    {
        $instance = self::instance();
        $instance->useSsl = false;
        return $instance;
    }

    public static function sink($to)
    {
        $instance = self::instance();
        $instance->to = $to;
        return $instance;
    }
    
    public static function retry($times, $sleep=1)
    {
        $instance = self::instance();
        $instance->tries = $times;
        $instance->sleep = $sleep;
        return $instance;
    }

    public function withToken($token)
    {
        $instance = self::instance();
        
        $instance->headers['Authorization'] = 'Bearer '.$token;
        
        return $instance;
    }

    public static function withHeaders($headers)
    {
        $instance = self::instance();
        
        foreach ($headers as $key => $value)
        {
            $instance->headers[$key] = $value;
        }
        
        return $instance;
    }

    public static function withHeader($key, $value)
    {
        $instance = self::instance();
        
        $instance->headers[$key] = $value;
        
        return $instance;
    }

    private function checkSink($response)
    {
        if ($this->to) {
            file_put_contents($this->to, $response->body());
        }
    }

    /** @return Response */
    public static function get($url=null)
    {
        $instance = self::instance();
        $res = $instance->getResults('get', $url ? $url : $instance->url);
        $instance->checkSink($res);
        return $res;
    }

    /** @return Response */
    public static function post($url=null, $data=array())
    {
        $instance = self::instance();
        return $instance->getResults('post', $url ? $url : $instance->url, $data);
    }

    /** @return Response */
    public static function put($url=null, $data=array())
    {
        $instance = self::instance();
        return $instance->getResults('put', $url ? $url : $instance->url, $data);
    }

    /** @return Response */    
    public static function delete($url=null, $data=array())
    {
        $instance = self::instance();
        return $instance->getResults('delete', $url ? $url : $instance->url, $data);
    }



}