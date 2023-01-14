<?php

Class Collection extends arrayObject
{

    protected $_parent = 'Model';
    protected $_hidden = array();

    protected $pagination = null;

    protected $items = array();


    /**
     * Creates a new Collection\
     * Associates it with a classname if defined
     * (only needed if you need to call relationships)
     * 
     * @param string $classname
     */
    public function __construct($classname='stdClass', $hidden=array())
    {
        $this->_parent = $classname;
        $this->_hidden = $hidden;
    }

    public function getParent()
    {
        return $this->_parent;
    }


    public function arrayToObject($array)
    {

        $obj = new $this->_parent;

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

        //dump($data);

        /* $model = new self::$_parent;
        $data = CastHelper::processCasts(
            array_merge($data->attributes, $data->append_attributes, $data->relations),
            $model,
            true
        ); */

        /* if ($data instanceof Model)
        {
            if (get_class($data) == 'DB')
            {
                $data = CastHelper::processCasts(
                    array_merge($data->attributes),
                    $data,
                    true
                );
            }
            else
            {
                $data = CastHelper::processCasts(
                    array_merge($data->attributes, $data->append_attributes, $data->relations),
                    $data,
                    true
                );
            }
        } */

        $res = array();
        foreach ($data as $key => $val)
        {
            if ($val instanceof Collection)
            {
                $res[$key] = $val->toArray();
            }
            /* elseif ($val instanceof Model)
            {
                $res[$key] = $this->_toArray($val);
            } */
            elseif (is_array($val) || is_object($val))
            {
                $res[$key] = $this->_toArray($val);
            }
            else
            {
                $res[$key] = $val;
            }
        }

        return $res;
    }

    private function _toArray($model)
    {
        //dump($model);

        /* if (get_class($model) == 'DB')
        {
            $model = CastHelper::processCasts(
                array_merge($model->attributes),
                $model,
                true
            );
        }
        else
        {
            $model = CastHelper::processCasts(
                array_merge($model->attributes, $model->append_attributes, $model->relations),
                $model,
                true
            );
        } */

        if ($model instanceof Model && get_class($model) == 'DB')
        {
            $model = CastHelper::processCasts(
                array_merge($model->getAttributes()),
                $model,
                true
            );
        }
        elseif ($model instanceof Model)
        {
            $model = CastHelper::processCasts(
                array_merge($model->getAttributes(), $model->getAppends(), $model->getRelations()),
                $model,
                true
            );
        }

        $arr = array();
        foreach ($model as $key => $val)
        {
            if (!in_array($key, $this->_hidden))
            {
                if ($val instanceof Collection)
                {
                    $arr[$key] = $val->toArray();
                }
                /* elseif ($val instanceof Model)
                {
                    $arr[$key] = $this->_toArray($val);
                } */
                elseif (is_array($val) || is_object($val))
                {
                    $arr[$key] = $this->_toArray($val);
                }
                else
                {
                    $arr[$key] = $val;
                }
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


    public function getPagination()
    {
        return $this->pagination;
    }

    public function setPagination($pagination)
    {
        $this->pagination = $pagination;
    }

    public function hasPagination()
    {
        return isset($this->pagination);
    }

    /**
     * Builds pagination links in View
     * 
     * @return string
     */
    public function links()
    {
        if ($this->pagination->meta['last_page']==1) return null;

        Paginator::setPagination($this->pagination);

        if (Paginator::style()!='tailwind') 
            return View::loadTemplate('layouts/pagination-bootstrap4', array('paginator' => Paginator::pagination()));

        return View::loadTemplate('layouts/pagination-tailwind', array('paginator' => Paginator::pagination()));
    }

    public function getPaginator()
    {
        $this->appends(request()->query());

        foreach ($this->pagination as $key => $val)
        {
            if (isset($val) && $key!='meta') 
                $this->pagination->$key = $this->pagination->meta['path'] . '?' . $val;
        }
    
        return $this->pagination;
    }

    public function setPaginator($pagination)
    {
        $this->pagination = $pagination;
    }

    public function currentPage()
    {
        if (!$this->pagination) return null;

        return $this->pagination->currentPage();
    }

    public function previousPageUrl()
    {
        if (!$this->pagination) return null;

        return $this->pagination->previousPageUrl();
    }

    public function nextPageUrl()
    {
        if (!$this->pagination) return null;

        return $this->pagination->nextPageUrl();
    }

    public function firstItem()
    {
        if (!$this->pagination) return null;

        return $this->pagination->firstItem();
    }

    public function lastItem()
    {
        if (!$this->pagination) return null;

        return $this->pagination->lastItem();
    }

    public function hasMorePages()
    {
        if (!$this->pagination) return null;

        return $this->pagination->hasMorePages();
    }

    public function url()
    {
        if (!$this->pagination) return null;

        return $this->pagination->url();
    }

    /**
     * Adds parameters to pagination links
     * 
     * @return Collection
     */
    public function appends($params=array())
    {
        if (!isset($this->pagination)) return $this;

        unset($params['ruta']);
        unset($params['p']);

        

        if (count($params)>0)
        {
            $str = array();
            foreach ($params as $key => $val)
                $str[] = $key.'='.$val;

            $this->pagination->query = implode('&', $str);
            
            if (isset($this->pagination->first))
                $this->pagination->first = implode('&', $str) . '&' . $this->pagination->first;

            if (isset($this->pagination->previous))
                $this->pagination->previous = implode('&', $str) . '&' . $this->pagination->previous;

            if (isset($this->pagination->next))
                $this->pagination->next = implode('&', $str) . '&' . $this->pagination->next;

            if (isset($this->pagination->last))
                $this->pagination->last = implode('&', $str) . '&' . $this->pagination->last;

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
     * @return Model|mixed
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
    public function duplicate($collection=null, $parent=null, $hidden=null)
    {
        if (!isset($collection)) $collection = $this;
        if (!isset($parent)) $parent = $this->_parent;
        if (!isset($hidden)) $hidden = $this->_hidden;

        $col = new Collection($parent, $hidden);
        
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
        $type = get_class($item);

        $obj = new $type; //StdClass;
    
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
                $type = get_class($item);
                $pagination = new $type; //stdClass();
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
            elseif ($k!=='__name')
            {
                //$col[] = $item;
                $this[$k] = $item; //is_array($item)? $this->arrayToObject($item) : $item;
            }
        }
        //return $col;
        return $this;
    }


    /**
     * Run a map over each of the items.
     * Check Laravel documentation
     *
     * @param  $callback
     * @return Collection
     */
    public function map($callback)
    {
        $res = new Collection($this->_parent, $this->_hidden);
        
        list($class, $method, $params) = getCallbackFromString($callback);

        foreach ($this as $record)
        {
            $res[] = executeCallback($class, $method, array_merge(array($record), $params), $this);
            //$res[] = call_user_func_array(array($class, $method), array_merge(array($record), $params));
        }
        return $res;
    }

    /**
     * Implode all items into a string
     * Check Laravel documentation
     *
     * @param  $callback
     * @param  $glue
     * @return string
     */
    public function implode($callback, $glue)
    {
        $res = array();
        
        list($class, $method, $params) = getCallbackFromString($callback);

        foreach ($this as $record)
        {
            $res[] = executeCallback($class, $method, array_merge(array($record), $params), $this);
            //$res[] = call_user_func_array(array($class, $method), array_merge(array($record), $params));
        }
        return implode($glue, $res);
    }

    /**
     * Filter items based on callback
     * Check Laravel documentation
     *
     * @param  $callback
     * @return Collection
     */
    public function filter($callback)
    {
        $res = new Collection($this->_parent, $this->_hidden);
        
        list($class, $method, $params) = getCallbackFromString($callback);

        foreach ($this as $record)
        {
            if(executeCallback($class, $method, array_merge(array($record), $params), $this))
                $res[] = $record;

            //if (call_user_func_array(array($class, $method), array_merge(array($record), $params)))
            //    $res[] = $record;
        }
        return $res;
    }

    /**
     * Check if collection contains callback
     * Check Laravel documentation
     *
     * @param  $callback
     * @return bool
     */
    public function contains($callback)
    {
        if (strpos($callback, '@')==false)
        {
            return in_array($callback, (array)$this);
        }

        $res = false;
        
        list($class, $method, $params) = getCallbackFromString($callback);

        foreach ($this as $record)
        {
            //if (call_user_func_array(array($class, $method), array_merge(array($record), $params)))
            if(executeCallback($class, $method, array_merge(array($record), $params), $this))
            {
                $res = true;
                break;
            }
        }

        return $res;
    }


    /**
     * Divides the collection based on callback
     * Check Laravel documentation
     *
     * @param  $callback
     * @return bool
     */
    public function partition($callback)
    {
        $res1 = new Collection($this->_parent, $this->_hidden);
        $res2 = new Collection($this->_parent, $this->_hidden);
        
        list($class, $method, $params) = getCallbackFromString($callback);

        foreach ($this as $record)
        {
            //if (call_user_func_array(array($class, $method), array_merge(array($record), $params)))
            if(executeCallback($class, $method, array_merge(array($record), $params), $this))
            {
                $res1[] = $record;
            }
            else
            {
                $res2[] = $record;
            }
        }

        return array($res1, $res2);
    }

    /**
     * Chunks the collection
     * Check Laravel documentation
     *
     * @param  $value
     * @return bool
     */
    public function chunk($value)
    {
        $arr = array();
        $res = new Collection($this->_parent, $this->_hidden);
        
        foreach ($this as $record)
        {
            if (count($arr)==$value)
            {
                $res[] = $arr;
                $arr = array();
            }
            $arr[] = $record;
        }
        if (count($arr)>0)
        {
            $res[] = $arr;
        }

        return $res;
    }


    /**
     * Run an associative map over each of the items.
     * Check Laravel documentation
     *
     * @param $callback
     * @return Collection
     */
    public function mapWithKeys($callback)
    {
        $res = new Collection($this->_parent, $this->_hidden);

        list($class, $method, $params) = getCallbackFromString($callback);

        foreach ($this as $record) {
            $assoc = executeCallback($class, $method, array_merge(array($record), $params), $this);
            //$assoc = call_user_func_array(array($class, $method), array_merge(array($record), $params));
            foreach ($assoc as $mapKey => $mapValue) {
                $res[$mapKey] = $mapValue;
            }
        }

        return $res;
    }




    /**
     * Returns the underlying array represented by the collection
     * 
     * @return Collection
     */
    public function all()
    {
        $res = array();
        foreach ($this as $key => $val)
        {
            $res[$key] = $val;
        }
        return $res;
    }


    /**
     * Returns a new collection with the keys reset to consecutive integers
     * 
     * @return Collection
     */
    public function values()
    {
        $res = new Collection($this->_parent, $this->_hidden);
        $count = 0;
        foreach ($this as $record)
        {
            $res[] = $record;
            ++$count;
        }
        return $res;
    }


    
    /* public function contains($value)
    {
        return in_array($value, (array)$this);
    } */

    public function whereStrict($key, $value)
    {
        return $this->where($key, '===', $value);
    }

    /**
     * Filters the collection by a given key/value pair
     * 
     * @return Collection
     */
    public function where($key, $operator='==', $value=true)
    {
        if (func_num_args() === 1)
        {
            $value = true;
            $operator = '=';
        }

        if (func_num_args() === 2)
        {
            $value = $operator;
            $operator = '=';
        }

        $res = new Collection($this->_parent, $this->_hidden);

        foreach ($this as $record)
        {
            $retrieved = $record->$key;

            if (is_string($value) && is_string($retrieved))
            {
                $retrieved = trim($retrieved);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':  if ($retrieved == $value) $res[] = $record; break;
                case '!=':
                case '<>':  if ($retrieved != $value) $res[] = $record; break;
                case '<':   if ($retrieved < $value) $res[] = $record; break;
                case '>':   if ($retrieved > $value) $res[] = $record; break;
                case '<=':  if ($retrieved <= $value) $res[] = $record; break;
                case '>=':  if ($retrieved >= $value) $res[] = $record; break;
                case '===': if ($retrieved === $value) $res[] = $record; break;
                case '!==': if ($retrieved !== $value) $res[] = $record; break;
                //case '<=>': if ($retrieved <=> $value) $res[] = $record; break;
            }

            /* if ($retrieved==$value)
            {
                $res[] = $record;
            } */

        }
        return $res;
    }

    private function insertItemInCollection($item, $collection)
    {
        $collection[] = $item;
    }

    /**
     * Filters the collection without the given key/value pair
     * 
     * @return Collection
     */
    public function whereNot($key, $value)
    {
        $res = new Collection($this->_parent, $this->_hidden);
        foreach ($this as $record)
        {
            if (isset($record->$key) && $record->$key!=$value)
                $res[] = $record;
        }
        return $res;
    }

    /**
     * Filters the collection where given key is null
     * 
     * @return Collection
     */
    public function whereNull($key)
    {
        $res = new Collection($this->_parent, $this->_hidden);
        foreach ($this as $record)
        {
            //dump($record->$key);
            if (!isset($record->$key))
                $res[] = $record;
        }
        return $res;
    }

    /**
     * Filters the collection where given key exists
     * 
     * @return Collection
     */
    public function whereNotNull($key)
    {
        $res = new Collection($this->_parent, $this->_hidden);
        foreach ($this as $record)
        {
            //dump($record->$key);
            if (isset($record->$key))
                $res[] = $record;
        }
        return $res;
    }

    /**
     * Filter items by the given key value pair.
     * 
     * @return Collection
     */
    public function whereIn($key, $values, $strict=false)
    {
        $res = new Collection($this->_parent, $this->_hidden);
        foreach ($this as $record)
        {
            if (in_array($record->$key, $values, $strict))
                $res[] = $record;
        }
        return $res;
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @return Collection
     */
    public function whereInStrict($key, $values)
    {
        return $this->whereIn($key, $values, true);
    }

    /**
     * Filter items by the given key value pair.
     * 
     * @return Collection
     */
    public function whereNotIn($key, $values, $strict=false)
    {
        $res = new Collection($this->_parent, $this->_hidden);
        foreach ($this as $record)
        {
            if (!in_array($record->$key, $values, $strict))
                $res[] = $record;
        }
        return $res;
    }

    /**
     * Shuffle the items in the collection.
     *
     * @return Collection
     */
    public function shuffle()
    {
        $array = (array) $this;
        
        shuffle($array);

        $res = new Collection($this->_parent, $this->_hidden);

        $res->collect($array, $this->_parent);

        return $res;
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @return Collection
     */
    public function whereNotInStrict($key, $values)
    {
        return $this->whereNotIn($key, $values, true);
    }

    /**
     * Filters the collection by a given key/value pair\
     * Returns elements containing that value
     * 
     * @return Collection
     */
    public function whereContains($key, $value)
    {
        $res = new Collection($this->_parent, $this->_hidden);
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
        $res = new Collection($this->_parent, $this->_hidden);
        foreach ($this as $record)
        {
            if (strpos($record->$key, $value)==false && substr($record->$key, 0, strlen($value))!=$value)
                $res[] = $record;
        }
        return $res;
    }

    /**
     * Filter the items, removing any items that don't match the given type(s).
     *
     * @return Collection
     */
    public function whereInstanceOf($type)
    {
        $res = new Collection($this->_parent, $this->_hidden);
        foreach ($this as $record)
        {
            if ($record instanceof $type)
                $res[] = $record;
        }
        return $res;
    }



    public function modelKeys()
    {
        $keys = $this->first()->getPrimaryKey();
        return $this->pluck($keys)->toArray();
    }

    /**
     * Retrieves all of the values for a given key\
     * You may also specify how you wish the resulting collection to be keyed
     * 
     * @return Collection
     */
    public function pluck($value, $key=null)
    {
        $extra = null;
        if (strpos($value, '.')!==false)
        {
            list($value, $extra) = explode('.', $value);
        }

        $res = array();
        foreach ($this as $record)
        {
            if (is_object($record))
            {
                if ($key) $res[$record->$key] = $record->$value;
                else 
                {
                    $val = $record->$value;
                    if (!in_array($val, $res) && $val)
                        $res[] = $extra? $val->$extra : $val;
                }
            }
            else
            {
                if ($key) $res[$record[$key]] = $record[$value];
                else 
                {
                    if (!in_array($record[$value], $res))
                        $res[] = $extra? $record[$value][$extra] : $record[$value];
                }
            }
        }

        $final = new Collection($this->_parent, $this->_hidden);

        foreach ($res as $key => $val)
            $final[$key] = $val;

        return $final;
    }

    /**
     * Retrieves only specified keys in collection
     * 
     * @return Collection
     */
    public function keys($keys)
    {        
        $res = new Collection($this->_parent, $this->_hidden);
        foreach ($this as $record)
        {
            if ($record instanceof stdClass)
            {
                $new = $record;
                foreach ($new as $key => $val)
                {
                    if (!in_array($key, $keys))
                    {
                        unset($new->$key);
                    }
                }
                $res[] = $new;
            }
            else
            {
                $new = $record;
                foreach ($new->getAttributes() as $key => $val)
                {
                    if (!in_array($key, $keys))
                    {
                        $new->unsetAttribute($key);
                    }
                }
                $res[] = $new;
            }
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
            if ($record->getAttribute($key) && $record->getAttribute($key)==$value)
                return $record;
        }
    }

    /**
     * Skip the first {$count} items.
     *
     * @return Collection
     */
    public function skip($count)
    {
        /* if ($count > $this->count())
            $count = $this->count();

        foreach( range(0, $count-1) as $index )
        {
            $this->offsetUnset($index);
        }

        return $this; */

        $total = 0;

        $res = new Collection($this->_parent, $this->_hidden);
        
        foreach ($this as $record)
        {
            if ($total >= $count)
                $res[] = $record;
            
            $total++;
        }

        return $res;
    }

    /**
     * Adds records from a sub-query inside the current records\
     * Check Laravel documentation
     * 
     * @return Collection
     */
    public function load($relations)
    {
        /* $res = $this->getParent();
        $class = new $res;
        $class->getQuery()->_collection = $this;
        $class->load( is_string($relations) ? func_get_args() : $relations );
        return $this; */
        $class = Model::instance($this->getParent());
        $class->_collection = $this;
        $class->load( is_string($relations) ? func_get_args() : $relations );
        return $this;
    }
    

    /**
     * Eager load relation's column aggregations on the model.
     *
     * @return Model
     */
    public function loadAggregate($relations, $column, $function = null)
    {
        //$parent = $this->getParent();
        //$class = new $parent;
        $class = new Builder($this->getParent());

        $relations = is_string($relations) ? array($relations) : $relations;

        foreach ($relations as $relation)
        {
            $class->_collection = $this;
            $class->loadAggregate($relation, $column, $function);//->_collection;
        }

        return $this;
    }
  

    /**
     * Eager load relation counts on the model.
     *
     * @return Model
     */
    public function loadCount($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;
        return $this->loadAggregate($relations, '*', 'count');
    }

    /**
     * Eager load relation max column values on the model.
     *
     * @return Model
     */
    public function loadMax($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'max');
    }

    /**
     * Eager load relation min column values on the model.
     *
     * @return Model
     */
    public function loadMin($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'min');
    }

    /**
     * Eager load relation's column summations on the model.
     *
     * @return Model
     */
    public function loadSum($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'sum');
    }

    /**
     * Eager load relation average column values on the model.
     *
     * @return Model
     */
    public function loadAvg($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'avg');
    }

    /**
     * Eager load related model existence values on the model.
     *
     * @return Model
     */
    public function loadExists($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;
        return $this->loadAggregate($relations, '*', 'exists');
    }

    
}