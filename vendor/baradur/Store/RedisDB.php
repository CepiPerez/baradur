<?php

Class RedisDB
{
    static $_instance = null;
    private $redis;

    public function __construct()
    {
        //echo "Connecting to REDIS";
        $this->redis = new Redis();
        $this->redis->connect(REDIS_HOST, REDIS_PORT);
        
    }


    /* public static function getInstance()
    {
        if (!self::$_instance)
            self::$_instance = new RedisDB();

        return self::$_instance->redis;
    } */


    public function has($name)
    {
        return $this->redis->exists($name) > 0;
    }

    public function get($name)
    {
        $cached = $this->redis->lrange($name, 0, 1);

        return unserialize($cached[0]);
    }

    public function put($name, $data)
    {    
        return $this->redis->lpush($name, serialize($data));
    }

    public function forever($key, $value)
    {
        return $this->put($key, $value);
    }

    public function forget($name)
    {
        $this->redis->del($name);
    }

    public function flush()
    {
        $this->redis->flushAll();
    }

    public function remember($key, $seconds, $callback)
    {
        if ($this->has($key))
        {
            return $this->get($key);
        }
        else
        {
            list($class, $method, $params) = getCallbackFromString($callback);
            $value = call_user_func_array(array($class, $method), $params);
            $this->put($key, $value/* , $seconds */);
            return $value;
        }
    }

}