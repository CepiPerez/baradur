<?php

Class Collection extends arrayObject
{

    protected static $_parent = 'Model';
    protected static $_hidden = array();

    /**
     * Creates a new Collection\
     * Associates it with a classname if defined
     * (only needed if you need to call relationships)
     * 
     * @param string $classname
     */
    public function __construct($classname, $hidden=array())
    {
        self::$_parent = $classname;
        self::$_hidden = $hidden;
    }

    public function getParent()
    {
        return self::$_parent;
    }


    public function arrayToObject($array)
    {

        $obj = new self::$_parent;

        if (count($array)==0)
            return $obj;

        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $obj->$key = $this->arrayToObject($value);
            } 
            else
            {
                $obj->$key = $value; 
            }
        }
        return $obj;
    }

    /**
     * Returns collection as array
     * 
     * @return array
     */
    public function toArray($data=null)
    {
        if (!isset($data))
            $data = $this;

        $res = array();
        foreach ($data as $key => $val)
        {
            if (!in_array($key, self::$_hidden))
            {
                if (is_object($val) || is_array($val))
                    $res[$key] = $data->_toArray($val);
                else
                    $res[$key] = $val;
            }
        }

        return $res;
    }

    private function _toArray($object)
    {
        $arr = array();
        foreach ($object as $key => $val)
        {
            if (!in_array($key, self::$_hidden))
            {
                # Remove hidden attributes from relationships
                if (is_object($val) && class_exists(get_class($val))) {
                    $classname = get_class($val);
                    $c_hidden = self::$_hidden;
                    //$class = new $classname;
                    $arr[$key] = $this->_toArray($val);
                    self::$_hidden = $c_hidden;
                }

                elseif (is_object($val) || is_array($val))
                    $arr[$key] = $this->_toArray($val);
                else
                    $arr[$key] = $val;
            }
        }
        return $arr;
    }

    public function toArrayObject()
    {
        $arr = array();
        foreach ($this as $obj)
        {
            $arr[] = $obj;
        }
        return $arr;
    }

    /**
     * Builds pagination links in View
     * 
     * @return string
     */
    public function links($bootstrap = false)
    {
        if (!isset($this->pagination)) return null;

        View::setPagination($this->pagination);
        if ($bootstrap) return View::loadTemplate('common/pagination');
        return View::loadTemplate('layouts/pagination');
    }

    /**
     * Adds parameters to pagination links
     * 
     * @return string
     */
    public function appends($params)
    {
        if (!isset($this->pagination)) return $this;

        unset($params['ruta']);
        unset($params['p']);

        

        if (count($params)>0)
        {
            $str = array();
            foreach ($params as $key => $val)
                $str[] = $key.'='.$val;
            
            if (isset($this->pagination->first))
                $this->pagination->first = implode('&', $str) . '&' . $this->pagination->first;

            if (isset($this->pagination->second))
                $this->pagination->second = implode('&', $str) . '&' . $this->pagination->second;

            if (isset($this->pagination->third))
                $this->pagination->third = implode('&', $str) . '&' . $this->pagination->third;

            if (isset($this->pagination->fourth))
                $this->pagination->fourth = implode('&', $str) . '&' . $this->pagination->fourth;

        }

        //var_dump($this->pagination);
        return $this;
    }

    /**
     * Removes and returns the first item from the collection
     * 
     * @return Model
     */
    public function shift()
    {
        if ($this->count()==0) return null;

        $res = $this[0];
        $this->offsetUnset(0);
        return $res;
    }
    
    /**
     * Removes and returns the last item from the collection
     * 
     * @return Model
     */
    public function pop()
    {
        if ($this->count()==0) return null;

        $res = $this[$this->count()-1];
        $this->offsetUnset($this->count()-1);
        return $res;
    }

    /**
     * Returns the first item from the collection
     * 
     * @return Model
     */
    public function first()
    {
        return $this->count()>0? $this[0] : null;
    }

    /**
     * Returns the last item from the collection
     * 
     * @return Model
     */
    public function last()
    {
        return $this->count()>0? $this[$this->count()-1] : null;
    }


    private function getObjectItemsForClone($item)
    {
        $obj = new StdClass;

        foreach ($item as $key => $val)
        {
            if (is_object($val))
            {
                $obj->$key = $this->getObjectItemsForClone($val);
                $obj->$key->__name = get_class($val);
            }
            else
            {
                $obj->$key = $val;
            }

        }
        return $obj;
    }

    /**
     * Returns a collection's duplicate
     * 
     * @return Collection
     */
    public function duplicate($collection=null, $parent=null)
    {
        if (!isset($collection)) $collection = $this;

        if (!isset($parent)) $parent = self::$_parent;

        $col = new Collection($parent);
        
        foreach ($collection as $k => $item)
        {
            if (is_object($item))
            {
                $col[$k] = $this->getObjectItemsForClone($item);
                $col[$k]->__name = get_class($item);
                
            }
            else
            {
                $col[$k] = $item;
            }
        }

        return $col;
    }


    private function getObjectItemsForCollect($item)
    {
        $obj = new StdClass;
    
        if (isset($item->__name))
            $obj = new $item->__name;

        foreach ($item as $key => $val)
        {
            if (is_object($val))
            {
                $obj->$key = $this->getObjectItemsForCollect($val);
            }
            elseif ($key!='__name')
            {
                $obj->$key = $val;
            }

        }
        return $obj;
    }


    /**
     * Fills the collection
     * 
     * @return Collection
     */
    public function collect($data, $parent='stdClass')
    {
        //echo "DATA:"; var_dump($data); echo "<br>";
        if (count($data)==0)
            return $this;

        foreach ($data as $k => $item)
        {
            if (is_object($item) && isset($item->__pagination))
            {
                $pagination = new arrayObject();
                $pagination->first = $item->__pagination->first;
                $pagination->second = $item->__pagination->second;
                $pagination->third = $item->__pagination->third;
                $pagination->fourth = $item->__pagination->fourth;
                //$col->pagination = $pagination;
                $this->pagination = $pagination;
            }
            elseif (is_object($item))
            {
                //$col[$k] = $this->getObjectItemsForCollect($item);
                $this[$k] = $this->getObjectItemsForCollect($item);
            }
            elseif ($k!='__name')
            {
                //$col[] = $item;
                $this[$k] = $item;
            }
        }
        //return $col;
        return $this;
    }


    /**
     * Returns all elements from the collection
     * 
     * @return Collection
     */
    public function all()
    {
        return $this;
    }


    /**
     * Checks if value exists in collection
     * 
     * @return bool
     */
    public function contains($value)
    {
        return in_array($value, (array)$this);
    }

    /**
     * Filters the collection by a given key/value pair
     * 
     * @return Collection
     */
    public function where($key, $value)
    {
        $res = new Collection(self::$_parent);
        foreach ($this as $record)
        {
            if (isset($record->$key) && $record->$key==$value)
                $res[] = $record;
        }
        return $res;
    }

    /**
     * Filters the collection by a given key/value pair
     * 
     * @return Collection
     */
    public function whereIn($key, $values)
    {
        $res = new Collection(self::$_parent);
        foreach ($this as $record)
        {
            if (in_array($record->$key, $values))
                $res[] = $record;
        }
        return $res;
    }

    /**
     * Filters the collection by a given key/value pair
     * 
     * @return Collection
     */
    public function whereNotIn($key, $values)
    {
        $res = new Collection(self::$_parent);
        foreach ($this as $record)
        {
            if (!in_array($record->$key, $values))
                $res[] = $record;
        }
        return $res;
    }

    /**
     * Filters the collection by a given key/value pair\
     * Returns elements containing that value
     * 
     * @return Collection
     */
    public function whereContains($key, $value)
    {
        $res = new Collection(self::$_parent);
        foreach ($this as $record)
        {
            if (isset($record->$key) && $record->$key==$value)
                $res[] = $record;
        }
        return $res;
    }

    /**
     * Filters the collection by a given key/value pair\
     * Returns elements NOT containing that value
     * 
     * @return Collection
     */
    public function whereNotContains($key, $value)
    {
        $res = new Collection(self::$_parent);
        foreach ($this as $record)
        {
            if (strpos($record->$key, $value)==false && substr($record->$key, 0, strlen($value))!=$value)
                $res[] = $record;
        }
        return $res;
    }

    /**
     * Retrieves all of the values for a given key\
     * You may also specify how you wish the resulting collection to be keyed
     * 
     * @return Collection
     */
    public function pluck($value, $key=null)
    {
        $count = 0;
        
        $res = new Collection(self::$_parent);
        foreach ($this as $record)
        {
            if ($key) $res[$record->$key] = $record->$value;
            else $res[] = $record->$value;
        }
        return $res;
    }

    /**
     * Sets the given item in the collection
     * 
     * @return Collection
     */
    public function put($item)
    {
        if (is_array($item))
            $item = $this->arrayToObject($item);

        $this->append($item);
    }

    /**
     * Removes and returns an item from the collection 
     * by its index or its key/value pair
     * 
     * @return Collection
     */
    public function pull($index, $value=null)
    {
        //echo("Removing: $index > $value<br>");
        if (!is_integer($index))
        {
            $ind = -1;
            $count = 0;
            foreach ($this as $record)
            {
                //print_r($record);
                if (isset($record->$index) && $record->$index==$value)
                {
                    $ind = $count;
                    break;
                }
                ++$count;
            }
            if ($ind==-1) return null;
            $index = $ind;
        }
        
        if ($index > $this->count()-1)
            return null;
        
        $res = $this[$index];
        $this->offsetUnset2($index);
        return $res;

    }

    function offsetUnset2($offset){
        $this->offsetUnset($offset);
        $this->exchangeArray(array_values($this->getArrayCopy()));
    }

    /**
     * Determines if a given key exists in the collection
     * 
     * @return bool
     */
    public function has($key)
    {
        return isset($this->$key);
    }

    /**
     * Returns an element by its key/value pair
     * 
     * @return bool
     */
    public function find($key, $value)
    {
        foreach ($this as $record)
        {
            if (isset($record->$key) && $record->$key==$value)
                return $record;
        }
    }

    /**
     * Adds records from a sub-query inside the current records\
     * Check Laravel documentation
     * 
     * @return Model
     */
    public function load($relations)
    {
        $class = new self::$_parent;
        $class->getQuery()->_collection = $this;
        $class->load( is_string($relations) ? func_get_args() : $relations );
    }
    


    
}