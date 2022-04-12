<?php

Class Collection extends arrayObject
{

    protected static $_parent = 'Model';

    /**
     * Creates a new Collection\
     * Associates it with a classname if defined
     * (only needed if you need to call relationships)
     * 
     * @param string $classname
     */
    public function __construct($classname)
    {
        self::$_parent = $classname;
    }

    public function getParent()
    {
        return self::$_parent;
    }


    private function arrayToObject($array)
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
     * Builds pagination links in View
     * 
     * @return string
     */
    public function links()
    {
        if (!isset($this->pagination)) return null;

        View::setPagination($this->pagination);
        return View::loadTemplate('common/pagination');
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
        $col = new Collection($parent);

        foreach ($data as $k => $item)
        {
            if (is_object($item) && isset($item->__pagination))
            {
                $pagination = new arrayObject();
                $pagination->first = $item->__pagination->first;
                $pagination->second = $item->__pagination->second;
                $pagination->third = $item->__pagination->third;
                $pagination->fourth = $item->__pagination->fourth;
                $col->pagination = $pagination;
            }
            elseif (is_object($item))
            {
                $col[$k] = $this->getObjectItemsForCollect($item);
            }
            elseif ($k!='__name')
            {
                $col[] = $item;
            }
        }
        return $col;
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
            if (strpos($record->$key, $value)!=false)
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
            if (strpos($record->$key, $value)==false)
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
        echo "Remiving: ".$index;
        if (!is_integer($index))
        {
            $ind = -1;
            $count = 0;
            foreach ($this as $record)
            {
                if ($record->$index==$value)
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
        $this->offsetUnset($index);
        return $res;
    }

    /**
     * Removes empty keys from each item in collection 
     * 
     * @param $item
     * @return Collection
     */
    public function removeEmptyKeys($item, $collection, $child=null, $parent=null)
    {
        echo "Removing empty item:".$item." : child:".$child. " : parent:".$parent."<br>";

        if (!isset($parent))
            return $this->removeRecursive($item, $collection, $child, null);
        elseif (isset($parent))
            return $this->removeRecursive($item, $collection, null, $parent);


    }

    public function removeRecursive($item, $collection, $child, $parent)
    {
        //dd($collection);
        $index = 0;

        if (!isset($parent)) # Removing items from collection
        {
            echo "Removing items from collection<br>";
            foreach ($collection as $current)
            {
                echo "checking: "; var_dump($current); echo "<br>";
                if (isset($current->$item))
                {
                    echo "<br>found item: "; var_dump($current->$item); echo "<br>";

                    if (count($current->$item) == 0)
                    {
                        echo "REMOVING: "; var_dump($current->$item); echo "<br>";
                        /* foreach ($current as $key)
                            unset($current->$key); */
                        unset($current->$item);
                    }    
                    /* $ind = 0;
                    foreach ($current->$item as $son)
                    {
                        echo "checking: "; var_dump($son); echo "<br>";
                        if (!isset($son->$child))
                        {
                            echo "REMOVING: "; var_dump($current->$item[$ind]); echo "<br>";
                            //foreach ($current as $key)
                            //    unset($current->$key);
                            array_splice($current->$item, $ind, 1);
                            //dd($collection);
                        }
                        ++$ind;
                    } */
                    
    
                }
                ++$index;
            }
            
        }
        else # Removing items from collection's child
        {
            echo "Removing items from collection's children<br>";
            if (isset($collection))
            {
                foreach ($collection as $child)
                {
                    foreach ($child->$parent as $current)
                    {
                        //echo "checking: "; var_dump($current); echo "<br>";
                        if (isset($current->$item))
                        {
                            //echo "<br>found item: "; var_dump($current->$item); echo "<br>";
                            if (count($current->$item) == 0)
                            {
                                echo "REMOVING CHILD: "; var_dump($current->$item); echo "<br>";
                                /* foreach ($current as $key)
                                    unset($current->$key); */
                                unset($current->$item);
                            }            
                        }
                        ++$index;
                    }
                }
            }
            
        }

        return $collection;
    }

    
}