<?php

class Http
{
    private static $_instance;

    private $url = null;
    private $useSsl = true;
    private $headers = array();
    private $timeout = 30;
    private $tries = 1;
    private $sleep = 1;

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

    public function __toString()
    {
        return $this->result;
    }

    public function getResults($method, $url, $data=null, $fullResponse=false)
    {
        $this->url = $url;
        $curl = new Curl;

        $result = null;

        if (!$this->useSsl)
            $curl->secure(false, false);

        $curl->option(CURLOPT_TIMEOUT, $this->timeout);

        foreach ($this->headers as $key => $value)
        {
            $curl->httpHeaders($key, $value);
        }

        
        $tries = 1;
        while ($tries <= $this->tries)
        {
            $result = $curl->sendRequest($method, $this->url, $data);

            if ($result)
                break;

            $tries++;
            sleep($this->sleep);
        }

        if ($fullResponse)
        {
            return $result;
        }

        $response = new Response($result);

        if (isset($result->error_code))
        {
            $response->error_code = $result->error_code;
            $response->error_string = $result->error_string;
        }

        return $response;
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

    public static function get($url)
    {
        $instance = self::instance();
        return $instance->getResults('get', $url);
    }

    public static function post($url, $data=array())
    {
        $instance = self::instance();
        return $instance->getResults('post', $url, $data);
    }

    public static function put($url, $data=array())
    {
        $instance = self::instance();
        return $instance->getResults('put', $url, $data);
    }

    public static function delete($url, $data=array())
    {
        $instance = self::instance();
        return $instance->getResults('delete', $url, $data);
    }



}