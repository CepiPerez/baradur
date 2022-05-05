<?php


class Model
{
    protected static $_parent = 'myparent';
    //protected static $_instances;
    protected static $_table;
    protected static $_primaryKey = 'id';
    protected static $_fillable = array();
    protected static $_guarded = null;
    protected static $_factory;
    protected static $_connector;
    protected static $_query;


    /**
     * Sets database table used in model\
     * Default value is Model' name in lowercase and plural
     */
    protected $table = null;

    /**
     * Sets table primary key\
     * Default value is 'id'
     */
    protected $primaryKey = null;

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
     * Sets the Model's factory
     */
    protected $factory = null;


    /**
     * Sets the connector for database\
     * Uses main connector by default, wich is
     * created using .env variables\
     * Example:\
     * array('host' => '192.168.1.1', 'user' => 'admin', 'password' => 'admin',
     * 'database' => 'mydatabase', 'port' => 3306);
     * @var array
     */
    Protected $connector = null;


    public function __construct($empty = false)
    {
        global $version;

        # Only for PHP => 5.3 
        if ($version=='NEW')
        {
            self::$_parent = get_called_class();
        }

        if (!$empty)
        {
            if (isset($this->connector))
            {
                $conn = new Connector($this->connector['host'], $this->connector['user'], 
                    $this->connector['password'], $this->connector['database'], 
                    $this->connector['port']?$this->connector['port']:3306);
    
                self::$_connector = $conn;
            }
            else
            {
                global $database;
                self::$_connector = $database;
            }
        }

        if (isset($this->table))
        {
            self::$_table = $this->table;
        }
        else if (!isset(self::$_table))
        {
            self::$_table = self::$_parent;
            self::$_table = Helpers::camelCaseToSnakeCase(self::$_table);
        }

        if ($this->primaryKey)
        {
            self::$_primaryKey = $this->primaryKey; 
        }
        else
        {
            self::$_primaryKey = 'id';
        }

        if ($this->factory)
        {
            self::$_factory = $this->factory; 
        }
        else
        {
            self::$_factory = self::$_parent.'Factory';
        }

        if ($this->fillable)
        {
            self::$_fillable = $this->fillable; 
        }

        if ($this->guarded)
        {
            self::$_guarded = $this->guarded; 
        }
  
        unset($this->connector);
        unset($this->table);
        unset($this->primaryKey);
        unset($this->fillable);
        unset($this->guarded);
        unset($this->factory);

        $routeKey = $this->getRouteKeyName();

        //echo "NEW MODEL: ".get_called_class()."<br>";
       
        //self::$_query = new QueryBuilder(self::$_connector, self::$_parent, self::$_table, self::$_primaryKey);

        if ($empty)
            self::$_query = null;
        else
            self::$_query = new QueryBuilder(self::$_connector, self::$_table, self::$_primaryKey, 
                        self::$_parent, self::$_fillable, self::$_guarded, $routeKey);

        
    }

    public function getRouteKeyName()
    {
        return self::$_primaryKey;
    }


    public static function getInstance($table=null)
    {
        global $version;

        # Only for PHP => 5.3 
        if ($version=='NEW') {
            self::$_parent = get_called_class();
        }

        
        if (isset($table))
        {
            if (strpos($table, ':')>0)
            {
                list($table, $primary) = explode(':', $table);
                self::$_primaryKey = $primary;
            }

            self::$_table = $table;
        }
        else
        {
            self::$_table = Helpers::camelCaseToSnakeCase(self::$_parent);
        }

        return new self::$_parent();

    }

    public function getQuery()
    {
        return self::$_query;
    }

    public function setQuery($query, $full=true)
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

