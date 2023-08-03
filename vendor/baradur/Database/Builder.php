<?php

/**
 * @method Builder clone()
*/

Class Builder
{
    protected static $_macros = array();

    public $_model = null;
    public $table;
    public $_table_alias=null;
    public $_primary;
    public $_parent = null;
    public $_fillable;
    public $_guarded;
    public $_hidden;
    public $_appends;
    public $_routeKey;
    public $_softDelete;
    public $_collection = array();

    public $_foreign;
    public $_fillableOff = false;

    protected $withDefault;
    
    public $_relationship;

    public $_method = '';
    public $_where = '';
    public $_bindings = array();
    public $joins = array();
    public $_limit = null;
    public $_offset = null;
    public $_order = '';
    public $_group = '';
    public $_having = '';
    public $_union = '';
    public $_fromSub = '';
    public $_keys = array();
    public $_values = array();

    public $_eagerLoad = array();

    public $_connector;
    public $_extraquery = null;
    public $_original = null;

    public $_withTrashed = false;

    public $_relationVars = null;
    public $_loadedRelations = array();

    public $_scopes = array();

    public $grammar;

    public $_lastInsert = null;

    public $_hasConstraints = null;

    public $_toBase = false;

    protected $sql_connector = null;

    public $distinct = false;


    public function __construct($model, $table = null, $as = null)
    {
        if ($model instanceof Builder) {
            $base = $model->_model;
            $model = $model->_parent;
        } else {
            $base = new $model;
        }

        $this->_model = $base;
        $this->table = $table? $table : $base->getTable();
        $this->_table_alias = $as? $as : null;
        $this->_connector = $base->getConnectionName();
        $this->_primary = is_array($base->getKeyName())? $base->getKeyName() : array($base->getKeyName());
        $this->_parent = $model;
        $this->_fillable = $base->getFillable();
        $this->_guarded = $base->getGuarded();
        $this->_hidden = $base->getHidden();
        $this->_appends = $base->getAppends();
        $this->_routeKey = $base->getRouteKeyName();
        $this->_softDelete = $base->usesSoftDeletes()? 1 : 0;
        $this->_collection = new Collection(); //collectWithParent(null, $model);

        $this->grammar = new Grammar();
        
        foreach ($base->__getWith() as $with) {
            $this->_eagerLoad[$with] = array();
        }

        if ($model=='DB') {
            $this->_fillableOff = true;
        }
        
        $this->_method = "SELECT `". $this->getTableNameOrAlias() ."`.*";

    }


    public function __call($method, $parameters)
    {
        global $_class_list;

        if (method_exists($this->_parent, 'scope'.lcfirst($method))) {
            return $this->callScope(lcfirst($method), $parameters); 
        }

        if (isset(self::$_macros[$method])) {
            $class = self::$_macros[$method];

            if (is_closure($class)) {
                list($c, $m) = getCallbackFromString($class);
                $class = new $c($this->_parent);
            } elseif (isset($_class_list[$class])) {
                $class = new $class;
                $m = '__invoke';
            }

            $this->_clone($class);
            return executeCallback($class, $m, $parameters, $class, false);
        }

        if (Str::startsWith($method, 'where')) {
            return $this->dynamicWhere($method, $parameters);
        }

        if ($method=='as') {
            return $this->_as($parameters);
        }

        throw new BadMethodCallException("Method $method does not exist");
    }

    public function __paramsToArray()
    {
        $params = array();

        foreach ($this as $key => $val) {
            if ($key!='_macros' && $key!='grammar') {
                $params[$key] = $val;
            }
        }

        return $params;
    }

    /** @return Builder */
    public function _clone($cloned=null)
    {
        if (!$cloned) {
            $cloned = new Builder($this->_parent, $this->table, $this->_table_alias);
        }

        $cloned->_foreign = $this->_foreign; 
        $cloned->_fillableOff = $this->_fillableOff; 
        $cloned->_table_alias = $this->_table_alias; 
        $cloned->_relationship = $this->_relationship; 
        $cloned->_method = $this->_method; 
        $cloned->_where = $this->_where; 
        $cloned->_bindings = $this->_bindings; 
        $cloned->joins = $this->joins; 
        $cloned->_limit = $this->_limit; 
        $cloned->_offset = $this->_offset; 
        $cloned->_order = $this->_order; 
        $cloned->_group = $this->_group; 
        $cloned->_having = $this->_having; 
        $cloned->_union = $this->_union; 
        $cloned->_fromSub = $this->_fromSub; 
        $cloned->_keys = $this->_keys; 
        $cloned->_values = $this->_values; 
        $cloned->_eagerLoad = $this->_eagerLoad; 
        $cloned->_connector = $this->_connector; 
        $cloned->_extraquery = $this->_extraquery; 
        $cloned->_original = $this->_original; 
        $cloned->_withTrashed = $this->_withTrashed; 
        $cloned->_relationVars = $this->_relationVars; 
        $cloned->_loadedRelations = $this->_loadedRelations; 
        $cloned->_scopes = $this->_scopes; 
        $cloned->_toBase = $this->_toBase;
        $cloned->distinct = $this->distinct;

        return $cloned;
    }

    private function clear()
    {
        $this->_method = "SELECT `" . $this->getTableNameOrAlias() . "`.*";
        $this->_table_alias = null;
        $this->_where = '';
        $this->joins = array();
        $this->_limit = null;
        $this->_offset = null;
        $this->_group = '';
        $this->_union = '';
        $this->_having = '';
        $this->_order = '';
        $this->_fromSub = '';
        $this->_keys = array();
        $this->_values = array();
        $this->_bindings = array();
    }

    protected function setConnectorDriver($driver, $host, $user, $password, $database, $port=3306)
    {
        global $artisan;

        if (isset($_SERVER['USERNAME']) || isset($_SERVER['SHELL'])) {
            $host = "127.0.0.1";
        }
    
        if ($driver=='mysql') {
            return new PdoConnector($host, $user, $password, $database, $port);
        }

        if ($driver=='mysqli') {
            return new MysqliConnector($host, $user, $password, $database, $port);
        }

        if ($driver=='oracle') {
            return new OracleConnector($host, $user, $password, $database, $port);
        }

        throw new RuntimeException("Driver [$driver] not supported.");
    }
    
    /**
     * @return PdoConnector|MysqliConnector|OracleConnector
     */
    public function connector()
    {
        if (!$this->sql_connector) {

            if ($this->_connector) {
                $config = config('database.connections.'.$this->_connector);
                $this->sql_connector = $this->setConnectorDriver(
                    $config['driver'],
                    $config['host'],
                    $config['username'], 
                    $config['password'], 
                    $config['database'],
                    $config['port']
                );
            } else {
                global $database;

                if (!isset($database)) {
                    $default = config('database.default');
                    $config = config('database.connections.'.$default);
                    $database = $this->setConnectorDriver(
                        $config['driver'],
                        $config['host'],
                        $config['username'], 
                        $config['password'], 
                        $config['database'],
                        $config['port']
                    );
                }

                $this->sql_connector = $database;
            }
        }
        
        return $this->sql_connector;
    }


    private function buildQuery()
    {
        if ($this->distinct) {
            if (is_bool($this->distinct)) {
                $this->_method = str_replace('SELECT ', 'SELECT DISTINCT ', $this->_method);
            } else {
                $this->_method = 'SELECT DISTINCT ('. implode(', ', $this->distinct) . ')';
            }
        }

        $res = $this->_method;
        if ($this->_fromSub!='') {
            $res .= ' FROM ' . $this->_fromSub . ' ';
        } else {
            if (strpos($this->table, 'information_schema')===false) {
                $res .= ' FROM `' . $this->table . '` ';
            } else {
                $res .= ' FROM ' . $this->table . ' ';
            }

            if ($this->_table_alias) {
                $res .= ' as ' . $this->_table_alias . ' ';
            }
        }

        if (!empty($this->joins)) {
            $res .= $this->implodeJoinClauses() . ' ';
        }

        if ($this->_where != '') {
            $res .= $this->_where . ' ';
        }

        if ($this->_union != '') {
            $res .= $this->_union . ' ';
        }

        if ($this->_group != '') {
            $res .= $this->_group . ' ';
        }

        if ($this->_having != '') {
            $res .= $this->_having . ' ';
        }
        
        if ($this->_order != '') {
            $res .= $this->_order . ' ';
        }

        if (!$this->_limit && $this->_offset) {
            $this->_limit = 9999999;
        }

        if ($this->_limit) {
            $res .= ' LIMIT '.$this->_limit;
        }

        if ($this->_limit && !$this->_offset) {
            $this->_offset = 0;
        }

        if ($this->_offset) {
            $res .= ' OFFSET '.$this->_offset;
        }

        /* if ($this->_table_alias) {
            $res = str_replace('`'.$this->table.'`', '`'.$this->_table_alias.'`', $res);
        } */

        /* if (strpos(strtolower($res), ' join ')===false && count($this->_loadedRelations)==0)
        {
            $res = str_replace("`$this->table`.", '', $res);
        } */

        return trim(str_replace('  ', ' ', str_replace(' )', ')', $res)));
    }

    private function getTableNameOrAlias()
    {
        if ($this->_table_alias) {
            return $this->_table_alias;
        }

        return $this->table;
    }

    private function implodeJoinClauses()
    {
        $res = '';
        
        foreach ($this->joins as $query) {

            if ($query instanceof JoinClause) {
                $res .= ' ' . strtoupper($query->type) . ' JOIN ';

                if ($query->table instanceof Expression) {
                    $res .= $query->table->__toString();
                } else {
                    $query->grammar->setTablePrefix(null);
                    $res .=  $query->grammar->wrapTable($query->getTableNameOrAlias()); //'`'.$query->table.'`';
                }
                              
                if ($query->_where) {
                    $res .= ' ON' . ltrim($query->_where, 'WHERE');
                }
            } else {
                $res .= ' ' . $query;
            }
        }

        return $res;
    }

    private function __mergeBindings($clause, $bindings, $remove_actual_values = false)
    {
        if (!isset($this->_bindings[$clause])) {
            $this->_bindings[$clause] = array();
        }

        $this->_bindings[$clause] = $remove_actual_values
            ? $bindings
            : array_merge($this->_bindings[$clause], $bindings);
    }

    public static function __joinBindings($bindings)
    {
        if (!is_assoc($bindings)) {
            return $bindings;
        }

        $array = array();

        if (!empty($bindings['select'])) { foreach ($bindings['select'] as $b) $array[] = $b; }
        if (!empty($bindings['from'])) { foreach ($bindings['from'] as $b) $array[] = $b; }
        if (!empty($bindings['join'])) { foreach ($bindings['join'] as $b) $array[] = $b; }
        if (!empty($bindings['where'])) { foreach ($bindings['where'] as $b) $array[] = $b; }
        if (!empty($bindings['union'])) { foreach ($bindings['union'] as $b) $array[] = $b; }
        if (!empty($bindings['group'])) { foreach ($bindings['union'] as $b) $array[] = $b; }
        if (!empty($bindings['having'])) { foreach ($bindings['having'] as $b) $array[] = $b; }
        if (!empty($bindings['order'])) { foreach ($bindings['order'] as $b) $array[] = $b; }
        
        return $array;
    }

    public static function __getPlainSqlQuery($query, $bindings)
    {
        $bind = self::__joinBindings($bindings);

        foreach ($bind as $val) {
            if (is_string($val)) {
                $val = "'$val'";
            }
            $query = preg_replace('/\?/', $val, $query, 1);
        }

        return $query;
    }

    /**
     * Dump the current SQL and bindings.
     *
     * @return $this
     */
    public function dump()
    {
        dump($this->toSql() . "<br>" . str_replace('"', "'", json_encode($this->getBindings())));

        return $this;
    }

    /**
     * Die and dump the current SQL and bindings.
     *
     * @return never
     */
    public function dd()
    {
        dd($this->toSql(), str_replace('"', "'", json_encode($this->getBindings())));
    }

    /**
     * Get the raw SQL representation of the query with embedded bindings.
     *
     * @return string
     */
    public function toRawSql()
    {
        return $this->toPlainSql();
    }

    /**
     * Dump the raw current SQL with embedded bindings.
     *
     * @return $this
     */
    public function dumpRawSql()
    {
        dump($this->toRawSql());

        return $this;
    }

    /**
     * Die and dump the current SQL with embedded bindings.
     *
     * @return never
     */
    public function ddRawSql()
    {
        dd($this->toRawSql());
    }
    
    /**
     * Returns an array with the full query as a string
     * 
     * @return array
     */
    public function toSql()
    {
        return $this->buildQuery();
    }

    /**
     * Returns the full query as a string
     * including binded values
     * 
     * @return string
     */
    public function toPlainSql($query=null, $bindings=null)
    {
        if (!$query) {
            $query = $this->buildQuery();
        }

        if (!$bindings) {
            $bindings = $this->_bindings;
        }

        return self::__getPlainSqlQuery($query, $bindings);
    }

    /**
     * Get the current query value bindings in a flattened array.
     *
     * @return array
     */
    public function getBindings()
    {
        return self::__joinBindings($this->_bindings);
    }

    /**
     * Add a binding to the query.
     *
     * @param  mixed  $value
     * @param  string  $type
     * @return Builder
     */
    public function addBinding($bindings, $clause)
    {
        return self::__mergeBindings($clause, $bindings, false);
    }

    /**
     * Hidrates the result using Objects instead of Models
     * 
     * @return Builder
     */
    public function toBase()
    {
        $this->_toBase = true;

        return $this;
    }

    /**
     * Specifies the SELECT clause\
     * Returns the Query builder
     * 
     * @return Builder
     */
    public function selectRaw($sql = '*', $bindings = array())
    {
        $this->__mergeBindings('select', $bindings, true);

        $this->_method = 'SELECT ' . $sql;

        return $this;
    }

    private function getSelectColumns($select)
    {
        $columns = array();

        foreach($select as $key => $value) 
        {
            if ($value instanceof Builder) {
                $columns[] = '(' . $value->toSql() . ') as ' . $key;
            } elseif ($value instanceof Expression) {
                $columns[] = $value->__toString();
            } elseif (is_numeric($key)) {
                $columns[] = $this->grammar->setTablePrefix($this->getTableNameOrAlias())->wrapTable($value);           
            }            
        }

        return $columns;
    }

    /**
     * Specifies the SELECT clause\
     * Returns the Query builder
     * 
     * @param string $columns String containing colums divided by comma
     * @return Builder
     */
    public function select($select = '*')
    {
        if (!is_array($select)) {
            $select = func_get_args();
        }    

        $result = $this->getSelectColumns($select);

        $this->_method = 'SELECT ' . implode(', ', $result);
        
        return $this;
    }

    /**
     * Adds columns to the SELECT clause\
     * Returns the Query builder
     * 
     * @param string $columns
     * @return Builder
     */
    public function addSelect($select = '*')
    {
        if (!is_array($select)) {
            $select = func_get_args();
        }    

        $result = $this->getSelectColumns($select);
        
        $this->_method .= ', ' . implode(', ', $result);
        
        return $this;
    }

    /**
     * Force the query to only return distinct results.
     *
     * @return $this
     */
    public function distinct()
    {
        $columns = func_get_args();

        if (count($columns) > 0) {
            $this->distinct = is_array($columns[0]) || is_bool($columns[0]) ? $columns[0] : $columns;
        } else {
            $this->distinct = true;
        }

        return $this;
    }

    /**
     * Set the table which the query is targeting.
     *
     * @param  string  $table
     * @param  string|null  $as
     * @return Builder
     */
    public function from($table, $as = null)
    {
        $old = $this->table;

        $new_name = $as ? $as : $table; 

        $this->_method = str_replace("`$old`", "`$new_name`", $this->_method);
        $this->_where = str_replace("`$old`", "`$new_name`", $this->_where);

        foreach ($this->joins as $join) {
            $join->_method = str_replace("`$old`", "`$new_name`", $join->_method);
            $join->_where = str_replace("`$old`", "`$new_name`", $join->_where);
        }
        
        $this->table = $table;
        $this->_table_alias = $as;

        return $this;
    }

    /**
     * Specifies custom from\
     * Returns the Query builder
     * 
     * @param Builder $subquery
     * @param string $alias
     * @return Builder
     */
    public function fromSub($subquery, $alias)
    {
        $this->_fromSub = ' (' . $subquery->toSql() . ') ' . $alias;

        $this->addBinding($subquery->getBindings(), 'from');

        return $this;
    }

    /**
     * Add a raw or where clause to the query.
     *
     * @param  string  $sql
     * @param  mixed  $bindings
     *
     * @return Builder
     */
    public function whereRaw($sql, $bindings = array(), $boolean = 'AND')
    {
        foreach ($bindings as $v) {
            $this->_bindings['where'][] = $v;
        }

        if ($this->_where == '') {
            $this->_where = 'WHERE ' . $sql ;
        } else {
            $this->_where .= " $boolean " . $sql;
        }

        return $this;
    }

    /**
     * Add a raw or where clause to the query.
     *
     * @param  string  $sql
     * @param  mixed  $bindings
     * @return Builder
     */
    public function orWhereRaw($sql, $bindings = array())
    {
        return $this->whereRaw($sql, $bindings, 'OR');
    }

    /**
     * Handles dynamic "where" clauses to the query.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return Builder
     */
    private function dynamicWhere($method, $parameters)
    {
        $finder = substr($method, 5);

        $segments = preg_split('/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE);

        $connector = 'and';
        $index = 0;

        if (count($segments)>1) {
            $prev_where = $this->_where;
            $this->_where = '';
        }

        foreach ($segments as $segment) {
            if ($segment !== 'And' && $segment !== 'Or') {
                $this->addDynamicWhere($segment, $connector, $parameters, $index);
                $index++;
            } else {
                $connector = $segment;
            }
        }

        if (count($segments)>1) {
            if ($prev_where!='') {
                $this->_where = $prev_where . ' AND (' . str_replace('WHERE ', '', $this->_where) . ')';
            } else {
                $this->_where = 'WHERE (' . str_replace('WHERE ', '', $this->_where) . ')';
            }
        }

        return $this;
    }

    /**
     * Add a single dynamic where clause statement to the query.
     *
     * @param  string  $segment
     * @param  string  $connector
     * @param  array  $parameters
     * @param  int  $index
     */
    private function addDynamicWhere($segment, $connector, $parameters, $index)
    {
        $bool = strtoupper($connector);

        $this->where(Str::snake($segment), '=', $parameters[$index], $bool);
    }

    private function getArrayOfWheres($column, $boolean, $method = 'where')
    {
        $res = array();

        if (is_array($column[0])) {
            foreach($column as $col) {
                $res[] = $this->getArrayOfWheres($col, $boolean);
            }
        } elseif (!is_numeric($column[0])) {
            foreach ($column as $key => $val) {
                $res[] = $this->getWhere($key, $val, null);
            }
        } else {
            $res[] = $this->getWhere($column[0], $column[1], (isset($column[2])? $column[2] : null));
        }

        if (count($res)>1) {
            return '(' . implode(' '.$boolean.' ', $res) . ')';
        }

        return implode(' '.$boolean.' ', $res);
    }

    private function getWhere($column, $cond, $val=null, $class='where')
    {
        if ($val===null) {
            $val = $cond;
            $cond = '=';
        }

        $column = $this->grammar->setTablePrefix($this->getTableNameOrAlias())->wrapTable($column);

        $this->_bindings[$class][] = $val;

        return $column . ' ' . $cond . ' ?';

    }

    private function addWhere($column, $cond=null, $val=null, $boolean='AND')
    {
        if (is_closure($column)) {
            $prev_where = $this->_where;
            $this->_where = '';
            $this->getCallback($column, $this);
            $result = '(' . str_replace('WHERE ', '', $this->_where) . ')';
            $this->_where = $prev_where;
        } elseif (is_array($column)) {
            $result = $this->getArrayOfWheres($column, $boolean);
        } elseif ($column instanceof Expression) {
            $result = $column->__toString();
        } else {
            $result = $this->getWhere($column, $cond, $val, get_class($this)=='JoinClause'?'join':'where');
        }

        if ($this->_where == '') {
            $this->_where = 'WHERE ' . $result; //$column . ' ' . $cond . ' ' . $val; // ' ?';
        } else {
            $this->_where .= " $boolean " . $result; //$column . ' ' .$cond . ' ' . $val; // ' ?';
        }

        return $this;
    }

    /**
     * Adds a basic WHERE clause\
     * Returns the Query builder
     * 
     * @param string|Closure|Expression $column 
     * @param string $condition Can be ommited for '='
     * @param string $value
     * @return Builder
     */
    public function where($column, $cond=null, $val=null, $boolean='AND')
    {
        $this->addWhere($column, $cond, $val, $boolean);

        return $this;
    }

    /**
     * Adds a basice WHERE NOT clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $condition Can be ommited for '='
     * @param string $value
     * @return Builder
     */
    public function whereNot($column, $cond=null, $val=null, $boolean='AND')
    {
        $this->addWhere($column, $cond, $val, $boolean);
        $this->_where = str_replace('WHERE ', 'WHERE NOT ', $this->_where);

        return $this;
    }

    /**
     * Specifies OR in WHERE clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $condition Can be ommited for '='
     * @param string $value
     * @return Builder
     */
    public function orWhere($column, $cond=null, $val=null)
    {
        $this->addWhere($column, $cond, $val, 'OR');

        return $this;
    }

    private function splitStringIntoArray($string)
    {
        $array = array();

        foreach (explode(',', $string) as $value)
        {
            $array[] = trim($value);
        }

        return $array;
    }

    /**
     * Specifies the WHERE IN clause\
     * Returns the Query builder
     * 
     * @param Expression|string $column 
     * @param string|array $values
     * @return Builder
     */
    public function whereIn($column, $values, $boolean = 'AND', $not = null)
    {
        $final_array = array();

        if ($column instanceof Expression) {
            $column = $column->__toString();
        } else {
            $column = $column = $this->grammar->wrapTable($column);
        }

        if ($values instanceof Builder) {

            if ($this->_where == '') {
                $this->_where = 'WHERE `' . $column . ($not? ' NOT' : '') . ' IN ('. $values->toSql() .')';
            } else {
                $this->_where .= " $boolean `" . $column . ($not? ' NOT' : '') . ' IN ('. $values->toSql() .')';
            }

            $this->addBinding($values->getBindings(), 'where');

            return $this;
        }


        if (is_string($values)) {
            $values = $this->splitStringIntoArray($values);
        }

        foreach ($values as $val) {
            $final_array[] = '?';
            $this->_bindings['where'][] = $val;
        }

        if ($this->_where == '') {
            $this->_where = 'WHERE ' . $column . ($not? ' NOT' : '') . ' IN ('. implode(',', $final_array) .')';
        } else {
            $this->_where .= ' ' . $boolean . ' ' . $column . ($not? ' NOT' : '') . ' IN ('. implode(',', $final_array) .')';
        }

        return $this;
    }

    /**
     * Specifies the WHERE IN clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $values
     * @return Builder
     */
    public function orWhereIn($column, $values)
    {
        return $this->whereIn($column, $values, 'OR', false);
    }

    /**
     * Specifies the WHERE NOT IT clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $values
     * @return Builder
     */
    public function whereNotIn($column, $values)
    {
        return $this->whereIn($column, $values, 'AND', true);
    }

    /**
     * Specifies the WHERE NOT IT clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $values
     * @return Builder
     */
    public function orWhereNotIn($column, $values)
    {
        return $this->whereIn($column, $values, 'OR', true);
    }

    /**
     * Specifies the WHERE BETWEEN clause\
     * Returns the Query builder
     * 
     * @param Expression|string $column 
     * @param array $values
     * @return Builder
     */
    public function whereBetween($column, $values, $boolean = 'AND', $not = false)
    {
        if ($column instanceof Expression) {
            $column = $column->__toString();
        } else {
            $column = $this->grammar->setTablePrefix($this->getTableNameOrAlias())->wrapTable($column);
        }

        if (is_string($values)) {
            $values = $this->splitStringIntoArray($values);
        }

        foreach ($values as $val) {
            if ($val instanceof Carbon) {
                $val = $val->toDateTimeString();
            } elseif (is_string($val)) {
                $val = "'".$val."'";
            }
            
            $this->_bindings['where'][] = $val;
        }
        
        if ($this->_where == '') {
            $this->_where = "WHERE $column". ($not? ' NOT ': ' ') . "BETWEEN ? AND ?";
        } else {
            $this->_where .= " $boolean $column". ($not? ' NOT ': ' ') . "BETWEEN ? AND ?";
        }

        return $this;
    }

    /**
     * Specifies the WHERE BETWEEN clause\
     * Returns the Query builder
     * 
     * @param Expression|string $column 
     * @param array $values
     * @return Builder
     */
    public function orWhereBetween($column, $values)
    {
        return $this->whereBetween($column, $values, 'OR');
    }

    /**
     * Specifies the WHERE NOT BETWEEN clause\
     * Returns the Query builder
     * 
     * @param Expression|string $column 
     * @param array $values
     * @return Builder
     */
    public function whereNotBetween($column, $values)
    {
        return $this->whereBetween($column, $values, 'AND', true);
    }

    /**
     * Specifies the WHERE NOT BETWEEN clause\
     * Returns the Query builder
     * 
     * @param Expression|string $column 
     * @param array $values
     * @return Builder
     */
    public function orWhereNotBetween($column, $values)
    {
        return $this->whereBetween($column, $values, 'OR', true);
    }

    /**
     * Add a where between statement using columns to the query.
     *
     * @param  Expression|string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereBetweenColumns($column, $values, $boolean = 'AND', $not = false)
    {
        if (!is_array($values) || count($values)!==2) {
            throw new LogicException("Values must be an array of two columns.");
        }

        if ($column instanceof Expression) {
            $column = $column->__toString();
        } else {
            $column = $this->grammar->setTablePrefix(null)->wrapTable($column);
        }

        $first = $this->grammar->setTablePrefix(null)->wrapTable(reset($values));
        $second = $this->grammar->setTablePrefix(null)->wrapTable(end($values));

        if ($this->_where == '')  {
            $this->_where = "WHERE $first". ($not? ' NOT ': ' ') . "BETWEEN $first AND $second";
        } else {
            $this->_where .= " $boolean $first". ($not? ' NOT ': ' ') . "BETWEEN $first AND $second";
        }

        return $this;
    }

    /**
     * Add an or where between statement using columns to the query.
     *
     * @param  Expression|string  $column
     * @param  array  $values
     * @return $this
     */
    public function orWhereBetweenColumns($column, $values)
    {
        return $this->whereBetweenColumns($column, $values, 'OR');
    }

    /**
     * Add a where not between statement using columns to the query.
     *
     * @param  Expression|string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @return $this
     */
    public function whereNotBetweenColumns($column, $values, $boolean = 'AND')
    {
        return $this->whereBetweenColumns($column, $values, $boolean, true);
    }

    /**
     * Add an or where not between statement using columns to the query.
     *
     * @param  Expression|string  $column
     * @param  array  $values
     * @return $this
     */
    public function orWhereNotBetweenColumns($column, array $values)
    {
        return $this->whereNotBetweenColumns($column, $values, 'OR');
    }

    /**
     * Add a "where null" clause to the query.
     * Returns the Query builder
     * 
     * @param string|array $column 
     * @return Builder
     */
    public function whereNull($column, $boolean = 'AND', $not = false)
    {
        if (is_array($column)) {
            foreach ($column as $co) {
                $this->whereNull($co, false);
            }
        }

        if ($this->_where == '') {
            $this->_where = 'WHERE ' . $column . ($not? ' IS NOT': ' IS') . ' NULL';
        } else {
            $this->_where .= " $boolean " . $column . ($not? ' IS NOT': ' IS') . ' NULL';
        }

        return $this;
    }

    /**
     * Add an "or where null" clause to the query.
     *
     * @param  string|array  $column
     * @return Builder
     */
    public function orWhereNull($column)
    {
        return $this->whereNull($column, 'OR');
    }

    /**
     * Add a "where not null" clause to the query.
     *
     * @param  string|array  $columns
     * @param  string  $boolean
     * @return Builder
     */
    public function whereNotNull($columns, $boolean = 'and')
    {
        return $this->whereNull($columns, $boolean, true);
    }

    /**
     * Add an "or where not null" clause to the query.
     *
     * @param  string  $column
     * @return Builder
     */
    public function orWhereNotNull($column)
    {
        return $this->whereNotNull($column, 'or');
    }

    /**
     * Add a "where in raw" clause for integer values to the query.
     *
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @param  bool  $not
     * 
     * @return Builder
     */
    public function whereIntegerInRaw($column, $values, $boolean = 'AND', $not = false)
    {
        $values = Arr::flatten($values);

        foreach ($values as &$value) {
            $value = (int) $value;
        }

        $values = implode(', ', $values);

        if ($this->_where == '') {
            $this->_where = 'WHERE ' . $column . ($not? ' NOT' : '') . ' IN ('. $values .')';
        } else {
            $this->_where .= " $boolean " . $column . ($not? ' NOT' : '') . ' IN ('. $values .')';
        }

        return $this;
    }

    /**
     * Add an "or where in raw" clause for integer values to the query.
     *
     * @param  string  $column
     * @param  array  $values
     * @return $this
     */
    public function orWhereIntegerInRaw($column, $values)
    {
        return $this->whereIntegerInRaw($column, $values, 'or');
    }

    /**
     * Add a "where not in raw" clause for integer values to the query.
     *
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @return $this
     */
    public function whereIntegerNotInRaw($column, $values, $boolean = 'and')
    {
        return $this->whereIntegerInRaw($column, $values, $boolean, true);
    }

    /**
     * Add an "or where not in raw" clause for integer values to the query.
     *
     * @param  string  $column
     * @param  array  $values
     * @return $this
     */
    public function orWhereIntegerNotInRaw($column, $values)
    {
        return $this->whereIntegerNotInRaw($column, $values, 'or');
    }

    /**
     * Add an exists clause to the query.
     * Check Laravel doumentation.
     *
     * @return Builder
     */
    public function whereExists($callback, $boolean = 'AND', $not = false)
    {
        if (is_closure($callback)) {
            list($class, $method, $params) = getCallbackFromString($callback);
            $query = $this->query();
            $params[0] = $query;
            executeCallback($class, $method, $params, $this);
        } else {
            $query = $callback;
        }

        return $this->addWhereExistsQuery($query, $boolean, $not);
    }

    /**
     * Add an or exists clause to the query.
     * Check Laravel doumentation.
     *
     * @return Builder
     */
    public function orWhereExists($callback, $not = false)
    {
        return $this->whereExists($callback, 'OR', $not);
    }

    /**
     * Add a where not exists clause to the query.
     * Check Laravel doumentation.
     *
     * @return Builder
     */
    public function whereNotExists($callback, $boolean = 'AND')
    {
        return $this->whereExists($callback, $boolean, true);
    }

    /**
     * Add a where not exists clause to the query.
     * Check Laravel doumentation.
     *
     * @return Builder
     */
    public function orWhereNotExists($callback)
    {
        return $this->orWhereExists($callback, true);
    }

    /**
     * Add an exists clause to the query.
     * Check Laravel doumentation.
     *
     * @return Builder
     */
    public function addWhereExistsQuery($query, $boolean = 'AND', $not = false)
    {
        if ($this->_where == '') {
            $this->_where = 'WHERE EXISTS('. $query->toSql() . ')';
        } else {
            $this->_where .= " $boolean " . 'EXISTS('. $query->toSql() . ')';
        }

        $this->addBinding($query->getBindings(), 'where');

        return $this;
    }

    /**
     * Adds a where condition using row values.
     *
     * @param  array  $columns
     * @param  string  $operator
     * @param  array  $values
     * @param  string  $boolean
     * 
     * @return Builder
     */
    public function whereRowValues($columns, $operator, $values, $boolean = 'AND')
    {
        if (count($columns) !== count($values)) {
            throw new InvalidArgumentException('The number of columns must match the number of values');
        }

        for ($i=0; $i < count($columns); $i++) {
            $this->where($columns[$i], $operator, $values[$i], $boolean);
        }

        return $this;
    }

    /**
     * Adds an or where condition using row values.
     *
     * @param  array  $columns
     * @param  string  $operator
     * @param  array  $values
     * 
     * @return Builder
     */
    public function orWhereRowValues($columns, $operator, $values)
    {
        return $this->whereRowValues($columns, $operator, $values, 'or');
    }

    /**
     * Add a "where" clause comparing two columns to the query
     *
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @param  string  $chain
     * @return Builder
     */
    public function whereColumn($first, $operator, $second=null, $chain='and')
    {
        if ($second==null)
        {
            $second = $operator;
            $operator = '=';
        }

        $first = $this->grammar->setTablePrefix(null)->wrapTable($first);
        $second = $this->grammar->setTablePrefix(null)->wrapTable($second);

        if ($this->_where == '') {
            $this->_where = "WHERE $first $operator $second";
        } else {
            $this->_where .= " $chain $first $operator $second";
        }

        return $this;
    }

    private function addWhereDate($first, $operator, $second, $chain, $type)
    {
        if ($second===null) {
            $second = $operator;
            $operator = '=';
        }

        if (!($second instanceof Carbon)) {
            $second = Carbon::parse($second);
        }

        if ($type=='date') {
            $second = '"' . $second->toDateString() . '"';
        } elseif ($type=='year') {
            $first = "YEAR($first)";
            $second = '"'. $second->year . '"';
        } elseif ($type=='month') {
            $first = "MONTH($first)";
            $second = '"'. $second->month . '"';
        } elseif ($type=='day') {
            $first = "DAY($first)";
            $second = '"'. $second->day . '"';
        } elseif ($type=='time') {
            $first = "TIME($first)";
            $second = '"'. $second->rawFormat('H:i:s') . '"';
        }

        if ($this->_where == '') {
            $this->_where = "WHERE $first $operator $second";
        } else {
            $this->_where .= " $chain $first $operator $second";
        }

        return $this;

    }

    /**
     * Add a "where date" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string|null  $value
     * @param  string  $boolean
     * @return $this
     */
    public function whereDate($column, $operator, $value=null, $boolean='AND')
    {
        return $this->addWhereDate($column, $operator, $value, $boolean, 'date');
    }

    /**
     * Add an "or where date" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string|null  $value
     * @return $this
     */
    public function orWhereDate($column, $operator, $value=null, $boolean='OR')
    {
        return $this->addWhereDate($column, $operator, $value, $boolean, 'date');
    }

    /**
     * Add a "where year" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string|null  $value
     * @param  string  $boolean
     * @return $this
     */
    public function whereYear($column, $operator, $value=null, $boolean='AND')
    {
        return $this->addWhereDate($column, $operator, $value, $boolean, 'year');
    }

    /**
     * Add an "or where year" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string|null  $value
     * @return $this
     */
    public function orWhereYear($column, $operator, $value=null, $boolean='OR')
    {
        return $this->addWhereDate($column, $operator, $value, $boolean, 'year');
    }

    /**
     * Add a "where month" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string|null  $value
     * @param  string  $boolean
     * @return $this
     */
    public function whereMonth($column, $operator, $value=null, $boolean='AND')
    {
        return $this->addWhereDate($column, $operator, $value, $boolean, 'month');
    }

    /**
     * Add an "or where month" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string|null  $value
     * @return $this
     */
    public function orWhereMonth($column, $operator, $value=null, $boolean='OR')
    {
        return $this->addWhereDate($column, $operator, $value, $boolean, 'month');
    }

    /**
     * Add a "where day" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string|null  $value
     * @param  string  $boolean
     * @return $this
     */
    public function whereDay($column, $operator, $value=null, $boolean='AND')
    {
        return $this->addWhereDate($column, $operator, $value, $boolean, 'day');
    }

    /**
     * Add an "or where day" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string|null  $value
     * @return $this
     */
    public function orWhereDay($column, $operator, $value=null, $boolean='OR')
    {
        return $this->addWhereDate($column, $operator, $value, $boolean, 'day');
    }

    /**
     * Add a "where time" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string|null  $value
     * @param  string  $boolean
     * @return $this
     */
    public function whereTime($column, $operator, $value=null, $boolean='AND')
    {
        return $this->addWhereDate($column, $operator, $value, $boolean, 'time');
    }

    /**
     * Add an "or where time" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string|null  $value
     * @return $this
     */
    public function orWhereTime($column, $operator, $value=null, $boolean='OR')
    {
        return $this->addWhereDate($column, $operator, $value, $boolean, 'time');
    }

    /**
     * Add a "where fulltext" clause to the query.
     *
     * @param  string|array  $columns
     * @param  string  $value
     * @param  string  $boolean
     * @return Builder
     */
    public function whereFullText($columns, $value, $options = array(), $boolean = 'AND')
    {
        $columns = is_array($columns) ? $columns : array($columns);

        $wrapped = array();

        foreach ($columns as $column) {
            $wrapped[] = $this->grammar->wrap($column);
        }

        $mode = 'in natural language mode';

        if (array_key_exists('mode', $options)) {
            $mode = $options['mode'] === 'boolean' ? 'in boolean mode' : 'in natural language mode';
        }

        $expand = array_key_exists('expanded', $options) && $options['expanded'];

        if ($expand) {
            $mode = 'with query expansion';
        }

        if ($this->_where == '') {
            $this->_where = 'WHERE MATCH(' . implode(', ', $wrapped) . ") AGAINST (? $mode)";
        } else {
            $this->_where .= " $boolean MATCH(" . implode(', ', $wrapped) . ") AGAINST (? $mode)";
        }

        $this->_bindings['where'][] = $value;

        return $this;
    }

    /**
     * Add a "or where fulltext" clause to the query.
     *
     * @param  string|array  $columns
     * @param  string  $value
     * @return $this
     */
    public function orWhereFullText($columns, $value, $options = array())
    {
        return $this->whereFulltext($columns, $value, $options, 'OR');
    }


    private function getHaving($reference, $operator, $value=null)
    {
        if (is_array($reference)) {
            foreach ($reference as $item) {
                list($reference, $operator, $value) = $item;
                $this->having($reference, $operator, $value);
            }

            return $this;
        }

        if ($reference instanceof Expression) {
            return $reference->__toString();
        } 

        if (!$value) {
            $value = $operator;
            $operator = '=';
        }

        $reference = $this->grammar->setTablePrefix(null)->wrapTable($reference);

        $this->__mergeBindings('having', array($value));

        return $reference . ' ' . $operator . ' ?';// . $value;

    }

    /**
     * Add a "having" clause to the query.
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $operator 
     * @param string $value 
     * @return Builder
     */
    public function having($reference, $operator = null, $value = null, $boolean = 'AND')
    {
        $result = $this->getHaving($reference, $operator, $value);

        if ($this->_having == '') {
            $this->_having = 'HAVING ' . $result; //$reference . ' ' . $operator . ' ' . $value;
        } else {
            $this->_having .= ' ' . $result; //$boolean . ' ' . $reference . ' ' .$operator . ' ' . $value;
        }

        return $this;
    }

    /**
     * Add an "or having" clause to the query.
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $operator 
     * @param string $value 
     * @return Builder
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    /**
     * Add a "having null" clause to the query.
     *
     * @param  string  $reference
     * @param  string  $boolean
     * @param  bool  $not
     * @return Builder
     */
    public function havingNull($reference, $boolean = 'and', $not = false)
    {
        if ($this->_having == '') {
            $this->_having = 'HAVING ' . $reference . ($not? ' IS NOT': ' IS') . ' NULL';
        } else {
            $this->_having .= ' ' . $boolean . ' ' . $reference . ($not? ' IS NOT': ' IS') . ' NULL';
        }

        return $this;
    }

    /**
     * Add an "or having null" clause to the query.
     *
     * @param  string  $reference
     * @return Builder
     */
    public function orHavingNull($reference)
    {
        return $this->havingNull($reference, 'or');
    }

    /**
     * Add a "having not null" clause to the query.
     *
     * @param  string $reference
     * @param  string  $boolean
     * @return Builder
     */
    public function havingNotNull($reference, $boolean = 'and')
    {
        return $this->havingNull($reference, $boolean, true);
    }

    /**
     * Add an "or having not null" clause to the query.
     *
     * @param  string  $reference
     * @return Builder
     */
    public function orHavingNotNull($reference)
    {
        return $this->havingNotNull($reference, 'or');
    }

    /**
     * Specifies the HAVING clause between to values\
     * Returns the Query builder
     * 
     * @param string $reference
     * @param array $values
     * @return Builder
     */
    public function havingBetween($reference, $values)
    {
        $bindings = array();

        foreach ($values as $val) {
            if (is_string($val)) $val = "'".$val."'";
            array_push($bindings, $val);
        }

        $reference = $this->grammar->setTablePrefix(null)->wrapTable($reference);

        $this->__mergeBindings('having', $bindings);

        if ($this->_having == '') {
            $this->_having = "HAVING $reference BETWEEN ? AND ?";
        } else {
            $this->_having = " AND $reference BETWEEN ? AND ?";
        }

        return $this;
    }

    /**
     * Add a raw having clause to the query.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  string  $boolean
     * @return Builder
     */
    public function havingRaw($sql, $bindings = array(), $boolean = 'AND')
    {
        $this->__mergeBindings('having', $bindings);

        if ($this->_having == '') {
            $this->_having = 'HAVING ' . $sql;
        } else {
            $this->_having = " $boolean " . $sql;
        }

        return $this;
    }

    /**
     * Add a raw or having clause to the query.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @return Builder
     */
    public function orHavingRaw($sql, $bindings = array())
    {
        return $this->havingRaw($sql, $bindings, 'OR');
    }

    private function getCallback($callback, $query, $extra_param=null)
    {
        if (is_closure($callback)) {
            list($class, $method, $params) = getCallbackFromString($callback);
            
            $params[0] = $query;

            if ($extra_param) {
                $params[1] = $extra_param;
            }

            return executeCallback($class, $method, $params, $this);
        }
    }

     /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * @param  $value
     * @param  Closure  $callback
     * @param  Closure  $default
     * @return Builder
     */
    public function when($value, $callback, $default = null)
    {
        if ($value) {
            $this->getCallback($callback, $this, $value);
        } elseif ($default) {
            $this->getCallback($default, $this, $value);
        }

        return $this;
    }

    /**
     * Add a join clause to the query.
     *
     * @param  string  $table
     * @param  Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool  $where
     * @return Builder
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        $join = $this->newJoinClause($this, $type, $table);

        if (is_closure($first)) {
            $this->getCallback($first, $join);
            $this->joins[] = $join;
            $this->addBinding($join->getBindings(), 'join');
        } else {
            $method = $where ? 'where' : 'on';
            $this->joins[] = $join->$method($first, $operator, $second);
            $this->addBinding($join->getBindings(), 'join');
        }

        return $this;
    }

    /**
     * Add a "join where" clause to the query.
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string  $operator
     * @param  string  $second
     * @param  string  $type
     * @return Builder
     */
    public function joinWhere($table, $first, $operator, $second, $type = 'inner')
    {
        return $this->join($table, $first, $operator, $second, $type, true);
    }

    /**
     * Add a subquery join clause to the query.
     *
     * @param  \Closure|Builder|string  $query
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool  $where
     * @return Builder
     */
    public function joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        list($query, $bindings) = $this->createSub($query);

        $expression = '('.$query.') as '.$this->grammar->wrapTable($as);

        $this->addBinding($bindings, 'join');

        return $this->join(new Expression($expression), $first, $operator, $second, $type, $where);
    }

    /**
     * Add a left join to the query.
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return Builder
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a "join where" clause to the query.
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return Builder
     */
    public function leftJoinWhere($table, $first, $operator, $second)
    {
        return $this->joinWhere($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a subquery left join to the query.
     *
     * @param  \Closure|Builder|string  $query
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return Builder
     */
    public function leftJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($query, $as, $first, $operator, $second, 'left');
    }

    /**
     * Add a right join to the query.
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return Builder
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Add a "right join where" clause to the query.
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return Builder
     */
    public function rightJoinWhere($table, $first, $operator, $second)
    {
        return $this->joinWhere($table, $first, $operator, $second, 'right');
    }

    /**
     * Add a subquery right join to the query.
     *
     * @param  \Closure|Builder|string  $query
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return Builder
     */
    public function rightJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($query, $as, $first, $operator, $second, 'right');
    }

    /**
     * Add a "cross join" clause to the query.
     *
     * @param  string  $table
     * @param  Closure|string|null  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return $this
     */
    public function crossJoin($table, $first = null, $operator = null, $second = null)
    {
        if ($first) {
            return $this->join($table, $first, $operator, $second, 'cross');
        }

        $this->joins[] = $this->newJoinClause($this, 'cross', $table);

        return $this;
    }

    /**
     * Add a subquery cross join to the query.
     *
     * @param  Closure|Builder|string  $query
     * @param  string  $as
     * @return Builder
     */
    public function crossJoinSub($query, $as)
    {
        list($query, $bindings) = $this->createSub($query);

        $expression = '('.$query.') as '.$this->grammar->wrapTable($as);

        $this->addBinding($bindings, 'join');

        $this->joins[] = $this->newJoinClause($this, 'cross', new Expression($expression));

        return $this;
    }

    /**
     * Get a new join clause.
     *
     * @param  Builder  $parentQuery
     * @param  string  $type
     * @param  string  $table
     * @return JoinClause
     */
    public function newJoinClause($parentQuery, $type, $table)
    {
        return new JoinClause($parentQuery, $type, $table);
    }

    /**
     * Creates a subquery and parse it.
     *
     * @param  Closure|Builder|string  $query
     * @return array
     */
    protected function createSub($query)
    {
        if (is_string($query)) {
            return array($query, array());
        }

        if (is_closure($query)) {
            $query = $this->getCallback($query, $this);
        }

        return array($query->toSql(), $query->getBindings());
    }

    /**
     * Specifies the UNION clause\
     * Returns the Query builder
     * 
     * @param Builder $query
     * @return Builder
     */
    public function union($query)
    {
        $this->_union = 'UNION ' . $query->toSql();

        if (count($query->_bindings['union'])>0) {
            $this->_bindings['union'] = array_merge($this->_bindings['union'], $query->_bindings['union']);
        }

        return $this;

    }

    /**
     * Specifies the UNION ALL clause\
     * Returns the Query builder
     * 
     * @param Builder $query
     * @return Builder
     */
    public function unionAll($query)
    {
        $this->_union = 'UNION ALL ' . $query->toSql();

        $bindings = self::__joinBindings($query->_bindings);
        $this->__mergeBindings('union', $bindings);

        return $this;
    }

    /**
     * Specifies the GROUP BY clause\
     * Returns the Query builder
     * 
     * @param string $order
     * @return Builder
     */
    public function groupBy($val)
    {
        if ($this->_group == '') {
            $this->_group = 'GROUP BY ' . $this->grammar->wrapTable($val);
        } else {
            $this->_group .= ', ' . $this->grammar->wrapTable($val);
        }

        return $this;
    }

    /**
     * Specifies the GROUP BY clause\
     * Returns the Query builder
     * 
     * @param string $order
     * @return Builder
     */
    public function groupByRaw($val, $bindings=array())
    {
        $this->__mergeBindings('group', $bindings);

        if ($this->_group == '') {
            $this->_group = 'GROUP BY ' . $val;
        } else {
            $this->_group .= ', ' . $val;
        }

        return $this;
    }

    /**
     * Orders the result by date or specified column\
     * Returns the Query builder
     * 
     * @return Builder
     */
    public function latest($column='created_at')
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Orders the result by date or specified column\
     * Returns the Query builder
     * 
     * @return Builder
     */
    public function oldest($column='created_at')
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * Add a descending "order by" clause to the query.
     *
     * @return Builder
     */
    public function orderByDesc($column)
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Specifies the ORDER BY clause\
     * Returns the Query builder
     * 
     * @param string|Builder $column
     * @param string $order
     * @return Builder
     */
    public function orderBy($column, $order='ASC')
    {
        if ($column instanceof Builder) {
            $bindings = self::__joinBindings($column->_bindings);

            $this->__mergeBindings('order', $bindings);

            $column = new Expression('(' . $column->buildQuery() . ')');
        }

        $column = $this->grammar->setTablePrefix($this->getTableNameOrAlias())->wrapTable($column);

        if ($this->_order == '') {
            $this->_order = "ORDER BY $column $order";
        } else {
            $this->_order .= ", $column $order";
        }

        return $this;
    }

    /**
     * Specifies the ORDER BY clause without changing it\
     * Returns the Query builder
     * 
     * @param string $sql
     * @param array $bindings
     * @return Builder
     */
    public function orderByRaw($sql, $bindings = array())
    {
        $this->__mergeBindings('order', $bindings);

        if ($this->_order == '') {
            $this->_order = "ORDER BY $sql";
        } else {
            $this->_order .= " ORDER BY $sql";
        }

        return $this;
    }

    /**
     * Put the query's results in random order.
     *
     * @return $this
     */
    public function inRandomOrder()
    {
        return $this->orderByRaw('RANDOM()');
    }

    /**
     * Remove all existing orders and optionally add a new order.
     *
     * @param  Expression|string|null $column
     * @param  string $direction
     * @return $this
     */
    public function reorder($column = null, $direction = 'ASC')
    {
        $this->_order = null;

        if ($column) {
            return $this->orderBy($column, $direction);
        }

        return $this;
    }


    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int  $value
     * @return Builder
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Specifies the LIMIT clause\
     * Returns the Query builder
     * 
     * @param string $value
     * @return Builder
     */
    public function limit($value)
    {
        $this->_limit = $value;
        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     *
     * @param  int  $value
     * @return Builder
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * Specifies the LIMIT clause\
     * Returns the Query builder
     * 
     * @param string $value
     * @return Builder
     */
    public function offset($value)
    {
        $this->_offset = $value;
        return $this;
    }

    /**
     * Specifies the SET clause\
     * Allows array with key=>value pairs in $key\
     * Returns the Query builder
     * 
     * @param string $key
     * @param string $value
     * @return Builder
     */
    public function set($key, $val=null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            array_push($this->_keys, $key);

            $val = Helpers::ensureValueIsNotObject($val);
            
            array_push($this->_values, $val); //isset($val)? $val : "NULL");  // WTF is this?????
        }

        return $this;
    }

    /**
     * INSERT a record or an array of records in database
     * 
     * @param array $record
     * @return bool
     */
    public function insert($values)
    {
        if (!is_array(reset($values))) {
            return $this->_insert($values);
        }

        foreach ($values as $value) {
            $res = $this->_insert($value);
        }

        return $res;
    }

    /**
     * UPSERT a record or an array of records in database
     * 
     * @param array $records
     * @param string|array $keys
     * @param string|array $values
     * @return bool
     */
    public function upsert($records, $keys, $values)
    {
        $res = true;

        if ($records instanceof Collection) {
            $records = $records->toArray();
        }

        $values = is_array($values) ? $values : array($values);
        
        $attributes = array();

        if ($this->_model->getTimestamps()) {
            $created_at = $this->_model->getCreatedAtColumn();
            $updated_at = $this->_model->getUpdatedAtColumn();
        } else {
            $created_at = null;
            $updated_at = null;
        }

        $to_insert = array();

        foreach ($records as $record) {

            if ($record instanceof Model) {
                $record = $record->toArray();
            } elseif (is_object($record)) {
                $record = (array)$record;
            }
            
            /* foreach ($keys as $key) {
                $attributes[] = $record[$key];
            } */

            foreach ($this->_primary as $primary) {
                $attributes[] = $record[$primary];
            }

            if (!array_key_exists($created_at, $record) && $created_at) {
                $record[$created_at] = now()->toDateTimeString();
            }
            
            if (!array_key_exists($updated_at, $record) && $updated_at) {
                $record[$updated_at] = now()->toDateTimeString();
            }

            $to_insert[] = $record;
        }
        
        //dd($created_at, $updated_at, $attributes, $to_insert);

        $sql = 'INSERT INTO `' . $this->table . "` (`";
        
        $sql .= implode("`, `", array_keys($records[0])) . "`) VALUES ";

        foreach ($to_insert as $item) {
            $sql .= '(';
            foreach ($item as $val) {
                $sql .= is_string($val) ? "'$val', " : "$val, ";
            }
            $sql = rtrim($sql, ', ');
            $sql .= '), ';
        }
        
        $sql = rtrim($sql, ', ');
        $sql .= ' ON DUPLICATE KEY UPDATE ';
        
        foreach ($values as $value) {
            $sql .= " `$value` = values(`$value`), ";
        }

        if ($updated_at) {
            $sql .= "`$updated_at` = values(`$updated_at`)";
        } else {
            $sql = rtrim($sql, ', ');
        }

        //dd($sql);
        
        $query = $this->connector()->execSql($sql, $this->_values);

        $this->clear();
        
        return $query;
    }

    private function _insert($record, $ignore=false)
    {
        $this->clear();

        //$record = CastHelper::processCastsBack($record, $this->_model);

        if ($this->_parent=='Model') {
            $this->_fillableOff = true;
        }

        $attributes = $record instanceof Model? $record->getAttributes() : $record;

        foreach ($attributes as $key => $val) {
            $this->setValues($key, $val);
        }

        if (count($this->_values)==0) {
            throw new LogicException("Error setting values for new model");
        }

        if ($this->_model->timestamps && 
            !isset($this->_values[$this->_model->getCreatedAtColumn()]) && 
            $this->_parent!='DB' && 
            $this->_parent!='Model')
        {
            $this->set($this->_model->getCreatedAtColumn(), now()->toDateTimeString());
        }

        $qmarks = array();

        for ($i=0; $i<count($this->_values); $i++) {
            $qmarks[] = '?';
        }

        $sql = 'INSERT ' . ($ignore? 'IGNORE ' : '') . 'INTO `' . $this->table . 
            '` (' . implode(', ', $this->_keys) . ') VALUES (' . implode(', ', $qmarks) . ')';

        $query = $this->connector()->execSql($sql, $this->_values);
    
        $last = array();

        for ($i=0; $i<count($this->_keys); ++$i) {
            $last[$this->_keys[$i]] = $this->_values[$i];
        }

        $this->_lastInsert = $last;

        $this->clear();
        
        return $query;
    }


    /**
     * INSERT IGNORE a record or an array of records in database
     * 
     * @param array $record
     * @return bool
     */
    public function insertOrIgnore($values)
    {
        if (!is_array(reset($values))) {    
            return $this->_insert($values, true);
        }

        foreach ($values as $value) {
            $res = $this->_insert($value, true);
        }

        return $res;
    }

    private function setValues($key, $val, $unset=false, $return=false)
    {
        global $preventSilentlyDiscardingAttributes;

        if ($key=='_global_scopes' || $key=='_query' || $key=='timestamps') {
            return $this;
        }

        if (in_array($key, $this->_fillable) || $this->_fillableOff) {
            $this->set($key, $val);
        } elseif (isset($this->_guarded) && !in_array($key, $this->_guarded)) {
            $this->set($key, $val);
        } else {
            if ($unset) {
                unset($record[$key]);
            }

            if ($preventSilentlyDiscardingAttributes) {
                throw new MassAssignmentException(sprintf(
                    'Add [%s] to fillable property to allow mass assignment on [%s].',
                    $key, $this->_parent
                ));
            }
        }
        
        return $this; 
    }

    /**
     * Creates a new record in database\
     * Returns new record
     * 
     * @param array $record
     * @return Model|null
     */
    public function create($record = null)
    {
        if (isset($this->_relationVars) 
            && $this->_relationVars['relationship']=='morphOne'
            && isset($this->_relationVars['current_id']))
        {
            $record[$this->_relationVars['foreign']] = $this->_relationVars['current_id'];
            $record[$this->_relationVars['relation_type']] = $this->_relationVars['current_type'];
        }
        elseif (isset($this->_relationVars['foreign']) && count($this->_relationVars['where_in'])>0)
        {
            if (!isset($record[$this->_relationVars['foreign']])) {
                $record[$this->_relationVars['foreign']] = $this->_relationVars['where_in'][0];
            }
        }

        $new = new $this->_parent;

        foreach ($record as $key => $val) {
            $new->$key = $val;
        }

        $this->_model->checkObserver('creating', $new);

        if ($this->_insert($new)) {
            $item = $this->insertNewItem();
            $item->_setRecentlyCreated(true);
            $this->_model->checkObserver('created', $item);
            return $item;
        }
        
        return null;

    }

    /**
     * Creates the new records in database\
     * 
     * @param array $record
     * @return bool
     */
    public function createMany($records=array())
    {
        if (!isset($this->_relationVars['foreign']) || count($this->_relationVars['where_in'])==0) {
            throw RelationNotFoundException::make($this->_parent, $this->_relationVars['foreign']);
        }

        foreach($records as $record) {
            $record[$this->_relationVars['foreign']] = $this->_relationVars['where_in'][0];
            $this->_clone()->create($record);
        }
        
        return true;
    }

    /**
     * Saves the model in database
     * 
     * @return bool
     */
    public function save($model)
    {
        if(!($model instanceof Model)) {
            throw new LogicException('No model asigned');
        }

        if(!isset($this->_relationVars['foreign'])) {
            throw RelationNotFoundException::make($this->_parent, $this->_relationVars['foreign']);
        }

        $res = Model::instance(get_class($model));

        $res = $res->updateOrCreate(
            array($this->_relationVars['foreign'] => $this->_relationVars['where_in'][0]), 
            $model->attributes
        );

        return true;
    }

    /**
     * Saves multiple models in database
     * 
     * @return bool
     */
    public function saveMany($models)
    {
        $models = is_array($models)? $models : array($models);

        if(!isset($this->_relationVars['foreign'])) {
            throw RelationNotFoundException::make($this->_parent, $this->_relationVars['foreign']);
        }

        foreach ($models as $val) {
            if(!($models[0] instanceof Model)) {
                throw new LogicException('No model asigned');
            }

            $res = Model::instance(get_class($val));
    
            $res = $res->updateOrCreate(
                array($this->_relationVars['foreign'] => $this->_relationVars['where_in'][0]), 
                $val->attributes
            );

            if (!$res) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update the column's update timestamp.
     *
     * @param  string|null  $column
     * @return int|false
     */
    public function touch($column = null)
    {
        if (!$column) {
            $column = $this->_model->getUpdatedAtColumn();
        }

        if (! $this->_model->getTimestamps() || is_null($column)) {
            return false;
        }

        if (!isset($this->_relationVars)) {
            if ($this->_where=='') {
                foreach ($this->_primary as $primary) {
                    $this->whereIn($primary, $this->_collection->pluck($primary)->toArray());
                }
            }

            return $this->update(array($column => now()->toDateTimeString()));
        }

        if ($this->_relationVars['relationship']=='belongsToMany') {
            $sql = 'UPDATE ' . $this->table . ' SET ' . $column . ' = "' 
                . now()->toDateTimeString() . '" WHERE ' . 
                (is_array($this->_primary)? $this->_primary[0] : $this->_primary)
                . ' = ' . $this->_relationVars['current'];
            
            return $this->connector()->execSql($sql, array()); //, $this->_where, $this->_bindings);
        }

        if ($this->_relationVars['relationship']=='belongsTo2') {
            $sql = 'UPDATE ' . $this->table . ' SET ' . $column . ' = "' 
                . now()->toDateTimeString() . '" WHERE ' . 
                (is_array($this->_primary)? $this->_primary[0] : $this->_primary)
                . ' = ' . $this->_relationVars['where_in'][0];
            
            return $this->connector()->execSql($sql, array()); //, $this->_where, $this->_bindings);
        }

        throw new LogicException("Unsupported relationship [".$this->_relationVars['relationship'].'] in [touch] method.');
    }
    
    /**
     * Updates a record in database
     * 
     * @param array|object $record
     * @return bool
     */
    public function update($attributes)
    {
        if ($this->_where=='') {
            foreach ($this->_primary as $primary) {
                $val = is_object($attributes) ? $attributes->$primary : $attributes[$primary];

                if (!isset($val)) {
                    throw new LogicException("Error in model's primary key");
                }
                    
                $this->where($primary, $val);
            }
        }

        $values = array();
        $bindings = array();

        foreach ($attributes as $key => $val) {

            if (!in_array($key, $this->_appends)) {
                $val = Helpers::ensureValueIsNotObject($val);

                $values[$key] = "`$key` = ". ($val!==null ? '?' : 'NULL');
                
                if ($val!==null) {
                    $bindings[] = $val;
                }

            }
        }
    
        if ($this->_where == '') {
            throw new LogicException('WHERE not assigned.');
        }

        if (count($values)==0) {
            throw new LogicException('No values assigned for update');
        }

        $sql = 'UPDATE `' . $this->table . '` SET ' . implode(', ', $values) . ' ' . $this->_where;

        $query = $this->connector()->execSql($sql, array_merge($bindings, $this->_bindings['where'])); //, $this->_where, $this->_bindings);

        $this->clear();
        
        return $query; 
    }

    /**
     * Create or update a record matching the attributes, and fill it with values
     * 
     * @param  array  $attributes
     * @param  array  $values
     * @return string
     */
    public function updateOrInsert($attributes, $values)
    {

        foreach ($attributes as $key => $val) {
           $this->where($key, $val);
        }
        
        $sql = 'SELECT * FROM `' . $this->table . '` '. $this->_where . ' LIMIT 0, 1';
        
        $cloned = $this->_clone();
        $res = $this->connector()->execSQL($sql, $cloned, true);

        if ($res->count()>0) {
            return $this->update($values);
        } 

        $new = array_merge($attributes, $values);
        return $this->_insert($new);
    }

    /**
     * Create or update a record matching the attributes, and fill it with values\
     * Returns the record
     * 
     * @param  array  $attributes
     * @param  array  $values
     * @return Model
     */
    public function updateOrCreate($attributes, $values)
    {
        $this->clear();
        
        foreach ($attributes as $key => $val) {
           $this->where($key, $val);
        }
        
        $sql = 'SELECT * FROM `' . $this->table . '` '. $this->_where . ' LIMIT 0, 1';
        
        $res = $this->connector()->execSQL($sql, $this, true);

        if ($res->count()>0) {
            $item = $res->first();
            
            $this->update($values);

            foreach($values as $key => $val) {
                $item->$key = $val;
            }

            return $item; //$this->insertUnique($item);
        }

        $new = array_merge($attributes, $values);
        return $this->create($new);

    }

    /**
     * Uses REPLACE clause\
     * Updates a record using PRIMARY KEY OR UNIQUE\
     * If the record doesn't exists then creates a new one
     * 
     * @param array $record
     * @return bool
     */
    public function insertReplace($record)
    {
        if (!is_array(reset($record))) {    
            $this->clear();
            return $this->_insertReplace($record);
        }

        foreach ($record as $item) {
            $this->clear();
            $res = $this->_insertReplace($item);
        }

        return $res;
    }

    private function _insertReplace($record)
    {
        foreach ($record as $key => $val) {
            $this->set($key, $val);
        }
        
        $sql = 'REPLACE INTO `' . $this->table . '` (' . implode(', ', $this->_keys) . ')'
                . ' VALUES (' . implode(', ', $this->_values) . ')';

        $query = $this->connector()->execSQL($sql, $this, false);

        $this->clear();
        
        return $query;
    }

    /**
     * INSERT new records into table using subQuery\
     * Check Laravel documentation
     * 
     * @return bool
     */
    public function insertUsing($columns, $query)
    {
        $table = $this->grammar->wrapTable($this->table);

        $sql = $query->buildQuery();

        $columns = is_array($columns) ? $columns : $columns;

        $cols = array();

        foreach ($columns as $column) {
            $cols[] = '`'.$column.'`';
        }
        
                $this->addBinding($query->getBindings(), 'where');

        if (count($columns)==0) {
            $exec_query = "insert into {$table} $sql";
        } else {
            $exec_query = "insert into {$table} (" . implode(',', $cols) . ") $sql";
        }

        $result = $this->connector()->execSql($exec_query, $this);
    
        $this->clear();
        
        return $result;
    }

    /**
     * DELETE the current records from database\
     * Returns error if WHERE clause was not specified
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->_where == '') {
            throw new LogicException('WHERE not assigned');
        }

        $sql = 'DELETE FROM `' . $this->table . '` ' . $this->_where;

        $query = $this->connector()->execSQL($sql, $this->_bindings);

        $this->clear();
        
        return $query;
    }


    public function destroy()
    {
        $models = func_get_args();
        $_delete = array();

        foreach ($models as $model) {
            $_delete = array_merge($_delete, is_array($model)? $model : array($model));
        }

        $result = $this->whereIn($this->_primary[0], $_delete)->get();

        $res = 0;

        foreach ($result as $model) {
            $res += $model->delete();
        }
        
        return $res;
    }

    /**
     * Increment a column's value by a given amount.
     *
     * @param  string  $column
     * @param  float|int  $amount
     * @param  array  $extra
     * @return int
     */
    public function increment($column, $amount = 1, $extra = array())
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Non-numeric value passed to increment method.');
        }

        return $this->incrementEach(array($column => $amount), $extra);
    }

    /**
     * Increment the given column's values by the given amounts.
     *
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     * @return int
     */
    public function incrementEach($columns, $extra = array())
    {
        foreach ($columns as $column => $amount) {
            if (! is_numeric($amount)) {
                throw new InvalidArgumentException("Non-numeric value passed as increment amount for column: '$column'.");
            } elseif (! is_string($column)) {
                throw new InvalidArgumentException('Non-associative array passed to incrementEach method.');
            }

            $columns[$column] = new Expression($this->grammar->wrap($column). " + $amount");
        }

        return $this->updateIncrement(array_merge($columns, $extra));
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param  string  $column
     * @param  float|int  $amount
     * @param  array  $extra
     * @return int
     */
    public function decrement($column, $amount = 1, $extra = array())
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Non-numeric value passed to decrement method.');
        }

        return $this->decrementEach(array($column => $amount), $extra);
    }

    /**
     * Decrement the given column's values by the given amounts.
     *
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     * @return int
     */
    public function decrementEach($columns, $extra = array())
    {
        foreach ($columns as $column => $amount) {
            if (! is_numeric($amount)) {
                throw new InvalidArgumentException("Non-numeric value passed as decrement amount for column: '$column'.");
            } elseif (! is_string($column)) {
                throw new InvalidArgumentException('Non-associative array passed to decrementEach method.');
            }

            $columns[$column] = new Expression($this->grammar->wrap($column). " - $amount");
        }

        return $this->updateIncrement(array_merge($columns, $extra));
    }

    private function updateIncrement($columns = array())
    {
        if (count($columns)==0) {
            throw new InvalidArgumentException('Invalid arguments.');
        }

        $sql = 'UPDATE ' . $this->getTableNameOrAlias() . ' SET ';

        $sets = array();
        $bindings = array();

        foreach ($columns as $key => $val) {
            $val = Helpers::ensureValueIsNotObject($val);
                        
            if (!($val instanceof Expression)) {
                if ($val!==null) {
                    $bindings[] = $val;
                }
                $sets[] = $this->grammar->wrap($key) . ' = ' . ($val!==null ? '?' : 'NULL');
            } else {
                $sets[] = $this->grammar->wrap($key) . ' = ' . $val->__toString();
            }
        }

        $sql .= implode(', ', $sets);

        $query = $this->connector()->execSql($sql, $bindings);

        $this->clear();
        
        return $query; 
    }


    /**
     * Include trashed models in Query
     * 
     * @return Builder
     */
    public function withTrashed()
    {
        if (!$this->_softDelete) {
            throw new BadMethodCallException('Trying to use softDelete method on a non-softDelete Model');
        }

        $this->_withTrashed = true;
        return $this;
    }

    /**
     * SOFT DELETE the current records from database
     * 
     * @return bool
     */
    public function softDeletes($record)
    {
        if (!$this->_softDelete) {
            throw new BadMethodCallException('Trying to use softDelete method on a non-softDelete Model');
        }
        
        $date = date("Y-m-d H:i:s");

        foreach ($this->_primary as $primary) {
            if (!isset($record[$primary])) {
                throw new LogicException("Error in model's primary key");
            }
                
            $this->where($primary, $record[$primary]);
        }

        $deleted_name = $this->_model->_getDeleteColumnName();

        $sql = 'UPDATE `' . $this->table . '` SET `'.$deleted_name.'` = ' . "'$date'" . ' ' . $this->_where;

        $query = $this->connector()->execSQL($sql, array());

        $this->clear();
        
        return $query;
    }

    /**
     * RESTORE the current records from database
     * 
     * @return bool
     */
    public function restore($record=null)
    {
        if (!$this->_softDelete) {
            throw new BadMethodCallException('Trying to use softDelete method on a non-softDelete Model');
        }

        if (isset($record)) {
            foreach ($this->_primary as $primary) {
                if (!isset($record[$primary])) {
                    throw new LogicException("Error in model's primary key");
                }
                    
                $this->where($primary, $record[$primary]);
            }
        }

        $sql = 'UPDATE `' . $this->table . '` SET `deleted_at` = NULL ' . $this->_where;

        $query = $this->connector()->execSQL($sql, array()); //, $this->_where, $this->_bindings);

        $this->clear();
        
        return $query;
    }

    /**
     * Permanently deletes the current record from database
     * 
     * @return bool
     */
    public function forceDelete($record)
    {
        foreach ($this->_primary as $primary) {
            if (!isset($record[$primary])) {
                throw new LogicException("Error in model's primary key");
            }
                
            $this->where($primary, $record[$primary]);
        }
        
        $sql = 'DELETE FROM `' . $this->table . '` ' . $this->_where;

        $query = $this->connector()->execSQL($sql, array()); //, $this->_bindings);

        $this->clear();
        
        return $query;
    }


    /**
     * Truncates the current table
     * 
     * @return bool
     */
    public function truncate()
    {
        $sql = 'TRUNCATE TABLE `' . $this->table . '`';

        $query = $this->connector()->execSQL($sql, array());

        $this->clear();
        
        return $query;
    }


    /**
     * Associate the model instance to the given parent.
     *
     * @param Model $model
     * @return Model
     */
    public function associate($model)
    {
        if (!($model instanceof Model)) {
            throw new LogicException("Model not found");
        }

        if ($this->_relationVars['relationship'] != 'belongsTo') {
            throw new LogicException("Associate method only works for belongsTo relations");
        }

        $item = $this->_relationVars['collection'];
        $item = $item->first();

        $primary = $this->_relationVars['primary'];
        $foreign = $this->_relationVars['foreign'];

        $item->$primary = $model->$foreign;

        $item->setQuery(null);

        return $item;

    }

    /**
     * Dissociate previously associated model from the given parent.
     *
     * @return Model
     */
    public function dissociate()
    {
        if ($this->_relationVars['relationship'] != 'belongsTo') {
            throw new LogicException("Associate method only works for belongsTo relations");
        }

        $item = $this->_relationVars['collection'];
        $item = $item->first();

        $primary = $this->_relationVars['primary'];

        $item->$primary = null;

        $item->setQuery(null);

        return $item;
    }


    /**
     * Returns the first record from query\
     * Returns 404 if not found
     * 
     * @return object
     */
    public function firstOrFail($columns=null)
    {        
        $model = $this->first($columns);

        if (!$model) {
            $ex = new ModelNotFoundException;
            $ex->setModel($this->_parent);
            throw $ex;
        }

        return $model;
    }

    /**
     * Retrieves the first record matching the attributes, and fill it with values (if asssigned)\
     * If the record doesn't exists creates a new one\
     * 
     * @param  array  $attributes
     * @param  array  $values
     * @return Model
     */
    public function firstOrNew($attributes, $values=array())
    {
        $this->clear();

        foreach ($attributes as $key => $val) {
            $this->where($key, $val);
        }

        $sql = $this->_method . ' FROM `' . $this->table . '` ' . $this->_where . ' LIMIT 0, 1';

        $this->connector()->execSQL($sql, $this, true);

        if ($this->_collection->count()>0) {
            $this->processEagerLoad();

            return $this->_collection->first(); //$this->insertUnique($this->_collection->first(), true);
        }

        $item = array(); //new $this->_parent;

        foreach ($attributes as $key => $val) {
            $item[$key] = $val;
        }
            
        foreach ($values as $key => $val) {
            $item[$key] = $val;
        }

        return $this->insertUnique($item);
    }

    /**
     * Retrieves the first record matching the attributes, and fill it with values (if asssigned)\
     * If the record doesn't exists creates a new one and persists in database\
     * 
     * @param  array  $attributes
     * @param  array  $values
     * @return Model
     */
    public function firstOrCreate($attributes, $values=array())
    {
        $this->clear();

        foreach ($attributes as $key => $val) {
            $this->where($key, $val);
        }

        $sql = $this->_method . ' FROM `' . $this->table . '` ' . $this->_where . ' LIMIT 0, 1';
        
        $this->connector()->execSQL($sql, $this, true);

        if ($this->_collection->count()>0) {
            $this->processEagerLoad();

            return $this->_collection->first(); //$this->insertUnique($this->_collection->first(), true);
        }

        $item = new $this->_parent;
        $item = $this->create(array_merge($attributes, $values));
        
        if ($item) {
            $item->_setRecentlyCreated(true);
        }

        return $item;
    }

    /**
     * Find a record or an array of records based on primary key

     * @return Model|Collection
     */
    public function find($id, $columns=null)
    {
        if (is_array($id)) {
            $this->whereIn($this->_primary[0], $id);
        } else {
            $this->where($this->_primary[0], $id);
        }

        return is_array($id)? $this->get($columns) : $this->first($columns);
    }

    /**
     * Find a record or an array of records based on primary key\
     * Throws error if not found
     *
     * @return Model|Collection
     */
    public function findOrFail($val, $columns=null)
    {
        $items = is_array($val)? $val : array($val);

        $res = $this->find($val, $columns);

        if ($res===null || (is_array($val) && $res->count() != count($items))) {
            $ex = new ModelNotFoundException;
            $ex->setModel($this->_parent);
            throw $ex;
        }
    
        return $res;
    }


    private function insertNewItem()
    {
        $last = $this->connector()->getLastId();

        if ($last==0) {
            $this->clear();

            foreach ($this->_lastInsert as $key => $value) {
                $this->where($key, $value);
            }

            return $this->first();
        }

        return $this->find($last); 
    }


    private function insertUnique($data)
    {
        $class = $this->_parent;
        $item = new $class;

        $item->setAttributes($data);

        $item->_setOriginalRelations($this->_eagerLoad);

        $item->__setGlobalScopes();
        unset($item->timestamps);

        if ($this->_softDelete) {
            $item->unsetAttribute('deleted_at');
        }

        $item->syncOriginal();

        return $item;

    }

    /**
     * Return all records from current query
     * 
     * @return Collection
     */
    public function all($columns='*')
    {
        return $this->get($columns);
    }

    private function addGlobalScopes()
    {
        if (method_exists($this->_model, 'booted')) 
            $this->_model->booted();

        foreach($this->_model->__getGlobalScopes() as $scope => $callback)
        {
            $this->_scopes[$scope] = $callback;
        }
    }

    private function applyGlobalScopes()
    {
        global $_class_list;
        
        $this->addGlobalScopes();

        foreach($this->_scopes as $scope => $callback)
        {
            if (isset($_class_list[$scope])) {
                $callback->apply($this, $this->_model);
            } else {
                list($class, $method, $params) = getCallbackFromString($callback);
                $params[0] = $this;
                executeCallback($class, $method, $params, $this);
            }
        }
    }

    /**
     * Remove a registered global scope.
     *
     * @param  Scope|string  $scope
     * @return $this
     */
    public function withoutGlobalScope($scope)
    {
        if (is_object($scope)) {
            $scope = get_class($scope);
        }

        unset($this->_scopes[$scope]);

        return $this;
    }

    /**
     * Remove all or passed registered global scopes.
     *
     * @return $this
     */
    public function withoutGlobalScopes()
    {
        $this->_scopes = array();

        return $this;
    }

    /**
     * Returns the first record from query
     * 
     * @return Model
     */
    public function first($columns=null)
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Return all records from current query
     * 
     * @return Collection
     */
    public function get($columns=null)
    {
        if ($this->_softDelete && !$this->_withTrashed) {
            $this->whereNull('deleted_at');
        }

        $this->applyGlobalScopes();

        if ($columns) {
            $this->select($columns);
        }

        $sql = $this->buildQuery();

        $this->connector()->execSQL($sql, $this, true);

        if ($this->_collection->count()>0) {
            $this->processEagerLoad();
        }

        $this->clear();

        return $this->_collection; //$this->insertData($this->_collection);

    }
    
    /**
     * Return all records from current query\
     * Limit the resutl to number of $records\
     * Send Pagination values to View class 
     * 
     * @param int $records
     * @return Paginator
     */
    public function paginate($cant=15, $columns=null, $pageName = 'page')
    {
        $filtros = $_GET;

        $pagina = $filtros[$pageName]>0? $filtros[$pageName] : 1;
        $offset = ($pagina-1) * $cant; 
        
        $this->_limit = null;
        $this->_offset = null;

        if ($this->_softDelete && !$this->_withTrashed) {
            $this->whereNull('deleted_at');
        }

        $this->applyGlobalScopes();

        if ($columns) {
            $this->select($columns);
        }
            
        $sql = $this->buildQuery() . " LIMIT $cant OFFSET $offset";

        $this->_collection = new Paginator;

        $this->_collection->setPageName($pageName);

        $this->connector()->execSQL($sql, $this, true);

        if ($this->_collection->count()==0)
        {
            return $this->_collection;
        }
        
        $records = 'select count(*) AS total from (' . $this->buildQuery() .') final';
        
        $query = $this->connector()->execSQL($records, $this->_clone(), true)->first();

        $total = isset($query)
            ? (int)($query->total
                ? $query->total 
                : ($query->TOTAL ? $query->TOTAL : 0)) 
            : 0;

            
        $pages = ceil($total / $cant);

        $pagina = (int)$pagina;
        $pages = (int)$pages;

        $this->_collection->first = $pagina<=1? null : $pageName.'=1';
        $this->_collection->last = $pagina==$pages? null : $pageName.'='.$pages;
        $this->_collection->previous = $pagina<=1? null : $pageName.'='.($pagina-1);
        $this->_collection->next = $pagina==$pages? null : $pageName.'='.($pagina+1);

        $meta = array();
        $meta['current'] = $pagina;
        $meta['from'] = $offset +1;
        $meta['last_page'] = $pages;
        $meta['path'] = config('app.url') . '/' . request()->route->url;
        $meta['per_page'] = $cant;
        $meta['to'] = $total < ($cant*$pagina)? $total : ($cant*$pagina);
        $meta['total'] = $total;

        $this->_collection->meta = $meta;

        //$this->_collection->setPaginator($pagination);
        
        $this->processEagerLoad();
        
        $this->clear();

        return $this->_collection; //$this->insertData($this->_collection);

    }

    /**
     * Get a collection instance containing the values of a given column.
     *
     * @param string $column
     * @param string|null $key
     * @return Collection
     */
    public function pluck($column, $key = null)
    {
        $this->select($key? array($column, $key) : $column);

        return $this->get()->pluck($column, $key);
    }

    /**
     * Concatenate values of a given column as a string.
     *
     * @param  string  $column
     * @param  string  $glue
     * @return string
     */
    public function implode($column, $glue = '')
    {
        return $this->pluck($column)->implode($glue);
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param  string  $column
     * @return mixed
     */
    public function value($column)
    {
        $result = $this->select($column)->first()->toArray();

        return count($result) > 0 ? reset($result) : null;
    }

    /**
     * Get a single expression value from the first result of a query.
     *
     * @param  string  $expression
     * @param  array  $bindings
     * @return mixed
     */
    public function rawValue($expression, $bindings = array())
    {
        $result = $this->selectRaw($expression, $bindings)->first()->toArray();

        return count($result) > 0 ? reset($result) : null;
    }

    /**
     * Execute the query and get the first result if it's the sole matching record.
     *
     * @param  array|string  $columns
     */
    private function baseSole($columns=null)
    {
        $result = $this->take(2)->get($columns);

        $count = $result->count();

        if ($count === 0) {
            throw new RecordsNotFoundException;
        }

        if ($count > 1) {
            throw new MultipleRecordsFoundException($count);
        }

        return $result->first();
    }

    /**
     * Execute the query and get the first result if it's the sole matching record.
     *
     * @param  array|string  $columns
     */
    public function sole($columns=null)
    {
        try {
            return $this->baseSole($columns);
        } catch (RecordsNotFoundException $exception) {
            $ex = new ModelNotFoundException;
            $ex->setModel($this->_parent);
            throw $ex;
        }
    }

    /**
     * Get a single column's value from the first result of a query if it's the sole matching record.
     *
     * @param  string  $column
     * @return mixed
     */
    public function soleValue($column)
    {
        $result = $this->sole();

        return $result->$column;
    }

    public function _fresh($original, $relations=null)
    {
        $keys = is_array($this->_primary) ? $this->_primary : array($this->_primary);
        $this->_where = '';
        
        foreach ($keys as $key) {
            $this->where($key, $original[$key]);
        }

        $this->_eagerLoad = $relations;

        $this->_collection = new Collection();
        return $this->first();
    }

    /**
     * Begin querying the model.
     *
     * @return Builder
     */
    public function query()
    {
        return $this->_model->newEloquentBuilder($this);
    }

    /**
     * Executes the SQL $query
     * 
     * @param string $query
     * @return mixed
     */
    public function runQuery($sql)
    {
        return $this->connector()->execSQL($sql, array());
    }


    public function setForeign($key)
    {
        $this->_foreign = $key;
        return $this;
    }

    public function setConnector($connector)
    {
        $this->sql_connector = null;
        $this->_connector = $connector;
        return $this;
    }

    private function isManyRelation($relation) {
        return in_array(
            $this->_relationVars['relationship'], 
            array('belongsToMany', 'morphToMany', 'morphedByMany')
        );
    }

    public function _as($pivot_name)
    {
        if ($this->_relationVars && isset($this->_relationVars['relationship'])) {
            if ($this->isManyRelation($this->_relationVars['relationship'])) {
                $this->_relationVars['pivot_name'] = is_array($pivot_name) ? $pivot_name[0] : $pivot_name;
            }
        }
        return $this;
    }

    public function using($model)
    {
        if ($this->_relationVars && isset($this->_relationVars['relationship'])) {
            if ($this->isManyRelation($this->_relationVars['relationship'])) {
                $this->_relationVars['pivot_model'] = $model;
            }
        }
        return $this;
    }

    public function withPivot()
    {
        $columns = func_get_args();
        
        if ($this->_relationVars && isset($this->_relationVars['relationship'])) {
            if ($this->_relationVars['relationship']=='belongsToMany') {
                $this->_relationVars['extra_columns'] = $columns;
            }
        }
        return $this;
    }


    private function addRelation(&$eager_load, $relation, $constraints=null)
    {
        $keys = explode('.', $relation);

        $last_key = array_pop($keys);

        while ($arr_key = array_shift($keys)) {
            if (!array_key_exists($arr_key, $eager_load)) {
                $eager_load[$arr_key] = array();
            }

            $eager_load = &$eager_load[$arr_key];
        }

        $eager_load[$last_key]['_constraints'] = $constraints;
    }


    /**
     * Set the relationships that should be eager loaded.
     *
     * @param  string|array  $relations
     * @param  string|Closure|null  $callback
     * @return $this
     */
    public function with($relations)
    {
        $relations = is_string($relations) ? array($relations) : $relations;

        //dd($relations);
        
        foreach ($relations as $relation => $values) {

            if (is_string($values)) {
                $this->addRelation($this->_eagerLoad, $values);
            }

            elseif (is_array($values) && !is_closure($values)) {
                foreach ($values as $val) {
                    $this->addRelation($this->_eagerLoad, $relation.'.'.$val);
                }
            }

            elseif (is_null($values)) {
                $this->addRelation($this->_eagerLoad, $relation);
            }

            else {
                $this->addRelation($this->_eagerLoad, $relation, $values);
            }
        }

        return $this;
    }

    /**
     * Return a new model instance in case the relationship does not exist.
     *
     * @param  Closure|array|bool  $callback
     * @return Builder
     */
    public function withDefault($callback = true)
    {
        if (!isset($this->_relationVars)) {
            throw new BadMethodCallException('Relationship not defined');
        }

        if (!in_array($this->_relationVars['relationship'], 
            array('belongsTo', 'hasOne', 'hasOneThrough', 'morphOne'))) {
            throw new BadMethodCallException('Undefined method [withDefault]');
        }

        $this->withDefault = $callback;

        return $this;
    }

    public function __getDefault()
    {
        return $this->withDefault;
    }

    /**
     * Prevent the specified relations from being eager loaded.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function without($relations)
    {
        $relations = is_array($relations)? $relations : array($relations);

        foreach ($relations as $relation) {
            unset($this->_eagerLoad[$relation]);
        }

        return $this;
    }

    /**
     * Set the relationships that should be eager loaded while removing any previously added eager loading specifications.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function withOnly($relations)
    {
        $this->_eagerLoad = array();

        return $this->with($relations);
    }

    private function processEagerLoad()
    {
        foreach ($this->_eagerLoad as $key => $val) {

            $q = $this->_model->getQuery();
            $q->_collection = $this->_collection;
            $q->_relationName = $key;
            $q->_relationColumns = '*';
            $q->_extraQuery = isset($val['_constraints']) ? $val['_constraints'] : null;
            $q->_nextRelation = $val;
            $q->_toBase = $this->_toBase;
            
            if (!isset($val['_constraints']) && isset($this->_hasConstraints['constraints'])) {
                $q->_hasConstraints = $this->_hasConstraints;
            }

            if (strpos($key, ':')>0) {
                list($key, $columns) = explode(':', $key);
                $q->_relationColumns = explode(',', $columns);
                $q->_relationName = $key;
            }

            $res = $this->_model->$key();

            if (!$res) {
                throw RelationNotFoundException::make($this->_parent, $key);
            }

            $res->_toBase = $this->_toBase;

            Relations::insertRelation($q, $res, $key);
        }

        /* if (!$this->_toBase)
        {
            foreach ($this->_collection as $item)
            {
                foreach ($this->_appends as $append)
                {
                    $item->setAppendAttribute($append, $item->$append);
                }
            }
        } */

    }

    public function load($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;
        
        $this->with($relations);

        $this->processEagerLoad();

        return $this->_collection;
    }

    private function _has($relation, $constraints=null, $comparator=null, $value=null, $boolean='AND')
    {
        $data = null;
        
        if (strpos($relation, '.')>0) {
            $data = explode('.', $relation);
            $relation = array_pop($data);
        }

        if (strpos($relation, ':')>0) {
            $data = explode(':', $relation);
            $relation = reset($data);
        }
        
        $data = $this->_model->$relation();

        $childtable = $data->table;
        $foreign = $data->_relationVars['foreign'];
        $primary = $data->_relationVars['primary'];

        $filter = '';
        
        if (isset($constraints) && is_closure($constraints)) {

            $this->getCallback($constraints, $data);

            if ($data->_bindings['where']) {
                if (!$this->_bindings['where']) {
                    $this->_bindings['where'] = array();
                }
                
                $this->_bindings['where'] = array_merge($this->_bindings['where'], $data->_bindings['where']);
            }
                
            $filter = ' AND ('. ltrim($data->_where, 'WHERE') . ')';
        } 

        elseif (isset($constraints) && !is_array($constraints)) {
            
            $filter = " AND `$data->table`.`$constraints` $comparator ?";
            
            $comparator = null;
            
            if ($value) {
                $this->_bindings['where'][] = $value;
            }
        }

        else {
            $filter = str_replace('WHERE', ' AND', $data->_where);
            
            if ($value) {
                $this->_bindings['where'][] = $value;
            }
        }

        if (!$comparator) {
            $where = 'EXISTS (SELECT * FROM `'.$childtable.'` WHERE `'.
                $this->table.'`.`'.$primary.'` = `'.$childtable.'`.`'.$foreign.'`' . $filter . ')';
        } else {
            $where = ' (SELECT COUNT(*) FROM `'.$childtable.'` WHERE `'.
                $this->table.'`.`'.$primary.'` = `'.$childtable.'`.`'.$foreign.'`' .
                $filter  . ') '.$comparator.' ?';//.$value;
        }

        if (isset($data->_relationVars['classthrough'])) {

            if ($data->_relationVars['relationship']=='morphedByMany') {
                $ct = $data->_relationVars['tablethrough'];
                $cp = $data->_relationVars['primary'];
                $cf = $data->_relationVars['foreignthrough'];
                $primary = $data->_relationVars['primarythrough'];
            }

            elseif ($data->_relationVars['relationship']=='morphToMany') {
                $ct = $data->_relationVars['tablethrough'];
                $cp = $data->_relationVars['foreignthrough'];
                $cf = $data->_relationVars['foreign'];
                $foreign = $data->_relationVars['primary'];
                $primary = $data->_relationVars['primarythrough'];
            }

            else {
                $ct = $data->_relationVars['tablethrough'];
                $cp = $data->_relationVars['foreignthrough'];
                $cf = $data->_relationVars['primarythrough'];
            }

            if (!$comparator) {
                $where = 'EXISTS (SELECT * FROM `'.$childtable.'` INNER JOIN `'.$ct.'` ON `'.$ct.'`.`'.$cf.
                    '` = `'.$childtable.'`.`'.$foreign.'` WHERE `'.
                    $this->table.'`.`'.$primary.'` = `'.$ct.'`.`'.$cp.'`' . $filter . ')';
            } else {
                $where = '(SELECT COUNT(*) FROM `'.$childtable.'` INNER JOIN `'.$ct.'` ON `'.$ct.'`.`'.$cf.
                    '` = `'.$childtable.'`.`'.$foreign.'` WHERE `'.
                    $this->table.'`.`'.$primary.'` = `'.$ct.'`.`'.$cp.'`' . $filter . ') '.$comparator.' ?';;
            }
        }

        if ($this->_where == '') {
            $this->_where = 'WHERE ' . $where;
        } else {
            $this->_where .= " $boolean " . $where;
        }

        return $this;
    }


    /**
     * Filter current query based on relationships\
     * Check Laravel documentation
     * 
     * @return Builder
     */
    public function has($relation, $operator = '>=', $count = 1, $boolean = 'AND', $callback = null)
    {
        return $this->_has($relation, $callback, $operator, $count, $boolean);
    }

    /**
     * Add a basic where clause to a relationship query.
     * Check Laravel documentation
     * 
     * @return Builder
     */
    public function whereRelation($relation, $column, $comparator=null, $value=null)
    {
        return $this->_has($relation, $column, $comparator, $value);
    }

    /**
     * Add an "or where" clause to a relationship query.
     * Check Laravel documentation
     * 
     * @return Builder
     */
    public function orWhereRelation($relation, $column, $comparator=null, $value=null)
    {
        return $this->_has($relation, $column, $comparator, $value, 'OR');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     * Check Laravel documentation
     * 
     * @param string $relation
     * @param Query $filter
     * @param string $comparator
     * @param string|int $value
     * @return Builder
     */
    public function whereHas($relation, $filter=null, $comparator=null, $value=null)
    {
        return $this->_has($relation, $filter, $comparator, $value);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     * Check Laravel documentation
     * 
     * @return Builder
     */
    public function orWhereHas($relation, $filter=null, $comparator=null, $value=null)
    {
        return $this->_has($relation, $filter, $comparator, $value, 'OR');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     * Check Laravel documentation
     *
     * @return Builder
     */
    public function whereDoesntHave($relation, $callback = null)
    {
        return $this->doesntHave($relation, 'and', $callback);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     * Check Laravel documentation
     *
     * @return Builder
     */
    public function orWhereDoesntHave($relation, $callback = null)
    {
        return $this->doesntHave($relation, 'or', $callback);
    }

    /**
    * Add a relationship count / exists condition to the query.
    * Check Laravel documentation
    *
    * @return Builder
    */
    public function doesntHave($relation, $boolean = 'AND', $callback = null)
    {
        return $this->has($relation, '<', 1, $boolean, $callback);
    }

    /**
     * Add a relationship count / exists condition to the query with an "or".
    * Check Laravel documentation
     *
     * @return Builder
     */
    public function orDoesntHave($relation)
    {
        return $this->doesntHave($relation, 'OR');
    }

    /**
     * Filter current query based on relationships\
     * Allows to specify additional filters\
     * Filters can be nested\
     * Check Laravel documentation
     * 
     * @param string $relation
     * @param Query $filter
     * @return Builder
     */
    public function withWhereHas($relation, $constraints=null)
    {
        $this->_hasConstraints = array('relation' => $relation, 'constraints' => $constraints);
        
        return $this->with(array($relation => $constraints))->_has($relation, $constraints);
    }

    /**
     * Convert the relationship to a "has one" relationship.
     *
     * @return Builder
     */
    public function one()
    {
        if (!isset($this->_relationVars['relationship'])) {
            throw new LogicException("Relationship is missing.");
        }

        if ($this->_relationVars['relationship'] == 'hasMany') {
            $this->_relationVars['relationship'] = 'hasOne';
        } 
        elseif ($this->_relationVars['relationship'] == 'morphMany') {
            $this->_relationVars['relationship'] = 'morphOne';
        } 
        else {
            throw new LogicException("Relationship [".$this->_relationVars['relationship']."] can't be converted.");
        }

        return $this;
    }

    /**
     * Indicate that the relation is the latest single result of a larger one-to-many relationship.
     *
     * @param  string|null  $column
     * @param  string|null  $relation
     * @return Builder
     */
    public function latestOfMany($column = 'id', $relation = 'latestOfMany')
    {
        return $this->ofMany($column, 'MAX', 'latestOfMany');
    }

    /**
     * Indicate that the relation is the oldest single result of a larger one-to-many relationship.
     *
     * @param  string|null  $column
     * @param  string|null  $relation
     * @return Builder
     */
    public function oldestOfMany($column = 'id', $relation = 'oldestOfMany')
    {
        return $this->ofMany($column, 'MIN', $relation);
    }

    /**
     * Indicate that the relation is a single result of a larger one-to-many relationship.
     *
     * @param  string|array  $column
     * @param  string|null|Closure  $aggregate
     * @param  string|null  $relation
     * @return Builder
     */
    public function ofMany($column = 'id', $aggregate = 'MAX', $relation = 'latestOfMany')
    {
        if (!isset($this->_relationVars['relationship'])) {
            throw new LogicException("Relationship is missing.");
        }

        $this->_relationVars['oneOfMany'] = true;

        if (is_array($column)) {
            $col_name = array_keys($column);
            $col_val = array_values($column);
            $col_name = reset($col_name);
            $col_val = reset($col_val);
        } else {
            $col_name = $column;
            $col_val = $aggregate;
        }

        $key_name = $this->_model->getKeyName();
        $key_val = 'MAX';

        $closure = null;
        
        if (is_closure($aggregate)) {
            $closure = $aggregate;
        }

        // This makes the subquery using column aggregate (and uses filtered records in relations)
        $subquery = $this->newJoinClause($this, 'inner', $this->table);

        $subquery->selectRaw(
            $col_val . "(`".$this->table."`.`".$col_name."`) as `".
            $col_name."_aggregate`, `".$this->table."`.`".$this->_relationVars['foreign']."`"
        );
        
        $subquery->groupBy($this->table.".".$this->_relationVars['foreign']);

        if ($closure) {
            list($class, $method) = getCallbackFromString($closure);
            executeCallback($class, $method, array($subquery), $subquery);
        }

        if ($this->_where!='') {

            $subquery->_where .= $closure 
                ? ' '. str_replace('WHERE ', 'AND ', $this->_where) 
                : $this->_where;

            if (isset($this->_bindings['where'])) {
                $subquery->addBinding($this->_bindings['where'], 'where');
            }
    
            unset($this->_bindings['where']);
            $this->_where = '';
        }
        
        // This makes the subquery using key aggregate
        $join = $this->newJoinClause($this, 'inner', $this->table);

        $join->selectRaw(
            $key_val . "(`".$this->table."`.`".$key_name."`) as `".
            $this->_primary[0]."_aggregate`, `".$this->table."`.`".$this->_relationVars['foreign']."` "
        );

        $join->whereColumn($relation.'.'.$col_name."_aggregate", $this->table.'.'.$col_name)
            ->whereColumn($relation.".".$this->_relationVars['foreign'], $this->table.".".$this->_relationVars['foreign']);

        $join->groupBy($this->table.".".$this->_relationVars['foreign']);

        $join->_where = str_replace('WHERE ', 'ON ', $join->_where);


        // This includes the column aggregate query in key aggregate query 
        list($query, $bindings) = $this->createSub($subquery);
        
        $expression = '('.$query.') as `'.$relation.'`';

        $join->addBinding($bindings, 'join');

        $join->joins[] = $this->newJoinClause($this, 'inner', new Expression($expression));


        // This join previows query (with nested) in main query
        $this->whereColumn($relation.".".$this->_primary[0]."_aggregate", $join->table.".".$this->_primary[0])
            ->whereColumn($relation.".".$this->_relationVars['foreign'], $join->table.".".$this->_relationVars['foreign']);

        $this->_where = str_replace('WHERE ', 'ON ', $this->_where);

        list($query, $bindings) = $this->createSub($join);

        $expression = '('.$query.') as `'.$relation.'`';

        $this->addBinding($bindings, 'join');

        $this->joins[] = $this->newJoinClause($this, 'inner', new Expression($expression));

        return $this;
    }


    /**
     * Add a "belongs to" relationship where clause to the query.
     * Check Laravel Documentation
     * 
     * @return Builder
     */
    public function whereBelongsTo($related, $relationshipName = null, $boolean = 'AND')
    {
        if (!$relationshipName) {
            if ($related instanceof Collection) {
                $relationshipName = get_class($related->first());
            } else {
                $relationshipName = strtolower(get_class($related));
            }
        }

        $class = new $this->_parent;
        $class->setQuery($this);
        $class->getQuery()->varsOnly = true;
        $res = $class->$relationshipName();
        $class->getQuery()->varsOnly = false;

        $foreign = $res->_relationVars['foreign'];

        if ($related instanceof Collection) {
            $values = $related->pluck($foreign)->toArray();
        } else {
            $values = array($related->$foreign);
        }

        if (strtolower($boolean)=='and') {
            $this->whereIn($res->_relationVars['primary'], $values);
        } else {
            $this->orWhereIn($res->_relationVars['primary'], $values);
        }

        return $this;
    }

    /**
     * Add an "BelongsTo" relationship with an "or where" clause to the query.
     * Check Laravel Documentation
     * 
     * @return Builder
     */
    public function orWhereBelongsTo($related, $relationshipName = null)
    {
        return $this->whereBelongsTo($related, $relationshipName, 'OR');
    }

    /**
     * Add subselect queries to count the relations.
     *
     * @param  mixed  $relations
     * @return Builder
     */
    public function withCount($relations)
    {
        return $this->withAggregate(is_array($relations) ? $relations : func_get_args(), '*', 'count');
    }

    /**
     * Add subselect queries to include the max of the relation's column.
     *
     * @param  string|array  $relation
     * @param  string  $column
     * @return Builder
     */
    public function withMax($relation, $column)
    {
        return $this->withAggregate($relation, $column, 'max');
    }

    /**
     * Add subselect queries to include the min of the relation's column.
     *
     * @param  string|array  $relation
     * @param  string  $column
     * @return Builder
     */
    public function withMin($relation, $column)
    {
        return $this->withAggregate($relation, $column, 'min');
    }

    /**
     * Add subselect queries to include the sum of the relation's column.
     *
     * @param  string|array  $relation
     * @param  string  $column
     * @return Builder
     */
    public function withSum($relation, $column)
    {
        return $this->withAggregate($relation, $column, 'sum');
    }

    /**
     * Add subselect queries to include the average of the relation's column.
     *
     * @param  string|array  $relation
     * @param  string  $column
     * @return Builder
     */
    public function withAvg($relation, $column)
    {
        return $this->withAggregate($relation, $column, 'avg');
    }

    /**
     * Add subselect queries to include the existence of related models.
     *
     * @param  string|array  $relation
     * @return Builder
     */
    public function withExists($relation)
    {
        return $this->withAggregate($relation, '*', 'exists');
    }

    /**
     * Add subselect queries to include an aggregate value for a relationship.
     *
     * @param  mixed  $relations
     * @param  string  $column
     * @param  string  $function
     * @return Builder
     */
    public function withAggregate($relations, $column, $function = 'count')
    {
        if (count($relations)==0) {
            return $this;
        }

        $relations = is_array($relations) ? $relations : array($relations);

        foreach ($relations as $key => $values) {
            $relation = null;
            $constraints = null;
            $alias = null;

            if (is_string($values)) {
                list($relation, $alias) =  explode(' as ', strtolower($values));
                $constraints = null;
            } elseif (is_null($values)) {
                list($relation, $alias) =  explode(' as ', strtolower($key));
                $constraints = null;
            } else {
                list($relation, $alias) =  explode(' as ', strtolower($key));
                $constraints = $values;
            }

            $this->_model->getQuery();
            $this->_model->getQuery()->varsOnly = true;
            $data = $this->_model->$relation();

            $column_name = $alias? $alias : $relation.'_'.$function;

            if ($function!='count' && $function!='exists') {
                $column_name .= '_'.$column;
            }

            $select = "(SELECT $function($column)";

            if ($function=='exists') {
                $select = "EXISTS (SELECT $column";
            }

            $subquery = "$select FROM `$data->table`";

            if (in_array(
                $data->_relationVars['relationship'],
                array('belongsToMany', 'hasManyThrough', 'hasOneThrough', 'morphToMany', 'morphedByMany')
            )) {
                $subquery .= ' ' . $data->implodeJoinClauses() . ' ' .
                    ($data->_where? $data->_where . ' AND `' : ' WHERE `') . 
                    $data->_relationVars['tablethrough'] . '`.`' . 
                    ($data->_relationVars['relationship']=='morphedByMany'? 
                    $data->_relationVars['primary'] : $data->_relationVars['foreignthrough']) . '` = `' .
                    $this->table . '`.`' . ($data->_relationVars['relationship']=='morphedByMany'? 
                    $data->_relationVars['primarythrough'] : $data->_relationVars['primary']) . '`';

            } else {
                $subquery .= " WHERE `$this->table`.`" . $data->_relationVars['primary'] . "` 
                = `$data->table`.`" . $data->_relationVars['foreign'] . "`";
            }

            // Revisar este WHERE
            // Es el where de la relacion
            //if ($data->_where)
            //    $subquery .= ' AND ' . str_replace('WHERE ', '', $data->_where);

            // Constraints (if declared)
            if ($constraints) {
                $this->getCallback($constraints, $data);

                if (!$this->_bindings['select']) {
                    $this->_bindings['select'] = array();
                }

                $this->_bindings['select'] = array_merge($this->_bindings['select'], $data->_bindings['where']);

                $new_where = str_replace("`_child_table_`.", "`".$data->table."`.", $data->_where);
                $subquery .= str_replace('WHERE',' AND', $new_where);
            }

            $subquery .= ") AS `" . $column_name . "`";

            $this->_method .= ', ' .$subquery;

            $this->_loadedRelations[] = 'count_'.$relation;
        }    

        return $this;
    }

    /**
    * Load a set of aggregations over relationship's column onto the collection.
    *
    * @return Builder
    */
    public function loadAggregate($relations, $column, $function = 'count')
    {
        if (count($relations)==0) {
            return $this;
        }

        $relations = is_array($relations) ? $relations : array($relations);

        foreach($relations as $relation) {

            list($relation, $forced_name) = explode(' as ', $relation);

            $this->_model->_query = new Builder($this->_parent);
            $this->_model->getQuery()->varsOnly = true;
            $data = $this->_model->$relation();

            $column_name = $forced_name? $forced_name : $relation.'_'.$function;

            if ($function!='count' && $function!='exists' && !$forced_name) {
                $column_name .= '_'.$column;
            }

            $select = "(SELECT $function($column)";

            if ($function=='exists') {
                $select = "EXISTS (SELECT $column";
            }

            $subquery = "$select FROM `$data->table`";

            if (in_array(
                $data->_relationVars['relationship'], 
                array('belongsToMany', 'hasManyThrough', 'hasOneThrough')
            )) {
                $subquery .= ' ' . implode(' ', $data->joins) . ' WHERE `'. 
                    $data->_relationVars['classthrough'] . '`.`' . 
                    $data->_relationVars['foreignthrough'] . '` = `' .
                    $this->table . '`.`' . $data->_relationVars['primary'] . '`';

            } else {
                $subquery .= " WHERE `$this->table`.`" . $data->_relationVars['primary'] . 
                    "` = `$data->table`.`" . $data->_relationVars['foreign'] . "`";
            }

            $subquery .= ") AS `" . $column_name . "`";

            $parentkey = is_array($this->_primary)? $this->_primary[0] : $this->_primary;

            $this->_method .= ', ' .$subquery;

            $this->whereIn($parentkey, $this->_collection->pluck($this->_primary[0])->toArray());

            $records = $this->_clone();
            $records = $records->connector()->execSQL($this->toSql(), $records, true);

            foreach ($records as $record) {
                $this->_collection->where($parentkey, $record->{$parentkey})
                    ->first()->$column_name = $record->$column_name;
            }

            $this->_loadedRelations[] = 'count_'.$relation;

            //$this->clear();
        }
        
        return $this;
    }

    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->count() > 0;
    }

    /**
     * Determine if no rows exist for the current query.
     *
     * @return bool
     */
    public function doesntExist()
    {
        return ! $this->exists();
    }

    /**
     * Execute the given callback if no rows exist for the current query.
     *
     * @param  Closure  $callback
     * @return mixed
     */
    public function existsOr($callback)
    {
        return $this->exists() ? true : $this->getCallback($callback, $this);
    }

    /**
     * Execute the given callback if rows exist for the current query.
     *
     * @param  Closure  $callback
     * @return mixed
     */
    public function doesntExistOr($callback)
    {
        return $this->doesntExist() ? true : $this->getCallback($callback, $this);
    }

    /**
     * Retrieve the "count" result of the query.
     *
     * @param  string  $columns
     * @return int
     */
    public function count($columns = '*')
    {
        return (int) $this->aggregate('count', $columns);
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function min($column)
    {
        return $this->aggregate('min', $column);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function max($column)
    {
        return $this->aggregate('max', $column);
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function sum($column)
    {
        return $this->aggregate('sum', $column);
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function avg($column)
    {
        return $this->aggregate('avg', $column);
    }

    /**
     * Alias for the "avg" method.
     *
     * @param  string  $column
     * @return mixed
     */
    public function average($column)
    {
        return $this->avg($column);
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param  string  $function
     * @param  string  $column
     * @return mixed
     */
    public function aggregate($function, $column='*')
    {
        $query = $this->_clone();
        $query->_method = "SELECT $function($column) as aggregate";
        $sql = $query->buildQuery();

        return $query->connector()->execSQL($sql, $query, true)->first()->aggregate;
    }


    public function seed($data, $persist)
    {
        $this->_fillableOff = true;

        $col = new Collection(); //collectWithParent(null, $this->_parent);

        foreach ($data as $item) {
            if ($persist) {
                if ($this->insert($item)) {
                    $col[] = $this->insertUnique($item);
                }
            } else {
                $col[] = $this->insertUnique($item);
            }
        }

        $this->_fillableOff = false;

        return $col;
    }
    
    /**
     * Attach a model to the parent.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @return void
     */
    public function attach($id, $attributes=array())
    {
        if (is_array($id)) {
            if (is_array(reset($id))) {
                foreach ($id as $key => $val) {
                    if (is_array($val)) {
                        $this->attach($key, $val);
                    } else {
                        $this->attach($val);
                    }
                }
            } else {
                foreach ($id as $val) {
                    $this->attach($val);
                }
            }
        } else {
            if ($this->_relationVars['relationship']=='belongsToMany') {
                $record = array(
                    $this->_relationVars['foreignthrough'] => $this->_relationVars['current'],
                    $this->_relationVars['primarythrough'] => $id
                );
            } 
            elseif ($this->_relationVars['relationship']=='morphToMany') {
                $record = array(
                    $this->_relationVars['foreignthrough'] => $this->_relationVars['current_id'],
                    $this->_relationVars['relation_type'] => $this->_relationVars['current_type'],
                    $this->_relationVars['foreign'] => $id
                );
            }
            else {
                return false;
            }

            foreach ($attributes as $key => $id) {
                $record[$key] = $id;
            }

            DB::table($this->_relationVars['classthrough'])->insertOrIgnore($record);
        }
    }

    /**
     * Detach models from the relationship.
     *
     * @param  mixed  $ids
     * @return int
     */
    public function detach($ids=null)
    {
        if (is_array($ids)) {
            foreach ($ids as $val) {
                $this->detach($val);
            }

            return true;
        }

        if ($this->_relationVars['relationship']=='belongsToMany') {
            
            $query = DB::table($this->_relationVars['classthrough'])
                ->where($this->_relationVars['foreignthrough'], $this->_relationVars['current']);

            if ($ids) {
               $query = $query->where($this->_relationVars['primarythrough'], $ids);
            }

            return $query->delete();
        }
        elseif ($this->_relationVars['relationship']=='morphToMany') {

            $query = DB::table($this->_relationVars['classthrough'])
                ->where($this->_relationVars['foreignthrough'], $this->_relationVars['current_id'])
                ->where($this->_relationVars['relation_type'], $this->_relationVars['current_type']);

            if ($ids) {
               $query = $query->where($this->_relationVars['foreign'], $ids);
            }

            return $query->delete();
        }

        return false;
    }

    private function detachAll()
    {
        if ($this->_relationVars['relationship']=='belongsToMany') {
            DB::table($this->_relationVars['classthrough'])
                ->where($this->_relationVars['foreignthrough'], $this->_relationVars['current'])
                ->delete();
        }
        elseif ($this->_relationVars['relationship']=='morphToMany') {
            DB::table($this->_relationVars['classthrough'])
                ->where($this->_relationVars['foreignthrough'], $this->_relationVars['current_id'])
                ->where($this->_relationVars['relation_type'], $this->_relationVars['current_type'])
                ->delete();
        }
    }

    /**
     * Sync the intermediate tables with a list of IDs without detaching.
     *
     */
    public function syncWithoutDetaching($ids, $attributes=array())
    {
        $this->attach($ids, $attributes);
    }

    /**
     * Sync the intermediate tables with a list of IDs or collection of models with the given pivot values.
     *
     * @param  Model|array  $ids
     * @param  array  $values
     * @return array
     */
    public function syncWithPivotValues($ids, $values=array())
    {
        if (!is_array($ids)) $ids = array($ids);

        $this->detachAll();

        foreach ($ids as $val) {
            $this->attach($val, $values);
        }
    }

    /**
     * Sync the intermediate tables with a list of IDs or collection of models.
     */
    public function sync($value, $extra=array())
    {
        $this->detachAll();
        $this->attach($value, $extra);        
    }


    public function observe($class)
    {
        global $observers;
        $model = $this->_parent;

        if (!isset($observers[$model])) {
            $observers[$model] = $class;
        }
    }

    private function callScope($scope, $args)
    {
        $func = 'scope'.ucfirst($scope);
        $res = new $this->_parent;

        return call_user_func_array(array($res, $func), array_merge(array($this), $args));
    }


    /**
     * Chunk the results of the query.
     *
     * @param  int  $count
     * @param  Closure  $callback
     * @return bool
     */
    public function chunk($count, $callback)
    {
        if (!is_closure($callback)) {
            throw new LogicException("You must define de callback.");
        }

        $this->_order = 'ORDER BY '.$this->_primary[0]." ASC";

        $actual = 0;

        do {
            $results = $this->limit($count)->offset($actual)->get();

            $countResults = $results->count();

            if ($countResults==0) {
                break;
            }

            list($class, $method, $params) = getCallbackFromString($callback);
            $params[0] = $results;
            executeCallback($class, $method, $params, $this);

            unset($results);
            unset($this->_collection);

            $this->_collection = new Collection(); //collectWithParent(null, $this->_parent);

            $actual += $count;
        }
        while ($countResults == $count);

        return true;
    }

    /**
     * Chunk the results of a query by comparing IDs.
     *
     * @param int  $count
     * @param Closure  $callback
     * @param string|null  $column
     * @param string|null  $alias
     * @return bool
     */
    public function chunkById($count, $callback, $column=null, $alias=null)
    {
        if (!is_closure($callback)) {
            throw new LogicException("You must define de callback.");
        }
        
        if (!$column) {
            $column = $this->_primary[0];
        }

        if (!$alias) {
            $alias = $column;
        }
        
        $lastId = null;

        $this->_order = 'ORDER BY '.$column." ASC";
        
        do {
            if ($lastId) {
                $this->where($alias, '>', $lastId);
            }

            $results = $this->limit($count)->get();

            $countResults = $results->count();

            if ($countResults==0) {
                break;
            }

            $lastId = $results->last()->$alias;

            list($class, $method, $params) = getCallbackFromString($callback);
            $params[0] = $results;
            executeCallback($class, $method, $params, $this);

            unset($results);
            unset($this->_collection);

            $this->_collection = new Collection(); //collectWithParent(null, $this->_parent);

        }
        while ($countResults == $count);

        return true;
    }

    /**
     * Appends a macro to the Builder
     *
     * @param string $name
     * @param Closure $function
     */
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

    ### CLOSURES
    ###(closures)

}
