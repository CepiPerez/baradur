<?php

class RequestSession
{
    protected $data = array();
    
    public function __construct()
    {
        if (isset($_SESSION['session'])) {

            foreach ($_SESSION['session']['data']['reflash'] as $key => $val) {
                $this->data[$key] = $val;
            }

            foreach ($_SESSION['session']['data']['flash'] as $key => $val) {
                $this->data[$key] = $val;
            }

            unset($_SESSION['session']['data']['flash']);
        }
    }

    public function get($key=null, $default=null)
    {
        if ($key) {
            return $this->exists($key) ? $this->data[$key] : $default; 
        }

        return $this->data;
    }

    public function put($key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function all()
    {
        return $this->data;
    }

    public function exists($key)
    {
        return array_key_exists($key, $this->data);
    }

    public function missing($key)
    {
        return !$this->exists($key);
    }

    public function has($key)
    {
        return isset($this->data[$key]);
    }

    public function push($key, $value)
    {
        $arr = $this->data;
        Arr::set($arr, $key, $value);
        $this->data = $arr;
    }

    public function pull($key, $default=null)
    {
        $arr = $this->data;
        $result = Arr::pull($arr, $key, $default);
        $this->data = $arr;
        unset($_SESSION['session']['data']['flash'][$key]);
        unset($_SESSION['session']['data']['reflash'][$key]);

        return $result;
    }

    public function increment($key, $amount = 1)
    {
        $this->put($key, $value = $this->get($key, 0) + $amount);

        return $value;
    }

    public function decrement($key, $amount = 1)
    {
        return $this->increment($key, $amount * -1);
    }

    public function flash($key, $value)
    {
        $this->put($key, $value);
        $_SESSION['session']['data']['flash'][$key] = $value;
    }

    public function reflash()
    {
        $_SESSION['session']['data']['reflash'] = $this->data;
        unset($_SESSION['session']['data']['flash']);
    }

    public function keep($values = array())
    {
        foreach ($values as $key) {
            if ($this->exists($key)) {
                $_SESSION['session']['data']['reflash'][$key] = $this->get($key);
                unset($_SESSION['session']['data']['flash'][$key]);
            }
        }
    }

    public function forget($key)
    {
        $key = !is_array($key) ? array($key) : $key;

        foreach ($key as $k) {
            unset($this->data[$k]);
            unset($_SESSION['session']['data']['flash'][$k]);
            unset($_SESSION['session']['data']['reflash'][$k]);
        }
    }

    public function flush()
    {
        $this->data = array();
        unset($_SESSION['session']['data']['flash']);
        unset($_SESSION['session']['data']['reflash']);
    }

    public function token()
    {
        return $_SESSION['_token'];
    }

    public function regenerateToken()
    {
        if ( config('app.key') === null ) {
            throw new MissingAppKeyException('No application encryption key has been specified.');
        }
        
        $_SESSION['_token'] = hash_hmac('sha256', Str::random(40), config('app.key'));
    }

    public function regenerate($destroy = false)
    {
        $result = $this->migrate($destroy);

        $this->regenerateToken();
        
        return $result;
    }

    public function migrate($destroy = false)
    {
        $id = session_regenerate_id($destroy);

        return true;
    }


}