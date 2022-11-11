<?php

/**
 * 
 * @method static Collection all()
 * @method static $this first()
 * @method static Collection paginate(int $value)
 * @method static $this find(string $value)
 * @method static $this findOrFail(string $value)
 * @method static Model firstOrNew()
 * @method static Model firstOrCreate()
 * @method static Builder select(string|array $column)
 * @method static Builder addSelect(string|array $column)
 * @method static Builder selectRaw(string $select, array $bindings=array())
 * @method static Builder where(string|array|closure $column, string $param1, string $param2, string $boolean='AND')
 * @method static Builder whereIn(string $colum, array $values)
 * @method static Builder whereNotIn(string $colum, array $values)
 * @method static Builder whereColumn(string $first, string $operator, string $second, string $chain)
 * @method static Builder whereRelation(string $relation, string $column, string $comparator, string $value)
 * @method static Builder whereBelongsTo(string $related, string $relationshipName=null, $boolean='AND')
 * @method static Builder when(bool $condition, Closure $callback, Closure $defut=null)
 * @method static Builder having(string|array $reference, string $operator=null, $value=null)
 * @method static Builder havingNull(string $reference)
 * @method static Builder havingNotNull(string $reference)
 * @method static Builder with(string|array $relations)
 * @method static Builder join($join_table, $column, $comparator, $join_column)
 * @method static Builder leftJoin($join_table, $column, $comparator, $join_column)
 * @method static Builder rightJoin($join_table, $column, $comparator, $join_column)
 * @method static Builder crossJoin($join_table, $column, $comparator, $join_column)
 * @method static Builder withCount(string|array $relations)
 * @method static Builder withMax(string $relations, string $column)
 * @method static Builder withMin(string $relations, string $column)
 * @method static Builder withAvg(string $relations, string $column)
 * @method static Builder withSum(string $relations, string $column)
 * @method static Builder withExists(string|array $relations)
 * @method static Builder withTrashed()
 * @method static Builder skip(int $value)
 * @method static Builder take(int $value)
 * @method static Builder latest($colun)
 * @method static Builder oldest($column)
 * @method static Builder orderBy(string $column, string $order)
 * @method static Builder orderByRaw(string $order)
 * @method static int count(string $column)
 * @method static mixed min(string $column)
 * @method static mixed max(string $column)
 * @method static mixed avg(string $column)
 * @method static mixed average(string $column)
 * @method static $this create(array $record)
 * @method static Builder has(string $relation, string $comparator=null, string $value=null)
 * @method static Builder whereHas(string $relation, Query $filter=null, string $comparator=null, string $value=null)
 * @method static Builder withWhereHas(string $relation, Query $filter=null)
 * @method static Builder withoutGlobalScope(Scope|string $scope)
 * @method static Builder withoutGlobalScopes()
 * @method static Builder query()
 * @method static Factory factory()
 */

class Model
{

    protected $_original = array();
    protected $_relations = array();
    
    /**
     * Sets database table used in model\
     * Default value is Model' name in lowercase and plural
     */
    protected $table = null;

    /**
     * Sets table primary key\
     * Default value is 'id'
     */
    protected $primaryKey = 'id';

    /**
     * Sets fillable columns\
     * Default is empty array
     */
    protected $fillable = array();

    /**
     * Sets guarded columns\
     * Default is null
     */
    protected $guarded = null;

    /**
     * Sets hidden attributes\relationships
     */
    protected $hidden = array();

    /**
     * Sets the Model's factory
     */
    protected $factory = null;


    protected $wasRecentlyCreated = false;

    /**
     * Sets the connector for database\
     * Uses main connector by default, wich is
     * created using .env variables\
     * Example:\
     * array('host' => '192.168.1.1', 'user' => 'admin', 'password' => 'admin',
     * 'database' => 'mydatabase', 'port' => 3306);
     * @var array
     */
    protected $connector = null;

