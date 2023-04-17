<?php

/**
 * @method static JsonResource collection($resource)
 * @method static JsonResource make($resource)
 */

class JsonResource
{
    public static $wrap = 'data';

    protected $parent;
    protected $resource;

    public $preserveKeys = false;

    /** @return JsonResource */
    public static function instance($parent)
    {
        return new $parent();
    } 

    public static function withoutWrapping()
    {
        self::$wrap = null;
    }

    public function make($resource)
    {
        $instance = get_class($this);
        return new $instance($resource);
    }

    public function collection($collection)
    {
        if ($collection=='_value_not_loaded') {
            return '_value_not_loaded';
        }

        $res = array();
        $class = get_class($this);

        foreach ($collection as $key => $item) {
            $item = new $class($item);

            $item = $this::$wrap? $item->{$this::$wrap} : $item;

            if ($this->preserveKeys) {
                $res[$key] = $item;
            } else {
                $res[] = $item;
            }
        }
        
        if ($this::$wrap) {
            $result = collect(array($this::$wrap => $res));
        } else {
            $result = collect($res);

            foreach ($result as $item) {
                unset($item->preserveKeys);
            }
        }
        
        return $result;

    }

    public function __construct($resource=null)
    {
        $this->parent = get_class($this);

        if ($resource)
        {
            $this->resource = $resource;
            
            $data = $this->toArray(request());
            
            foreach ($data as $key => $val) {

                if($val!='_value_not_loaded') {

                    if (is_array($val) && isset($val['_merged'])) {
                        foreach($val['_merged'] as $k => $v) {
                            $data[$k] = $v;
                        }
                    } else {
                        $data[$key] = $this->_toArray($val);
                    }
                } else {
                    unset($data[$key]);
                }
            }

            if (!$this::$wrap) {
                $res = $data;
            } else {
                $res = array($this::$wrap => $data);
            }

            foreach ($this as $key => $val) {
                unset($this->$key);
            }

            foreach ($res as $key => $val) {
                $this->$key = $val;
            }

        } else {
            unset($this->parent);
            unset($this->resource);
        }

    }

    private function _toArray($item)
    {
        if ($item instanceof Model) {
            return $item->toArray();
        }

        /* if ($item instanceof JsonResource) {
            return $item->toArray(request());
        } */
        //dump($item, true);

        if ($item instanceof Collection || $item instanceof Paginator) {
            return $item->toArray();
        }

        if (is_array($item) ) {
            $res = array();

            foreach ($item as $key => $val) {
                if ($key != 'preserveKeys') {
                    $res[$key] = $this->_toArray($val);
                }
            }

            return $res;
        }

        return $item;
    }

    public function toArray($request)
    {
        return $this->_toArray($this->resource);
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
        if ($this->resource->getRelation($relation)) {
            return $this->resource->$relation;
        }

        return '_value_not_loaded';
    }

    public function whenCounted($relation)
    {
        $relation = str_replace('_count', '', $relation) . '_count';
        
        if ($this->resource->getAttribute($relation)) {
            return $this->resource->$relation;
        }

        return '_value_not_loaded';
    }

    public function when($condition, $value, $default=null)
    {
        if ($condition) {
            return value($value, $this);
        }

        return func_num_args()===3 ? value($default, $this) : '_value_not_loaded';

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
        if ($value) {
            return $value;
        }

        return '_value_not_loaded';
    }

    public function mergeWhen($condition, $value)
    {
        return $condition ? array('_merged' => value($value, $this)) : '_value_not_loaded';

    }

}