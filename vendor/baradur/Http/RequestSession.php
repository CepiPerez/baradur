<?php

class RequestSession
{
    protected $data = array();

    public function __construct()
    {
        if (isset($_SESSION['baradur_flash']))
        {
            $this->data = $_SESSION['baradur_flash']['data'];

            if ($_SESSION['baradur_flash']['type']=='flash')
            {
                unset($_SESSION['baradur_flash']);
            }
        }
    }

    public function get($value=null, $default=null)
    {
        if ($value && is_string($value)) {
            return $this->exists($value) ? $this->data[$value] : $default; 
        }

        if (is_array($value))
        {
            foreach ($value as $key => $val)
            {
                $this->put($key, $val);
            }
        }

        return $this;
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
        $_SESSION['baradur_flash']['data'][$key] = $value;
        $_SESSION['baradur_flash']['type'] = 'flash';
    }

    public function reflash()
    {
        $_SESSION['baradur_flash']['data'] = $this->data;
        $_SESSION['baradur_flash']['type'] = 'reflash';
    }

    public function keep($key, $value)
    {
        $this->put($key, $value);
        $_SESSION['baradur_flash']['data'][$key] = $value;
        $_SESSION['baradur_flash']['type'] = 'reflash';
    }

    public function forget($key)
    {
        if (!is_array($key)) $key = array($key);

        foreach ($key as $k)
        {
            unset($this->data[$k]);
            unset($_SESSION['baradur_flash']['data'][$k]);
        }

        if (count($_SESSION['baradur_flash']['data'])==0)
        {
            unset($_SESSION['baradur_flash']);
        }
    }

    public function flush()
    {
        $this->data = array();
        unset($_SESSION['baradur_flash']);
    }

}