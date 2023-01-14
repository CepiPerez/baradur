<?php

class ResourceCollection extends ArrayObject
{
	protected $_wrap = 'data';

    public $collection;
    protected $_links;
    protected $_meta;

    public $collects = null;

    public function __construct($resource)
    {
        if ($resource->getPagination())
        {
            $paginator = $resource->getPaginator();
            $this->_links = $paginator;
            $this->_meta = $paginator->meta;
            unset($this->_links->meta);
        }

        if (!isset($this->collects))
        {
            $childName = str_replace('Collection', '', str_replace('Resource', '', get_class($this))) . 'Resource';
            $this->collects = $childName!='Resource' ? $childName : 'JsonResource';
        }

        $this->collection = new Collection(get_class($this));

        foreach ($resource as $item)
        {
            $class = $this->collects;
            $new = new $class($item);
            $this->collection[] = $new;
        }
        
        if (JsonResource::getWrapping() && !$resource->getPagination())
        {
            foreach ($this->toArray(request()) as $val)
            {
                $this[] = $this->_toArray($val);
            } 
        }
        else
        {
            $wrapname = $this->_wrap;
            $wrap = array();
            foreach ($this->toArray(request()) as $key => $val)
            {
                $val = $this->_toArray($val);
                $wrap[$key] = JsonResource::getWrapping()? $val : $val->data;
            } 
            $this[$wrapname] = $wrap;
        }

        
        unset($this->collection);
        unset($this->collects);

        if (isset($this->_links))
        {
            $this['links'] = $this->_links;
            $this['meta'] = $this->_meta;
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
        /* if (isset($this->_links))
            return array('data' => $this->collection);
        else  */
            return (array) $this->collection;
    }

    public function getResult()
    {        
        if (!isset($this['meta']) && !isset($this[$this->_wrap]))
        {
            $res = array();
            foreach ($this as $val)
                $res[] = $val;

            return $res;
        }

        return $this;
    }
}