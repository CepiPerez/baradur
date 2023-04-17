<?php

class ResourceCollection extends ArrayObject
{
	public static $wrap = 'data';

    public $collection;
    protected $_links = null;
    protected $_meta = null;

    public $collects = null;

    public function __construct($resource)
    {
        global $_class_list;

        if (is_array($resource)) {
            $resource = collect($resource);
        }

        if (!$resource instanceof Collection) {
            throw new LogicException("Invalid resource collection assigned.");
        }
        
        if ($resource instanceof Paginator) {
            $this->_links = $resource;
            $this->_meta = $resource->meta;
            unset($this->_links->meta);
            unset($this->_links->query);
        }

        if (!isset($this->collects)) {
            $childName = str_replace('Collection', '', str_replace('Resource', '', get_class($this))) . 'Resource';
            $this->collects = $childName!='Resource' ? $childName : 'JsonResource';
        }

        if (isset($this->collects) && !isset($_class_list[$this->collects])) {
            throw new LogicException("Resource collections must collect instances of JsonResource.");
        }

        $this->collection = new Collection(); 

        $class = $this->collects;
        $base = new $class();
        $preserveKeys = $base->preserveKeys;

        //dump($class."::".($preserveKeys?'preserve':'skip')."::".($base::$wrap?$base::$wrap:'null'));

        foreach ($resource as $key => $item) {
            $item = new $class($item);

            if ($preserveKeys) {
                $this->collection[$key] = $base::$wrap? $item->{$base::$wrap} : $item;
            } else {
                $this->collection[] = $base::$wrap? $item->{$base::$wrap} : $item;
            }

        }

        $resource = $this->toArray(request());

        if ($resource == $this->collection->all()) {
           
            $resource = $this->_toArray($resource, null);
    
            if (!isset($base::$wrap) && isset($this->_links)) {
                $base::$wrap = 'data';
            }

            if (!isset($base::$wrap)) {
                foreach ($resource as $key => $item) {
                    if ($preserveKeys) {
                        $this[$key] = $item;
                    } else {
                        $this[] = $item;
                    }
                }
            } else {
                $this[$base::$wrap] = $resource;
            }

        } else {
            $resource = $this->_toArray($resource, null);

            if (isset($this->_links)) {
                $this['data'] = $resource;
            } else {
                $this[] = $resource;
            }
        }
        
        unset($this->collection);
        unset($this->collects);
        
        if (isset($this->_links)) {
            $this['links'] = $this->_links;
            $this['meta'] = $this->_meta;
        }

    }

    private function getItemData($item)
    {
        if (is_array($item)) {
            $res = array();

            foreach ($item as $it) {
                $res[] = $this->getItemData($it);
            }
            
            return $res;
        }

        if (!JsonResource::getWrapping() && $item instanceof JsonResource) {
            return $item->data;
        }

        return $item;
    }

    private function _toArray($item, $wrap)
    {
        if ($item instanceof Model) {
            return $item->toArray();
        }

        $class = $this->collects;
        $base = new $class();

        if (is_array($item) || $item instanceof Collection) 
        {
            if ($wrap && is_array($item)) {
                if (isset($item[$wrap])) {
                    $item = $item[$wrap];
                }
            }

            $res = array();
            
            foreach ($item as $key => $val) {
                $res[$key] = $this->_toArray($val, $base::$wrap);
            }

            return $res;
        }

        return $item;
    }

    public function toArray($request)
    {
        return $this->collection->all();
    }

    public function getResult()
    {
        if (!isset($this['meta'])) {
            $res = array();

            foreach ($this as $key => $val) {
                $res[$key] = $val;
            }

            return $res;
        }

        return (array)$this;
    }
}