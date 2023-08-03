<?php

Class Collection implements ArrayAccess, Iterator
{
    protected static $_macros = array();

    //protected $pagination = null;

    protected $items = array();

    private $position = 0;

    protected static $proxies = array(
        'average',
        'avg',
        'contains',
        'doesntContain',
        'each',
        'every',
        'filter',
        'first',
        'flatMap',
        'groupBy',
        'keyBy',
        'map',
        'max',
        'min',
        'partition',
        'reject',
        'skipUntil',
        'skipWhile',
        'some',
        'sortBy',
        'sortByDesc',
        'sum',
        'takeUntil',
        'takeWhile',
        'unique',
        'unless',
        'until',
        'when',
    );

    protected $_closure_method;
    protected $_closure_params;

    public static function make($data)
    {
        return new Collection($data);
    }

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
        global $_class_list;

        if (isset(self::$_macros[$method])) {

            $class = self::$_macros[$method];
            $params = array();

            if (is_closure($class)) {
                list($c, $m, $params) = getCallbackFromString($class);
                $class = new $c();
            } elseif (isset($_class_list[$class])) {
                $class = new $class;
                $m = '__invoke';
            }

            for ($i=0; $i<count($params); $i++) {
                if (count($parameters)>=$i) {
                    $params[$i] = $parameters[$i];
                }
            }

            $params[0] = $this->items;

            $class->collect($this->items);
            return executeCallback($class, $m, array_merge($parameters, $params), $class, false);
        }

        throw new BadMethodCallException("Method $method does not exist");
    }

    public function __get($key)
    {
        if (! in_array($key, self::$proxies)) {
            throw new Exception("Property [{$key}] does not exist on this collection instance.");
        }

        return new HigherOrderCollectionProxy($this, $key);
    }

    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset) 
    {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    public function offsetSet($offset, $value) 
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset) 
    {
        unset($this->items[$offset]);
    }

    public function current()
    {
        return $this->items[$this->position];
    }
    
    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->position++;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return null !== $this->items[$this->position];
    }

    public function __closure($value)
    {
        return call_user_func_array(array($value, $this->_closure_method), $this->_closure_params);
    }

    public function __setClosure($method, $parameters)
    {
        $this->_closure_method = $method;
        $this->_closure_params = $parameters;
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

    /**
     * Dump the items.
     *
     * @return $this
     */
    public function dump()
    {
        dump($this);

        return $this;
    }

    /**
     * Dump the items and end the script.
     *
     * @return never
     */
    public function dd()
    {
        dd($this);
    }

    /**
     * Remove an item from the collection by key.
     *
     * @param  TKey|array<array-key, TKey>  $keys
     * @return $this
     */
    public function forget($keys)
    {
        foreach ((array) $keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    /**
     * Get an item from the collection by key or add it to collection if it does not exist.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function getOrPut($key, $value)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        $this->offsetSet($key, $value = value($value));

        return $value;
    }

    /**
     * Returns collection as array
     * 
     * @return array
     */
    public function toArray($data=null)
    {
        if (!isset($data)) {
            $data = $this->items;
        }

        return Helpers::toArray($data);
    }

    public function toArrayObject()
    {
        $arr = array();
        
        foreach ($this->items as $obj) {
            $arr[] = $obj;
        }

        return $arr;
    }


    public function hasPagination()
    {
        return $this instanceof Paginator;
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    } 

    /**
     * Removes and returns the first item from the collection
     * 
     * @return Model|null
     */
    public function shift()
    {
        if ($this->count()==0) {
            return null;
        }

        return array_shift($this->items);
    }
    
    /**
     * Removes and returns the last item from the collection
     * 
     * @return Model|null
     */
    public function pop()
    {
        if ($this->count()==0) {
            return null;
        }

        return array_pop($this->items);
    }


    /**
     * Returns the first item from the collection
     * 
     * @return mixed
     */
    public function first($callback = null, $default = null)
    {
        if (is_closure($callback)) {

            list($class, $method) = getCallbackFromString($callback);

            foreach ($this->items as $key => $item) {
                if (executeCallback($class, $method, array($item, $key) )) {
                    return $item;
                }
            }

            return $default;
        }

        return $this->count()>0 ? $this->items[0] : $default;
    }

    /**
     * Get the first item in the collection but throw an exception if no matching items exist.
     *
     * @return mixed
     */
    public function firstOrFail($key = null, $operator = null, $value = null)
    {
        $res = new Collection();

        if (is_null($key) && $this->count() > 0) {
            $res[] = $this->first();
        } 
        elseif (!is_closure($key)) {
            if (func_num_args() === 1) {
                $value = true;
                $operator = '=';
            }

            if (func_num_args() === 2) {
                $value = $operator;
                $operator = '=';
            }
            
            $res = $this->where($key, $operator, $value);
        }
        else {
            list($class, $method) = getCallbackFromString($key);
     
            foreach ($this->items as $key => $item) {    
                if (executeCallback($class, $method, array($item, $key), $this)) {
                    $res[] = $item;
                    break;
                }
            }
        }

        if ($res->count() === 0) {
            throw new ItemNotFoundException("Item not found in Collection.");
        }

        return $res->first();

    }

    /**
     * Returns the last item from the collection
     * 
     * @return Model
     */
    public function last()
    {
        return $this->count()>0 ? end($this->items) : null;
    }

    /**
     * Fills the collection
     * 
     * @return Collection
     */
    public function collect($data)
    {
        $data = is_array($data) ? $data : array($data);

        $this->items = $data;

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
            throw new InvalidArgumentException('Invalid callback');
        }

        $res = new Collection();
        
        list($class, $method, $params, $names) = getCallbackFromString($callback);

        foreach ($this->items as $key => $item) {

            for ($i=0; $i < count($params); $i++) {
                if ($names[$i]=='key') $params[$i] = $key;
                if ($names[$i]=='value') $params[$i] = $item;
            }

            if (!in_array('value', $names)) {
                $params[0] = $item;
            }

            $res->items[] = executeCallback($class, $method, $params);
        }
        
        return $res;
    }

        /**
     * Run a grouping map over the items.
     * The callback should return an associative array with a single key/value pair.
     */
    public function mapToGroups($callback)
    {
        $groups = $this->mapToDictionary($callback);

        return $groups; //->map([$this, 'make']);
    }

    /**
     * Run a dictionary map over the items.
     * The callback should return an associative array with a single key/value pair.
     */
    public function mapToDictionary($callback)
    {
        if (!is_closure($callback)) {
            throw new InvalidArgumentException('Invalid callback');
        }

        $dictionary = array();

        list($class, $method, $params, $names) = getCallbackFromString($callback);

        foreach ($this->items as $key => $item) {

            for ($i=0; $i < count($params); $i++) {
                if ($names[$i]=='key') $params[$i] = $key;
                if ($names[$i]=='value') $params[$i] = $item;
            }

            if (!in_array('value', $names)) {
                $params[0] = $item;
            }

            $pair = executeCallback($class, $method, $params);

            $key = key($pair);

            $value = reset($pair);

            if (! isset($dictionary[$key])) {
                $dictionary[$key] = array();
            }

            $dictionary[$key][] = $value;
        }

        return collect($dictionary);
    }


    /**
     * Implode all items into a string
     * Check Laravel documentation
     *
     * @param  $callback
     * @param  $glue
     * @return string
     */
    public function implode($value, $glue = null)
    {
        if (is_closure($value)) {
            return implode($glue? $glue : '', $this->map($value)->all());
        }

        $first = $this->first();

        if (is_array($first) || (is_object($first) && ! $first instanceof Stringable)) {
            return implode($glue? $glue : '', $this->pluck($value)->all());
        }

        return implode($value? $value : '', $this->items);
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
            throw new InvalidArgumentException('Invalid callback');
        }

        $res = new Collection(); 
        
        list($class, $method, $params, $names) = getCallbackFromString($callback);

        foreach ($this->items as $key => $item) {

            for ($i=0; $i < count($params); $i++) {
                if ($names[$i]=='key') $params[$i] = $key;
                if ($names[$i]=='value') $params[$i] = $item;
            }

            if (!in_array('value', $names)) {
                $params[0] = $item;
            }

            if (executeCallback($class, $method, $params, $this)) {
                $res->items[] = $item;
            }
        }

        return $res;
    }

    /**
     * Alias for contains method
     * Check Laravel documentation
     *
     * @return bool
     */
    public function some($key, $operator = null, $value = null)
    {
        return $this->contains($key, $operator, $value);
    }

    /**
     * Check if collection contains callback
     * Check Laravel documentation
     *
     * @param mixed $key
     * @param mixed $operator
     * @param mixed $value
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        if (func_num_args()===1 && !is_closure($key)) {
            return in_array($key, $this->items);
        }

        if (!is_closure($key)) {
            if (!$value) {
                $value = $operator? $operator : $key;
                $operator = '==';
            }

            return $this->where($key, $operator, $value)->count() > 0;
        }

        $res = false;
        
        list($class, $method, $params, $names) = getCallbackFromString($key);

        foreach ($this->items as $key => $value) {

            for ($i=0; $i < count($params); $i++) {
                if ($names[$i]=='key') $params[$i] = $key;
                if ($names[$i]=='value') $params[$i] = $value;
            }

            if (!in_array('value', $names)) {
                $params[0] = $value;
            }

            if(executeCallback($class, $method, $params, $this)) {
                $res = true;
                break;
            }
        }

        return $res;
    }

    /**
     * Determine if an item exists, using strict comparison.
     *
     * @param mixed $key
     * @param mixed $value
     * @return bool
     */
    public function containsStrict($key, $value = null)
    {
        if (func_num_args() === 2) {
            return $this->contains($key, '===', $value);
        }

        return in_array($key, $this->items, !is_object($key));
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @return static
     */
    public function dot()
    {
        return new Collection(Arr::dot($this->all()));
    }

    /**
     * Convert a flatten "dot" notation array into an expanded array.
     *
     * @return static
     */
    public function undot()
    {
        return new Collection(Arr::undot($this->all()));
    }


    /**
     * Check if collection contains callback
     * Check Laravel documentation
     *
     * @param mixed $key
     * @param mixed $operator
     * @param mixed $value
     * @return bool
     */
    public function doesntContain($key, $operator = null, $value = null)
    {
        return ! $this->contains($key, $operator, $value);
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
            throw new InvalidArgumentException('Invalid callback');
        }

        $res1 = new Collection(); 
        $res2 = new Collection(); 
        
        list($class, $method) = getCallbackFromString($callback);

        foreach ($this as $record) {
            if(executeCallback($class, $method, array($record), $this)) {
                $res1->items[] = $record;
            } else {
                $res2->items[] = $record;
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
                $diff->items[] = $dictionary[$item];
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
            throw new InvalidArgumentException('Invalid callback');
        }
        
        list($class, $method, $params, $names) = getCallbackFromString($callback);

        foreach ($this->items as $key => $value) {

            for ($i=0; $i < count($params); $i++) {
                if ($names[$i]=='key') $params[$i] = $key;
                if ($names[$i]=='value') $params[$i] = $value;
            }

            if (!in_array('value', $names)) {
                $params[0] = $value;
            }

            $result = executeCallback($class, $method, $params, $this, false);

            if ($result === false) {
                break;
            }
        }
        
        return $this;
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
        $col = new Collection; 
        
        foreach ($this->items as $record) {
            if ($col->count()==$value) {
                $result[] = $col;
                $col = new Collection;
            }
            $col->items[] = $record;
        }

        if ($col->count()>0) {
            $result[] = $col;
        }

        return $result;
    }


    /**
     * Group an associative array by a field or using a callback.
     *
     * @return Collection
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        $results = array();

        list($class, $method, $params, $names) = getCallbackFromString($groupBy);

        foreach ($this->items as $key => $item) {

            if (is_closure($groupBy)) {

                for ($i=0; $i < count($params); $i++) {
                    if ($names[$i]=='key') $params[$i] = $key;
                    if ($names[$i]=='value') $params[$i] = $item;
                }
    
                if (!in_array('value', $names)) {
                    $params[0] = $item;
                }

                $res = executeCallback($class, $method, $params);

                $results[$res][] = $item;

            } else {
                if ($item->{$groupBy}) {
                    $results[$item->{$groupBy}][] = $item;
                }
            }
        }

        return collect($results);
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
            throw new InvalidArgumentException('Invalid callback');
        }

        $res = new Collection(); 

        list($class, $method, $params, $names) = getCallbackFromString($callback);

        foreach ($this->items as $key => $item) {

            for ($i=0; $i < count($params); $i++) {
                if ($names[$i]=='key') $params[$i] = $key;
                if ($names[$i]=='value') $params[$i] = $item;
            }

            if (!in_array('value', $names)) {
                $params[0] = $item;
            }

            $assoc = executeCallback($class, $method, $params, $this);

            foreach ($assoc as $mapKey => $mapValue) {
                $res->items[$mapKey] = $mapValue;
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
        return $this->items;
    }

    /**
     * Returns a new collection with the keys reset to consecutive integers
     * 
     * @return Collection
     */
    public function values()
    {
        $res = new Collection();
        
        foreach ($this->items as $record) {
            $res->items[] = $record;
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

    /**
     * Get an item from the collection by key.
     *
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->items)) {
            return collect($this->items[$key]);
        }

        return value($default);
    }


    public function whereStrict($key, $value)
    {
        return $this->where($key, '===', $value);
    }


    private function getItemValue($item, $key)
    {
        if (is_array($item)) {
            return $item[$key];
        }

        if ($item instanceof Model) {
            $attrs = $item->getAttributes();
            return $attrs[$key];
        }

        if (is_object($item)) {
            return $item->$key;
        }

        return $item;
    }


    /**
     * Filters the collection by a given key/value pair
     * 
     * @return Collection
     */
    public function where($key, $operator='==', $value=true)
    {
        if (func_num_args() === 1) {
            $value = true;
            $operator = '=';
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $value = Helpers::ensureValueIsNotObject($value);

        $res = new Collection();

        foreach ($this->items as $record)
        {
            $retrieved = $this->getItemValue($record, $key);

            if (is_string($value) && is_string($retrieved)) {
                $retrieved = trim($retrieved);
                $value = trim($value);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':  if ($retrieved == $value) $res->items[] = $record; break;
                case '!=':
                case '<>':  if ($retrieved != $value) $res->items[] = $record; break;
                case '<':   if ($retrieved < $value) $res->items[] = $record; break;
                case '>':   if ($retrieved > $value) $res->items[] = $record; break;
                case '<=':  if ($retrieved <= $value) $res->items[] = $record; break;
                case '>=':  if ($retrieved >= $value) $res->items[] = $record; break;
                case '===': if ($retrieved === $value) $res->items[] = $record; break;
                case '!==': if ($retrieved !== $value) $res->items[] = $record; break;
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
        $res = new Collection(); 

        foreach ($this->items as $record) {
            if (isset($record->$key) && $record->$key!=$value) {
                $res->items[] = $record;
            }
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
        $res = new Collection(); 
        
        foreach ($this->items as $record) {
            if (!isset($record->$key)) {
                $res->items[] = $record;
            }
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
        $res = new Collection(); 
        foreach ($this->items as $record)
        {
            if (isset($record->$key)) {
                $res->items[] = $record;
            }
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
        $res = new Collection(); 

        foreach ($this->items as $record) {
            if (in_array($record->$key, $values, $strict)) {
                $res->items[] = $record;
            }
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
        $res = new Collection(); 
        
        foreach ($this->items as $record) {
            if (!in_array($record->$key, $values, $strict)) {
                $res->items[] = $record;
            }
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

        $res = new Collection(); 

        foreach ($array as $a) {
            $res->items[] = $this[$a];
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
        $res = new Collection(); 
        
        foreach ($this->items as $record) {
            if (isset($record->$key) && $record->$key==$value) {
                $res->items[] = $record;
            }
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
        $res = new Collection(); 
        
        foreach ($this->items as $record) {
            if (strpos($record->$key, $value)==false && substr($record->$key, 0, strlen($value))!=$value) {
                $res->items[] = $record;
            }
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
        $res = new Collection(); 
        
        foreach ($this->items as $record) {
            if ($record instanceof $type) {
                $res->items[] = $record;
            }
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
        if (strpos($value, '.')!==false) {
            list($value, $extra) = explode('.', $value);
        }

        $array = array();
        
        foreach ($this->items as $record) {
            
            if (is_object($record)) {
                if ($key) {
                    $array[$record->$key] = $record->$value;
                } else {
                    $val = $record->$value;
                    if (!in_array($val, $array) && $val!==null) {
                        $array[] = $extra? $val->$extra : $val;
                    }
                }
            } else {
                if ($key) {
                    $res[$record[$key]] = $record[$value];
                } else {
                    if (!in_array($record[$value], $array)) {
                        $array[] = $extra? $record[$value][$extra] : $record[$value];
                    }
                }
            }
        }

        $result = new Collection(); 

        foreach ($array as $key => $val) {
            $result->items[$key] = $val;
        }

        return $result;
    }

    /**
     * Retrieves only specified keys in collection
     * 
     * @return Collection
     */
    public function keys($keys)
    {        
        $result = new Collection(); 
        
        foreach ($this->items as $record) {

            if ($record instanceof stdClass) {
                $new = $record;
                foreach ($new as $key => $val) {
                    if (!in_array($key, $keys)) {
                        unset($new->$key);
                    }
                }
                $result->items[] = $new;
            } else {
                $new = $record;
                foreach ($new->getAttributes() as $key => $val) {
                    if (!in_array($key, $keys)) {
                        $new->unsetAttribute($key);
                    }
                }
                $result->items[] = $new;
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

        foreach ($this->items as $item) {
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
        $this->items[] = $item;
    }

    /**
     * Push one or more items onto the end of the collection.
     *
     * @return $this
     */
    public function push($values)
    {
        $values = is_array($values) ? $values : array($values);

        foreach ($values as $value) {
            $this->items[] = $value;
        }

        return $this;
    }

    /**
     * Removes and returns an item from the collection 
     * by its index or its key/value pair
     * 
     * @return mixed
     */
    public function pull($index, $value=null)
    {
        if (!is_integer($index)) {
            $ind = -1;
            $count = 0;

            foreach ($this->items as $record) {
                if (isset($record->$index) && $record->$index==$value) {
                    $ind = $count;
                    break;
                }
                ++$count;
            }

            if ($ind==-1) {
                return null;
            }

            $index = $ind;
        }
        
        if ($index > $this->count()-1) {
            return null;
        }
        
        $res = $this->items[$index];

        array_splice($this->items, $index, 1);
        
        return $res;
    }

    /**
     * Determines if a given key exists in the collection
     * 
     * @return bool
     */
    public function has($key)
    {
        return isset($this->items[$key]);
    }

    /**
     * Returns an element by its key/value pair
     * 
     * @return mixed
     */
    public function find($key, $value)
    {
        foreach ($this->items as $record) {
            if ($record->getAttribute($key) && $record->getAttribute($key)==$value) {
                return $record;
            }
        }

        return null;
    }

    /**
     * Skip the first {$count} items.
     *
     * @return Collection
     */
    public function skip($count)
    {
        return $this->slice($count);
    }

    /**
     * Slice the underlying collection array.
     *
     * @param  int  $offset
     * @param  int|null  $length
     * @return Collection
     */
    public function slice($offset, $length = null)
    {
        $res = $length
            ? array_slice($this->items, $offset, $length, false)
            : array_slice($this->items, $offset);

        return collect($res);
    }

    /**
     * Skip items in the collection until the given condition is met.
     *
     * @return Collection
     */
    public function skipUntil($value)
    {
        $res = new Collection();
        
        $class = null;

        if (is_closure($value)) {
            list($class, $method) = getCallbackFromString($value);
        }

        $skip = true;

        foreach ($this->items as $item) {

            if ($class && $skip) {
                if (executeCallback($class, $method, array($item), $this)) {
                    $skip = false;
                }
            } elseif ($skip) {
                if ($item == $value) {
                    $skip = false;
                }
            }

            if (!$skip) {
                $res[] = $item;
            }
        }

        return $res;
    }

    /**
     * Skip items in the collection while the given condition is met.
     *
     * @return Collection
     */
    public function skipWhile($value)
    {
        $res = new Collection();
        
        $class = null;

        if (is_closure($value)) {
            list($class, $method) = getCallbackFromString($value);
        }

        $skip = true;

        foreach ($this->items as $item) {

            if ($class) {
                $skip = executeCallback($class, $method, array($item), $this);
            } else {
                $skip = $item == $value;
            }

            if (!$skip) {
                $res[] = $item;
            }
        }

        return $res;
    }

    /**
     * Take the first or last {$limit} items.
     *
     * @param  int  $limit
     * @return static
     */
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * Take items in the collection until the given condition is met.
     *
     * @return static
     */
    public function takeUntil($value)
    {
        $res = new Collection();
        
        $class = null;

        if (is_closure($value)) {
            list($class, $method) = getCallbackFromString($value);
        }

        foreach ($this->items as $item) {

            if ($class) {
                if (executeCallback($class, $method, array($item), $this)) {
                    break;
                }
            } else {
                if ($item == $value) {
                    break;
                }
            }

            $res[] = $item;
        }

        return $res;
    }

    /**
     * Take items in the collection while the given condition is met.
     *
     * @return static
     */
    public function takeWhile($value)
    {
        $res = new Collection();
        
        $class = null;

        if (is_closure($value)) {
            list($class, $method) = getCallbackFromString($value);
        }

        foreach ($this->items as $item) {

            if ($class) {
                if (!executeCallback($class, $method, array($item), $this)) {
                    break;
                }
            } else {
                if ($item != $value) {
                    break;
                }
            }

            $res[] = $item;
        }

        return $res;
    }

    /**
     * Get the first item in the collection, but only if exactly one item exists.
     * Otherwise, throw an exception.
     * Check Laravel documentation
     *
     * @return Collection
     */
    public function sole($key = null, $operator = null, $value = null)
    {
        $res = new Collection();

        if (!is_closure($key)) {

            if (func_num_args() === 1) {
                $value = true;
                $operator = '=';
            }

            if (func_num_args() === 2) {
                $value = $operator;
                $operator = '=';
            }
            
            $res = $this->where($key, $operator, $value);
        }
        else 
        {
            list($class, $method) = getCallbackFromString($key);
            
            $skip = true;
    
            foreach ($this->items as $item) {
    
                $skip = !executeCallback($class, $method, array($item), $this);
    
                if (!$skip) {
                    $res[] = $item;
                }
            }
        }
        
        if ($res->count() === 0) {
            throw new ItemNotFoundException("Item not found in Collection.");
        }

        if ($res->count() > 1) {
            throw new MultipleItemsFoundException($res->count());
        }

        return $res->first();
    }



    /**
     * Get a dictionary keyed by primary keys.
     *
     * @return array
     */
    public function getDictionary($items = null)
    {
        $items = is_null($items) ? $this->items : $items;

        $dictionary = array();

        foreach ($items as $item) {

            $key = $item instanceof Model ? $item->getKey() : (
                $item instanceof RouteItem ? $item->url : (
                    is_array($item)
                        ? reset(array_values($item)) 
                        : $item
                ));

            $dictionary[$key] = $item;
        }

        return $dictionary;
    }

    private function valueRetriever($value)
    {
        if (is_closure($value)) {
            return $value;
        }

        //return fn ($item) => data_get($item, $value);
        return 'Collection|__dataGet|2|$item, $value';
    }

    public function __dataGet($item, $value)
    {
        return data_get($item, $value);
    }

    private function identity()
    {
        //return fn ($value) => $value;
        return 'Collection|__identity|1|$value';
    }

    public function __identity($item, $value)
    {
        return $value;
    }


    /**
     * Return only unique items from the collection.
     *
     * @return Collection
     */
    public function unique($key = null, $strict = false)
    {
        if (is_null($key) && $strict === false) {
            return collect(array_unique($this->items, SORT_REGULAR));
        }

        $new = array();

        if (is_closure($key)) {
            
            list($class, $method, $params, $names) = getCallbackFromString($key);
                
            foreach ($this->items as $key => $item) {
    
                for ($i=0; $i < count($params); $i++) {
                    if ($names[$i]=='key') $params[$i] = $key;
                    if ($names[$i]=='value') $params[$i] = $item;
                }
    
                if (!in_array('value', $names)) {
                    $params[0] = $item;
                }
    
                $key = executeCallback($class, $method, $params);

                if (!isset($new[$key])) {
                    $new[$key] = $item;
                }
            }

            return collect(array_values($new));
        }

        foreach ($this->items as $item) {
    
            $k = is_array($item)? $item[$key] : $item->$key;

            if (!isset($new[$k])) {
                $new[$k] = $item;
            }
        }

        return collect(array_values($new));
    }

    /**
     * Sort through each item with a callback.
     *
     * @return static
     */
    public function sort($callback = null)
    {
        $items = $this->items;

        if ($callback && is_closure($callback)) {
            list($class, $method) = getCallbackFromString($callback);
            uasort($items, array($class, $method));
        } else {
            asort($items, $callback ? $callback : SORT_REGULAR);
        }

        return collect($items);
    }

    /**
     * Sort items in descending order.
     *
     * @param  int  $options
     * @return static
     */
    public function sortDesc($options = SORT_REGULAR)
    {
        $items = $this->items;

        arsort($items, $options);

        return collect($items);
    }

    /**
     * Sort the collection in descending order using the given callback.
     *
     * @return Collection
     */
    public function sortByDesc($keyBy, $options = SORT_REGULAR)
    {
        return $this->sortBy($keyBy, $options, true);
    }

    /**
     * Sort the collection using the given key
     *
     * @return Collection
     */
    public function sortBy($keyBy, $options = SORT_REGULAR, $descending = false)
    {
        $array = array();

        foreach ($this->items as $item) {
            $key = is_array($item) ? $item[$keyBy] : $item->$keyBy;
            $array[$key] = $item;
        }

        $descending ? krsort($array, $options)
            : ksort($array, $options);

        $this->items = array();
        
        foreach (array_keys($array) as $key) {
            $this->items[] = $array[$key];
        }

        return $this;
    }

    /**
     * Sort the collection keys.
     *
     * @param  int  $options
     * @param  bool  $descending
     * @return Collection
     */
    public function sortKeys($options = SORT_REGULAR, $descending = false)
    {
        $items = $this->items;

        $descending ? krsort($items, $options) : ksort($items, $options);

        return collect($items);
    }

    /**
     * Sort the collection keys in descending order.
     *
     * @param  int  $options
     * @return Collection
     */
    public function sortKeysDesc($options = SORT_REGULAR)
    {
        return $this->sortKeys($options, true);
    }

    /**
     * Sort the collection keys using a callback.
     *
     * @return Collection
     */
    public function sortKeysUsing($callback)
    {
        $items = $this->items;

        if (is_closure($callback)) {
            list($class, $method) = getCallbackFromString($callback);
            uksort($items, array($class, $method));
        }

        uksort($items, $callback);

        return collect($items);
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
                $intersect->items[] = $dictionary[$item];
            }
        }
        return $intersect;
    }

    private function getContentType()
    {
        $types = array();

        foreach ($this->items as $item)
        {
            if (is_object($item) && !in_array(get_class($item), $types)) {
                $types[] = get_class($item); 
            }
        }

        return $types;
    }

    private function verifedContenType()
    {
        $types = $this->getContentType();
        
        if (count($types)==0) {
            throw new LogicException("This collection doesn't have models");
        }

        if (count($types)>1) {
            throw new LogicException("This collection have more than one model type");
        }

        return reset($types);
    }

    /**
     * Get the min value of a given key.
     *
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        # Filter
        $res = array();

        foreach ($this->items as $item) {
            if (!is_null($item)) {
                $res[] = $item;
            }
        }

        $this->items = $res;

        # Reduce
        $result = null;

        list($class, $method) = getCallbackFromString($callback);

        foreach ($this->items as $key => $item) {

            $value = executeCallback($class, $method, array($item));

            $result = is_null($result) || $value < $result ? $value : $result;
        }
        
        return is_assoc($result) ? reset($result) : $result;
    }

    /**
     * Get the max value of a given key.
     *
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        # Filter
        $res = array();

        foreach ($this->items as $item) {
            if (!is_null($item)) {
                $res[] = $item;
            }
        }

        $this->items = $res;

        # Reduce
        $result = null;

        list($class, $method) = getCallbackFromString($callback);

        foreach ($this->items as $key => $item) {

            $value = executeCallback($class, $method, array($item));

            $result = is_null($result) || $value > $result ? $value : $result;
        }
        
        return is_assoc($result) ? reset($result) : $result;
    }


     /**
     * Alias for the "avg" method.
     *
     * @return float|int|null
     */
    public function average($callback = null)
    {
        return $this->avg($callback);
    }

    /**
     * Get the average value of a given key.
     * 
     * @return float|int|null
     */
    public function avg($callback = null)
    {
        return $this->sum($callback) / $this->count();
    }

    /**
     * Get the sum of the given values.
     *
     * @return mixed
     */
    public function sum($callback = null)
    {       
        $result = null;

        foreach ($this->items as $key => $item) {
            $result += data_get($item, $callback);
        }

        return $result;
    }

    /**
     * Reduce the collection to a single value.
     *
     */
    public function reduce($callback, $initial = null)
    {
        $result = $initial;

        list($class, $method) = getCallbackFromString($callback);
                
        foreach ($this->items as $key => $item) {
            $result = executeCallback($class, $method, array($result, $item, $key));
        }

        return $result;
    }

    /**
     * Get the items with the specified keys.
     *
     * @return Collection
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new Collection($this->items);
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        if (is_assoc($this->items)) {
            return new Collection(Arr::only($this->items, $keys));
        }

        $dictionary = Arr::only($this->getDictionary(), $keys);

        return collect(array_values($dictionary));
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * @return Collection
     */
    public function except($keys)
    {
        if (is_null($keys)) {
            return new Collection($this->items);
        }

        $keys = is_array($keys)? $keys : func_get_args();

        if (is_assoc($this->items)) {
            return new Collection(Arr::except($this->items, $keys));
        }

        $dictionary = Arr::except($this->getDictionary(), $keys);

        return collect(array_values($dictionary));
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

        foreach ($relations as $relation) {
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
        return $this->each->makeHidden($attributes);
    }

    /**
     * Make the given, typically hidden, attributes visible across the entire collection.
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function makeVisible($attributes)
    {
        return $this->each->makeVisible($attributes);
    }

    /**
     * Set the visible attributes across the entire collection.
     *
     * @param  array $visible
     * @return $this
     */
    public function setVisible($visible)
    {
        return $this->each->setVisible($visible);
    }

    /**
     * Set the hidden attributes across the entire collection.
     *
     * @param  array $hidden
     * @return $this
     */
    public function setHidden($hidden)
    {
        return $this->each->setHidden($hidden);
    }

    /**
     * Append an attribute across the entire collection.
     *
     * @param  array<array-key, string>|string  $attributes
     * @return $this
     */
    public function append($attributes)
    {
        return $this->each->append($attributes);
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

    ### MACROS
    ###(macros)

}