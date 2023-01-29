<?php

/**
 * @method static Collection all()
 * @method static Model first()
 * @method static Collection paginate(int $value)
 * @method static Model|Collection find(string|array $value, string|array $columns='*')
 * @method static Model findOrFail(string $value)
 * @method static Model firstOrNew(array $attributes, array $values)
 * @method static Model firstOrCreate(array $attributes, array $values)
 * @method static Model updateOrCreate(array $attributes, array $values)
 * @method static Model upsert(array $records, array $keys, array $values)
 * @method static mixed insertOrIgnore(array $records)
 * @method static Builder select(string|array $column)
 * @method static Builder addSelect(string|array $column)
 * @method static Builder selectRaw(string $select, array $bindings=array())
 * @method static Builder where(string|array|closure $column, string $param1, string $param2=null, string $boolean='AND')
 * @method static Builder whereNot(string|array|closure $column, string|array|null $param1=null, string|null $param2=null, string $boolean='AND')
 * @method static Builder whereIn(string $colum, array $values)
 * @method static Builder whereNotIn(string $colum, array $values)
 * @method static Builder whereColumn(string $first, string $operator, string $second=null, string $chain=null)
 * @method static Builder whereBetween(string $column, array $values)
 * @method static Builder whereRelation(string $relation, string $column, string $comparator, string $value)
 * @method static Builder whereBelongsTo(string $related, string $relationshipName=null, $boolean='AND')
 * @method static Builder when(bool $condition, Closure $callback, Closure $defut=null)
 * @method static Builder having(string|array $reference, string $operator=null, $value=null)
 * @method static Builder havingNull(string $reference)
 * @method static Builder havingNotNull(string $reference)
 * @method static Builder with(string|array $relations)
 * @method static Builder join($join_table, $column, $comparator=null, $join_column=null)
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
 * @method static Builder orderBy(string|Builder $column, string $order='ASC')
 * @method static Builder orderByRaw(string $order)
 * @method static int count(string $column)
 * @method static mixed min(string $column)
 * @method static mixed max(string $column)
 * @method static mixed avg(string $column)
 * @method static mixed average(string $column)
 * @method static Model|null create(array $record)
 * @method static Builder has(string $relation, string $comparator=null, string $value=null)
 * @method static Builder whereHas(string $relation, Builder $filter=null, string $comparator=null, string $value=null)
 * @method static Builder withWhereHas(string $relation, Builder|Closure $filter=null)
 * @method static Builder withoutGlobalScope(Scope|string $scope)
 * @method static Builder withoutGlobalScopes()
 * @method static Builder without($relations)
 * @method static Builder withOnly$relations)
 * @method static Builder toBase()
 * @method static Builder query()
 * @method static int|mixed destroy()
 * @method static Factory factory()
 * @method static void observe()
 * @method static mixed truncate()
 * @method static mixed forceDelete()
 * @method static mixed restore()
 */

class Model
{
    public $timestamps = true;
    protected $_CREATED_AT = 'created_at';
    protected $_UPDATED_AT = 'updated_at';
    protected $original = array();
    protected $_relations = null;
    protected $table = null;
    protected $primaryKey = 'id';
    protected $fillable = array();
    protected $guarded = null;
    protected $visible = array();
    protected $hidden = array();
    protected $_timestamps = null;
    protected $casts = array();
    protected $with = array();
    protected $wasRecentlyCreated = false;
    protected $attributes = array();
    protected $relations = array();
    protected $appends = array();
    protected $_query;
    protected $connection = null;
    public $_global_scopes = array();

    public function __construct($attributes=array())
    {
        if (!isset($this->table))
        {
            $this->table = Helpers::camelCaseToSnakeCase(get_class($this));
        }
        $this->_timestamps = $this->timestamps;

        foreach ($attributes as $key => $value)
        {
            $this->attributes[$key] = $value;
        }
    }

    protected function addGlobalScope($scope, $callback=null)
    {
        if (is_object($scope))
            $this->_global_scopes[get_class($scope)] = $scope;
        else
            $this->_global_scopes[$scope] = $callback;
    }

    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /** @return string */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    public function getRouteKey()
    {
        return $this->getAttribute($this->getRouteKeyName());
    }
    
