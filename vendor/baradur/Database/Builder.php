<?php

Class Builder
{
    protected static $_macros = array();


    public $_model = null;
    public $_table;
    public $_primary;
    public $_parent = null;
    public $_fillable;
    public $_guarded;
    public $_hidden;
    public $_appends;
    public $_routeKey;
    public $_softDelete;
    public $_collection = array();
    public $_timestamps;
    public $_timestamp_created;
    public $_timestamp_updated;
    
    public $_foreign;
    public $_fillableOff = false;

    public $_factory = null;

    public $_relationship;

    public $_method = '';
    public $_where = '';
    public $_bindings = array();
    public $_join = '';
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

    private $_scopes = array();

    private $_lastInsert = null;

    public $_hasConstraints = null;

    public $_toBase = false;


    public function __construct($model, $table = null)
    {
        $this->_model = new $model;
        $this->_table = $table? $table : $this->_model->getTable();
        $this->_connector = $this->_model->getConnector();
        $this->_primary = is_array($this->_model->getPrimaryKey())? $this->_model->getPrimaryKey() : array($this->_model->getPrimaryKey());
        $this->_parent = $model;
        $this->_fillable = $this->_model->getFillable();
        $this->_guarded = $this->_model->getGuarded();
        $this->_hidden = $this->_model->getHidden();
        $this->_appends = $this->_model->getAppends();
        $this->_routeKey = $this->_model->getRouteKeyName();
        $this->_softDelete = $this->_model->usesSoftDeletes()? 1 : 0;
        $this->_collection = new Collection($model, $this->_model->getHidden());
        $this->_timestamps = $this->_model->getTimestamps();
        
        $this->_timestamp_created = $this->_model->getCreatedAt();
        $this->_timestamp_updated = $this->_model->getUpdatedAt();

        if ($model=='DB')
        {
            $this->_fillableOff = true;
        }

        $this->_method = "SELECT `$this->_table`.*";

    }


    public function __call($method, $parameters)
    {

        if (method_exists($this->_parent, 'scope'.lcfirst($method)))
        {
            //return Model::instance($this->_parent)->callScope(lcfirst($method), $parameters);
            return $this->callScope(lcfirst($method), $parameters); 
        }

        if (isset(self::$_macros[$method]))
        {
            list($c, $m, $p) = getCallbackFromString(self::$_macros[$method]);
            $class = new $c($this->_parent);
            return executeCallback($class, $m, $parameters, $this);
            //return call_user_func_array(array($class, $m), array_merge($parameters, array($this)));
        }

        if (Str::startsWith($method, 'where'))
        {
            return $this->dynamicWhere($method, $parameters);
        }

        if ($method=='as')
        {
            return $this->_as($parameters);
        }


        throw new Exception("Method $method does not exist");
    }

    public function _clone()
    {
        $cloned = new Builder($this->_parent, $this->_table);

        $cloned->_foreign = $this->_foreign; 
        $cloned->_fillableOff = $this->_fillableOff; 
        $cloned->_factory = $this->_factory; 
        $cloned->_relationship = $this->_relationship; 
        $cloned->_method = $this->_method; 
        $cloned->_where = $this->_where; 
        $cloned->_bindings = $this->_bindings; 
        $cloned->_join = $this->_join; 
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
        $cloned->_timestamps = $this->_timestamps;
        $cloned->_timestamp_created = $this->_timestamp_created;
        $cloned->_timestamp_updated = $this->_timestamp_updated;
        $cloned->_toBase = $this->_toBase;

        return $cloned;
    }

    /* public function __clone()
    {
        die("HOLA");
        $cloned = new Builder($this->_parent, $this->_table);
        return $cloned;
    }  */

    private function clear()
    {
        $this->_method = "SELECT `$this->_table`.*";
        $this->_where = '';
        $this->_join = '';
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

    private $sql_connector = null;


    protected function setConnectorDriver($driver, $host, $user, $password, $database, $port=3306)
    {
        if ($driver=='mysql')
        {
            return new PdoConnector($host, $user, $password, $database, $port);
        }

        if ($driver=='mysqli')
        {
            return new MysqliConnector($host, $user, $password, $database, $port);
        }

        if ($driver=='oracle')
        {
            return new OracleConnector($host, $user, $password, $database, $port);
        }
    }
    
    /**
     * @return PdoConnector|MysqliConnector|OracleConnector
     */
    public function connector()
    {
        if (!$this->sql_connector)
        {
            
            if ($this->_connector)
            {
                $config = config('database.connections.'.$this->_connector);
                $this->sql_connector = $this->setConnectorDriver(
                    $config['driver'],
                    $config['host'],
                    $config['username'], 
                    $config['password'], 
                    $config['database'],
                    $config['port']
                );
            }
            else
            {
                global $database;

                if (!isset($database))
                {
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



    private function arrayToObject($array)
    {
        if (count($array)==0)
            return array();

        $obj = new stdClass;
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


    private function buildQuery()
    {
        if (strpos($this->_join, '!WHERE!')>0)
        {
            $this->_join = str_replace('!WHERE!', $this->_where, $this->_join);
            $this->_where = '';
        }

        $res = $this->_method;
        if ($this->_fromSub!='') $res .= ' FROM ' . $this->_fromSub . ' ';
        else {
            if (strpos($this->_table, 'information_schema')===false)
                $res .= ' FROM `' . $this->_table . '` ';
            else
                $res .= ' FROM ' . $this->_table . ' ';
        }
        if ($this->_join != '') $res .= $this->_join . ' ';
        if ($this->_where != '') $res .= $this->_where . ' ';
        if ($this->_union != '') $res .= $this->_union . ' ';
        if ($this->_group != '') $res .= $this->_group . ' ';
        if ($this->_having != '') $res .= $this->_having . ' ';
        if ($this->_order != '') $res .= $this->_order . ' ';
        if (!$this->_limit && $this->_offset) $this->_limit = 9999999;
        if ($this->_limit) $res .= ' LIMIT '.$this->_limit;
        if ($this->_limit && !$this->_offset) $this->_offset = 0;
        if ($this->_offset) $res .= ' OFFSET '.$this->_offset;

        if (strpos(strtolower($res), ' join ')===false && count($this->_loadedRelations)==0)
        {
            $res = str_replace("`$this->_table`.", '', $res);
        }

        return $res;
    }

    private function checkObserver($function, $model)
    {
        global $observers;
        $class = $this->_parent;
        if (isset($observers[$class]))
        {
            $observer = new $observers[$class];
            if (method_exists($observer, $function))
            {
                if (is_array($model))
                    $model = $this->insertUnique($model); 

                $observer->$function($model);
            }
        }
    }


    private function toSqlFirst()
    {
        $res = $this->toSql();
        return $res[0];
    }

    
    /**
     * Returns the full query as a string
     * 
     * @return string
     */
    public function toSql()
    {
        $res = $this->buildQuery();

        /* foreach ($this->_bindings as $val)
        {
            if (is_string($val)) $val = "'$val'";
            $res = preg_replace('/\?/', $val, $res, 1);
        } */

        return array($res, $this->_bindings);
    }

    /**
     * Returns the full query as a string
     * 
     * @return string
     */
    public function toPlainSql()
    {
        //dump($this);
        $res = $this->buildQuery();

        $bind = array();
        foreach ($this->_bindings['select'] as $b) $bind[] = $b;
        foreach ($this->_bindings['join'] as $b) $bind[] = $b;
        foreach ($this->_bindings['where'] as $b) $bind[] = $b;
        foreach ($this->_bindings['union'] as $b) $bind[] = $b;
        foreach ($this->_bindings['having'] as $b) $bind[] = $b;

        foreach ($bind as $val)
        {
            if (is_string($val)) $val = "'$val'";
            $res = preg_replace('/\?/', $val, $res, 1);
        }

        return $res;
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
     * @param string $columns String containing colums divided by comma
     * @return Builder
     */
    public function selectRaw($query = '*', $bindings = array())
    {
        $this->_bindings['select'] = array();
        foreach ($bindings as $v)
        {
            //$val = preg_replace('/\?/', $v, $val, 1);
            $this->_bindings['select'][] = $v;
        }

        $this->_method = 'SELECT ' . $query;

        return $this;
    }

    private function getSelectColumns($select)
    {
        $columns = array();
        foreach($select as $key => $value)
        {
            if ($value instanceof Builder)
            {
                $columns[] = '(' . $value->toSqlFirst() . ') as ' . $key;
            }
            elseif ($value instanceof Raw)
            {
                $columns[] = $value->query;
            }
            elseif (is_numeric($key))
            {
                list($col, $as, $alias) = explode(' ', $value);
                list($db, $col) = explode('.', $col);
    
                $col = trim($col);
                $as = trim($as); 
                $alias = trim($alias); 
                $db = trim($db);
    
                $columns[] = ($db=='*'? '`'.$this->_table.'`.*' : '`'.$db.'`') . 
                    ($col? '.' . ($col=='*'? '*' : '`'.$col.'`') : '') . 
                    (trim(strtolower($as))=='as'? ' as `'.$alias.'`':'');
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
        if (!is_array($select)) $select = func_get_args();

        $result = $this->getSelectColumns($select);
        $this->_method = 'SELECT ' . implode(', ', $result);
        return $this;
    }

    /**
     * Adds columns to the SELECT clause\
     * Returns the Query builder
     * 
     * @param string $columns String containing colums divided by comma
     * @return Builder
     */
    public function addSelect($select = '*')
    {
        if (!is_array($select)) $select = func_get_args();
        $result = $this->getSelectColumns($select);
        $this->_method .= ', ' . implode(', ', $result);
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
        $this->_fromSub = ' (' . $subquery->toSqlFirst() . ') ' . $alias;
        return $this;
    }


    /**
     * Specifies the WHERE clause\
     * Returns the Query builder
     * 
     * @param string $where
     * @return Builder
     */
    public function whereRaw($where, $bindings=array())
    {
        foreach ($bindings as $v)
        {
            //$where = preg_replace('/\?/', $v, $where, 1);
            $this->_bindings['where'][] = $v;
        }

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $where ;
        else
            $this->_where .= ' AND ' . $where;

        return $this;
    }

    private function dynamicWhere($method, $parameters)
    {
        $finder = substr($method, 5);

        $segments = preg_split(
            '/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE
        );

        $connector = 'and';
        $index = 0;

        if (count($segments)>1)
        {
            $prev_where = $this->_where;
            $this->_where = '';
        }

        foreach ($segments as $segment)
        {
            if ($segment !== 'And' && $segment !== 'Or')
            {
                $this->addDynamicWhere($segment, $connector, $parameters, $index);
                $index++;
            }
            else
            {
                $connector = $segment;
            }
        }

        if (count($segments)>1)
        {
            if ($prev_where!='')
                $this->_where = $prev_where . ' AND (' . str_replace('WHERE ', '', $this->_where) . ')';
            else
                $this->_where = 'WHERE (' . str_replace('WHERE ', '', $this->_where) . ')';
        }

        //dump($this);

        return $this;
    }

    private function addDynamicWhere($segment, $connector, $parameters, $index)
    {
        $bool = strtoupper($connector);

        $this->where(Str::snake($segment), '=', $parameters[$index], $bool);
    }


    private function getArrayOfWheres($column, $boolean, $method = 'where')
    {
        $res = array();

        if (is_array($column[0]))
        {
            foreach($column as $col)
                $res[] = $this->getArrayOfWheres($col, $boolean);
        }
        elseif (!is_numeric($column[0]))
        {
            foreach ($column as $key => $val)
            {
                $res[] = $this->getWhere($key, $val, null);
            }
        }
        else
        {
            $res[] = $this->getWhere($column[0], $column[1], (isset($column[2])? $column[2] : null));
        }

        if (count($res)>1)
            return '(' . implode(' '.$boolean.' ', $res) . ')';

        return implode(' '.$boolean.' ', $res);
    }

    private function getWhere($column, $cond='', $val='', $class='where')
    {
        if ($val=='')
        {
            $val = $cond;
            $cond = '=';
        }

        list ($table, $col) = explode('.', $column);
        if ($col) $column = '`'.$table.'`.`'.$col.'`';
        else $column = '`'.$this->_table.'`.`'.$table.'`';

        /* $vtype = 'i';
        if (is_string($val))
        {
            $vtype = 's';   
        }

        $this->_bindings[] = array($vtype => $val); */

        /* if (is_string($val))
        {
            $val = rtrim(ltrim($val, "'"), "'");
            $val = "'$val'";
        } */

        $this->_bindings[$class][] = $val;

        return $column . ' ' . $cond . ' ?';// . $val;


    }

    private function addWhere($column, $cond='', $val='', $boolean='AND')
    {
        //dump(func_get_args());
        //dump(get_class($this));
        //dump($this->_bindings);
        if (is_array($column))
        {
            $result = $this->getArrayOfWheres($column, $boolean);
        }
        elseif (strpos($column, '@')!==false)
        {
            $prev_where = $this->_where;
            $this->_where = '';
            $this->getCallback($column, $this);
            $result = '(' . str_replace('WHERE ', '', $this->_where) . ')';
            $this->_where = $prev_where;
        }
        else
        {
            $result = $this->getWhere($column, $cond, $val, get_class($this)=='JoinClause'?'join':'where');
        }

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $result; //$column . ' ' . $cond . ' ' . $val; // ' ?';
        else
            $this->_where .= ' '.$boolean.' ' . $result; //$column . ' ' .$cond . ' ' . $val; // ' ?';

        return $this;
    }


    /**
     * Adds a basic WHERE clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $condition Can be ommited for '='
     * @param string $value
     * @return Builder
     */
    public function where($column, $cond='', $val='', $boolean='AND')
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
    public function whereNot($column, $cond='', $val='', $boolean='AND')
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
    public function orWhere($column, $cond='', $val='')
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

    private function addWhereIn($column, $values, $boolean, $not=null)
    {
        $final_array = array();

        if (is_string($values))
        {
            $values = $this->splitStringIntoArray($values);
        }

        foreach ($values as $val)
        {
            /* if (is_string($val))
                $val = "'".$val."'"; */
            
            $final_array[] = '?';
            $this->_bindings['where'][] = $val;
        }

        list ($table, $col) = explode('.', $column);
        if ($col) $column = '`'.$table.'`.`'.$col.'`';
        else $column = '`'.$table.'`';

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ($not? ' NOT' : '') . ' IN ('. implode(',', $final_array) .')';
        else
            $this->_where .= ' ' . $boolean . ' ' . $column . ($not? ' NOT' : '') . ' IN ('. implode(',', $final_array) .')';

        return $this;
    }


    /**
     * Specifies the WHERE IN clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string|array $values
     * @return Builder
     */
    public function whereIn($column, $values)
    {
        return $this->addWhereIn($column, $values, 'AND', null);
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
        return $this->addWhereIn($column, $values, 'OR', null);
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
        return $this->addWhereIn($column, $values, 'AND', true);
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
        return $this->addWhereIn($column, $values, 'OR', true);
    }


    private function addWhereBetween($column, $values, $boolean)
    {
        $final_array = array();

        if (is_string($values))
        {
            $values = $this->splitStringIntoArray($values);
        }

        foreach ($values as $val)
        {
            if ($val instanceof Carbon)
            {
                $val = $val->toDateTimeString();
            }

            elseif (is_string($val))
            {
                $val = "'".$val."'";
            }
            
            $final_array[] = $val;
        }

        list ($table, $col) = explode('.', $column);
        if ($col) $column = '`'.$table.'`.`'.$col.'`';
        else $column = '`'.$table.'`';
        
        if ($this->_where == '')
            $this->_where = "WHERE $column BETWEEN '$final_array[0]' AND '$final_array[1]'";
        else
            $this->_where .= " $boolean $column BETWEEN '$final_array[0]' AND '$final_array[1]'";

        return $this;
    }

    /**
     * Specifies the WHERE BETWEEN clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param array $values
     * @return Builder
     */
    public function whereBetween($column, $values)
    {
        return $this->addWhereBetween($column, $values, 'AND');
    }

    /**
     * Specifies the WHERE BETWEEN clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param array $values
     * @return Builder
     */
    public function orWhereBetween($column, $values)
    {
        return $this->addWhereBetween($column, $values, 'OR');
    }

    /**
     * Add a "where null" clause to the query.
     * Returns the Query builder
     * 
     * @param string|array $column 
     * @return Builder
     */
    public function whereNull($column, $boolean = 'and', $not = false)
    {
        if (is_array($column))
        {
            foreach ($column as $co)
            {
                $this->whereNull($co, false);
            }
        }

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ($not? ' NOT': ' IS') . ' NULL';
        else
            $this->_where .= ' AND ' . $column . ($not? ' NOT': ' IS') . ' NULL';

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
        return $this->whereNull($column, 'or');
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

        list ($table, $col) = explode('.', $first);
        if ($col) $first = '`'.$table.'`.`'.$col.'`';
        else $first = '`'.$table.'`';

        list ($table, $col) = explode('.', $second);
        if ($col) $second = '`'.$table.'`.`'.$col.'`';
        else $second = '`'.$table.'`';

        if ($this->_where == '')
            $this->_where = "WHERE $first $operator $second";
        else
            $this->_where .= " $chain $first $operator $second";

        return $this;
    }

    private function addWhereDate($first, $operator, $second, $chain, $type)
    {
        if (!isset($value))
        {
            $second = $operator;
            $operator = '=';
        }
        if (!($second instanceof Carbon))
        {
            $second = Carbon::parse($second);
        }

        if ($type=='date')
        {
            $second = $second->toDateString();
        }
        elseif ($type=='year')
        {
            $first = "YEAR($first)";
            $second = $second->year;
        }
        elseif ($type=='month')
        {
            $first = "MONTH($first)";
            $second = $second->month;
        }
        elseif ($type=='day')
        {
            $first = "DAY($first)";
            $second = $second->day;
        }
        elseif ($type=='time')
        {
            $first = "TIME($first)";
            $second = $second->rawFormat('H:i:s');
        }

        if ($this->_where == '')
            $this->_where = "WHERE $first $operator $second";
        else
            $this->_where .= " $chain $first $operator $second";

        return $this;

    }

    public function whereDate($column, $cond, $value=null, $boolean='AND')
    {
        return $this->addWhereDate($column, $cond, $value, $boolean, 'date');
    }

    public function orWhereDate($column, $cond, $value=null, $boolean='OR')
    {
        return $this->addWhereDate($column, $cond, $value, $boolean, 'date');
    }

    public function whereYear($column, $cond, $value=null, $boolean='AND')
    {
        return $this->addWhereDate($column, $cond, $value, $boolean, 'year');
    }

    public function orWhereYear($column, $cond, $value=null, $boolean='OR')
    {
        return $this->addWhereDate($column, $cond, $value, $boolean, 'year');
    }

    public function whereMonth($column, $cond, $value=null, $boolean='AND')
    {
        return $this->addWhereDate($column, $cond, $value, $boolean, 'month');
    }

    public function orWhereMonth($column, $cond, $value=null, $boolean='OR')
    {
        return $this->addWhereDate($column, $cond, $value, $boolean, 'month');
    }

    public function whereDay($column, $cond, $value=null, $boolean='AND')
    {
        return $this->addWhereDate($column, $cond, $value, $boolean, 'day');
    }

    public function orWhereDay($column, $cond, $value=null, $boolean='OR')
    {
        return $this->addWhereDate($column, $cond, $value, $boolean, 'day');
    }

    public function whereTime($column, $cond, $value=null, $boolean='AND')
    {
        return $this->addWhereDate($column, $cond, $value, $boolean, 'time');
    }

    public function orWhereTime($column, $cond, $value=null, $boolean='OR')
    {
        return $this->addWhereDate($column, $cond, $value, $boolean, 'time');
    }


    private function getHaving($reference, $operator, $value)
    {
        if (is_array($reference))
        {
            foreach ($reference as $co)
            {
                //var_dump($co); echo "<br>";
                list($var1, $var2, $var3) = $co;
                $this->having($var1, $var2, $var3);
            }
            return $this;
        }

        if ($value=='')
        {
            $value = $operator;
            $operator = '=';
        }

        list ($table, $col) = explode('.', $reference);
        if ($col) $reference = '`'.$table.'`.`'.$col.'`';
        else $reference = '`'.$table.'`';

        /* $vtype = 'i';
        if (is_string($value))
        {
            $vtype = 's';   
        }

        $this->_bindings[] = array($vtype => $value); */


        //if (is_string($value)) $value = "'$value'";

        $this->_bindings['having'][] = $value;

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

        if ($this->_having == '')
            $this->_having = 'HAVING ' . $reference . ' ' . $operator . ' ' . $value; // ' ?';
        else
            $this->_having .= ' ' . $boolean . ' ' . $reference . ' ' .$operator . ' ' . $value; // ' ?';

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
        if ($this->_having == '')
            $this->_having = 'HAVING ' . $reference . ($not? ' NOT': ' IS') . ' NULL';
        else
            $this->_having .= ' ' . $boolean . ' ' . $reference . ($not? ' NOT': ' IS') . ' NULL';

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
        $win = array();
        foreach ($values as $val)
        {
            if (is_string($val)) $val = "'".$val."'";
            array_push($win, $val);
        }

        list ($table, $col) = explode('.', $reference);
        if ($col) $reference = '`'.$table.'`.`'.$col.'`';
        else $reference = '`'.$table.'`';

        if ($this->_having == '')
            $this->_having = "HAVING $reference BETWEEN '$win[0]' AND '$win[1]'";
        else
            $this->_having = " AND $reference BETWEEN '$win[0]' AND '$win[1]'";

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
        foreach ($bindings as $v)
        {
            $this->_bindings['having'] = $v;
            //$val = preg_replace('/\?/', $v, $sql, 1);
        }

        if ($this->_having == '')
            $this->_having = 'HAVING ' . $sql;
        else
            $this->_having = ' ' . $boolean . ' ' . $sql;

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


    private function getCallback($callback, $query)
    {
        if (strpos($callback, '@')!==false)
        {
            list($class, $method, $params) = getCallbackFromString($callback);
            array_shift($params);
            executeCallback($class, $method, array_merge(array($query), $params), $this);
            //call_user_func_array(array($class, $method), array_merge(array($query), $params));
        }
    }


    public function when($value, $callback, $default = null)
    {
        if ($value)
        {
            $this->getCallback($callback, $this);
        }
        elseif ($default)
        {
            $this->getCallback($default, $this);
        }

        return $this;
    }

    private function _joinCallback($side, $name, $column)
    {
        $join = new JoinClause($name);
        $this->getCallback($column, $join);

        if (!$this->_bindings['join']) $this->_bindings['join'] = array();
        $this->_bindings['join'] = array_merge($this->_bindings['join'], $join->_bindings['join']);
        
        $result = str_replace('WHERE', '', $join->_where);
        unset($join);

        if ($this->_join == '')
            $this->_join = $side . ' JOIN `' . $name . '` ON ' . $result;
        else
            $this->_join .= ' ' . $side . ' JOIN `' . $name . '` ON ' . $result;
        
        //dump($this);
        return $this;

    }


    private function _joinResult($side, $name, $column, $comparator, $ncolumn)
    {
        if (strpos($column, '@')>0)
        {
            return $this->_joinCallback($side, $name, $column);
        }

        list($name, $as, $alias) = explode(' ', $name);

        $side = trim($side);
        $as = trim($as);
        $name = trim($name);
        $alias = trim($alias);
        $column =  strpos($column, '.')!==false? $column : '`' . ($as? $alias : $name) . '`.`' . trim($column) . '`';
        $ncolumn =  strpos($ncolumn, '.')!==false? $ncolumn : '`' . $this->_table . '`.`' . trim($ncolumn) . '`';

        if ($this->_join == '')
            $this->_join = $side.' JOIN ' . $name . ($as?' AS '.$alias:'') . ' ON ' . $ncolumn .' '.$comparator.' ' . $column;
        else
            $this->_join .= ' ' . $side.' JOIN ' . $name . ($as?' AS '.$alias:'') . ' ON ' . $ncolumn .' '.$comparator.' ' . $column;
  
        return $this;
    }

    /**
     * Specifies the INNER JOIN clause\
     * Returns the Query builder
     * 
     * @param string $join_table 
     * @param string $column
     * @param string $comparator
     * @param string $join_column
     * @return Builder
     */
    public function join($join_table, $column, $comparator=null, $join_column=null)
    {
        return $this->_joinResult('INNER', $join_table, $column, $comparator, $join_column);
    }

    /**
     * Specifies the LEFT JOIN clause\
     * Returns the Query builder
     * 
     * @param string $join_table 
     * @param string $column
     * @param string $comparator
     * @param string $join_column
     * @return Builder
     */
    public function leftJoin($join_table, $column, $comparator=null, $join_column=null)
    {
        return $this->_joinResult('LEFT', $join_table, $column, $comparator, $join_column);
    }

    /**
     * Specifies the RIGHT JOIN clause\
     * Returns the Query builder
     * 
     * @param string $join_table 
     * @param string $column
     * @param string $comparator
     * @param string $join_column
     * @return Builder
     */
    public function rightJoin($join_table, $column, $comparator=null, $join_column=null)
    {
        return $this->_joinResult('RIGHT', $join_table, $column, $comparator, $join_column);
    }

    /**
     * Specifies the CROSS JOIN clause\
     * Returns the Query builder
     * 
     * @param string $join_table 
     * @param string $column
     * @param string $comparator
     * @param string $join_column
     * @return Builder
     */
    public function crossJoin($join_table, $column, $comparator=null, $join_column=null)
    {
        return $this->_joinResult('CROSS', $join_table, $column, $comparator, $join_column);
    }


    private function _joinSubResult($side, $query, $alias, $filter)
    {
        $side = trim($side);
        $alias = '`' . trim($alias) . '`';

        if ($query instanceof Builder)
        {
            if (!$this->_bindings['select']) $this->_bindings['select'] = array();
            if (!$this->_bindings['where']) $this->_bindings['where'] = array();
            if (!$this->_bindings['join']) $this->_bindings['join'] = array();

            if (is_array($query->_bindings['select']) && count($query->_bindings['select'])>0) {
                $this->_bindings['join'] = array_merge($this->_bindings['join'], $query->_bindings['select']);
            }
            if (is_array($query->_bindings['join']) && count($query->_bindings['join'])>0) {
                $this->_bindings['join'] = array_merge($this->_bindings['join'], $query->_bindings['join']);
            }
            
            if (is_array($query->_bindings['where']) && count($query->_bindings['where'])>0) {
                $this->_bindings['join'] = array_merge($this->_bindings['join'], $query->_bindings['where']);
            }

            $query = $query->toSqlFirst();
        }


        if ($this->_join == '')
            $this->_join = $side.' JOIN (' . $query . ') as ' . $alias . ' ' . $filter;
        else
            $this->_join .= ' '.$side.' JOIN (' . $query . ') as ' . $alias . ' ' . $filter;

        return $this;
    }

    private function getSubJoinFilter($first, $operator, $second = null)
    {
        $filter = '';

        if (strpos($first, '@') > 0)
        {
            $join = new JoinClause(null);
            $this->getCallback($first, $join);

            $filter = str_replace('WHERE', 'ON', $join->_where);
            unset($join);

            return $filter;
        }

        if (!isset($second) && isset($operator))
        {
            $operator = '=';
            $second = $operator;
        }
        if (isset($second) && isset($operator))
        {
            $filter = ' ON ' . $first . ' ' . $operator . ' ' . $second;  
        }

        return $filter;
    }


    /**
     * INNER Joins as subquery\
     * Returns the Query builder
     * 
     * @param string $query 
     * @param string $alias
     * @param Query $filter
     * @return Builder
     */
    public function joinSub($query, $alias, $first, $operator = null, $second = null, $type = 'INNER', $where = false)
    {
        $filter = $this->getSubJoinFilter($first, $operator, $second);
        return $this->_joinSubResult($type, $query, $alias, $filter);
    }

    /**
     * LEFT Joins as subquery\
     * Returns the Query builder
     * 
     * @param string $query 
     * @param string $alias
     * @param Query $filter
     * @return Builder
     */
    public function leftJoinSub($query, $alias, $first, $operator = null, $second = null)
    {
        $filter = $this->getSubJoinFilter($first, $operator, $second);
        return $this->_joinSubResult('LEFT', $query, $alias, $filter);
    }

    /**
     * RIGHT Joins as subquery\
     * Returns the Query builder
     * 
     * @param string $query 
     * @param string $alias
     * @param Query $filter
     * @return Builder
     */
    public function rightJoinSub($query, $alias, $first, $operator = null, $second = null)
    {
        $filter = $this->getSubJoinFilter($first, $operator, $second);
        return $this->_joinSubResult('RIGHT', $query, $alias, $filter);
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
        $this->_union = 'UNION ' . $query->toSqlFirst();

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
        $this->_union = 'UNION ALL ' . $query->toSqlFirst();

        if (count($query->_bindings['union'])>0) {
            $this->_bindings['union'] = array_merge($this->_bindings['union'], $query->_bindings['union']);
        }

        return $this;
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
    /* public function search($var, $val)
    {
        $var = str_replace(',','," ",',$var);

        if ($this->_where == '')
            $this->_where = 'WHERE CONCAT(' . $var . ') LIKE "%'.$val.'%"';
        else
            $this->_where .= ' OR CONCAT(' . $var . ') LIKE "%'.$val.'%"';
            
        return $this;
    } */

    /**
     * Specifies the GROUP BY clause\
     * Returns the Query builder
     * 
     * @param string $order
     * @return Builder
     */
    public function groupBy($val)
    {
        if ($this->_group == '')
            $this->_group = 'GROUP BY ' . $val;
        else
            $this->_group .= ', ' . $val;

        return $this;
    }

    /**
     * Specifies the GROUP BY clause\
     * Returns the Query builder
     * 
     * @param string $order
     * @return Builder
     */
    public function groupByRaw($val)
    {
        if ($this->_group == '')
            $this->_group = 'GROUP BY ' . $val;
        else
            $this->_group .= ', ' . $val;

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
     * @param string $order
     * @return Builder
     */
    public function orderBy($column, $order='ASC')
    {
        if ($column instanceof Builder)
        {
            $column = '(' . $column->buildQuery() . ')';
        }

        if ($this->_order == '')
            $this->_order = "ORDER BY $column $order";
        else
            $this->_order .= ", $column $order";

        return $this;
    }

    /**
     * Specifies the ORDER BY clause without changing it\
     * Returns the Query builder
     * 
     * @param string $order
     * @return Builder
     */
    public function orderByRaw($order)
    {
        if ($this->_order == '')
            $this->_order = "ORDER BY $order";
        else
            $this->_order .= " ORDER BY $order";

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
        if (is_array($key))
        {
            foreach ($key as $k => $v)
            {
                $this->set($k, $v);
            }
        }
        else
        {
            array_push($this->_keys, $key);

            $camel = Helpers::snakeCaseToCamelCase($key);
            //echo "KEY: $camel"."Attribute<br>";

            if (method_exists($this->_parent, 'set'.ucfirst($camel).'Attribute'))
            {
                $fn = 'set'.ucfirst($camel).'Attribute';
                $newmodel = new $this->_parent;
                $val = $newmodel->$fn($val);
            }

            if (method_exists($this->_parent, $camel.'Attribute'))
            {
                #echo "Value:$val<br>";
                $fn = $camel.'Attribute';
                $newmodel = new $this->_parent;
                $nval = $newmodel->$fn($val, (array)$newmodel);
                if (isset($nval['set'])) $val = $nval['set'];
                #echo "NEW value:$val<br>";
            }

            /* if ($val instanceof Carbon)
            {
                $val = $val->toDateTimeString();
            } */
            
            /* if (is_string($val)) 
            {
                $val = "'".$val."'";
            } */

            array_push($this->_values, isset($val)? $val : "NULL");
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

        if (!is_array(reset($values)))
        {    
            $this->clear();
            return $this->_insert($values);
        }

        foreach ($values as $value)
        {
            $this->clear();
            $res = $this->_insert($value);
        }

        return $res;
    }

    /**
     * UPSERT a record or an array of records in database
     * 
     * @param array $record
     * @return bool
     */
    public function upsert($records, $keys, $values)
    {
        foreach ($records as $record)
        {
            $attributes = array();
            foreach ($keys as $key)
            {
                $attributes[$key] = $record[$key];
            }

            $update = array();
            foreach ($values as $value)
            {
                $update[$value] = $record[$value];
            }
            
            $res = $this->updateOrInsert($attributes, $update);

        }
        return $res;
    }

    private function _insert($record, $ignore=false)
    {
        $record = CastHelper::processCastsBack(
            $record,
            $this->_model
        );
        
        foreach ($record as $key => $val)
            $this->setValues($key, $val);

        if (count($this->_values)==0)
            throw new Exception ("Error setting values for new model");

        if ($this->_timestamps && !isset($this->_values[$this->_timestamp_created]) && $this->_parent!='DB')
            $this->set($this->_timestamp_created, now()->toDateTimeString());

        //$sql = 'INSERT ' . ($ignore? 'IGNORE ' : '') . 'INTO `' . $this->_table . '` (' . implode(', ', $this->_keys) . ')'
        //        . ' VALUES (' . implode(', ', $this->_values) . ')';

        $qmarks = array();
        for ($i=0; $i<count($this->_values); $i++) {
            $qmarks[] = '?';
        }

        $sql = 'INSERT ' . ($ignore? 'IGNORE ' : '') . 'INTO `' . $this->_table . 
            '` (' . implode(', ', $this->_keys) . ') VALUES (' . implode(', ', $qmarks) . ')';

        //dump("INSERTING:: $sql<br>".implode(', ', $this->_values));

        $query = $this->connector()->query($sql, $this->_values);
    
        $last = array();
        for ($i=0; $i<count($this->_keys); ++$i)
        {
            $last[$this->_keys[$i]] = $this->_values[$i];
        }
        $this->_lastInsert = $last;

        $this->clear();
        
        return $query; //$this->connector()->error;
    }


    /**
     * INSERT IGNORE a record or an array of records in database
     * 
     * @param array $record
     * @return bool
     */
    public function insertOrIgnore($values)
    {
        if (!is_array(reset($values)))
        {    
            $this->clear();
            return $this->_insert($values, true);
        }

        foreach ($values as $value)
        {
            $this->clear();
            $res = $this->_insert($value, true);
        }

        return $res;
    }


    private function setValues($key, $val, $unset=false, $return=false)
    {
        global $preventSilentlyDiscardingAttributes;

        if ($key=='_global_scopes' || $key=='_query' || $key=='timestamps')
            return $this;

        if (in_array($key, $this->_fillable) || $this->_fillableOff)
        {
            $this->set($key, $val);
        }
        elseif (isset($this->_guarded) && !in_array($key, $this->_guarded))
        {
            $this->set($key, $val);
        }
        else
        {
            if ($unset)
                unset($record[$key]);

            if ($preventSilentlyDiscardingAttributes)
                throw new Exception("Add fillable property [$key] to allow mass assignment on [$this->_parent]");

        }
        
        return $this; 
    }


    /**
     * Creates a new record in database\
     * Returns new record
     * 
     * @param array $record
     * @return Model
     */
    public function create($record = null)
    {
        if (isset($this->_relationVars) && $this->_relationVars['relationship']=='morphOne'
            && isset($this->_relationVars['current_id']))
        {
            $record[$this->_relationVars['foreign']] = $this->_relationVars['current_id'];
            $record[$this->_relationVars['relation_type']] = $this->_relationVars['current_type'];
        }

        elseif (isset($this->_relationVars['foreign']) && count($this->_relationVars['where_in'])>0)
        {
            if (!isset($record[$this->_relationVars['foreign']]))
               $record[$this->_relationVars['foreign']] = $this->_relationVars['where_in'][0];
        }
        
        $this->checkObserver('creating', $record);

        if ($this->_insert($record))
        {
            $this->checkObserver('created', $record);
            $item = $this->insertNewItem();
            $item->_setRecentlyCreated(true);
            return $item;
        }
        
        return null;

    }

    /**
     * Creates the new records in database\
     * 
     * @param array $record
     * @return Model
     */
    public function createMany($records=array())
    {
        //dump($this);
        if (!isset($this->_relationVars['foreign']) || count($this->_relationVars['where_in'])==0)
        {
            throw new Exception('Parent relation is missing');
        }

        foreach($records as $record)
        {
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
            throw new Exception('No model asigned');
        }

        if(!isset($this->_relationVars['foreign'])) {
            throw new Exception('Parent relation is missing');
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
            throw new Exception('Parent relation is missing');
        }

        foreach ($models as $val)
        {
            if(!($models[0] instanceof Model)) {
                throw new Exception('No model asigned');
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
     * Updates a record in database
     * 
     * @param array|object $record
     * @return bool
     */
    public function update($attributes)
    {
        if ($this->_where=='')
        {
            foreach ($this->_primary as $primary)
            {
                $val = is_object($attributes) ? $attributes->$primary : $attributes[$primary];

                if (!isset($val))
                    throw new Exception("Error in model's primary key");
                    
                $this->where($primary, $val);
            }
        }

        $attributes = CastHelper::processCastsBack(
            $attributes,
            $this->_model
        );

        $values = array();
        $bindings = array();
        foreach ($attributes as $key => $val)
        {
            if (!is_object($val) && !in_array($key, $this->_appends))
            {
                if (is_bool($val)) {
                    $val = $val==true? 1 : 0;
                }

                $camel = Helpers::snakeCaseToCamelCase($key);

                if (method_exists($this->_parent, 'set'.ucfirst($camel).'Attribute'))
                {
                    $fn = 'set'.ucfirst($camel).'Attribute';
                    $newmodel = new $this->_parent;
                    $val = $newmodel->$fn($val);
                }

                if (method_exists($this->_parent, $camel.'Attribute'))
                {
                    #echo "Value:$val<br>";
                    $fn = $camel.'Attribute';
                    $newmodel = new $this->_parent;
                    $nval = $newmodel->$fn($val, (array)$newmodel);
                    if (isset($nval['set'])) $val = $nval['set'];
                    #echo "NEW value:$val<br>";
                }

                //$values[$key] = "`$key` = ". ($val!==null ? "'".$val."'" : 'NULL');
                $values[$key] = "`$key` = ". ($val!==null ? '?' : 'NULL');
                if ($val!==null) $bindings[] = $val;
            }
        }
    
        if ($this->_where == '')
            throw new Exception('WHERE not assigned.');

        if (count($values)==0)
           throw new Exception('No values assigned for update');


        /* if ($this->_softDelete && !$this->_withTrashed)
            $this->whereNull('deleted_at'); */

        $sql = 'UPDATE `' . $this->_table . '` SET ' . implode(', ', $values) . ' ' . $this->_where;

        //dump(array_merge($bindings, $this->_bindings['where']));

        $this->checkObserver('updating', $attributes);

        $query = $this->connector()->query($sql, array_merge($bindings, $this->_bindings['where'])); //, $this->_where, $this->_bindings);

        if ($query)
            $this->checkObserver('updated', $attributes);

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

        foreach ($attributes as $key => $val)
        {
           $this->where($key, $val);
        }
        
        $sql = 'SELECT * FROM `' . $this->_table . '` '. $this->_where . ' LIMIT 0, 1';
        
        $cloned = $this->_clone();
        $res = $this->connector()->execSQL($sql, $cloned, true);

        if ($res->count()>0)
        {
            return $this->update($values);
        }
        else
        {
            $new = array_merge($attributes, $values);
            return $this->_insert($new);
        }

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
        foreach ($attributes as $key => $val)
        {
           $this->where($key, $val);
        }
        
        $sql = 'SELECT * FROM `' . $this->_table . '` '. $this->_where . ' LIMIT 0, 1';
        
        $res = $this->connector()->execSQL($sql, $this, true);

        if ($res->count()>0)
        {
            $item = $res->first();
            
            $this->update($values);

            foreach($values as $key => $val)
                $item->$key = $val;

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
        if (!is_array(reset($values)))
        {    
            $this->clear();
            return $this->_insertReplace($values, true);
        }

        foreach ($values as $value)
        {
            $this->clear();
            $res = $this->_insertReplace($value, true);
        }

        return $res;
    }

    private function _insertReplace($record)
    {
        
        foreach ($record as $key => $val)
            $this->set($key, $val);
        
        $sql = 'REPLACE INTO `' . $this->_table . '` (' . implode(', ', $this->_keys) . ')'
                . ' VALUES (' . implode(', ', $this->_values) . ')';

        //echo $sql;
        $query = $this->connector()->execSQL($sql, $this, false);

        $this->clear();
        
        return $query;
    }

    /**
     * DELETE the current records from database\
     * Returns error if WHERE clause was not specified
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->_where == '')
            throw new Exception('WHERE not assigned');

        $sql = 'DELETE FROM `' . $this->_table . '` ' . $this->_where;

        //dump($sql); dd($this->_bindings);

        $query = $this->connector()->query($sql, $this->_bindings);

        $this->clear();
        
        return $query;
    }


    public function destroy()
    {
        $models = func_get_args();
        $_delete = array();

        foreach ($models as $model)
        {
            $_delete = array_merge($_delete, is_array($model)? $model : array($model));
        }

        $result = $this->whereIn($this->_primary[0], $_delete)->get();

        $res = 0;
        foreach ($result as $model)
        {
            $res += $model->delete();
        }
        
        return $res;
    }


    /**
     * Include trashed models in Query
     * 
     * @return Builder
     */
    public function withTrashed()
    {
        if (!$this->_softDelete)
            throw new Exception('Trying to use softDelete method on a non-softDelete Model');

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
        if (!$this->_softDelete)
            throw new Exception('Trying to use softDelete method on a non-softDelete Model');
        
        $date = date("Y-m-d H:i:s");

        foreach ($this->_primary as $primary)
        {
            if (!isset($record[$primary]))
                throw new Exception("Error in model's primary key");
                
            $this->where($primary, $record[$primary]);
        }

        $sql = 'UPDATE `' . $this->_table . '` SET `deleted_at` = ' . "'$date'" . ' ' . $this->_where;

        $query = $this->connector()->query($sql);

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
        if (!$this->_softDelete)
            throw new Exception('Trying to use softDelete method on a non-softDelete Model');

        if (isset($record))
        {
            foreach ($this->_primary as $primary)
            {
                if (!isset($record[$primary]))
                    throw new Exception("Error in model's primary key");
                    
                $this->where($primary, $record[$primary]);
            }
        }

        $sql = 'UPDATE `' . $this->_table . '` SET `deleted_at` = NULL ' . $this->_where;

        //dd($sql); exit();

        $query = $this->connector()->query($sql); //, $this->_where, $this->_bindings);

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
        foreach ($this->_primary as $primary)
        {
            if (!isset($record[$primary]))
                throw new Exception("Error in model's primary key");
                
            $this->where($primary, $record[$primary]);
        }
        
        $sql = 'DELETE FROM `' . $this->_table . '` ' . $this->_where;

        $query = $this->connector()->query($sql); //, $this->_bindings);

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
        $sql = 'TRUNCATE TABLE `' . $this->_table . '`';

        $query = $this->connector()->query($sql);

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
        if (!($model instanceof Model))
        {
            throw new Exception("Model not found");
        }

        if ($this->_relationVars['relationship'] != 'belongsTo')
        {
            throw new Exception("Associate method only works for belongsTo relations");
        }


        $item = $this->_relationVars['collection'];
        $item = $item->first();

        $primary = $this->_relationVars['primary'];
        $foreign = $this->_relationVars['foreign'];

        $item->$primary = $model->$foreign;
        //$item->setRelation($this->_relationVars['relationship'], $model);

        $item->setQuery(null);

        return $item;

    }

    public function dissociate()
    {
        //return $this->child->setRelation($this->relationName, null);

        if ($this->_relationVars['relationship'] != 'belongsTo')
        {
            throw new Exception("Associate method only works for belongsTo relations");
        }

        $item = $this->_relationVars['collection'];
        $item = $item->first();

        $primary = $this->_relationVars['primary'];

        $item->$primary = null;
        //$item->setRelation($this->_relationVars['relationship'], $model);

        $item->setQuery(null);

        return $item;
    }


    /**
     * Returns the first record from query\
     * Returns 404 if not found
     * 
     * @return object
     */
    public function firstOrFail()
    {        
        $model = $this->first();

        if (!$model)
            abort(404);

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
        //$this->_collection = array();

        $this->clear();
        foreach ($attributes as $key => $val)
        {
            $this->where($key, $val);
        }

        $sql = $this->_method . ' FROM `' . $this->_table . '` ' . $this->_where . ' LIMIT 0, 1';

        $this->connector()->execSQL($sql, $this, true);

        if ($this->_collection->count()>0)
        {
            $this->processEagerLoad();

            return $this->_collection->first(); //$this->insertUnique($this->_collection->first(), true);
        }

        $item = array(); //new $this->_parent;

        foreach ($attributes as $key => $val)
            $item[$key] = $val;
            
        foreach ($values as $key => $val)
            $item[$key] = $val;

        return $this->insertUnique($item, true);
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
        foreach ($attributes as $key => $val)
        {
            $this->where($key, $val);
        }

        $sql = $this->_method . ' FROM `' . $this->_table . '` ' . $this->_where . ' LIMIT 0, 1';
        
        $this->connector()->execSQL($sql, $this, true);

        if ($this->_collection->count()>0)
        {
            $this->processEagerLoad();

            return $this->_collection->first(); //$this->insertUnique($this->_collection->first(), true);
        }

        $item = new $this->_parent;
        $item = $this->create(array_merge($attributes, $values));
        if ($item) $item->_setRecentlyCreated(true);

        return $item;

    }

    /**
     * Find a record or an array of records based on primary key

     * @return Model|Collection
     */
    public function find($val)
    {
        //$this->_where = 'WHERE ' . $this->_primary . ' = "' . $val . '"';
        //$value = is_array($val)? $val : array($val);

        //$this->_where = '';
        if (is_array($val))
        {
            $this->whereIn($this->_primary[0], $val);
        }
        else
        {
            $this->where($this->_primary[0], $val);
        }

        return is_array($val)? $this->get() : $this->first();
    }

    public function findOrFail($val)
    {
        //$this->_where = 'WHERE ' . $this->_primary . ' = "' . $val . '"';
        $val = is_array($val)? $val : array($val);
        //$this->_where = '';
        $i = 0;
        foreach ($this->_primary as $primary)
        {
            $this->where($primary, $val[$i]);
            ++$i;
        }

        $res = $this->first();

        if ($res)
            return $res;
            
        abort(404);
    }


    private function insertNewItem()
    {
        $last = $this->connector()->getLastId();
        //dump($last); dump($this);

        if ($last==0)
        {
            $keys = is_array($this->_primary) ? $this->_primary : array($this->_primary);

            $this->clear();
            foreach ($keys as $key)
            {
                $this->where($key, $this->_lastInsert[$key]);
            }

            return $this->first(); //$this->insertUnique($this->first());
        }

        return $this->find($last); //$this->insertUnique($this->find($last));
    }


    

    private function insertUnique($data, $new=false)
    {
        $class = $this->_parent;
        $item = new $class;

        foreach ($data as $key => $val)
        {
            $item->$key = $val;
        }

        //$this->__new = false;
        $item->_setOriginalRelations($this->_eagerLoad);

        unset($item->_global_scopes);
        unset($item->timestamps);

        $item->setAttributes(CastHelper::processCasts(
            $item->getAttributes(),
            $this->_model,
            false
        ));


        foreach ($item->getAttributes() as $key => $val)
        {
            if ($this->_softDelete)
            {
                $item->unsetAttribute('deleted_at');
            }


            $camel = Helpers::snakeCaseToCamelCase($key);

            if (method_exists($this->_parent, 'get'.ucfirst($camel).'Attribute'))
            {
                $fn = 'get'.ucfirst($camel).'Attribute';
                $val = $item->$fn($val);
            }
            if (method_exists($this->_parent, $camel.'Attribute'))
            {
                $fn = $camel.'Attribute';
                $nval = $item->$fn($val, (array)$item);
                if (isset($nval['get'])) $item->$key = $nval['get'];
            }
        }

        foreach ($item->getAttributes() as $key => $val)
        {
            $item->_setOriginalKey($key, $val);
        }

        /* foreach ($this->_appends as $append)
        {
            $item->setAppendAttribute($append, $item->$append);
        } */

        return $item;

    }

    /* private function insertData($data)
    {
        $col = new Collection($this->_parent, $this->_hidden); //get_class($this->_parent));
        //$col->_modelKeys = $this->_primary;

        foreach ($data as $item)
        {    
            $col[] = $this->insertUnique($item);
        }

        if ($data->hasPagination())
        {
            $col->setPagination($data->getPagination());
        }

        return $col;
       
    } */

    /**
     * Return all records from current query
     * 
     * @return Collection
     */
    public function all()
    {
        return $this->get();
    }

    private function addGlobalScopes()
    {
        if (method_exists($this->_model, 'booted')) 
            $this->_model->booted();

        foreach($this->_model->_global_scopes as $scope => $callback)
        {
            $this->_scopes[$scope] = $callback;
        }
    }

    private function applyGlobalScopes()
    {
        $this->addGlobalScopes();

        foreach($this->_scopes as $scope => $callback)
        {
            if (class_exists($scope))
            {
                $callback->apply($this, $this->_model);
            }
            else
            {
                list($class, $method, $params) = getCallbackFromString($callback);
                array_shift($params);
                executeCallback($class, $method, array_merge(array($this), $params), $this);
                //call_user_func_array(array($class, $method), array_merge(array($this), $params));
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
    public function first()
    {
        if ($this->_softDelete && !$this->_withTrashed)
            $this->whereNull('deleted_at');

        $this->applyGlobalScopes();

        $this->limit(1);

        $sql = $this->buildQuery();

        $this->connector()->execSQL($sql, $this, true);

        if ($this->_collection->count()==0)
            return NULL;

        $this->processEagerLoad();

        $this->clear();

        return $this->_collection->first(); // $this->insertUnique($this->_collection[0]);
    }

    /**
     * Return all records from current query
     * 
     * @return Collection
     */
    public function get($columns=null)
    {
        if ($this->_softDelete && !$this->_withTrashed)
            $this->whereNull('deleted_at');

        $this->applyGlobalScopes();

        if (isset($columns))
        {
            $this->select($columns);
        }

        $sql = $this->buildQuery();

        $this->connector()->execSQL($sql, $this, true);

        if ($this->_collection->count()==0)
            return $this->_collection;

        $this->processEagerLoad();

        $this->clear();

        return $this->_collection; //$this->insertData($this->_collection);

    }
    
    /**
     * Return all records from current query\
     * Limit the resutl to number of $records\
     * Send Pagination values to View class 
     * 
     * @param int $records
     * @return Collection
     */
    public function paginate($cant = 10)
    {
        $filtros = $_GET;

        $pagina = $filtros['p']>0? $filtros['p'] : 1;
        $offset = ($pagina-1) * $cant; 

        if ($this->_softDelete && !$this->_withTrashed)
            $this->whereNull('deleted_at');

        $this->_limit = null;
        $this->_offset = null;

        $this->applyGlobalScopes();
            
        $sql = $this->buildQuery() . " LIMIT $cant OFFSET $offset";

        
        $this->connector()->execSQL($sql, $this, true);

        if ($this->_collection->count()==0)
        {
            return $this->_collection;
        }
        
        $records = 'select count(*) AS total from (' . $this->buildQuery() .') final';
        
        $query = $this->connector()->execSQL($records, $this->_clone(), true)->first();

        $total = isset($query)? (int)( 
            isset($query->total)? $query->total : (
                isset($query->TOTAL)? $query->TOTAL : 0)
            ) : 0;

        $pages = ceil($total / $cant);

        $pagina = (int)$pagina;
        $pages = (int)$pages;
        
        $pagination = new Paginator;

        $pagination->first = $pagina<=1? null : 'p=1';
        $pagination->last = $pagina==$pages? null : 'p='.$pages;
        $pagination->previous = $pagina<=1? null : 'p='.($pagina-1);
        $pagination->next = $pagina==$pages? null : 'p='.($pagina+1);

        $meta = array();
        $meta['current'] = $pagina;
        $meta['from'] = $offset +1;
        $meta['last_page'] = $pages;
        $meta['path'] = env('APP_URL') . '/' . request()->route->url;
        $meta['per_page'] = $cant;
        $meta['to'] = $total < ($cant*$pagina)? $total : ($cant*$pagina);
        $meta['total'] = $total;

        $pagination->meta = $meta;

        
        $this->_collection->setPaginator($pagination);
        
        $this->processEagerLoad();
        
        $this->clear();

        return $this->_collection; //$this->insertData($this->_collection);

    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param  string  $column
     * @return mixed
     */
    public function value($column)
    {
        $result = $this->first();

        if (!isset($result) || !isset($result->$column))
        {
            return null;
        }

        return $result->$column;
    }

    /**
     * Execute the query and get the first result if it's the sole matching record.
     *
     * @param  array|string  $columns
     */
    public function sole($columns=null)
    {
        $result = $this->take(2)->get($columns);

        $count = $result->count();

        if ($count === 0) {
            throw new Exception('Record not found');
        }

        if ($count > 1) {
            throw new Exception('Multiple records found');
        }

        return $result->first();
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
        
        foreach ($keys as $key)
        {
            $this->where($key, $original[$key]);
        }

        $this->_eagerLoad = $relations;

        return $this->first();
    }

    public function query()
    {
        return $this;
    }

    /**
     * Executes the SQL $query
     * 
     * @param string $query
     * @return msqli_result|bool
     */
    public function runQuery($sql)
    {
        return $this->connector()->query($sql);
    }


    public function setForeign($key)
    {
        $this->_foreign = $key;
        return $this;
    }

    /* public function setPrimary($key)
    {
        $this->_primary = $key;
        return $this;
    } */

    /* public function setRelationship($key)
    {
        $this->_relationship = $key;
        return $this;
    } */

    /* public function setParent($key)
    {
        $this->_parent = $key;
        //return $this;
    } */

    public function setConnector($connector)
    {
        $this->sql_connector = null;
        $this->_connector = $connector;
        return $this;
    }

    public function _as($pivot_name)
    {
        if ($this->_relationVars && isset($this->_relationVars['relationship']))
        {
            if (in_array($this->_relationVars['relationship'], array('belongsToMany', 'morphToMany', 'morphedByMany')))
                $this->_relationVars['pivot_name'] = is_array($pivot_name) ? $pivot_name[0] : $pivot_name;
        }
        return $this;
    }

    public function withPivot()
    {
        $columns = func_get_args();
        
        if ($this->_relationVars && isset($this->_relationVars['relationship']))
        {
            if ($this->_relationVars['relationship']=='belongsToMany')
                $this->_relationVars['extra_columns'] = $columns;
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
     * Adds records from a sub-query inside the current records\
     * Check Laravel documentation
     * 
     * @return Builder
     */
    public function with($relations)
    {
        if (is_string($relations))
            $relations = array($relations);

        //dd($relations);
        
        foreach ($relations as $relation => $values)
        {
            //dump($relation); dump($values);
            /* if (is_string($values))
            {
                $this->addRelation($this->_eagerLoad, $values);
            }
            elseif (is_null($values))
            {
                $this->addRelation($this->_eagerLoad, $relation);
            }
            elseif (is_object($values))
            {
                $values->_where = str_replace("`".$values->_table."`.", "`_child_table_`.", $values->_where);
                $this->addRelation($this->_eagerLoad, $relation, $values);
            } */

            if (is_string($values) && strpos($values, '@')===false)
            {
                $this->addRelation($this->_eagerLoad, $values);
            }
            elseif (is_array($values))
            {
                foreach ($values as $val)
                {
                    $this->addRelation($this->_eagerLoad, $relation.'.'.$val);
                }
            }
            elseif (is_null($values))
            {
                $this->addRelation($this->_eagerLoad, $relation);
            }
            else //if (is_object($values))
            {
                $this->addRelation($this->_eagerLoad, $relation, $values);
            }
            /* else
            {
                array_push($this->_eagerLoad, array('relation' => trim($relation), 'constraints' => $values ));
            } */
        }
        //echo "<br>";var_dump($this->_eagerLoad);echo "<br>";
        //dd($this->_eagerLoad);
        //dd($this);
        return $this;
    }


    private function processEagerLoad()
    {
        //if (count($this->_eagerLoad) == 0) return;
        //dd($this);
        
        foreach ($this->_eagerLoad as $key => $val)
        {
            $q = $this->_model->getQuery();
            $q->_collection = $this->_collection;
            $q->_relationName = $key;
            $q->_relationColumns = '*';
            $q->_extraQuery = isset($val['_constraints']) ? $val['_constraints'] : null;
            $q->_nextRelation = $val;
            $q->_toBase = $this->_toBase;
            //dump($key);dump($val);
            //dump($this->_hasConstraints);
            
            if (!isset($val['_constraints']) && isset($this->_hasConstraints['constraints']))
            {
                $q->_hasConstraints = $this->_hasConstraints;
            }

            if (strpos($key, ':')>0) {
                list($key, $columns) = explode(':', $key);
                $q->_relationColumns = explode(',', $columns);
                $q->_relationName = $key;
            }

            $res = $this->_model->$key();

            $res->_toBase = $this->_toBase;

            Relations::insertRelation($q, $res, $key);
        }

        if (!$this->_toBase)
        {
            foreach ($this->_collection as $item)
            {
                foreach ($this->_appends as $append)
                {
                    $item->setAppendAttribute($append, $item->$append);
                }
    
            }
        }


    }


    public function load($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;
        
        $this->with($relations);
        $this->processEagerLoad();

        return $this->_collection;
    }


    /* public function setHasConstraint($value)
    {
        if (!$value)
            unset($this->_hasConstraints);
        else
            $this->_hasConstraints = $value;
    } */

    public function _has($relation, $constraints=null, $comparator=null, $value=null)
    {
        //echo "HAS: ".$relation. " :: ".$constraints."<br>";
        //dump($this, true);
        $data = null;
        
        //$newparent = new $this->_parent;
        
        if (strpos($relation, '.')>0)
        {
            $data = explode('.', $relation);
            $relation = array_pop($data);
            $parent_relation = array_shift($data);
        }

        if (strpos($relation, ':')>0)
        {
            $data = explode(':', $relation);
            $relation = reset($data);
        }
        
        //$newparent->getQuery()->varsOnly = true;
        //$newparent->$relation();
        $data = $this->_model->$relation();
        //dump($data, true);

        $childtable = $data->_table;
        $foreign = $data->_relationVars['foreign'];
        $primary = $data->_relationVars['primary'];

        $filter = '';
        if (isset($constraints) && !is_array($constraints) && strpos($constraints, '@')!==false)
        {
            $this->getCallback($constraints, $data);

            if ($data->_bindings['where']) {
                if (!$this->_bindings['where'])
                    $this->_bindings['where'] = array();
                
                $this->_bindings['where'] = array_merge($this->_bindings['where'], $data->_bindings['where']);
            }
                
            $filter = str_replace('WHERE', ' AND', $data->_where);
        } 
        elseif (isset($constraints) && !is_array($constraints) && strpos($constraints, '@')===false)
        {
            $filter = " AND `$data->_table`.`$constraints` $comparator ?";//$value";
            $comparator = null;
            if ($value) $this->_bindings['where'][] = $value;
        }
        else
        {
            $filter = str_replace('WHERE', ' AND', $data->_where);
            if ($value) $this->_bindings['where'][] = $value;
        }

        
        /* elseif (isset($constraints) && is_array($constraints))
        {
            foreach ($constraints as $exq)
            {
                $new_where = str_replace('`'.$exq->_table.'`.', '`'.$data->_table.'`.', $exq->_where);
                $new_where = str_replace('`_child_table_`.', '`'.$data->_table.'`.', $exq->_where);
                $filter .= str_replace('WHERE', ' AND', $new_where);
            }
        }  */

        //if (isset($constraints) && strpos($constraints, '@')!==false)
        //    $this->_bindings = $constraints->_bindings;


        if (!$comparator)
            $where = 'EXISTS (SELECT * FROM `'.$childtable.'` WHERE `'.
                $this->_table.'`.`'.$primary.'` = `'.$childtable.'`.`'.$foreign.'`' . $filter . ')';
        else
            $where = ' (SELECT COUNT(*) FROM `'.$childtable.'` WHERE `'.
                $this->_table.'`.`'.$primary.'` = `'.$childtable.'`.`'.$foreign.'`' . $filter  . ') '.$comparator.' ?';//.$value;

        if (isset($data->_relationVars['classthrough']))
        {
            if ($data->_relationVars['relationship']=='morphedByMany')
            {
                $ct = $data->_relationVars['classthrough'];
                $cp = $data->_relationVars['primary'];
                $cf = $data->_relationVars['foreignthrough'];
                $primary = $data->_relationVars['primarythrough'];
            }
            elseif ($data->_relationVars['relationship']=='morphToMany')
            {
                $ct = $data->_relationVars['classthrough'];
                $cp = $data->_relationVars['foreignthrough'];
                $cf = $data->_relationVars['foreign'];
                $foreign = $data->_relationVars['primary'];
                $primary = $data->_relationVars['primarythrough'];
            }
            else
            {
                $ct = $data->_relationVars['classthrough'];
                $cp = $data->_relationVars['foreignthrough'];
                $cf = $data->_relationVars['primarythrough'];
            }

            if (!$comparator)
                $where = 'EXISTS (SELECT * FROM `'.$childtable.'` INNER JOIN `'.$ct.'` ON `'.$ct.'`.`'.$cf.
                    '` = `'.$childtable.'`.`'.$foreign.'` WHERE `'.
                    $this->_table.'`.`'.$primary.'` = `'.$ct.'`.`'.$cp.'`' . $filter . ')';
            else
                $where = '(SELECT COUNT(*) FROM `'.$childtable.'` INNER JOIN `'.$ct.'` ON `'.$ct.'`.`'.$cf.
                    '` = `'.$childtable.'`.`'.$foreign.'` WHERE `'.
                    $this->_table.'`.`'.$primary.'` = `'.$ct.'`.`'.$cp.'`' . $filter . ') '.$comparator.' ?'; //.$value;

        }


        if ($this->_where == '')
            $this->_where = 'WHERE ' . $where;
        else
            $this->_where .= ' AND ' . $where;

        //echo "<br>".$this->toSql()."<br>";
        return $this;
    }

    /**
     * Filter current query based on relationships\
     * Check Laravel documentation
     * 
     * @return Builder
     */
    public function has($relation, $comparator=null, $value=null)
    {
        return $this->_has($relation, null, $comparator, $value);
    }


    /**
     * Filter current query based on relationships
     * Check Laravel documentation
     * 
     * @param string $relation
     * @param string $colum
     * @param string $comparator
     * @param string|int $value
     * @return Builder
     */
    public function whereRelation($relation, $column, $comparator=null, $value=null)
    {
        return $this->_has($relation, $column, $comparator, $value);
    }


    /**
     * Filter current query based on relationships\
     * Allows to specify additional filters\
     * Filters can be nested\
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
        return $this->with(array($relation => $constraints))
                ->_has($relation, $constraints);
    }


    /**
     * Indicate that the relation is the latest single result of a larger one-to-many relationship.
     *
     * @param  string|null  $column
     * @param  string|null  $relation
     * @return Builder
     */
    public function latestOfMany($column = 'id', $relation = null)
    {
        return $this->ofMany($column, 'MAX', $relation, 'latestOfMany');
    }

    /**
     * Indicate that the relation is the oldest single result of a larger one-to-many relationship.
     *
     * @param  string|null  $column
     * @param  string|null  $relation
     * @return Builder
     */
    public function oldestOfMany($column = 'id', $relation = null)
    {
        return $this->ofMany($column, 'MIN', $relation, 'oldestOfMany');
    }


    /**
     * Indicate that the relation is a single result of a larger one-to-many relationship.
     *
     * @param  string|null  $column
     * @param  string|null  $aggregate
     * @param  string|null  $relation
     * @return Builder
     */
    public function ofMany($column = 'id', $aggregate = 'MAX', $relation = null, $relationName = null)
    {
        $relationship = $this->_relationVars['relationship'];

        $this->_relationVars['oneOfMany'] = true;

         
        $query = "SELECT MAX(`".$this->_table."`.`".$this->_primary[0]."`) as ".
            $this->_primary[0]."_aggregate, `".$this->_table."`.`".$this->_relationVars['foreign']."` 
            FROM ".$this->_table." INNER JOIN (SELECT ".$aggregate."(`".$this->_table."`.`".$column."`) as 
            `".$column."_aggregate`, `".$this->_table."`.`".$this->_relationVars['foreign']."` 
            FROM ".$this->_table." !WHERE! GROUP BY `".$this->_table."`.`".
            $this->_relationVars['foreign']."`) AS `$relationName` on `$relationName`.`".$column."_aggregate` = `".
            $this->_table."`.`".$column."` AND `$relationName`.`".$this->_relationVars['foreign']."` = 
            `".$this->_table."`.`".$this->_relationVars['foreign']."` GROUP BY 
            `".$this->_table."`.`".$this->_relationVars['foreign']."`";

        $filter = "ON `$relationName`.`".$this->_primary[0]."_aggregate` = `$this->_table`.`".$this->_primary[0]."` 
        AND `$relationName`.`".$this->_relationVars['foreign']."` = 
        `".$this->_table."`.`".$this->_relationVars['foreign']."`";

        $this->_joinSubResult('INNER', $query, $relationName, $filter);


        if ($relationship == 'hasOneThrough' || $relationship == 'hasManyThrough')
            $this->_where = str_replace($this->_table, $this->_relationVars['classthrough'], $this->_where);

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

        if (!$relationshipName)
        {
            if ($related instanceof Collection)
                $relationshipName = get_class($related->first());
            else
                $relationshipName = strtolower(get_class($related));
        }

        $class = new $this->_parent;
        $class->setQuery($this);
        $class->getQuery()->varsOnly = true;
        $res = $class->$relationshipName();
        $class->getQuery()->varsOnly = false;

        $foreign = $res->_relationVars['foreign'];

        if ($related instanceof Collection)
            $values = $related->pluck($foreign)->toArray();
        else
            $values = array($related->$foreign);


        if (strtolower($boolean)=='and')
            $this->whereIn($res->_relationVars['primary'], $values);
        else
            $this->orWhereIn($res->_relationVars['primary'], $values);

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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function withAvg($relation, $column)
    {
        return $this->withAggregate($relation, $column, 'avg');
    }

    /**
     * Add subselect queries to include the existence of related models.
     *
     * @param  string|array  $relation
     * @return $this
     */
    public function withExists($relation)
    {
        return $this->withAggregate($relation, '*', 'exists');
    }

    public function withAggregate($relations, $column, $function = 'count')
    {

        if (count($relations)==0) {
            return $this;
        }

        $relations = is_array($relations) ? $relations : array($relations);

        foreach ($relations as $key => $values)
        {
            $relation = null;
            $constraints = null;
            $alias = null;

            if (is_string($values) && strpos($values, '@')===false)
            {
                list($relation, $alias) =  explode(' as ', strtolower($values));
                $constraints = null;
            }
            elseif (is_null($values))
            {
                list($relation, $alias) =  explode(' as ', strtolower($key));
                $constraints = null;
            }
            else //if (is_object($values))
            {
                //$values->_where = str_replace("`".$values->_table."`.", "`_child_table_`.", $values->_where);
                list($relation, $alias) =  explode(' as ', strtolower($key));
                $constraints = $values;
            }


            /* $newparent = new $this->_parent;
            $newparent->getQuery()->varsOnly = true;
            $data = $newparent->$relation(); */

            $this->_model->getQuery();
            $this->_model->getQuery()->varsOnly = true;
            $data = $this->_model->$relation();

            //dump($data);

            $column_name = $alias? $alias : $relation.'_'.$function;

            if ($function!='count' && $function!='exists')
                $column_name .= '_'.$column;

            $select = "(SELECT $function($column)";

            if ($function=='exists')
                $select = "EXISTS (SELECT $column";

            $subquery = "$select FROM `$data->_table`";

            if ($data->_relationVars['relationship']=='belongsToMany' 
            || $data->_relationVars['relationship']=='hasManyThrough'
            || $data->_relationVars['relationship']=='hasOneThrough'
            || $data->_relationVars['relationship']=='morphToMany'
            || $data->_relationVars['relationship']=='morphedByMany')
            {
                $subquery .= ' ' . $data->_join . ' ' .
                    ($data->_where? $data->_where . ' AND `' : ' WHERE `') . 
                    $data->_relationVars['classthrough'] . '`.`' . 
                    ($data->_relationVars['relationship']=='morphedByMany'? 
                    $data->_relationVars['primary'] : $data->_relationVars['foreignthrough']) . '` = `' .
                    $this->_table . '`.`' . ($data->_relationVars['relationship']=='morphedByMany'? 
                    $data->_relationVars['primarythrough'] : $data->_relationVars['primary']) . '`';

            }
            else
            {
                $subquery .= " WHERE `$this->_table`.`" . $data->_relationVars['primary'] . "` 
                = `$data->_table`.`" . $data->_relationVars['foreign'] . "`";
            }

            // Revisar este WHERE
            // Es el where de la relacion
            //if ($data->_where)
            //    $subquery .= ' AND ' . str_replace('WHERE ', '', $data->_where);

            // Constraints (if declared)
            if ($constraints)
            {
                //list($class, $method, $params) = getCallbackFromString($constraints);
                //array_shift($params);
                //call_user_func_array(array($class, $method), array_merge(array($data), $params));
                $this->getCallback($constraints, $data);

                if (!$this->_bindings['select']) $this->_bindings['select'] = array();
                $this->_bindings['select'] = array_merge($this->_bindings['select'], $data->_bindings['where']);

                $new_where = str_replace("`_child_table_`.", "`".$data->_table."`.", $data->_where);
                $subquery .= str_replace('WHERE',' AND', $new_where);
            }

            $subquery .= ") AS `" . $column_name . "`";

            //$this->addSelect($subquery);
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

        foreach($relations as $relation)
        {
            /* $newparent = new $this->_parent;
            $newparent->getQuery()->varsOnly = true;
            $data = $newparent->$relation(); */
            $this->_model->_query = new Builder($this->_parent);
            $this->_model->getQuery()->varsOnly = true;
            $data = $this->_model->$relation();

            $column_name = $relation.'_'.$function;

            if ($function!='count' && $function!='exists')
                $column_name .= '_'.$column;

            $select = "(SELECT $function($column)";

            if ($function=='exists')
                $select = "EXISTS (SELECT $column";

            $subquery = "$select FROM `$data->_table`";

            if ($data->_relationVars['relationship']=='belongsToMany' 
            || $data->_relationVars['relationship']=='hasManyThrough'
            || $data->_relationVars['relationship']=='hasOneThrough')
            {
                $subquery .= ' ' . $data->_join . ' WHERE `'. $data->_relationVars['classthrough'] . '`.`' . 
                    $data->_relationVars['foreignthrough'] . '` = `' .
                    $this->_table . '`.`' . $data->_relationVars['primary'] . '`';

            }
            else
            {
                $subquery .= " WHERE `$this->_table`.`" . $data->_relationVars['primary'] . "` 
                = `$data->_table`.`" . $data->_relationVars['foreign'] . "`";
            }

            $subquery .= ") AS `" . $column_name . "`";

            $p_key = is_array($this->_primary)? $this->_primary[0] : $this->_primary;
            //$this->select($p_key)->addSelect($subquery);
            $this->_method .= ', ' .$subquery;

            $this->whereIn($p_key, $this->_collection->pluck($this->_primary[0])->toArray());

            $temp = $this->_clone();            
            $temp = $temp->toBase()->connector()->execSQL($this->toSqlFirst(), $temp, true);

            foreach ($temp as $t)
            {
                $this->_collection->where($p_key, $t->{$p_key})
                    ->first()->$column_name = $t->$column_name;
            }

            $this->_loadedRelations[] = 'count_'.$relation;


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

        //return $result ?: 0;
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
     * @param  array  $columns
     * @return mixed
     */
    public function aggregate($function, $columns='*')
    {
        $this->_method = "SELECT $function($columns) as aggregate";
        $sql = $this->buildQuery();

        return $this->connector()->execSQL($sql, $this, true)->first()->aggregate;
    }



    /**
     * Sets the Query's factory
     * 
     * @return Factory
     */
    /* public function factory()
    {
        if (!$this->_model->usesHasFactory())
        {
            throw new Exception('Model '.$this->_parent.' has no Factory');
        }


        global $_class_list;

        $class = $this->_parent.'Factory';

        if (!isset($_class_list[$class]))
        {
            throw new Exception('Class '.$class.' not found');
        }


        $this->_factory = new $class;
        //$factory = call_user_func_array(array($class, 'newFactory'), array());
        //$factory = $class::newFactory();

        return $this->_factory;

    } */


    public function seed($data, $persist)
    {

        $this->_fillableOff = true;

        $col = new Collection($this->_parent);

        foreach ($data as $item)
        {
            if ($persist)
            {
                if ($this->insert($item))
                {
                    $col[] = $this->insertUnique($item);
                }
            }
            else
            {
                $col[] = $this->insertUnique($item);
            }
        }

        $this->_fillableOff = false;

        return $col;

    }
    
    public function attach($value, $extra=array())
    {
        //dump($value);
        if (is_array($value))
        {
            if (is_array(reset($value)))
            {
                foreach ($value as $key => $val)
                {
                    if (is_array($val))
                       $this->attach($key, $val);
                    else
                        $this->attach($val);
                }
            }
            else
            {
                foreach ($value as $val)
                    $this->attach($val);
            }
        }
        else
        {
            if ($this->_relationVars['relationship']=='belongsToMany')
            {
                $record = array(
                    $this->_relationVars['foreignthrough'] => $this->_relationVars['current'],
                    $this->_relationVars['primarythrough'] => $value
                );
            }
            elseif ($this->_relationVars['relationship']=='morphToMany')
            {
                $record = array(
                    $this->_relationVars['foreignthrough'] => $this->_relationVars['current_id'],
                    $this->_relationVars['relation_type'] => $this->_relationVars['current_type'],
                    $this->_relationVars['foreign'] => $value
                );
            }
            else
            {
                return false;
            }

            foreach ($extra as $key => $value)
            {
                $record[$key] = $value;
            }

            DB::table($this->_relationVars['classthrough'])
                ->insertOrIgnore($record);
        }
    }


    public function dettach($value, $extra=array())
    {
        //dd($this);
        if (is_array($value))
        {
            foreach ($value as $val)
                $this->dettach($val);
        }
        else
        {
            if ($this->_relationVars['relationship']=='belongsToMany')
            {
                DB::table($this->_relationVars['classthrough'])
                    ->where($this->_relationVars['foreignthrough'], $this->_relationVars['current'])
                    ->where($this->_relationVars['primarythrough'], $value)
                    ->delete();
            }
            elseif ($this->_relationVars['relationship']=='morphToMany')
            {
                DB::table($this->_relationVars['classthrough'])
                    ->where($this->_relationVars['foreignthrough'], $this->_relationVars['current_id'])
                    ->where($this->_relationVars['relation_type'], $this->_relationVars['current_type'])
                    ->where($this->_relationVars['foreign'], $value)
                    ->delete();
            }
            else
            {
                return false;
            }
        }
    
    }

    private function detachAll()
    {
        if ($this->_relationVars['relationship']=='belongsToMany')
        {
            DB::table($this->_relationVars['classthrough'])
                ->where($this->_relationVars['foreignthrough'], $this->_relationVars['current'])
                    ->delete();
        }
        elseif ($this->_relationVars['relationship']=='morphToMany')
        {
            DB::table($this->_relationVars['classthrough'])
                ->where($this->_relationVars['foreignthrough'], $this->_relationVars['current_id'])
                ->where($this->_relationVars['relation_type'], $this->_relationVars['current_type'])
                ->delete();
        }
    }

    public function syncWithoutDetaching($value, $extra=array())
    {
        $this->attach($value, $extra);
    }

    public function syncWithPivotValues($value, $extra=array())
    {
        if (!is_array($value)) $value = array($value);

        $this->detachAll();

        foreach ($value as $val)
        {
            $this->attach($val, $extra);
        }
    }

    public function sync($value, $extra=array())
    {
        $this->detachAll();
        $this->attach($value, $extra);        
    }


    public function observe($class)
    {
        global $observers;
        $model = $this->_parent;

        if (!isset($observers[$model]))
        {
            $observers[$model] = $class;
        }
    }

    private function callScope($scope, $args)
    {
        //echo "<br>SCOPE: ".$this->_parent."::scope".ucfirst($scope)."<br>";
        $func = 'scope'.ucfirst($scope);
        $res = new $this->_parent;
        return call_user_func_array(array($res, $func), array_merge(array($this), $args));
    }


    /**
     * Chunk the results of the query.
     *
     * @param  int  $count
     * @param  callable  $callback
     * @return bool
     */
    public function chunk($count, $callback)
    {
        $this->_order = 'ORDER BY '.$this->_primary[0]." ASC";

        $actual = 0;

        do
        {
            $results = $this->limit($count)->offset($actual)->get();

            $countResults = $results->count();

            if ($countResults==0)
                break;

            list($class, $method, $params) = getCallbackFromString($callback);
            executeCallback($class, $method, array_merge(array($results), $params), $this);
            //call_user_func_array(array($class, $method), array_merge(array($results), $params));

            unset($results);
            unset($this->_collection);

            $this->_collection = new Collection($this->_parent, $this->_model->getHidden());

            $actual += $count;
        }
        while ($countResults == $count);

        return true;
    }

    /**
     * Chunk the results of a query by comparing IDs.
     *
     * @param  int  $count
     * @param  callable  $callback
     * @param  string|null  $column
     * @param  string|null  $alias
     * @return bool
     */
    public function chunkById($count, $callback, $column=null, $alias=null)
    {
        if (!$column)
            $column = $this->_primary[0];

        if (!$alias)
            $alias = $column;
        
        $lastId = null;

        $this->_order = 'ORDER BY '.$column." ASC";
        
        do
        {
            if ($lastId)
                $this->where($alias, '>', $lastId);

            $results = $this->limit($count)->get();

            $countResults = $results->count();

            if ($countResults==0)
                break;

            $lastId = $results->last()->$alias;

            list($class, $method, $params) = getCallbackFromString($callback);
            executeCallback($class, $method, array_merge(array($results), $params), $this);
            //call_user_func_array(array($class, $method), array_merge(array($results), $params));

            unset($results);
            unset($this->_collection);

            $this->_collection = new Collection($this->_parent, $this->_model->getHidden());

        }
        while ($countResults == $count);

        return true;
    }

    public static function macro($name, $function)
    {
        self::$_macros[$name] = $function;
    }

    public static function getMacros()
    {
        return self::$_macros;
    }

}
