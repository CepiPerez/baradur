<?php

Class Collection extends arrayObject
{
    protected static $_macros = array();

    protected $_parent = 'stdClass';

    protected $pagination = null;

    protected $items = array();

    /**
     * Creates a new Collection\
     * Associates it with a classname if defined
     * (only needed if you need to call relationships)
     * 
     * @param string $classname
     */
    public function __construct($data=null)
    {
        if ($data) $this->collect($data);

    }

    public function __call($method, $parameters)
    {
        if (isset(self::$_macros[$method]))
        {
            $class = self::$_macros[$method];
            
            if (is_closure($class))
            {
                list($c, $m) = getCallbackFromString($class);
                $class = new $c();
            }
            elseif (class_exists($class))
            {
                $class = new $class;
                $m = '__invoke';
            }

            $class->collect($this);
            return executeCallback($class, $m, $parameters, $class);
        }
        throw new Exception("Method $method does not exist");
    }

    public function __paramsToArray()
    {
        $params = array();

        /* foreach ($this as $key => $val)
        {
            if ($key!='_macros' && $key!='grammar')
                $params[$key] = $val;
        } */

        return $params;
    }


    public function getParent()
    {
        return $this->_parent;
    }

    public function setParent($parent)
    {
        $this->_parent = $parent;
        return $this;
    }

    /**
     * Returns collection as array
     * 
     * @return array
     */
    public function toArray($data=null)
    {
        if (!isset($data)) $data = $this;
        
        return Helpers::toArray($data);
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
            $str = http_build_query($params);

            $this->pagination->query = $str;
            
            if (isset($this->pagination->first))
                $this->pagination->first = $str . '&' . $this->pagination->first;

            if (isset($this->pagination->previous))
                $this->pagination->previous = $str . '&' . $this->pagination->previous;

            if (isset($this->pagination->next))
                $this->pagination->next = $str . '&' . $this->pagination->next;

            if (isset($this->pagination->last))
                $this->pagination->last = $str . '&' . $this->pagination->last;
        }

        return $this;
    }

    /**
     * Removes and returns the first item from the collection
     * 
     * @return Model|null
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
     * @return Model|null
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
            if ($item instanceof Model)
            {
                $obj->$key = $val;
                $obj->$key->__name = get_class($val);
            }
            elseif (is_object($val))
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
        if (!isset($parent)) $parent = $this->_parent;

        $col = new Collection(); //collectWithParent(null, $parent);
        
        foreach ($collection as $k => $item)
        {
            if ($item instanceof Model)
            {
                $col[$k] = $item;
                $col[$k]->__name = get_class($item);
            }
            elseif (is_object($item))
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
            if ($val instanceof Model)
            {
                $obj->$key = $val;
            }
            elseif (is_object($val))
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
    public function collect($data) //, $parent='stdClass')
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
                $this->pagination = $pagination;
            }
            elseif ($item instanceof Model)
            {
                $this[$k] = $item;
            }
            elseif (is_object($item))
            {
                $this[$k] = $this->getObjectItemsForCollect($item);
            }
            elseif ($k!=='__name')
            {
                $this[$k] = $item; //is_array($item)? $this->arrayToObject($item) : $item;
            }
        }

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
        if (!is_closure($callback)) {
            throw new Exception('Invalid callback');
        }

        $res = new Collection(); //collectWithParent(null, $this->_parent);
        
        list($class, $method, $params) = getCallbackFromString($callback);
        array_shift($params);

        foreach ($this as $record) {
            $res[] = executeCallback($class, $method, array_merge(array($record), $params), null);
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
        if (!is_closure($callback)) {
            throw new Exception('Invalid callback');
        }

        $res = array();
        
        list($class, $method, $params) = getCallbackFromString($callback);

        foreach ($this as $record) {
            $res[] = executeCallback($class, $method, array_merge(array($record), $params), $this);
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
        if (!is_closure($callback)) {
            throw new Exception('Invalid callback');
        }

        $res = new Collection(); //collectWithParent(null, $this->_parent);
        
        list($class, $method, $params) = getCallbackFromString($callback);

        foreach ($this as $record)
        {
            if(executeCallback($class, $method, array_merge(array($record), $params), $this))
            {
                $res[] = $record;
            }
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
    public function contains($key, $operator = null, $value = null)
    {
        if (!is_closure($key))
        {
            if (!$value) {
                $value = $operator? $operator : $key;
                $operator = '==';
            }

            return $this->where($key, $operator, $value)->count() > 0;
        }

        $res = false;
        
        list($class, $method, $params) = getCallbackFromString($key);

        foreach ($this as $record)
        {
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
     * @return array
     */
    public function partition($callback)
    {
        if (!is_closure($callback)) {
            throw new Exception('Invalid callback');
        }

        $res1 = new Collection(); //collectWithParent(null, $this->_parent);
        $res2 = new Collection(); //collectWithParent(null, $this->_parent);
        
        list($class, $method, $params) = getCallbackFromString($callback);

        foreach ($this as $record)
        {
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
     * Diff the collection with the given items.
     *
     * @return Collection
     */
    public function diff($items)
    {
        if (empty($items)) {
            return $this;
        }

        $diff = new Collection;

        $dictionary = $this->getDictionary();
        $items = $this->getDictionary($items);
        
        foreach (array_keys($dictionary) as $item) {
            if (!isset($items[$item])) {
                $diff[] = $dictionary[$item];
            }
        }
        return $diff;
    }

    /**
     * Execute a callback over each item.
     *
     * @return Collection
     */
    public function each($callback)
    {
        if (!is_closure($callback)) {
            throw new Exception('Invalid callback');
        }
        
        list($class, $method) = getCallbackFromString($callback);

        foreach ($this as $key => $value) {
            $result = executeCallback($class, $method, array($value, $key), null);

            if ($result === false) {
                break;
            }
        }
        
        return $this;
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * @return Collection
     */
    public function except($keys)
    {
        $keys = is_array($keys)? $keys : func_get_args();

        $dictionary = Arr::except($this->getDictionary(), $keys);

        return collect(array_values($dictionary));
    }

    /**
     * Chunks the collection
     * Check Laravel documentation
     *
     * @param  $value
     * @return array
     */
    public function chunk($value)
    {
        $result = array();
        $col = new Collection; //collectWithParent(null, $this->_parent);
        
        foreach ($this as $record)
        {
            if ($col->count()==$value)
            {
                $result[] = $col;
                $col = new Collection;
            }
            $col[] = $record;
        }
        if ($col->count()>0)
        {
            $result[] = $col;
        }

        return $result;
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
        if (!is_closure($callback)) {
            throw new Exception('Invalid callback');
        }

        $res = new Collection(); //collectWithParent(null, $this->_parent);

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
     * @return array
     */
    public function all()
    {
        $res = array();

        foreach ($this as $key => $val) {
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
        $res = new Collection(); //collectWithParent(null, $this->_parent);
        $count = 0;
        
        foreach ($this as $record)
        {
            $res[] = $record;
            ++$count;
        }
        
        return $res;
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() == 0;
    }

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return ! $this->isEmpty();
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

        $res = new Collection(); //collectWithParent(null, $this->_parent);

        foreach ($this as $record)
        {
            $retrieved = is_object($record)? $record->$key : 
                (is_array($record) ? $record[$key] : $record);

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
        $res = new Collection(); //collectWithParent(null, $this->_parent);
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
        $res = new Collection(); //collectWithParent(null, $this->_parent);
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
        $res = new Collection(); //collectWithParent(null, $this->_parent);
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
        $res = new Collection(); //collectWithParent(null, $this->_parent);
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
        $res = new Collection(); //collectWithParent(null, $this->_parent);
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
        $array = range(0, $this->count()-1);
        
        shuffle($array);

        $res = new Collection(); //collectWithParent(null, $this->_parent);

        foreach ($array as $a) {
            $res->append($this[$a]);
        }

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
        $res = new Collection(); //collectWithParent(null, $this->_parent);
        
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
        $res = new Collection(); //collectWithParent(null, $this->_parent);
        
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
        $res = new Collection(); //collectWithParent(null, $this->_parent);
        
        foreach ($this as $record)
        {
            if ($record instanceof $type)
                $res[] = $record;
        }

        return $res;
    }

    public function modelKeys()
    {
        $keys = $this->first()->getKeyName();
        
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

        $array = array();
        foreach ($this as $record)
        {
            if (is_object($record))
            {
                if ($key) $array[$record->$key] = $record->$value;
                else 
                {
                    $val = $record->$value;
                    if (!in_array($val, $array) && $val)
                        $array[] = $extra? $val->$extra : $val;
                }
            }
            else
            {
                if ($key) $res[$record[$key]] = $record[$value];
                else 
                {
                    if (!in_array($record[$value], $array))
                        $array[] = $extra? $record[$value][$extra] : $record[$value];
                }
            }
        }

        $result = new Collection(); //collectWithParent(null, $this->_parent);

        foreach ($array as $key => $val)
            $result[$key] = $val;

        return $result;
    }

    /**
     * Retrieves only specified keys in collection
     * 
     * @return Collection
     */
    public function keys($keys)
    {        
        $result = new Collection(); //collectWithParent(null, $this->_parent);
        
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
                $result[] = $new;
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
                $result[] = $new;
            }
        }
        return $result;
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * @return Collection
     */
    public function keyBy($keyBy)
    {
        $results = array();

        foreach ($this as $item) {
            $key = is_array($item) ? $item[$keyBy] : $item->$keyBy;
            $results[$key] = $item;
        }

        return collect($results);
    }


    /**
     * Sets the given item in the collection
     * 
     */
    public function put($item)
    {
        $this->append($item);
    }

    /**
     * Removes and returns an item from the collection 
     * by its index or its key/value pair
     * 
     * @return mixed
     */
    public function pull($index, $value=null)
    {
        if (!is_integer($index))
        {
            $ind = -1;
            $count = 0;

            foreach ($this as $record)
            {
                if (isset($record->$index) && $record->$index==$value)
                {
                    $ind = $count;
                    break;
                }
                ++$count;
            }

            if ($ind==-1) 
                return null;

            $index = $ind;
        }
        
        if ($index > $this->count()-1)
            return null;
        
        $res = $this[$index];
        $this->offsetUnset2($index);
        
        return $res;
    }

    private function offsetUnset2($offset){
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
     * @return mixed
     */
    public function find($key, $value)
    {
        foreach ($this as $record)
        {
            if ($record->getAttribute($key) && $record->getAttribute($key)==$value)
            {
                return $record;
            }
        }
    }

    /**
     * Skip the first {$count} items.
     *
     * @return Collection
     */
    public function skip($count)
    {
        $total = 0;

        $res = new Collection(); //collectWithParent(null, $this->_parent);
        
        foreach ($this as $record)
        {
            if ($total >= $count)
                $res[] = $record;
            
            $total++;
        }

        return $res;
    }

    /**
     * Get a dictionary keyed by primary keys.
     *
     * @return array
     */
    public function getDictionary($items = null)
    {
        $items = is_null($items) ? $this : $items;

        $dictionary = array();

        foreach ($items as $item)
        {
            $key = $item instanceof Model ? $item->getKey() : (
                $item instanceof RouteItem ? $item->url : (
                    is_array($item) ? reset(array_values($item)) : $item
                ));

            $dictionary[$key] = $item;
        }

        return $dictionary;
    }

    private function _unique($key, $strict = false)
    {
        if ($strict === false) {
            return collect(array_unique((array)$this, SORT_REGULAR));
        }

        /* $callback = $this->valueRetriever($key);

        $exists = [];

        return $this->reject(function ($item, $key) use ($callback, $strict, &$exists) {
            if (in_array($id = $callback($item, $key), $exists, $strict)) {
                return true;
            }

            $exists[] = $id;
        }); */
    }

    /**
     * Return only unique items from the collection.
     *
     * @return Collection
     */
    public function unique($key = null, $strict = false)
    {
        if (!is_null($key))
        {
            return $this->_unique($key, $strict);
        }

        return collect(array_values($this->getDictionary()));
    }

    /**
     * Sort the collection in descending order using the given callback.
     *
     * @return Collection
     */
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->sortBy($callback, $options, true);
    }

    /**
     * Sort the collection using the given key
     *
     * @return Collection
     */
    public function sortBy($keyBy, $options = SORT_REGULAR, $descending = false)
    {
        $array = array();
        $results = array();

        foreach ($this as $item) {
            $key = is_array($item) ? $item[$keyBy] : $item->$keyBy;
            $array[$key] = $item;
        }
        
        $array = array_sort($array, $keyBy, $descending ? SORT_DESC : SORT_ASC);

        foreach (array_keys($array) as $key) {
            $results[] = $array[$key];
        }

        return collect($results);
    }

    /**
     * Intersect the collection with the given items.
     *
     * @return Collection
     */
    public function intersect($items)
    {
        $intersect = new Collection;

        if (empty($items)) {
            return $intersect;
        }

        $dictionary = $this->getDictionary();
        $items = $this->getDictionary($items);
        
        foreach (array_keys($items) as $item) {
            if (isset($dictionary[$item])) {
                $intersect[] = $dictionary[$item];
            }
        }
        return $intersect;
    }  

    private function verifedContenType()
    {
        $types = array();
        foreach ($this as $item)
        {
            if (is_object($item) && !in_array(get_class($item), $types)) {
                $types[] = get_class($item); 
            }
        }
        
        if (count($types)==0) {
            throw new Exception("This collection doesn't have models");
        }

        if (count($types)>1) {
            throw new Exception("This collection have more than one model type");
        }

        return reset($types);
    }

    /**
     * Adds records from a sub-query inside the current records\
     * Check Laravel documentation
     * 
     * @return Collection
     */
    public function load($relations)
    {
        $class = new Builder($this->verifedContenType());
        $class->_collection = $this;
        $class->load( is_string($relations) ? func_get_args() : $relations );
        return $this;
    }

    /**
     * Eager load relation's column aggregations on the model.
     *
     * @return Collection
     */
    public function loadAggregate($relations, $column, $function = null)
    {
        $class = new Builder($this->verifedContenType());

        $relations = is_string($relations) ? array($relations) : $relations;

        foreach ($relations as $relation)
        {
            $class->_collection = $this;
            $class->loadAggregate($relation, $column, $function);
        }

        return $this;
    }

    /**
     * Eager load relation counts on the model.
     *
     * @return Collection
     */
    public function loadCount($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;
        return $this->loadAggregate($relations, '*', 'count');
    }

    /**
     * Eager load relation max column values on the model.
     *
     * @return Collection
     */
    public function loadMax($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'max');
    }

    /**
     * Eager load relation min column values on the model.
     *
     * @return Collection
     */
    public function loadMin($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'min');
    }

    /**
     * Eager load relation's column summations on the model.
     *
     * @return Collection
     */
    public function loadSum($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'sum');
    }

    /**
     * Eager load relation average column values on the model.
     *
     * @return Collection
     */
    public function loadAvg($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'avg');
    }

    /**
     * Eager load related model existence values on the model.
     *
     * @return Collection
     */
    public function loadExists($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;
        
        return $this->loadAggregate($relations, '*', 'exists');
    }


    /**
     * Make the given, typically visible, attributes hidden across the entire collection.
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function makeHidden($attributes)
    {
        $attributes = is_array($attributes)? $attributes : func_get_args();

        foreach ($this as $model)
        {
            if ($model instanceof Model) $model->makeHidden($attributes);
        }

        return $this;
    }

    /**
     * Make the given, typically hidden, attributes visible across the entire collection.
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function makeVisible($attributes)
    {
        $attributes = is_array($attributes)? $attributes : func_get_args();

        foreach ($this as $model)
        {
            if ($model instanceof Model) $model->makeVisible($attributes);
        }

        return $this;
    }

    /**
     * Set the visible attributes across the entire collection.
     *
     * @param  array $visible
     * @return $this
     */
    public function setVisible($visible)
    {
        foreach ($this as $model)
        {
            if ($model instanceof Model) $model->setVisible($visible);
        }

        return $this;
    }

    /**
     * Set the hidden attributes across the entire collection.
     *
     * @param  array $hidden
     * @return $this
     */
    public function setHidden($hidden)
    {
        $hidden = is_array($hidden)? $hidden : func_get_args();

        foreach ($this as $model)
        {
            if ($model instanceof Model) $model->setHidden($hidden);
        }

        return $this;
    }

    public static function macro($name, $function)
    {
        self::$_macros[$name] = $function;
    }

    public static function hasMacro($name)
    {
        return array_key_exists($name, self::$_macros);
    }

    public static function getMacros()
    {
        return self::$_macros;
    }

}