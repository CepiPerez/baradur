<?php

/**
 * @method static JsonResource collection($resource)
 */

class JsonResource
{
    static $withoutWrapping = false;

    protected $parent;
    protected $resource;

    /** @return JsonResource */
    public static function instance($parent)
    {
        return new $parent();
    } 

    public static function withoutWrapping()
    {
        self::$withoutWrapping = true;
    }

    public static function getWrapping()
    {
        return self::$withoutWrapping;
    }


    public function collection($collection)
    {
        if ($collection=='_value_not_loaded')
        {
            return '_value_not_loaded';
        }

        $res = array();
        foreach ($collection as $item)
        {
            $class = $this->parent;
            $it = new $class($item);

            if (JsonResource::getWrapping())
                $res[] = $it;
            else
                $res[] = $it->data;
        }

        if (JsonResource::getWrapping())
            return $res;

        return array('data' => $res);
        //return new JsonResource($collection);
    }

    public function __construct($resource=null)
    {
        $this->parent = get_class($this);

        if (!isset($resource))
        {
            return;
        }

        $this->resource = $resource;
        
        $data = array();
        foreach ($this->toArray(request()) as $key => $val)
        {
            if($val != '_value_not_loaded')
            {
                if (is_array($val) && isset($val['_merged']))
                {
                    foreach($val['_merged'] as $k => $v)
                    {
                        $data[$k] = $v;
                    }
                }
                else
                {
                    $data[$key] = $this->_toArray($val);
                }
            }
        }

        if (JsonResource::getWrapping())
            $res = $data;
        else
            $res = array('data' => $data);

        foreach ($this as $key => $val)
            unset($this->$key);

        foreach ($res as $key => $val)
        {
            $this->$key = $val;
        }
    }

    private function _toArray($item)
    {
        if ($item instanceof Model)
        {
            return $item->toArray();
        }

        if (is_array($item) || $item instanceof Collection)
        {
            $res = array();
            foreach ($item as $key => $val)
            {
                $res[$key] = $this->_toArray($val);
            }
            return $res;
        }

        return $item;
    }

    public function toArray($request)
    {
        return (array)$this;
    }

    public function __get($name)
    {
        try {
            return $this->resource->$name;
        } catch (Exception $e) {
            return null;
        }
    }

    public function whenHas($attribute, $value = null, $default = null)
    {
        if (func_num_args() < 3) {
            $default = '_value_not_loaded';
        }

        if (! array_key_exists($attribute, $this->resource->getAttributes())) {
            return value($default, $this);
        }

        return func_num_args() === 1
                ? $this->resource->{$attribute}
                : value($value, $this->resource->{$attribute});
    }

    public function whenLoaded($relation)
    {
        if ($this->resource->getRelation($relation))
            return $this->resource->$relation;

        return '_value_not_loaded';
    }

    public function whenCounted($relation)
    {
        $relation = str_replace('_count', '', $relation) . '_count';
        
        if ($this->resource->getAttribute($relation))
            return $this->resource->$relation;

        return '_value_not_loaded';
    }

    public function when($condition, $value, $default=null)
    {
        if ($condition) {
            return value($value, $this);
        }

        return func_num_args()===3 ? value($default, $this) : '_value_not_loaded';

        /* if (is_bool($condition) && !$condition)
            return '_value_not_loaded';

        if (!$condition)
            return '_value_not_loaded';

        if (is_object($value))
        {
            return $value;
        }

        if (is_string($value) && strpos($value, '@')===false)
        {
            return $value;
        }

        list($class, $method, $params) = getCallbackFromString($value);
        $res = call_user_func_array(array($class, $method), array_merge(array($this), $params));

        return $res; */
    }

    public function unless($condition, $value, $default=null)
    {
        if (!$condition) {
            return value($value, $this);
        }

        return func_num_args()===3 ? value($default, $this) : '_value_not_loaded';
    }

    public function whenNotNull($value)
    {
        if ($value) return $value;

        return '_value_not_loaded';
    }

    public function mergeWhen($condition, $value)
    {
        return $condition ? array('_merged' => value($value, $this)) : '_value_not_loaded';


        /* if (is_bool($condition) && !$condition)
            return '_value_not_loaded';

        if (!$condition)
            return '_value_not_loaded';

        if (is_array($value))
        {
            return array('_merged' => $value);
        }

        list($class, $method, $params) = getCallbackFromString($value);
        $res = call_user_func_array(array($class, $method), array_merge(array($this), $params));

        foreach ($res as $key => $val)
        {
            $this->$key = $val;
        } */
    }

}