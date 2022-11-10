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

        /* if (count($cached) > 0)
        {
            $data = json_decode($cached[0], true);
            $data = Helpers::arrayToObject($data);
            $col = new Collection('stdClass');
            $col->collect($data);
            return $col;
        }
        return null; */
        return unserialize($cached[0]);

    }


    public function put($name, $data)
    {
        /* if (get_class($data) == 'Collection')
        {
            $collection = $data->duplicate();
            
            if (isset($data->pagination))
                $collection[] = array('__pagination' => array(
                    '__name' => get_class($data->pagination),
                    'first' => $data->pagination->first,
                    'second' => $data->pagination->second,
                    'third' => $data->pagination->third,
                    'fourth' => $data->pagination->fourth
                ));
    
        } */
        
        $this->redis->lpush($name, serialize($data));
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
            array_shift($params);
            $value = call_user_func_array(array($class, $method), $params);
            $this->put($key, $value, $seconds);
            return $value;
        }
    }

}