        foreach($query as $key => $val)
            self::$_query->$key = $val;
    }

    public function getTable()
    {
        return self::$_table;
    }

    /* public static function initialize($val)
    {
        eval( "self::\$_parent = \$val;" );
    } */

   
    public function __get($name)
    {

        if (method_exists($this, 'get'.ucfirst($name).'Attribute'))
        {
            $fn = 'get'.ucfirst($name).'Attribute';
            return $this->$fn();
        }

        elseif (method_exists($this, $name))
        {
            $array = new Collection($this->_parent);
            $array->put($this);
                        
            $inst = self::getInstance();
            $inst->getQuery()->_collection = $array;
            $res = $inst->$name()->get();

            if (get_class($this)=='User')
            {
                $this->$name = $res;
                $_SESSION['user'] = $this;
            }
            return $res;
            //dd($this);
            //return $this->$name()->get();
        }

    }

    # PHP > 5.3 only
    public static function __callStatic($name, $arguments)
    {
        if (method_exists(get_called_class(), 'scope'.ucfirst($name)))
        {
            return self::getInstance()->getQuery()->callScope($name, $arguments);
        }

        /* else if (method_exists('QueryBuilder', $name))
        {
            return self::getInstance()->getQuery()->$name($arguments);
        } */
    }


    public static function newFactory()
    {
        //echo "Called factory(): ".self::$_factory;
        $class = self::$_factory;
        return new $class;
    }


     /**
     * Declare model observer
     * 
     */
    public static function observer($class)
    {
        global $version, $observers;
        $model = $version=='NEW'? get_called_class() : self::$_parent;
        if (!isset($observers[$model]))
            $observers[$model] = $class;
    }

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
     * Returns the query in string format
     * 
     */
    public static function toSql()
    {
        return self::getInstance()->getQuery()->toSql();
    }

    /**
     * Specifies the SELECT clause\
     * Returns the Query builder
     * 
     * @param string $columns String containing colums divided by comma
     * @return QueryBuilder
     */
    public static function select($columns = '*')
    {
        return self::getInstance()->getQuery()->select(func_get_args());
    }


    /**
     * Specifies the SELECT clause\
     * Returns the Query builder
     * 
     * @param string $columns String containing colums divided by comma
     * @return QueryBuilder
     */
    public static function selectRaw($columns = '*')
    {
        return self::getInstance()->getQuery()->selectRaw($columns);
    }


    /**
     * Specifies the WHERE clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $condition Can be ommited for '='
     * @param string $value
     * @return QueryBuilder
     */
    public static function where($column, $condition='', $value='')
    {
        return self::getInstance()->getQuery()->where($column, $condition, $value);
    }

    /**
     * Specifies the WHERE IN clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $values
     * @return QueryBuilder
     */
    public static function whereIn($column, $values)
    {
        return self::getInstance()->getQuery()->whereIn($column, $values);
    }

    /**
     * Specifies the WHERE NOT IT clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $values
     * @return QueryBuilder
     */
    public static function whereNotIn($column, $values)
    {
        return self::getInstance()->getQuery()->whereNotIn($column, $values);
    }

    /**
     * Specifies OR in WHERE clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $condition Can be ommited for '='
     * @param string $value
     * @return QueryBuilder
     */
    public static function orWhere($col, $cond='', $val='')
    {
        return self::getInstance()->getQuery()->orWhere($col, $cond, $val);

    }

    /**
     * Specifies the INNER JOIN clause\
     * Returns the Query builder
     * 
     * @param string $join_table 
     * @param string $column
     * @param string $comparator
     * @param string $join_column
     * @return QueryBuilder
     */
    public static function join($join_table, $column, $comparator, $join_column)
    {
        return self::getInstance()->getQuery()->join($join_table, $column, $comparator, $join_column);
    }

    /**
     * Specifies the WHERE BETWEEN clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param array $values
     * @return QueryBuilder
     */
    public static function whereBetween($column, $values)
    {
        return self::getInstance()->getQuery()->whereBetween($column, $values);
    }

    /**
     * Specifies the HAVING clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $reference 
     * @param string $value 
     * @return QueryBuilder
     */
    public static function having($reference, $operator, $value)
    {
        return self::getInstance()->getQuery()->having($reference, $operator, $value);
    }

    /**
     * Specifies the HAVING clause between to values\
     * Returns the Query builder
     * 
     * @param string $reference
     * @param array $values
     * @return QueryBuilder
     */
    public static function havingBetween($reference, $values)
    {
        return self::getInstance()->getQuery()->havingBetween($reference, $values);
    }

    /**
     * Specifies the LEFT JOIN clause\
     * Returns the Query builder
     * 
     * @param string $join_table 
     * @param string $column
     * @param string $comparator
     * @param string $join_column
     * @return QueryBuilder
     */
    public static function leftJoin($join_table, $column, $comparator, $join_column)
    {
        return self::getInstance()->getQuery()->leftJoin($join_table, $column, $comparator, $join_column);
    }

    /**
     * Specifies the RIGHT JOIN clause\
     * Returns the Query builder
     * 
     * @param string $join_table 
     * @param string $column
     * @param string $comparator
     * @param string $join_column
     * @return QueryBuilder
     */
    public static function rightJoin($join_table, $column, $comparator, $join_column)
    {
        return self::getInstance()->getQuery()->rightJoin($join_table, $column, $comparator, $join_column);
    }

    /**
     * Specifies the CROSS JOIN clause\
     * Returns the Query builder
     * 
     * @param string $join_table 
     * @param string $column
     * @param string $comparator
     * @param string $join_column
     * @return QueryBuilder
     */
    public static function crossJoin($join_table, $column, $comparator, $join_column)
    {
        return self::getInstance()->getQuery()->crossJoin($join_table, $column, $comparator, $join_column);
    }

    /**
     * INNER Joins as subquery\
     * Returns the Query builder
     * 
     * @param string $query 
     * @param string $alias
     * @param Query $filter
     * @return QueryBuilder
     */
    public static function joinSub($query, $alias, $filter)
    {
        return self::getInstance()->getQuery()->joinSub($query, $alias, $filter);
    }


    /**
     * Find a recond where primary key equals $value\
     * Returns the record
     * 
     * @param string $value
     * @return object
     */
    public static function find($value)
    {
        return self::getInstance()->getQuery()->find($value);
    }

    /**
     * Find a recond where primary key equals $value\
     * Returns the record or 404 if not found
     * 
     * @param string $value
     * @return object
     */
    public static function findOrFail($value)
    {
        return self::getInstance()->getQuery()->findOrFail($value);
    }

    /**
     * Search in reconds for $value in several $colums\
     * Uses WHERE CONTACT($columns) LIKE $value\
     * Returns the records
     * 
     * @param string $columns
     * @param string $value
     * @return $array
     */
    public static function search($columns, $value)
    {
        return self::getInstance()->getQuery()->search($columns, $value);
    }

     /**
     * Specifies the GROUP BY clause\
     * Returns the Query builder
     * 
     * @param string $group
     * @return QueryBuilder
     */
    public static function groupBy($group)
    {
        return self::getInstance()->getQuery()->groupBy($group);
        //return self::getInstance();
    }


    /**
     * Specifies the ORDER BY clause\
     * Returns the Query builder
     * 
     * @param string $order
     * @return QueryBuilder
     */
    public static function orderBy($order)
    {
        return self::getInstance()->getQuery()->orderBy($order);
        //return self::getInstance();
    }

    /**
     * Specifies the LIMIT clause\
     * Returns the Query builder
     * 
     * @param string $limit
     * @return QueryBuilder
     */
    public static function limit($limit)
    {
        return self::getInstance()->getQuery()->limit($limit);
        //return self::getInstance();
    }

    /**
     * Specifies the SET clause\
     * Allows array with key=>value pairs in $key\
     * Returns the Query builder
     * 
     * @param string $key
     * @param string $value
     * @return QueryBuilder
     */
    public static function set($key, $value=null)
    {
        return self::getInstance()->getQuery()->set($key, $value);
        //return self::getInstance();
    }

    /**
     * Saves the current record in database\
     * Uses INSERT for new record\
     * Uses UPDATE for retrieved record
     * 
     * @return bool
     */
    public function save()
    {
        //$res = self::getInstance();
        return $this->getQuery()->save($this);
    }

    /**
     * INSERT a record or an array of records in database
     * 
     * @param array $record
     * @return bool
     */
    public static function insert($records)
    {
        return self::getInstance()->getQuery()->insert($records);
    }

    /**
     * Creates a new record in database\
     * Returns new record
     * 
     * @param array $record
     * @return Model
     */
    public static function create($record)
    {
        return self::getInstance()->getQuery()->create($record);
    }

    /**
     * INSERT IGNORE a record or an array of records in database
     * 
     * @param array $record
     * @return bool
     */
    public static function insertOrIgnore($record)
    {
        return self::getInstance()->getQuery()->insertOrIgnore($record);
    }

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
            $this->checkObserver('updating', $this);

            $class = get_class($this);
            $primary = $this->getRouteKeyName();
            $newmodel = call_user_func_array(array($class, 'where'), array($primary, $this->$primary));
            $res = $newmodel->update($record);

            if ($res) $this->checkObserver('updated', $this);

            return $res;
            //return self::getInstance()->getQuery()->update($record, $this);
        }


        return self::getInstance()->getQuery()->update($record);
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

            $class = get_class($this);
            $primary = $this->getRouteKeyName();
            $newmodel = call_user_func_array(array($class, 'where'), array($primary, $this->$primary));
            $res =  $newmodel->delete();

            if ($res) $this->checkObserver('deleted', $this);

            return $res;
        }

        //return self::getInstance()->getQuery()->update($record);
    }

    /**
     * Create or update a record matching the attributes, and fill it with values
     * 
     * @param  array  $attributes
     * @param  array  $values
     * @return bool
     */
    public static function updateOrInsert($attributes, $values)
    {
        return self::getInstance()->getQuery()->updateOrInsert($attributes, $values);
    }

    /**
     * Create or update a record matching the attributes, and fill it with values\
     * Returns the record
     * 
     * @param  array  $attributes
     * @param  array  $values
     * @return Model
     */
    public static function updateOrCreate($attributes, $values)
    {
        return self::getInstance()->getQuery()->updateOrCreate($attributes, $values);
    }

    /**
     * Uses REPLACE clause\
     * Updates a record using PRIMARY KEY OR UNIQUE\
     * If the record doesn't exists then creates a new one
     * 
     * @param array $record
     * @return bool
     */
    public static function insertReplace($record)
    {
        return self::getInstance()->getQuery()->insertReplace($record);
    }

    /* public static function updateAll()
    {
        return self::getInstance()->getQuery()->updateAll();
    } */


    /**
     * Truncates the current table
     * 
     * @return bool
     */
    public static function truncate()
    {
        return self::getInstance()->getQuery()->truncate();
    }


    /**
     * DELETE the current records from database\
     * Returns error or empty string if ok
     * 
     * @return string
     */
    /* public static function deleteAll()
    {
        return self::getInstance()->getQuery()->deleteAll();
    } */


    /**
     * Returns the first record from query
     * 
     * @return object
     */
    public static function first()
    {
        return self::getInstance()->getQuery()->first();
    }

    /**
     * Returns the first record from query\
     * Returns 404 if not found
     * 
     * @return object
     */
    public static function firstOrFail()
    {
        return self::getInstance()->getQuery()->firstOrFail();
    }

    /**
     * Retrieves the first record matching the attributes, and fill it with values (if asssigned)\
     * If the record doesn't exists creates a new one\
     * 
     * @param  array  $attributes
     * @param  array  $values
     * @return object
     */
    public static function firstOrNew($attributes, $values=null)
    {
        return self::getInstance()->getQuery()->firstOrNew($attributes, $values);
    }

    /**
     * Return all records from current query
     * 
     * @return Collection
     */
    public static function get()
    {
        return self::getInstance()->getQuery()->get();
    }

    /**
     * Return all records from current query\
     * Limit the resutl to number of $records\
     * Send Pagination values to View class 
     * 
     * @param int $records
     * @return Collection
     */
    public static function paginate($records = 10)
    {
        return self::getInstance()->getQuery()->paginate($records);
    }

    /**
     * Executes the SQL $query
     * 
     * @param string $query
     * @return Collection
     */
    public static function query($query)
    {
        return self::getInstance()->getQuery()->query($query);
    }

    /**
     * Adds records from a sub-query inside the current records\
     * Check Laravel documentation
     * 
     * @return QueryBuilder
     */
    public static function with($relations)
    {
        return self::getInstance()->getQuery()->with(
            is_string($relations) ? func_get_args() : $relations
        );
    }

    /**
     * Set the query relation\
     * Needed to apply constraints in with()
     * 
     * @return QueryBuilder
     */
    public static function relation($relation)
    {
        return self::getInstance()->getQuery()->_has($relation);
    }

    /**
     * Filter current query based on relationships\
     * Check Laravel documentation
     * 
     * @return QueryBuilder
     */
    public static function has($relation, $comparator=null, $value=null)
    {
        //$res = self::getInstance();
        return self::getInstance()->getQuery()->_has($relation, null, $comparator, $value);
    }

    /**
     * Filter current query based on relationships\
     * Allows to specify additional filters\
     * Since we can't use closures it should be done this way:\
     * whereHas('my_relation', \
     *  Query::where('condition', '>', 'value')\
     * );\
     * Filters can be nested\
     * Check Laravel documentation
     * 
     * @param string $relation
     * @param Query $filter
     * @param string $comparator
     * @param string|int $value
     * @return QueryBuilder
     */
    public static function whereHas($relation, $filter=null, $comparator=null, $value=null)
    {
        return self::getInstance()->getQuery()->whereHas($relation, $filter, $comparator, $value);
    }

    /**
     * Filter current query based on relationships\
     * Includes the relations, so with() is not needed\
     * Since we can't use closures it should be done this way:\
     * withWhereHas('my_relation', \
     *  Query::where('condition', '>', 'value')\
     * );\
     * Filters can be nested\
     * Check Laravel documentation
     * 
     * @param string $relation
     * @param array $filters
     * @return QueryBuilder
     */
    /* public static function withWhereHas($relation, $constraint=null)
    {
        return self::getInstance()->getQuery()->_withWhereHas($relation, $constraint);
    } */

    /**
     * Makes a relationship\
     * Check Laravel documentation
     * 
     * @param string $class - Model class (or table name)
     * @param string $foreign - Foreign key
     * @param string $primary - Primary key
     * @return QueryBuilder
     */
    public function hasMany($class, $foreign=null, $primary=null)
    {        
        //$this->checkQuery();

        return $this->getQuery()->processRelationship($class, $foreign, $primary, 'hasMany');
    }

    /**
     * Makes a relationship\
     * Check Laravel documentation
     * 
     * @param string $class - Model class (or table name)
     * @param string $foreign - Foreign key
     * @param string $primary - Primary key
     * @return QueryBuilder
     */
    public function belongsTo($class, $foreign=null, $primary=null)
    {
        //$this->checkQuery();

        return $this->getQuery()->processRelationship($class, $foreign, $primary, 'belongsTo');
    }

    /**
     * Makes a relationship\
     * Check Laravel documentation
     * 
     * @param string $class - Model class (or table name)
     * @param string $foreign - Foreign key
     * @param string $primary - Primary key
     * @return QueryBuilder
     */
    public function hasOne($class, $foreign=null, $primary=null)
    {
        //$this->checkQuery();

        return $this->getQuery()->processRelationship($class, $foreign, $primary, 'hasOne');
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
     * @return QueryBuilder
     */
    public function hasManyThrough($class, $classthrough, $foreignthrough, $foreign, $primary='id', $primarythrough='id')
    {
        //$this->checkQuery();
        
        return $this->getQuery()->processRelationshipThrough($class, $classthrough, $foreignthrough, $foreign, $primary, $primarythrough, 'hasManyThrough');
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
     * @return QueryBuilder
     */
    public function hasOneThrough($class, $classthrough, $foreignthrough=null, $foreign=null, $primary=null, $primarythrough=null)
    {
        //$this->checkQuery();

        return $this->getQuery()->processRelationshipThrough($class, $classthrough, $foreignthrough, $foreign, $primary, $primarythrough, 'hasOneThrough');
    }

    /**
     * Makes a relationship\
     * Check Laravel documentation
     * 
     * @param string $class - Model class (or table name)
     * @param string $foreign - Foreign key
     * @param string $primary - Primary key
     * @return QueryBuilder
     */
    public function belongsToMany($class, $foreign=null, $primary=null)
    {
        //$this->checkQuery();

        
        return $this->getQuery()->belongsToMany($class, $foreign, $primary);
    }

    public function morphTo()
    {
        $query = self::getInstance()->getQuery(); 
        $primary = $query->_connector->execSQL("SHOW KEYS FROM $query->_table WHERE Key_name = 'PRIMARY'")
                ->pluck('Column_name')->toArray();
        return $primary;
    }

    public function morphOne($class, $method)
    {
        return $this->getQuery()->processMorphRelationship($class, $method, 'morphOne');
    }

    public function morphMany($class, $method)
    {
        return $this->getQuery()->processMorphRelationship($class, $method, 'morphMany');
    }

    public function checkQuery()
    {
        if (!$this->getQuery())
        {
            $col = new Collection(get_class($this));
            $col->put($this);
            $classname = get_class($this);
            $newmodel = call_user_func_array(array($classname, 'select'), array('*'));
            $this->setQuery($newmodel);
        }
        
        if (count($this->getQuery()->_collection)==0)
            $this->getQuery()->_collection->put($this);
            
    }

    
    /**
     * Set a factory to seed the model
     * 
     * @return Factory
     */
    public static function factory()
    {
        return self::getInstance()->getQuery()->factory();
    }

    public static function seed($array, $persist)
    {
        return self::getInstance()->getQuery()->seed($array, $persist);
    }


}