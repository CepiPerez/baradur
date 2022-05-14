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


    public static function getInstance()
    {
        if (!self::$_instance)
            self::$_instance = new RedisDB();

        return self::$_instance->redis;
    }


    public static function has($name)
    {
        return self::getInstance()->exists($name) > 0;
    }

    public static function get($name)
    {
        $cached = self::getInstance()->lrange($name, 0, 1);

        if (count($cached) > 0)
        {
            $data = json_decode($cached[0], true);
            $data = Helpers::arrayToObject($data);
            $col = new Collection('stdClass');
            $col->collect($data);
            return $col;
        }
        return null;

    }


    public static function put($name, $data)
    {
        if (get_class($data) == 'Collection')
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
    
        }
        
        self::getInstance()->lpush($name, json_encode($collection));
    }

    public static function forget($name)
    {
        self::getInstance()->del($name);
    }

    public static function flush()
    {
        self::getInstance()->flushAll();
    }



}