    public function getRouteKeyName()
    {
        return $this->getKeyName();
    }

    public function getForeignKey()
    {
        return Str::snake(class_basename($this)).'_'.$this->getKeyName();
    }

    public function getFillable()
    {
        return $this->fillable;
    }

    public function getTimestamps()
    {
        return $this->_timestamps;
    }

    public function getCreatedAtColumn()
    {
        return $this->_CREATED_AT;
    }

    public function getUpdatedAtColumn()
    {
        return $this->_UPDATED_AT;
    }

    public function getGuarded()
    {
        return $this->guarded;
    }

    public function getCasts()
    {
        return $this->casts;
    }

    public function getAppends()
    {
        return $this->appends;
    }

    public function hasAppended($attribute)
    {
        return in_array($attribute, $this->appends);
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($key)
    {
        return array_key_exists($key, $this->attributes)? $this->attributes[$key] : null;
    }

    public function getRelations()
    {
        return $this->relations;
    }

    public function getRelation($key)
    {
        return array_key_exists($key, $this->relations)? $this->relations[$key] : null;
    }

    public function getVisible()
    {
        return $this->visible;
    }

    public function setVisible(array $visible)
    {
        $this->visible = $visible;

        return $this;
    }

    public function makeVisible($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->hidden = array_diff($this->hidden, $attributes);

        if (! empty($this->visible)) {
            $this->visible = array_merge($this->visible, $attributes);
        }

        return $this;
    }

    public function makeVisibleIf($condition, $attributes)
    {
        return value($condition, $this) ? $this->makeVisible($attributes) : $this;
    }

    public function getHidden()
    {
        return $this->hidden;
    }

    public function setHidden(array $hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function makeHidden($attributes)
    {
        $this->hidden = array_merge(
            $this->hidden, is_array($attributes) ? $attributes : func_get_args()
        );

        return $this;
    }
    
    public function makeHiddenIf($condition, $attributes)
    {
        return value($condition, $this) ? $this->makeHidden($attributes) : $this;
    }

    public function usesSoftDeletes()
    {
        return isset($this->useSoftDeletes) && $this->useSoftDeletes==true;
    }

    public function usesHasFactory()
    {
        return isset($this->hasFactory);
    }


    /** @return Builder */
    public static function instance($parent, $table=null)
    {
        return new Builder($parent, $table);
    }

    /** @return Builder */
    public function getQuery($query=null)
    {            
        if (!isset($this->_query))
        {
            $this->_query = $query? $query : new Builder(get_class($this));       
        }

        if ($this->_query->_collection->count()==0 && count($this->original)>0)
        {
            $this->_query->_collection->append($this);
        }

        return $this->_query;
    }

    public function setQuery($query)
    {
        $this->_query = $query;
    }

    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    public function newInstance($attributes = array())
    {
        $model = get_class($this);
        $model = new $model($attributes);

        $model->setConnection(
            $this->getConnectionName()
        );

        $model->setTable($this->getTable());

        $model->mergeCasts($this->casts);

        //$model->fill((array) $attributes);

        return $model;

    }

    public function mergeCasts($casts)
    {
        $this->casts = array_merge($this->casts, $casts);

        return $this;
    }

    public function syncOriginal()
    {
        $this->original = $this->attributes;

        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getConnectionName()
    {
        return $this->connection;
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }


    public function _setOriginalRelations($relations)
    {
        $this->_relations = $relations;
    }

    public function _setRecentlyCreated($val)
    {
        $this->wasRecentlyCreated = $val;
    }


    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

   
    public function __get($name)
    {
        if (array_key_exists($name, $this->attributes))
            return $this->attributes[$name];

        //if (array_key_exists($name, $this->appends))
        //    return $this->appends[$name];

        if (array_key_exists($name, $this->relations))
            return $this->relations[$name];

        if ($name=='exists')
            return count($this->original)>0;

        if ($name=='wasRecentlyCreated')
            return $this->wasRecentlyCreated;
        
        if (method_exists($this, 'get'.Str::camel(ucfirst($name)).'Attribute'))
        {
            $fn = 'get'.Str::camel(ucfirst($name)).'Attribute';
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
            
            return $this->relations[$name];
        }
        
        global $preventAccessingMissingAttributes;

        if ($preventAccessingMissingAttributes)
        {
            throw new Exception("The attribute [$name] either does not 
                exist or was not retrieved for model [".get_class($this)."]", 120);
        }

        return null;
    }

    public function __call($name, $arguments)
    {
        if (method_exists('Builder', $name))
        {
            $calls = $this->newEloquentBuilder($this->getQuery());
            return call_user_func_array(array($calls, $name), $arguments);
        }
    }

    public function __getWith()
    {
        return $this->with;
    }

    /**
     * Append attributes to query when building a query.
     *
     * @param  array|string  $attributes
     * @return Model
     */
    public function append($attributes)
    {
        $this->appends = array_unique(
            array_merge($this->appends, is_string($attributes) ? func_get_args() : $attributes)
        );

        return $this;
    }


    public function is($model)
    {
        return ! is_null($model) &&
            $this->getKey() === $model->getKey() &&
            $this->getTable() === $model->getTable() &&
            $this->getConnectionName() === $model->getConnectionName();
    }

    public function isNot($model)
    {
        return ! $this->is($model);
    }

    protected function getArrayableItems($values)
    {
        if (count($this->getVisible()) > 0) {
            $values = array_intersect_key($values, array_flip($this->getVisible()));
        }

        if (count($this->getHidden()) > 0) {
            $values = array_diff_key($values, array_flip($this->getHidden()));
        }

        return $values;
    }

    protected function getArrayableAppends()
    {
        if (! count($this->appends)) {
            return array();
        }

        return $this->getArrayableItems(
            array_combine($this->appends, $this->appends)
        );
    }

    /**
     * Returns model as array
     * 
     * @return array
     */
    public function toArray()
    {
        $values = $this instanceof DB ? 
            $this->getAttributes() :
            array_merge($this->getAttributes(), $this->getRelations());

            
        $values = $this->getArrayableItems($values);
        
        foreach ($this->getArrayableAppends() as $key) {
            $values[$key] = $this->$key;
        }

        $values = CastHelper::processCasts($values, $this, true);

        return Helpers::toArray($values);
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
     * Get the original Model attribute(s)
     * 
     * @param string $value
     * @return mixed
     */
    public function getOriginal($key=null, $default=null)
    {
        if ($key) {    
            return Arr::get($this->original, $key, $default);
        }

        return $this->original;
    }

    /**
     * Discard attribute changes and reset the attributes to their original state.
     *
     * @return $this
     */
    public function discardChanges()
    {
        $this->attributes = $this->original;

        return $this;
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
            return $this->original[$value] != $this->attributes[$value];

        foreach ($this->original as $key => $val)
        {
            if ($this->attributes[$key] != $val)
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
            return $this->original[$value] == $this->attribute[$value];

        $res = true;
        foreach ($this->original as $key => $val)
        {
            if ($this->attribute[$key] != $val)
            {
                $res = false;
                break;
            }
        }
        return $res;

    }

    /**
     * Reload a fresh model instance from the database.
     *
     * @return Model
     */
    public function fresh()
    {
        if (count($this->original)==0)
            throw new Exception('Trying to re-retrieve from a new Model'); 

        return $this->getQuery()->_fresh($this->original, $this->relations);

    }

    /**
     * Reload the current model instance with fresh attributes from the database.
     *
     * @return Model
     */
    public function refresh()
    {
        $cloned = $this->fresh();

        $this->attributes = $cloned->attributes;
        $this->appends = $cloned->appends;
        $this->relations = $cloned->relations;
        $this->_relations = $cloned->_relations;
        $this->original = $cloned->original;
        
        return $this;
    }

    public function setAppendAttribute($key, $val)
    {
        $this->appends = array_diff($this->appends, array($key));
        $this->appends[$key] = $val;
    }

    public function setRelationAttribute($key, $val)
    {
        $this->relations[$key] = $val;
    }

    public function setAttribute($key, $val)
    {
        global $preventSilentlyDiscardingAttributes;

        if (in_array($key, $this->fillable))
        {
            $this->attributes[$key] = $val;
        }
        elseif (isset($this->guarded) && !in_array($key, $this->guarded))
        {
            $this->attributes[$key] = $val;
        }
        else
        {
            if ($preventSilentlyDiscardingAttributes)
                throw new Exception("Add fillable property [$key] to allow mass assignment on [".get_class($this)."]");
        }
    }

    public function unsetAttribute($key)
    {
        unset($this->attributes[$key]);
    }

    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    public function setAppends($appends=array())
    {
        $this->appends = array();

        if (is_array($appends))
        {
            foreach ($appends as $append)
            {
                $this->appends($append);
            }
        }

        return $this;
    }

    protected function serializeDate($date)
    {
        return $date->toDateTimeString();
    }

    public function _getSerializedDate($date)
    {
        return $this->serializeDate($date);
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array  $attributes
     * @return $this
     *
     */
    public function fill($attributes)
    {
        foreach($attributes as $key => $val)
        {
            $this->setAttribute($key, $val);
        }
        return $this;
    }

    public function fillableOff()
    {
        return $this->getQuery()->_fillableOff = true;
    }

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
        if (count($this->original)>0)
        {
            return $this->update();
        }

        $query = $this->getQuery();
        $query->_fillableOff = true;         
        $result = $query->create($this->attributes);
        $query->_fillableOff = false;

        return $result ? true : false;
    }

    /**
     * Save the model and all of its relationships
     * 
     * @return bool
     */
    public function push()
    {
        if (! $this->save()) {
            return false;
        }

        foreach ($this->relations as $models)
        {
            $models = $models instanceof Collection
                ? $models->all() : array($models);

            foreach (array_filter($models) as $model)
            {
                if (! $model->push())
                {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Updates a record or an array of reccords in database
     * 
     * @param array $record
     * @return bool
     */
    public function update($attributes=array())
    {
        $this->fill($attributes);

        if ($this->_timestamps) {
            $key = $this->_UPDATED_AT;
            $this->$key = now()->toDateTimeString();
        }

        $result = $this->getQuery()->update($this->attributes);
        $this->_query = null;
     
        return $result;
    }

    /**
     * Deletes the current model from database
     * 
     * @return bool
     */
    public function delete()
    {
        $this->checkObserver('deleting', $this);

        $res = self::instance(get_class($this));
        $primary = $this->getKeyName();
        $res = $res->where($primary, $this->$primary)->delete();

        if ($res) $this->checkObserver('deleted', $this);

        return $res;
    }

    /**
     * Adds records from a sub-query inside the current records\
     * Check Laravel documentation
     * 
     * @return Model
     */
    public function load($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;
        
        $this->getQuery()->load($relations);

        $this->_query = null;

        return $this;
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
        $relations = is_string($relations) ? func_get_args() : $relations;            

        foreach ($relations as $relation)
        {
            $query = $this->getQuery()->loadAggregate($relation, $column, $function); //->first();
        }
        $this->_query = null;

        return $query->_collection->first();
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

    /**
     * Set the given relationship on the model.
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    /**
     * Unset a loaded relationship.
     *
     * @param  string  $relation
     * @return $this
     */
    public function unsetRelation($relation)
    {
        unset($this->relations[$relation]);

        return $this;
    }

    /**
     * Set the entire relations array on the model.
     *
     * @param  array  $relations
     * @return $this
     */
    public function setRelations($relations)
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * Duplicate the instance and unset all the loaded relations.
     *
     * @return $this
     */
    public function withoutRelations()
    {
        $model = clone $this;

        return $model->unsetRelations();
    }

    /**
     * Unset all the loaded relations for the instance.
     *
     * @return $this
     */
    public function unsetRelations()
    {
        $this->relations = array();

        return $this;
    }

}