    public $global_scopes = array();

    public function __construct()
    {
        if (!isset($this->table))
        {
            $this->table = Helpers::camelCaseToSnakeCase(get_class($this));
        }
    }

    protected function addGlobalScope($scope, $callback=null)
    {
        if (is_object($scope))
            $this->global_scopes[get_class($scope)] = $scope;
        else
            $this->global_scopes[$scope] = $callback;
    }

    public function getRouteKeyName()
    {
        return $this->primaryKey;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getFillable()
    {
        return $this->fillable;
    }

    public function getHidden()
    {
        return $this->hidden;
    }

    public function getGuarded()
    {
        return $this->guarded;
    }

    public function getUseSoftDeletes()
    {
        return isset($this->useSoftDeletes);
    }

    /** @return Builder */
    public static function instance($parent, $table=null)
    {
        return new Builder($parent, $table);
    }

    /**
     * @return Builder
     */
    public function getQuery($query=null)
    {
        /* if (!isset(self::$_query) || self::$_query->_parent!=get_class($this))
        {
            self::$_query = self::instance(get_class($this));
            self::$_query->_collection->append($this);
        } */
            
        if (!isset($this->_query))
        {
            $this->_query = $query? $query : new Builder(get_class($this));
            
        }
        if ($this->_query->_collection->count()==0 && count($this->_original)>0)
        {
            $this->_query->_collection->append($this);
        }

        return $this->_query;
    }

    /* public function setQuery($query, $full=true)
    {
        if (!$full)
        {
            unset($query->_parent);
            unset($query->_table);
            unset($query->_primary);
            unset($query->_foreign);
            unset($query->_fillable);
            unset($query->_guarded);
        }

        self::$_query = $query;
        #foreach($query as $key => $val)
        #    self::$_query->$key = $val;
    } */

    public function getTable()
    {
        return $this->table;
    }

    public function getConnector()
    {
        return $this->connector;
    }

    public function _setOriginalKey($key, $val)
    {
        $this->_original[$key] = $val;
    }

    public function _getOriginalKeys()
    {
        return $this->_original;
    }

    public function _setOriginalRelations($relations)
    {
        $this->_relations = $relations;
    }

    public function _setRecentlyCreated($val)
    {
        $this->wasRecentlyCreated = $val;
    }




   
    public function __get($name)
    {
        //dump("GET::$name");
        if ($name=='exists')
            return count($this->_original)>0;

        if ($name=='wasRecentlyCreated')
            return $this->wasRecentlyCreated;
        
        if (method_exists($this, 'get'.ucfirst($name).'Attribute'))
        {
            $fn = 'get'.ucfirst($name).'Attribute';
            return $this->$fn();
        }

        if (method_exists($this, $name.'Attribute'))
        {
            $fn = $name.'Attribute';
            $nval = $this->$fn($name, (array)$this);
            return $nval['get'];
        }

        if (method_exists($this, $name))
        {
            global $preventLazyLoading;

            if ($preventLazyLoading)
                throw new Exception("Attempted to lazy load [$name] on Model [".get_class($this)."]");

            $this->load($name);
            
            return $this->$name;
        }
        else
        {
            global $preventAccessingMissingAttributes;

            if ($preventAccessingMissingAttributes)
                throw new Exception("The attribute [$name] either does not 
                    exist or was not retrieved for model [".get_class($this)."]", 120);
        }


    }


    /**
     * Returns model as array
     * 
     * @return array
     */
    public function toArray()
    {
        $c = new Collection(get_class($this), $this->hidden);
        return $c->toArray($this);
    }


    /* public function newFactory()
    {
        return $this->factory = new Factory();
    } */


     /**
     * Declare model observer
     * 
     */
    /* public static function observe($class)
    {
        global $version, $observers;
        $model = self::$_parent;
        if (!isset($observers[$model]))
            $observers[$model] = $class;
    } */

    private function checkObserver($function, $model)
    {
        global $observers;
        $class = get_class($this);
        if (isset($observers[$class]))
        {
            $observer = new $observers[$class];
            if (method_exists($observer, $function))
                $observer->$function($model);
        }
    }

    /**
     * Get the original Model attribute(s)
     * 
     * @param string $value
     * @return mixed
     */
    public function getOriginal($value=null)
    {
        if ($value)
            return $this->_original[$value];

        return $this->_original;

    }

    /**
     * Determine if attribute(s) has changed
     * 
     * @param string $value
     * @return mixed
     */
    public function isDirty($value=null)
    {
        if ($value)
            return $this->_original[$value] != $this->$value;

        foreach ($this->_original as $key => $val)
        {
            if ($this->$key != $val)
                return true;
        }
        return false;

    }

    /**
     * Determine if attribute(s) has remained unchanged
     * 
     * @param string $value
     * @return mixed
     */
    public function isClean($value=null)
    {
        if ($value)
            return $this->_original[$value] == $this->$value;

        $res = true;
        foreach ($this->_original as $key => $val)
        {
            if ($this->$key != $val)
            {
                $res = false;
                break;
            }
        }
        return $res;

    }

    /**
     * Re-retrieve the model from the database.\
     * The existing model instance will not be affected
     * 
     * @return Model
     */
    public function fresh()
    {
        if (count($this->_original)==0)
            throw new Exception('Trying to re-retrieve from a new Model'); 

        return $this->getQuery()->fresh($this->_original, null);

    }

    /**
     * Re-retrieve the model from the database.\
     * The existing model instance will not be affected
     * 
     * @return Model
     */
    public function refresh()
    {
        if (count($this->_original)==0)
            throw new Exception('Trying to re-retrieve from a new Model'); 

        $res = $this->getQuery()->refresh($this->_original, $this->_relations);

        foreach($this as $key => $val)
            unset($this->$key);

        foreach ($res as $key => $val)
        {
            $this->$key = $val;
        }

    }


    public function fillableOff()
    {
        return $this->getQuery()->_fillableOff = true;
    }

    /**
     * Set a factory to seed the model
     * 
     * @return Factory
     */
    /* public static function factory()
    {
        $class = new self;
        print_r($class);
        return $class->getQuery()->factory();
    } */

    public function seed($array, $persist)
    {
        return $this->getQuery()->seed($array, $persist);
    }


    public static function shouldBeStrict($shouldBeStrict = true)
    {
        self::preventLazyLoading($shouldBeStrict);
        self::preventSilentlyDiscardingAttributes($shouldBeStrict);
        self::preventAccessingMissingAttributes($shouldBeStrict);
    }

    public static function preventLazyLoading($prevent=true)
    {
        global $preventLazyLoading;
        $preventLazyLoading = $prevent;
    }

    public static function preventSilentlyDiscardingAttributes($prevent=true)
    {
        global $preventSilentlyDiscardingAttributes;
        $preventSilentlyDiscardingAttributes = $prevent;
    }

    public static function preventAccessingMissingAttributes($prevent=true)
    {
        global $preventAccessingMissingAttributes;
        $preventAccessingMissingAttributes = $prevent;
    }


    /**
     * Saves the model in database
     * 
     * @return bool
     */
    public function save()
    {
        $res = $this->getQuery();

        //dump($res);

        //dump($this);
        //dd($this->_getOriginalKeys());

        if (count($this->_original)>0)
        {
            $res->_fillableOff = true;
            $final = $res->update($this, $this->_original);
            $res->_fillableOff = false;
        }
        else
        {
            $res->_fillableOff = true;         
            $final = $res->create($this);
            $res->_fillableOff = false;
        }

        return $final;
    }

    /**
     * Save the model and all of its relationships
     * 
     * @return bool
     */
    public function push()
    {
        return $this->getQuery()->push($this, count($this->_original)==0);
    }


    /**
     * Creates a new record in database\
     * Returns new record
     * 
     * @param array $record
     * @return object
     */
    /* public static function create($record)
    {
        return self::getInstance()->getQuery()->create($record);
    } */

    /**
     * Updates a record or an array of reccords in database
     * 
     * @param array $record
     * @return bool
     */
    public function update($record)
    {
        //var_dump(self::getInstance()->getQuery());
        if( isset($this) && $this instanceof self )
        {
            //$this->checkObserver('updating', $this);

            $res = $this->getQuery();
            $primary = $this->getRouteKeyName();
            $res = $res->where($primary, $this->$primary)->update($record);

            //if ($res) $this->checkObserver('updated', $this);

            return $res;
        }

        //return self::getInstance()->getQuery()->update($record);
    }

    /**
     * Deletes the current model from database
     * 
     * @return bool
     */
    public function delete()
    {
        if( isset($this) && $this instanceof self )
        {
            $this->checkObserver('deleting', $this);

            $res = self::instance(get_class($this));
            $primary = $this->getRouteKeyName();
            $res = $res->where($primary, $this->$primary)->delete();

            if ($res) $this->checkObserver('deleted', $this);

            return $res;
        }

        //return self::getInstance()->getQuery()->update($record);
    }




    /**
     * Adds records from a sub-query inside the current records\
     * Check Laravel documentation
     * 
     * @return Model
     */
    public function load($relations)
    {
        if( isset($this) && $this instanceof self )
        {
            $relations = is_string($relations) ? func_get_args() : $relations;
            
            $res = $this->getQuery();
            //$res->_collection->append($this);
            
            $res->load($relations);
            unset($res);

            return $this;
        }
    }


    /**
     * Makes a relationship\
     * Check Laravel documentation
     * 
     * @param string $class - Model class (or table name)
     * @param string $foreign - Foreign key
     * @param string $primary - Primary key
     * @return Builder
     */
    public function hasOne($class, $foreign=null, $primary=null)
    {
        return Relations::hasOne($this->getQuery(), $class, $foreign, $primary);
        //return $this->getQuery()->hasOne($class, $foreign, $primary);
    }

    /**
     * Makes a relationship\
     * Check Laravel documentation
     * 
     * @param string $class - Model class (or table name)
     * @param string $foreign - Foreign key
     * @param string $primary - Primary key
     * @return Builder
     */
    public function hasMany($class, $foreign=null, $primary=null)
    {
        return Relations::hasMany($this->getQuery(), $class, $foreign, $primary);
        //return $this->getQuery()->hasMany($class, $foreign, $primary);
    }

    /**
     * Makes a relationship\
     * Check Laravel documentation
     * 
     * @param string $class - Model class (or table name)
     * @param string $foreign - Foreign key
     * @param string $primary - Primary key
     * @return Builder
     */
    public function belongsTo($class, $foreign=null, $primary=null)
    {
        return Relations::belongsTo($this->getQuery(), $class, $foreign, $primary);
        //return $this->getQuery()->belongsTo($class, $foreign, $primary);
    }

    /**
     * Makes a relationship\
     * Check Laravel documentation
     * 
     * @param string $class - Model class (or table name)
     * @param string $classthrough - Model class through (or table name)
     * @param string $foreignthrough - Foreign key from through 
     * @param string $foreign - Foreign key
     * @param string $primary - Primary key
     * @param string $primarythrough - Primary key through
     * @return Builder
     */
    public function hasOneThrough($class, $classthrough, $foreignthrough=null, $foreign=null, $primary=null, $primarythrough=null)
    {
        return Relations::hasOneThrough($this->getQuery(), $class, $classthrough, $foreignthrough, $foreign, $primary, $primarythrough);
        //return $this->getQuery()->hasOneThrough($class, $classthrough, $foreignthrough, $foreign, $primary, $primarythrough);
    }

    /**
     * Makes a relationship\
     * Check Laravel documentation
     * 
     * @param string $class - Model class (or table name)
     * @param string $classthrough - Model class through (or table name)
     * @param string $foreignthrough - Foreign key from through 
     * @param string $foreign - Foreign key
     * @param string $primary - Primary key
     * @param string $primarythrough - Primary key through
     * @return Builder
     */
    public function hasManyThrough($class, $classthrough, $foreignthrough, $foreign, $primary='id', $primarythrough='id')
    {
        return Relations::hasManyThrough($this->getQuery(), $class, $classthrough, $foreignthrough, $foreign, $primary, $primarythrough);
        //return $this->getQuery()->hasManyThrough($class, $classthrough, $foreignthrough, $foreign, $primary, $primarythrough);
    }

    /**
     * Makes a relationship\
     * Check Laravel documentation
     * 
     * @param string $class - Model class (or table name)
     * @param string $foreign - Foreign key
     * @param string $primary - Primary key
     * @return Builder
     */
    public function belongsToMany($class, $foreign=null, $primary=null, $foreignthrough=null, $primarythrough=null)
    {
        return Relations::belongsToMany($this->getQuery(), $class, $foreign, $primary, $foreignthrough, $primarythrough);
        //return $this->getQuery()->belongsToMany($class, $foreign, $primary, $foreignthrough, $primarythrough);
    }

    public function morphOne($class, $method)
    {
        return Relations::morphOne($this->getQuery(), $class, $method);
        //return $this->getQuery()->morphOne($class, $method);
    }

    public function morphMany($class, $method)
    {
        return Relations::morphMany($this->getQuery(), $class, $method);
        //return $this->getQuery()->morphMany($class, $method);
    }

    public function morphTo()
    {
        return Relations::morphTo($this->getQuery());
        //return $this->getQuery()->morphTo();
    }

    public function morphToMany($class, $method)
    {
        return Relations::morphToMany($this->getQuery(), $class, $method);
        //return $this->getQuery()->morphToMany($class, $method);
    }

    public function morphedByMany($class, $method)
    {
        return Relations::morphedByMany($this->getQuery(), $class, $method);
        //return $this->getQuery()->morphedByMany($class, $method);

    }


    /**
     * Eager load relation's column aggregations on the model.
     *
     * @param  array|string  $relations
     * @param  string  $column
     * @param  string  $function
     * @return Model
     */
    public function loadAggregate($relations, $column, $function = null)
    {
        $res = $this;
        if( isset($this) && $this instanceof self )
        {
            $relations = is_string($relations) ? func_get_args() : $relations;            

            $this->getQuery();
            //$this->getQuery()->_collection->append($this);

            foreach ($relations as $relation)
            {
                $res = $this->getQuery()->loadAggregate($relation, $column, $function); //->first();
            }
            unset($this->_query);
        }
        return $res->_collection->first();
    }


   

    /**
     * Eager load relation counts on the model.
     *
     * @param  array|string  $relations
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
     * @param  array|string  $relations
     * @param  string  $column
     * @return Model
     */
    public function loadMax($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'max');
    }

    /**
     * Eager load relation min column values on the model.
     *
     * @param  array|string  $relations
     * @param  string  $column
     * @return Model
     */
    public function loadMin($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'min');
    }

    /**
     * Eager load relation's column summations on the model.
     *
     * @param  array|string  $relations
     * @param  string  $column
     * @return Model
     */
    public function loadSum($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'sum');
    }

    /**
     * Eager load relation average column values on the model.
     *
     * @param  array|string  $relations
     * @param  string  $column
     * @return Model
     */
    public function loadAvg($relations, $column)
    {
        return $this->loadAggregate($relations, $column, 'avg');
    }

    /**
     * Eager load related model existence values on the model.
     *
     * @param  array|string  $relations
     * @return Model
     */
    public function loadExists($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;
        return $this->loadAggregate($relations, '*', 'exists');
    }





}