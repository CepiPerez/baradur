<?php

/**
 * @method static Collection all()
 * @method static Model first()
 * @method static Paginator paginate(int $value)
 * @method static Collection pluck(string $column, string $key)
 * @method static Model|Collection find(string|array $value, string|array $columns='*')
 * @method static Model findOrFail(string $value)
 * @method static Model firstOrNew(array $attributes, array $values)
 * @method static Model firstOrCreate(array $attributes, array $values)
 * @method static Model updateOrCreate(array $attributes, array $values)
 * @method static Model upsert(array $records, array $keys, array $values)
 * @method static mixed insertOrIgnore(array $records)
 * @method static Builder select(string|array $column)
 * @method static Builder from(string $table, string $as=null)
 * @method static Builder addSelect(string|array $column)
 * @method static Builder selectRaw(string $select, array $bindings=array())
 * @method static Builder where(string|array|closure $column, string $param1, string $param2=null, string $boolean='AND')
 * @method static Builder whereNot(string|array|closure $column, string|array|null $param1=null, string|null $param2=null, string $boolean='AND')
 * @method static Builder whereIn(string $colum, array $values)
 * @method static Builder whereNotIn(string $colum, array $values)
 * @method static Builder whereColumn(string $first, string $operator, string $second=null, string $chain=null)
 * @method static Builder whereBetween(string $column, array $values, string $boolan='AND', bool $not=false)
 * @method static Builder whereNotBetween(string $column, array $values, string $boolan='AND', bool $not=false)
 * @method static Builder whereBetweenColumns(string $column, array $values, string $boolan='AND', bool $not=false)
 * @method static Builder whereNotBetweenColumns(string $column, array $values, string $boolan='AND', bool $not=false)
 * @method static Builder whereRelation(string $relation, string $column, string $comparator, string $value)
 * @method static Builder whereBelongsTo(string $related, string $relationshipName=null, $boolean='AND')
 * @method static Builder when($value, Closure $callback, Closure $defut=null)
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
    protected $primaryKey = 'id';
    protected $fillable = array();
    protected $guarded = null;
    protected $visible = array();
    protected $hidden = array();
    protected $casts = array();
    protected $with = array();
    protected $original = array();
    protected $attributes = array();
    protected $relations = array();
    protected $touches = array();
    protected $table = null;
    protected $appends = array();
    protected $wasRecentlyCreated = false;
    protected $connection = null;
    protected $_query;
    protected $_CREATED_AT = 'created_at';
    protected $_UPDATED_AT = 'updated_at';
    protected $_eagerLoad = null;
    protected $_global_scopes = array();

    protected static $modelsShouldPreventLazyLoading = false;
    protected static $modelsShouldPreventSilentlyDiscardingAttributes = false;
    protected static $modelsShouldPreventAccessingMissingAttributes = false;

    //protected static $attributeMutatorCache = array();
    //protected static $setAttributeMutatorCache = array();
    //protected static $getAttributeMutatorCache = array();

    //protected $attributeCastCache = array();
    protected static $castTypeCache = array();
    protected $classCastCache = array();
    
    public static $snakeAttributes = true;
    //protected static $mutatorCache = array();

    protected static $primitiveCastTypes = array(
        'array',
        'bool',
        'boolean',
        'collection',
        'custom_datetime',
        'date',
        'datetime',
        'decimal',
        'double',
        'encrypted',
        'encrypted:array',
        'encrypted:collection',
        'encrypted:json',
        'encrypted:object',
        'float',
        'immutable_date',
        'immutable_datetime',
        'immutable_custom_datetime',
        'int',
        'integer',
        'json',
        'object',
        'real',
        'string',
        'timestamp',
    );


    public static function preventsLazyLoading()
    {
        return Model::$modelsShouldPreventLazyLoading;
    }

    public static function preventsSilentlyDiscardingAttributes()
    {
        return Model::$modelsShouldPreventSilentlyDiscardingAttributes;
    }

    public static function preventsAccessingMissingAttributes()
    {
        return Model::$modelsShouldPreventAccessingMissingAttributes;
    }

    public static function shouldBeStrict($shouldBeStrict = true)
    {
        self::preventLazyLoading($shouldBeStrict);
        self::preventSilentlyDiscardingAttributes($shouldBeStrict);
        self::preventAccessingMissingAttributes($shouldBeStrict);
    }

    public static function preventLazyLoading($value = true)
    {
        self::$modelsShouldPreventLazyLoading = $value;
    }

    public static function preventSilentlyDiscardingAttributes($value = true)
    {
        self::$modelsShouldPreventSilentlyDiscardingAttributes = $value;
    }

    public static function preventAccessingMissingAttributes($value = true)
    {
        self::$modelsShouldPreventAccessingMissingAttributes = $value;
    }

    public function __construct($attributes=array())
    {
        if (!isset($this->table)) {
            $this->table = Helpers::camelCaseToSnakeCase(get_class($this));
        }

        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    protected function addGlobalScope($scope, $callback=null)
    {
        if (is_object($scope)) {
            $this->_global_scopes[get_class($scope)] = $scope;
        } else {
            $this->_global_scopes[$scope] = $callback;
        }
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
        return $this->timestamps;
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
    

    /* public function getAttribute($key)
    {
        return array_key_exists($key, $this->attributes)? $this->attributes[$key] : null;
    } */

    public function getRelations()
    {
        return is_array($this->relations) ? $this->relations : array();
    }

    public function getRelation($key)
    {
        return array_key_exists($key, $this->relations)? $this->relations[$key] : null;
    }

    public function getVisible()
    {
        return $this->visible;
    }

    public function __getGlobalScopes()
    {
        return $this->_global_scopes;
    }

    public function __setGlobalScopes($scopes=array())
    {
        $this->_global_scopes = $scopes;
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
        return isset($this->_useSoftDeletes) && $this->_useSoftDeletes==true;
    }

    /* public function usesHasFactory()
    {
        return isset($this->_hasFactory);
    } */


    /** @return Builder */
    public static function instance($parent, $table=null, $as=null)
    {
        return new Builder($parent, $table, $as);
    }

    /** @return Builder */
    public function getQuery($query=null)
    {            
        if (!isset($this->_query)) {
            $this->_query = $query? $query : new Builder(get_class($this));       
        }

        if ($this->_query->_collection->count()==0 && count($this->original)>0) {
            $this->_query->_collection->put($this);
        }

        return $this->_query;
    }

    public function setQuery($query)
    {
        $this->_query = $query;
    }

    public function newQuery()
    {
        return $this->newInstance()->getQuery();
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

    public function resolveRouteBinding($value, $field = null)
    {
        $key = $field ? $field : $this->getRouteKeyName();

        return $this->where($key, $value)->firstOrFail();
    }



    public function _setOriginalRelations($relations)
    {
        $this->_eagerLoad = $relations;
    }

    public function _setRecentlyCreated($val)
    {
        $this->wasRecentlyCreated = $val;
    }


    /* public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    } */

    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

   
    /* public function __get($name)
    {
        if (array_key_exists($name, $this->attributes))
            return $this->attributes[$name];

        if (array_key_exists($name, $this->relations))
            return $this->relations[$name];

        if ($name=='exists')
            return count($this->original)>0;

        if ($name=='wasRecentlyCreated')
            return $this->wasRecentlyCreated;
        
        if (method_exists($this, 'get'.ucfirst(Str::camel($name)).'Attribute'))
        {
            $fn = 'get'.Str::camel(ucfirst($name)).'Attribute';
            return $this->$fn();
        }

        if (method_exists($this, $name.'Attribute'))
        {
            $fn = $name.'Attribute';
            $nval = $this->$fn($name, (array)$this);
            if (isset($nval['get'])) {
                return $nval['get'];
            }
        }

        if (method_exists($this, $name))
        {
            global $preventLazyLoading;

            if ($preventLazyLoading)
                throw new LazyLoadingViolationException($this, $name);

            $this->load($name);
            
            return $this->relations[$name];
        }
        
        global $preventAccessingMissingAttributes;

        if ($preventAccessingMissingAttributes)
        {
            throw new MissingAttributeException($this, $name);
        }

        return null;
    } */

    public function __call($method, $arguments)
    {
        if (method_exists('Builder', $method)) {
            $calls = $this->newEloquentBuilder($this->getQuery());
            return call_user_func_array(array($calls, $method), $arguments);
        }
        
        if (Str::startsWith($method, 'through')) {
            $relationMethod = Str::of($method)->after('through')->lcfirst()->toString();
            if (method_exists($this, $relationMethod)) {
                return $this->through($relationMethod);
            }
        }

        //throw new BadMethodCallException("Method $method does not exist");
    }

    public function __get($key)
    {
        if ($key == 'wasRecentlyCreated') {
            return $this->wasRecentlyCreated;
        }

        return $this->getAttribute($key);
    }

    public function getAttribute($key)
    {
        if (! $key) {
            return;
        }

        // If the attribute exists in the attribute array or has a "get" mutator we will
        // get the attribute's value. Otherwise, we will proceed as if the developers
        // are asking for a relationship's value. This covers both types of values.
        if (array_key_exists($key, $this->attributes) ||
            array_key_exists($key, $this->casts) ||
            $this->hasGetMutator($key) ||
            $this->hasAttributeMutator($key) ||
            $this->isClassCastable($key))
        {
            return $this->getAttributeValue($key);
        }


        // Here we will determine if the model base class itself contains this given key
        // since we don't want to treat any of those methods as relationships because
        // they are all intended as helper methods and none of these are relations.
        /* if (method_exists(self::class, $key)) {
            return $this->throwMissingAttributeExceptionIfApplicable($key);
        } */

        return $this->isRelation($key) || $this->relationLoaded($key)
                    ? $this->getRelationValue($key)
                    : $this->throwMissingAttributeExceptionIfApplicable($key);
    }

    public function relationLoaded($key)
    {
        return array_key_exists($key, $this->relations);
    }

    public function isRelation($key)
    {
        if ($this->hasAttributeMutator($key)) {
            return false;
        }

        return method_exists($this, $key)/*  ||
               $this->relationResolver(static::class, $key) */;
    }

    public function getRelationValue($key)
    {
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        if (! $this->isRelation($key)) {
            return;
        }

        if (self::$modelsShouldPreventLazyLoading) {
            $this->handleLazyLoadingViolation($key);
        }

        return $this->getRelationshipFromMethod($key);
    }

    protected function handleLazyLoadingViolation($key)
    {
        /* if (isset(static::$lazyLoadingViolationCallback)) {
            return call_user_func(static::$lazyLoadingViolationCallback, $this, $key);
        } */

        if (/* ! $this->exists || */ $this->wasRecentlyCreated) {
            return;
        }

        throw new LazyLoadingViolationException($this, $key);
    }

    protected function getRelationshipFromMethod($method)
    {
        $this->load($method);
            
        return $this->relations[$method];
    }
    
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get'.Str::studly($key).'Attribute');
    }

    public function hasAttributeMutator($key)
    {
        return method_exists($this, Str::studly($key).'Attribute');
        
        /* if (isset(self::$attributeMutatorCache[get_class($this)][$key])) {
            return self::$attributeMutatorCache[get_class($this)][$key];
        }

        if (! method_exists($this, $method = Str::camel($key))) {
            return self::$attributeMutatorCache[get_class($this)][$key] = false;
        } */

        /* $reflection = new ReflectionMethod($this, $method);
        $returnType = $reflection->getReturnType();

        return self::$attributeMutatorCache[get_class($this)][$key] =
                    $returnType instanceof ReflectionNamedType &&
                    $returnType->getName() === Attribute::class; */
    }

    public function hasAttributeSetMutator($key)
    {
        return method_exists($this, Str::studly($key).'Attribute');

        /* $class = get_class($this);

        if (isset(self::$setAttributeMutatorCache[$class][$key])) {
            return self::$setAttributeMutatorCache[$class][$key];
        }

        if (! method_exists($this, $method = Str::camel($key).'Attribute')) {
            return self::$setAttributeMutatorCache[$class][$key] = false;
        } */

        /* $returnType = (new ReflectionMethod($this, $method))->getReturnType();

        return static::$setAttributeMutatorCache[$class][$key] =
                    $returnType instanceof ReflectionNamedType &&
                    $returnType->getName() === Attribute::class &&
                    is_callable($this->{$method}()->set); */
    }

    protected function isClassCastable($key)
    {
        $casts = $this->getCasts();

        if (! array_key_exists($key, $casts)) {
            return false;
        }

        $castType = $this->parseCasterClass($casts[$key]);

        if (in_array($castType, self::$primitiveCastTypes)) {
            return false;
        }

        global $_enum_list;
        if (isset($_enum_list[$castType])) {
            return true;
        }

        if (class_exists($castType)) {
            return true;
        }

        throw new InvalidCastException($this, $key, $castType);
    }
    
    protected function parseCasterClass($class)
    {
        return ! str_contains($class, ':')
            ? $class
            : reset(explode(':', $class, 2));
    }

    public function getAttributeValue($key)
    {
        return $this->transformModelValue($key, $this->getAttributeFromArray($key));
    }

    protected function getAttributeFromArray($key)
    {
        $attributes = $this->getAttributes();
        return array_key_exists($key, $attributes) ? $attributes[$key] : null;
    }

    protected function transformModelValue($key, $value)
    {
        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $value);
        } elseif ($this->hasAttributeGetMutator($key)) {
            return $this->mutateAttributeMarkedAttribute($key, $value);
        }

        // If the attribute exists within the cast array, we will convert it to
        // an appropriate native PHP type dependent upon the associated value
        // given with the key in the pair. Dayle made this comment line up.
        if ($this->hasCast($key)) {
            return $this->castAttribute($key, $value);
        }

        // If the attribute is listed as a date, we will convert it to a DateTime
        // instance on retrieval, which makes it quite convenient to work with
        // date fields without having to create a mutator for each property.
        if ($value !== null
            && in_array($key, $this->getDates(), false)) {
            return $this->asDateTime($value);
        }

        return $value;
    }

    protected function mutateAttribute($key, $value)
    {
        return $this->{'get'.Str::studly($key).'Attribute'}($value);
    }

    public function hasAttributeGetMutator($key)
    {
        return method_exists($this, Str::studly($key).'Attribute');

        /* if (isset(self::$getAttributeMutatorCache[get_class($this)][$key])) {
            return self::$getAttributeMutatorCache[get_class($this)][$key];
        }

        if (! $this->hasAttributeMutator($key)) {
            return self::$getAttributeMutatorCache[get_class($this)][$key] = false;
        }

        return false; */
        //return self::$getAttributeMutatorCache[get_class($this)][$key] = is_callable($this->{Str::camel($key)}()->get);
    }

    protected function castAttribute($key, $value)
    {
        $castType = $this->getCastType($key);

        if (is_null($value) && in_array($castType, self::$primitiveCastTypes)) {
            return $value;
        }

        // If the key is one of the encrypted castable types, we'll first decrypt
        // the value and update the cast type so we may leverage the following
        // logic for casting this value to any additionally specified types.
        /* if ($this->isEncryptedCastable($key)) {
            $value = $this->fromEncryptedString($value);

            $castType = Str::after($castType, 'encrypted:');
        } */
        $casts = $this->getCasts();

        switch ($castType) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return floatval($value);
            case 'decimal':
                return number_format($value, end(explode(':', $casts[$key], 2)));
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return $this->fromJson($value, true);
            case 'array':
            case 'json':
                return $this->fromJson($value);
            case 'collection':
                return new Collection($this->fromJson($value));
            case 'date':
                return $this->asDateTime($value);
            case 'datetime':
            case 'custom_datetime':
                return $this->asDateTime($value);
            case 'immutable_date':
                return $this->asDateTime($value)/* ->toImmutable() */;
            case 'immutable_custom_datetime':
            case 'immutable_datetime':
                return $this->asDateTime($value)/* ->toImmutable() */;
            case 'timestamp':
                return $this->asDateTime($value)->timestamp;
        }

        /* if ($this->isEnumCastable($key)) {
            return $this->getEnumCastableAttributeValue($key, $value);
        } */

        if ($this->isClassCastable($key)) {
            return $this->getClassCastableAttributeValue($key, $value);
        }

        return $value;
    }

    public function fromJson($value, $asObject = false)
    {
        return json_decode($value ? $value : '', ! $asObject);
    }

    protected function mutateAttributeMarkedAttribute($key, $value)
    {
        $method = Str::camel($key).'Attribute';
        $modelvalue = $this->$method($value, $this->attributes);

        return isset($modelvalue['get']) ? $modelvalue['get'] : $value;
        
        /* if (array_key_exists($key, $this->attributeCastCache)) {
            return $this->attributeCastCache[$key];
        }

        $attribute = $this->{Str::camel($key)}();

        $value = call_user_func($attribute->get ?: function ($value) {
            return $value;
        }, $value, $this->attributes);

        if ($attribute->withCaching || (is_object($value) && $attribute->withObjectCaching)) {
            $this->attributeCastCache[$key] = $value;
        } else {
            unset($this->attributeCastCache[$key]);
        }

        return $value; */
        return null;
    }

    public function hasCast($key, $types = null)
    {
        if (array_key_exists($key, $this->getCasts())) {
            return $types ? in_array($this->getCastType($key), (array) $types, true) : true;
        }

        return false;
    }

    protected function getCastType($key)
    {
        $castType = $this->getCasts();
        $castType = $castType[$key];

        if (isset(self::$castTypeCache[$castType])) {
            return self::$castTypeCache[$castType];
        }

        if ($this->isCustomDateTimeCast($castType)) {
            $convertedCastType = 'custom_datetime';
        } elseif ($this->isImmutableCustomDateTimeCast($castType)) {
            $convertedCastType = 'immutable_custom_datetime';
        } elseif ($this->isDecimalCast($castType)) {
            $convertedCastType = 'decimal';
        } else {
            $convertedCastType = trim(strtolower($castType));
        }

        return self::$castTypeCache[$castType] = $convertedCastType;
    }

    protected function isCustomDateTimeCast($cast)
    {
        return str_starts_with($cast, 'date:') ||
                str_starts_with($cast, 'datetime:');
    }

    protected function isImmutableCustomDateTimeCast($cast)
    {
        return str_starts_with($cast, 'immutable_date:') ||
                str_starts_with($cast, 'immutable_datetime:');
    }

    protected function isDecimalCast($cast)
    {
        return str_starts_with($cast, 'decimal:');
    }

    public function setAttribute($key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // this model, such as "json_encoding" a listing of data for storage.
        if ($this->hasSetMutator($key)) {
            $value = $this->setMutatedAttributeValue($key, $value);
        } elseif ($this->hasAttributeSetMutator($key)) {
            $value = $this->setAttributeMarkedMutatedAttributeValue($key, $value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        elseif (! is_null($value) && $this->isDateAttribute($key)) {
            $value = $this->fromDateTime($value);
        }

        /* if ($this->isEnumCastable($key)) {
            $this->setEnumCastableAttribute($key, $value);

            return $this;
        } */

        if ($this->isClassCastable($key)) {
            $this->setClassCastableAttribute($key, $value);

            return $this;
        }

        /* if (! is_null($value) && $this->isJsonCastable($key)) {
            $value = $this->castAttributeAsJson($key, $value);
        } */
        /* if (str_contains($key, '->')) {
            return $this->fillJsonAttribute($key, $value);
        } */

        /* if (! is_null($value) && $this->isEncryptedCastable($key)) {
            $value = $this->castAttributeAsEncryptedString($key, $value);
        } */

        $this->attributes[$key] = $value;

        return $this;
    }

    public function hasSetMutator($key)
    {
        return method_exists($this, 'set'.Str::studly($key).'Attribute');
    }

    protected function setMutatedAttributeValue($key, $value)
    {
        $method = 'set'.Str::studly($key).'Attribute';
        return $this->{$method}($value);
    }

    protected function setAttributeMarkedMutatedAttributeValue($key, $value)
    {
        $method = Str::camel($key).'Attribute';
        $modelvalue = $this->$method($value, $this->attributes);

        return isset($modelvalue['set']) ? $modelvalue['set'] : $value;

        /* $attribute = $this->{Str::camel($key)}();

        $callback = $attribute->set ?: function ($value) use ($key) {
            $this->attributes[$key] = $value;
        };

        $this->attributes = array_merge(
            $this->attributes,
            $this->normalizeCastClassResponse(
                $key, $callback($value, $this->attributes)
            )
        );

        if ($attribute->withCaching || (is_object($value) && $attribute->withObjectCaching)) {
            $this->attributeCastCache[$key] = $value;
        } else {
            unset($this->attributeCastCache[$key]);
        }

        return $this; */
    }

    protected function isDateAttribute($key)
    {
        return in_array($key, $this->getDates(), true) ||
            $this->isDateCastable($key);
    }

    protected function isDateCastable($key)
    {
        return $this->hasCast($key, array('date', 'datetime', 'immutable_date', 'immutable_datetime'));
    }

    public function getDates()
    {
        return $this->usesTimestamps() ? array(
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ) : array();
    }

    public function usesTimestamps()
    {
        return $this->timestamps; // && ! self::isIgnoringTimestamps($this::class);
    }

    public function fromDateTime($value)
    {
        return empty($value) ? $value : $this->asDateTime($value)/* ->format(
            $this->getDateFormat()
        ) */;
    }

    protected function asDateTime($value)
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        return Carbon::parse($value);
    }

    protected function setClassCastableAttribute($key, $value)
    {
        $caster = $this->resolveCasterClass($key);

        /* $this->attributes = array_replace(
            $this->attributes,
            $this->normalizeCastClassResponse($key, $caster->set(
                $this, $key, $value, $this->attributes
            ))
        ); */

        if ($caster instanceof EnumHelper) {
            $this->attributes[$key] = $caster->$value->value;
        }

        if (/* $caster instanceof CastsInboundAttributes || */ ! is_object($value)) {
            unset($this->classCastCache[$key]);
        } else {
            $this->classCastCache[$key] = $value;
        }
    }

    protected function resolveCasterClass($key)
    {
        $castType = $this->getCasts();
        $castType = $castType[$key];

        $arguments = array();

        if (is_string($castType) && str_contains($castType, ':')) {
            $segments = explode(':', $castType, 2);

            $castType = $segments[0];
            $arguments = explode(',', $segments[1]);
        }

        /* if (is_subclass_of($castType, Castable::class)) {
            $castType = $castType::castUsing($arguments);
        } */

        if (is_object($castType)) {
            return $castType;
        }

        global $_enum_list;

        if (isset($_enum_list[$castType])) {
            baradur_class_loader($_enum_list[$castType], true);
            return new $castType;
        }

        return new $castType($arguments);
    }


    public function __getWith()
    {
        return $this->with;
    }

    /* public function __parseAccessorAttributes()
    {
        foreach ($this->attributes as $key => $val)
        {
            $camel = Helpers::snakeCaseToCamelCase($key);

            if (method_exists($this, 'get'.ucfirst($camel).'Attribute'))
            {
                $fn = 'get'.ucfirst($camel).'Attribute';
                $this->$key = $this->$fn($val);
            }
            
            if (method_exists($this, $camel.'Attribute'))
            {
                $fn = $camel.'Attribute';
                $res = $this->$fn($val, $this->attributes);
                if (isset($res['get'])) $this->$key = $res['get'];
            }
        }
    } */

    /* public function __getMutatorAttribute($key, $value)
    {
        $camel = Helpers::snakeCaseToCamelCase($key);

        if (method_exists($this, 'set'.ucfirst($camel).'Attribute'))
        {
            $method = 'set'.ucfirst($camel).'Attribute';
            $value = $this->$method($value);
        }

        if (method_exists($this, $camel.'Attribute'))
        {
            $method = $camel.'Attribute';
            $res = $this->$method($value, $this->attributes);
            if (isset($res['set'])) $value = $res['set'];
        }

        if (is_bool($value)) {
            $value = $value==true? 1 : 0;
        }

        return $value;
    } */


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
    /* public function toArray()
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
    } */
    public function toArray()
    {
        return array_merge($this->attributesToArray(), $this->relationsToArray());
    }

    public function attributesToArray()
    {
        $attributes = $this->addDateAttributesToArray(
            $attributes = $this->getArrayableAttributes()
        );

        $attributes = $this->addMutatedAttributesToArray($attributes);

        $attributes = $this->addCastAttributesToArray($attributes);

        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        return $attributes;
    }

    protected function getMutatedAttributes($attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($this->hasGetMutator($key)) {
                $attributes[$key] = $this->mutateAttribute($key, $value);
            } elseif ($this->hasAttributeGetMutator($key)) {
                $attributes[$key] = $this->mutateAttributeMarkedAttribute($key, $value);
            }
    
            /* if ($this->hasCast($key)) {
                $attributes[$key] = $this->castAttribute($key, $value);
            } */
        }
        
        return $attributes;
    }

    protected function getArrayableRelations()
    {
        return $this->getArrayableItems($this->relations);
    }

    public function relationsToArray()
    {
        $attributes = array();

        foreach ($this->getArrayableRelations() as $key => $value) {
            // If the values implement the Arrayable interface we can just call this
            // toArray method on the instances which will convert both models and
            // collections to their proper array form and we'll set the values.
            if ($value instanceof Collection || $value instanceof Model) {
                $relation = $value->toArray();
            }

            // If the value is null, we'll still go ahead and set it in this list of
            // attributes, since null is used to represent empty relationships if
            // it has a has one or belongs to type relationships on the models.
            elseif (is_null($value)) {
                $relation = $value;
            }

            // If the relationships snake-casing is enabled, we will snake case this
            // key so that the relation attribute is snake cased in this returned
            // array to the developers, making this consistent with attributes.
            if (self::$snakeAttributes) {
                $key = Str::snake($key);
            }

            // If the relation value has been set, we will set it on this attributes
            // list for returning. If it was not arrayable or null, we'll not set
            // the value on the array because it is some type of invalid value.
            if (isset($relation) || is_null($value)) {
                $attributes[$key] = $relation;
            }

            unset($relation);
        }

        return $attributes;
    }

    protected function addCastAttributesToArray($attributes)
    {
        foreach ($this->getCasts() as $key => $value) {
            if (array_key_exists($key, $attributes)) {

                $attributes[$key] = $this->castAttribute(
                    $key, $attributes[$key]
                );

                if (isset($attributes[$key]) && in_array($value, array('date', 'datetime', 'immutable_date', 'immutable_datetime'))) {
                    $attributes[$key] = $this->serializeDate($attributes[$key]);
                }
    
                if (isset($attributes[$key]) && ($this->isCustomDateTimeCast($value) ||
                    $this->isImmutableCustomDateTimeCast($value))) {
                    $attributes[$key] = $attributes[$key]->format(end(explode(':', $value, 2)));
                }
    
                if ($attributes[$key] instanceof Carbon &&
                    $this->isClassCastable($key)) {
                    $attributes[$key] = $this->serializeDate($attributes[$key]);
                }

                if ($attributes[$key] instanceof EnumHelper) {
                    $attributes[$key] = $attributes[$key]->value;
                }
    
                /* if (isset($attributes[$key]) && $this->isClassSerializable($key)) {
                    $attributes[$key] = $this->serializeClassCastableAttribute($key, $attributes[$key]);
                } */
    
                /* if ($this->isEnumCastable($key) && (! ($attributes[$key] ?? null) instanceof Arrayable)) {
                    $attributes[$key] = isset($attributes[$key]) ? $this->getStorableEnumValue($attributes[$key]) : null;
                } */
    
                if ($attributes[$key] instanceof Collection || $attributes[$key] instanceof Model) {
                    $attributes[$key] = $attributes[$key]->toArray();
                }
            }
        }

        return $attributes;
    }


    protected function addDateAttributesToArray(array $attributes)
    {
        foreach ($this->getDates() as $key) {
            if (! isset($attributes[$key])) {
                continue;
            }

            $attributes[$key] = $this->serializeDate(
                $this->asDateTime($attributes[$key])
            );
        }

        return $attributes;
    }

    /* public static function cacheMutatedAttributes($classOrInstance)
    {
        $reflection = new ReflectionClass($classOrInstance);

        $class = $reflection->getName();

        self::$getAttributeMutatorCache[$class] =
            collect($attributeMutatorMethods = static::getAttributeMarkedMutatorMethods($classOrInstance))
                    ->mapWithKeys(function ($match) {
                        return [lcfirst(static::$snakeAttributes ? Str::snake($match) : $match) => true];
                    })->all();

        self::$mutatorCache[$class] = collect(static::getMutatorMethods($class))
                ->merge($attributeMutatorMethods)
                ->map(function ($match) {
                    return lcfirst(static::$snakeAttributes ? Str::snake($match) : $match);
                })->all();
    } */

    /* public function getMutatedAttributes()
    {
        if (! isset(self::$mutatorCache[$this])) {
            self::cacheMutatedAttributes($this);
        }

        return self::$mutatorCache[$this];
    } */

    protected function getArrayableAttributes()
    {
        return $this->getArrayableItems($this->getAttributes());
    }

    protected function addMutatedAttributesToArray($attributes)
    {
        foreach ($attributes as $key => $value) {
            
            if ($this->hasGetMutator($key)) {
                $attributes[$key] = $this->mutateAttribute($key, $value);
            } elseif ($this->hasAttributeGetMutator($key)) {
                $attributes[$key] = $this->mutateAttributeMarkedAttribute($key, $value);
            }
        }

        return $attributes;
    }

    protected function mutateAttributeForArray($key, $value)
    {
        if ($this->isClassCastable($key)) {
            $value = $this->getClassCastableAttributeValue($key, $value);
        } elseif ($this->hasAttributeGetMutator($key)) {
            $value = $this->mutateAttributeMarkedAttribute($key, $value);
        } else {
            $value = $this->mutateAttribute($key, $value);
        }

        $value = $value instanceof Carbon
            ? $this->serializeDate($value)
            : $value;

        return $value instanceof Collection || $value instanceof Model 
            ? $value->toArray() 
            : $value;
    }
    
    protected function getClassCastableAttributeValue($key, $value)
    {
        if (isset($this->classCastCache[$key])) {
            return $this->classCastCache[$key];
        } else {
            $caster = $this->resolveCasterClass($key);

            //dump($key, $value, ($caster instanceof EnumHelper), $caster, ($caster->$value));
            $value = $caster instanceof EnumHelper
                ? $caster->$value
                : $caster->get($this, $key, $value, $this->attributes);

            if (/* $caster instanceof CastsInboundAttributes || */ ! is_object($value)) {
                unset($this->classCastCache[$key]);
            } else {
                $this->classCastCache[$key] = $value;
            }

            return $value;
        }
    }



    public function checkObserver($function, $model)
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
        if ($value) {
            return $this->original[$value] != $this->attributes[$value];
        }

        foreach ($this->original as $key => $val) {
            if ($this->attributes[$key] != $val) {
                return true;
            }
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
        if ($value) {
            return $this->original[$value] == $this->attribute[$value];
        }

        $res = true;

        foreach ($this->original as $key => $val) {
            if ($this->attribute[$key] != $val) {
                $res = false;
                break;
            }
        }

        return $res;
    }

    public function __addEagerLoad($value)
    {
        $this->_eagerLoad[] = $value;
    }

    /**
     * Reload a fresh model instance from the database.
     *
     * @return Model
     */
    public function fresh()
    {
        if (count($this->original)==0) {
            throw new LogicException('Trying to re-retrieve from a new Model'); 
        }

        return $this->getQuery()->_fresh($this->original, $this->_eagerLoad);
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
        $this->_eagerLoad = $cloned->_eagerLoad;
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

    /* public function setAttribute($key, $val)
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
            if ($preventSilentlyDiscardingAttributes) {
                throw new MassAssignmentException(sprintf(
                    'Add [%s] to fillable property to allow mass assignment on [%s].',
                    $key, get_class($this)
                ));
            }
        }
    } */

    public function unsetAttribute($key)
    {
        unset($this->attributes[$key]);
    }

    public function setAttributes($attributes)
    {
        foreach ($attributes as $key => $val) {
            $this->attributes[$key] = $val;
        }
        //$this->attributes = $attributes;
    }

    public function setAppends($appends=array())
    {
        $this->appends = array();

        if (is_array($appends)) {
            foreach ($appends as $append) {
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
        foreach($attributes as $key => $val) {
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


    /**
     * Saves the model in database
     * 
     * @return bool
     */
    public function save()
    {
        $this->checkObserver('saving', $this);

        if (count($this->original)>0) {
            $result = $this->update();
        } else {
            $query = $this->getQuery();
            $query->_fillableOff = true;         
            $result = $query->create($this->attributes);
            $query->_fillableOff = false;
        }

        if ($result) {
            $this->checkObserver('saved', $this);
        }

        $this->touchOwners();

        return $result ? true : false;
    }

    /**
     * Update the column's update timestamp.
     *
     * @param  string|null  $column
     * @return int|false
     */
    public function touch($column = null)
    {
        return $this->getQuery()->touch($column);

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

        foreach ($this->relations as $models) {

            $models = $models instanceof Collection ? $models->all() : array($models);

            foreach (array_filter($models) as $model) {
                if (! $model->push()) {
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

        if ($this->timestamps) {
            $key = $this->_UPDATED_AT;
            $this->$key = now()->toDateTimeString();
        }

        $this->checkObserver('updating', $this);
        
        $result = $this->getQuery()->update($this->attributes);
        $this->_query = null;

        if ($result) {
            $this->checkObserver('updated', $this);
        }

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

        if ($res) {
            $this->checkObserver('deleted', $this);
        }

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
     * Create a pending has-many-through or has-one-through relationship.
     *
     * @return PendingHasThroughRelationship
     */
    public function through($relationship)
    {
        if (is_string($relationship)) {
            $relationship = $this->{$relationship}();
        }

        return new PendingHasThroughRelationship($this, $relationship);
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
        $relations = is_string($relations) ? array($relations) : $relations;            

        $query = $this;

        foreach ($relations as $relation) {
            $query = $query->getQuery()->loadAggregate($relation, $column, $function)->_collection->first();
            $query->setQuery(null);
        }

        return $query;//->_collection->first();
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

    /**
     * Get the relationships that are touched on save.
     *
     * @return array
     */
    public function getTouchedRelations()
    {
        return $this->touches;
    }

    /**
     * Set the relationships that are touched on save.
     *
     * @param  array  $touches
     * @return $this
     */
    public function setTouchedRelations($touches)
    {
        $this->touches = $touches;

        return $this;
    }

    /**
     * Determine if the model touches a given relation.
     *
     * @param  string  $relation
     * @return bool
     */
    public function touches($relation)
    {
        return in_array($relation, $this->getTouchedRelations());
    }

    /**
     * Touch the owning relations of the model.
     *
     * @return void
     */
    public function touchOwners()
    {
        foreach ($this->getTouchedRelations() as $relation) {
            $this->$relation()->touch();

            /* if ($this->$relation instanceof self) {
                $this->$relation->fireModelEvent('saved', false);

                $this->$relation->touchOwners();
            } elseif ($this->$relation instanceof Collection) {
                $this->$relation->each->touchOwners();
            } */
        }
    }
    
}