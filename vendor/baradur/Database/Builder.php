<?php

Class Builder
{
    public $_parent = null;
    public $_table;
    public $_primary;
    public $_foreign;
    public $_fillable;
    public $_guarded;
    public $_hidden;
    public $_routeKey;
    
    public $_fillableOff = false;

    public $_factory = null;

    public $_relationship;
    public $_rparent = null;

    public $_method = '';
    public $_where = '';
    public $_wherevals = array();
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

    public $_collection = array();
    public $_connector;
    public $_extraquery = null;
    public $_original = null;

    public $_softDelete;
    public $_withTrashed = false;

    public $_relationVars = null;
    public $_loadedRelations = array();

    public $_model = null;

    private $_scopes = array();

    /* public function __construct($connector, $table, $primary, $parent, $fillable, $guarded, $hidden, $routeKey='id', $soft=false)
    {

        $this->connector() = $connector;
        $this->_table = $table;
        $this->_primary = is_array($primary)? $primary : array($primary);
        $this->_parent = $parent;
        $this->_fillable = $fillable;
        $this->_guarded = $guarded;
        $this->_hidden = $hidden;
        $this->_routeKey = $routeKey;
        $this->_softDelete = $soft? 1 : 0;
        $this->_collection = new Collection($parent, $hidden);

    } */


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
        $this->_routeKey = $this->_model->getRouteKeyName();
        $this->_softDelete = $this->_model->getUseSoftDeletes()? 1 : 0;
        $this->_collection = new Collection($model, $this->_model->getHidden());

        $this->addGlobalScopes();

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

    public function clear()
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
        $this->_wherevals = array();
    }

    private $sql_connector = null;
    
    /**
     * @return Connector
     */
    public function connector()
    {
        //dump($this->_connector);
        if (!$this->sql_connector)
        {
            if ($this->_connector)
            {
                $this->sql_connector = new Connector(
                    $this->_connector['host'],
                    $this->_connector['user'], 
                    $this->_connector['password'], 
                    $this->_connector['database'],
                    $this->_connector['port']
                );
            }
            else
            {
                global $database;
                $this->sql_connector = new Connector(
                    $database['host'],
                    $database['user'], 
                    $database['password'], 
                    $database['database'],
                    $database['port']
                );
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


    
    /**
     * Returns the full query in a string
     * 
     * @return string
     */
    public function toSql()
    {
        $res = $this->buildQuery();

        foreach ($this->_wherevals as $val)
        {
            foreach ($val as $k => $v)
                $res = preg_replace('/\?/', $v, $res, 1);
        }

        return $res;
    }



    /**
     * Specifies the SELECT clause\
     * Returns the Query builder
     * 
     * @param string $columns String containing colums divided by comma
     * @return Builder
     */
    public function selectRaw($val = '*', $bindings = array())
    {
        foreach ($bindings as $v)
        {
            /* $vtype = 'i';
            if (is_string($v))
            {
                $vtype = 's';   
            }    
            $this->_wherevals[] = array($vtype => $v); */

            $val = preg_replace('/\?/', $v, $val, 1);
        }

        $this->_method = 'SELECT ' . $val;

        return $this;
    }

    private function getSelectColumns($select)
    {
        if (!is_array($select)) $select = func_get_args();

        $columns = array();
        foreach($select as $key => $value)
        {
            if (is_numeric($key))
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
            elseif ($value instanceof Builder)
            {
                $columns[] = '(' . $value->toSql() . ') as ' . $key;
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
        $this->_fromSub = ' (' . $subquery->toSql() . ') ' . $alias;
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
            /* $vtype = 'i';
            if (is_string($v))
            {
                $vtype = 's';   
            }    
            $this->_wherevals[] = array($vtype => $v); */

            $where = preg_replace('/\?/', $v, $where, 1);
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
                $res[] = $this->getWhere($key, $val);
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

    private function getWhere($column, $cond='', $val='')
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

        $this->_wherevals[] = array($vtype => $val); */

        if (is_string($val))
        {
            $val = rtrim(ltrim($val, "'"), "'");
            $val = "'$val'";
        }

        return $column . ' ' . $cond . ' ' . $val;


    }

    private function addWhere($column, $cond='', $val='', $boolean='AND')
    {
        //dump(func_get_args());
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
            $result = $this->getWhere($column, $cond, $val);
        }

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $result; //$column . ' ' . $cond . ' ' . $val; // ' ?';
        else
            $this->_where .= ' '.$boolean.' ' . $result; //$column . ' ' .$cond . ' ' . $val; // ' ?';

        return $this;
    }


    /**
     * Specifies the WHERE clause\
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
     * Specifies OR in WHERE clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $condition Can be ommited for '='
     * @param string $value
     * @return Builder
     */
    public function orWhere($column, $cond, $val='')
    {
        $this->addWhere($column, $cond, $val, 'OR');

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
    public function whereIn($column, $values)
    {
        $win = array();
        if (!is_array($values))
        {
            foreach (explode(',', $values) as $val)
            {
                //$val = trim($val);
                if (is_string($val)) $val = "'".$val."'";
                array_push($win, $val);
            }
        }
        else
        {
            foreach ($values as $val)
            {
                if (is_string($val)) $val = "'".$val."'";
                array_push($win, $val);
            }
        }

        list ($table, $col) = explode('.', $column);
        if ($col) $column = '`'.$table.'`.`'.$col.'`';
        else $column = '`'.$table.'`';

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ' IN ('. implode(',', $win) .')';
        else
            $this->_where .= ' AND ' . $column . ' IN ('. implode(',', $win) .')';

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
        $win = array();
        if (!is_array($values))
        {
            foreach (explode(',', $values) as $val)
            {
                //$val = trim($val);
                if (is_string($val)) $val = "'".$val."'";
                array_push($win, $val);
            }
        }
        else
        {
            $win = $values;
        }

        list ($table, $col) = explode('.', $column);
        if ($col) $column = '`'.$table.'`.`'.$col.'`';
        else $column = '`'.$table.'`';

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ' IN ('. implode(',', $win) .')';
        else
            $this->_where .= ' OR ' . $column . ' IN ('. implode(',', $win) .')';

        return $this;
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
        $win = array();
        
        if (is_string($values))
            $values = explode(',', $values);

        foreach ($values as $val)
        {
            //$val = trim($val);
            if (is_string($val)) $val = "'".$val."'";
            array_push($win, $val);
        }

        list ($table, $col) = explode('.', $column);
        if ($col) $column = '`'.$table.'`.`'.$col.'`';
        else $column = '`'.$table.'`';

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ' NOT IN ('. implode(',', $win) .')';
        else
            $this->_where .= ' AND ' . $column . ' NOT IN ('. implode(',', $win) .')';

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
        $win = array();
        foreach ($values as $val)
        {
            if (is_string($val)) $val = "'".$val."'";
            array_push($win, $val);
        }

        list ($table, $col) = explode('.', $column);
        if ($col) $column = '`'.$table.'`.`'.$col.'`';
        else $column = '`'.$table.'`';

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ' BETWEEN '. $win[0] . ' AND ' . $win[1];
        else
            $this->_where .= ' AND ' . $column . ' BETWEEN '. $win[0] . ' AND ' . $win[1];

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
    public function orWhereBetween($column, $values)
    {
        $win = array();
        foreach ($values as $val)
        {
            if (is_string($val)) $val = "'".$val."'";
            array_push($win, $val);
        }

        list ($table, $col) = explode('.', $column);
        if ($col) $column = '`'.$table.'`.`'.$col.'`';
        else $column = '`'.$table.'`';

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ' BETWEEN '. $win[0] . ' AND ' . $win[1];
        else
            $this->_where .= ' OR ' . $column . ' BETWEEN '. $win[0] . ' AND ' . $win[1];

        return $this;
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

        $this->_wherevals[] = array($vtype => $value); */


        if (is_string($value)) $value = "'$value'";

        return $reference . ' ' . $operator . ' ' . $value;

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
            $this->_having = 'HAVING ' . $reference . ' BETWEEN '. $win[0] . ' AND ' . $win[1];
        else
            $this->_having = ' AND ' . $reference . ' BETWEEN '. $win[0] . ' AND ' . $win[1];

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
            /* $vtype = 'i';
            if (is_string($v))
            {
                $vtype = 's';   
            }    
            $this->_wherevals[] = array($vtype => $v); */

            $val = preg_replace('/\?/', $v, $sql, 1);
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
            call_user_func_array(array($class, $method), array_merge(array($query), $params));
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

        $result = str_replace('WHERE', '', $join->_where);
        unset($join);

        if ($this->_join == '')
            $this->_join = $side . ' JOIN `' . $name . '` ON ' . $result;
        else
            $this->_join .= ' ' . $side . ' JOIN `' . $name . '` ON ' . $result;

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
            $query = $query->toSql();
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
        $this->_union = 'UNION ' . $query->toSql();

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
    public function search($var, $val)
    {
        $var = str_replace(',','," ",',$var);

        /* if ($this->_where == '')
            $this->_where = 'WHERE FIELD LIKE "%' . $val . '%" IN (' . $var . ')';
        else
            $this->_where .= ' OR FIELD LIKE "%' . $val . '%" IN (' . $var . ')'; */

        if ($this->_where == '')
            $this->_where = 'WHERE CONCAT(' . $var . ') LIKE "%'.$val.'%"';
        else
            $this->_where .= ' OR CONCAT(' . $var . ') LIKE "%'.$val.'%"';
            
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
                array_push($this->_keys, $k);

                $camel = Helpers::snakeCaseToCamelCase($key);
                    
                if (method_exists($this->_parent, 'set'.ucfirst($camel).'Attribute'))
                {
                    //$cl = $this->_parent;
                    $fn = 'get'.ucfirst($camel).'Attribute';
                    //$v = call_user_func_array(array($cl, $fn), array($v));
                    $newmodel = new $this->_parent;
                    $v = $newmodel->$fn($v);
                }

                if (method_exists($this->_parent, $camel.'Attribute'))
                {
                    #echo "Value:$v<br>";
                    $fn = $camel.'Attribute';
                    $newmodel = new $this->_parent;
                    $nval = $newmodel->$fn($v, (array)$newmodel);
                    if (isset($nval['set'])) $v = $nval['set'];
                    #echo "NEW value:$v<br>";
                }

                if (is_string($v))
                    $v = "'".$v."'";

                array_push($this->_values, $v? $v : "NULL");
            }
        }
        else
        {
            array_push($this->_keys, $key);

            $camel = Helpers::snakeCaseToCamelCase($key);
            #echo "KEY: $camel"."Attribute<br>";

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

            if (is_string($val)) 
                $val = "'".$val."'";

            array_push($this->_values, isset($val)? $val : "NULL");
        }

        return $this;
    }

    /**
     * Saves the model in database
     * 
     * @return bool
     */
    // This should be only in Model
    /* public function save($values)
    {
        //dd($values); dd($this);
        //exit();

        if(!$values)
            throw new Exception('No values asigned');


        $final_vals = array();
        if (is_object($values) && class_exists(get_class($values)) && isset($this->_relationVars['relation_current']))
        {
            //dd($values); exit();
            $vals = array();
            foreach ($values as $key => $val)
                $vals[$key] = $val;

            $where = array($this->_relationVars['foreign'] => $this->_relationVars['relation_current']);

            return $this->updateOrCreate($where, $vals);

        }
        else
        {
            foreach ($values as $key => $val)
            {
                if (!(is_object($val) && class_exists(get_class($val))))
                {
                    $final_vals[$key] = $val;
                }
            }
            
            if (!$this->_collection->first())
            {
                //die("CREATE");
                return $this->_insert($final_vals);
            }
            else
            {
                //dd($final_vals);
                //dd($this);
                die("UPDATE");
                $this->_fillableOff = true;
                //dd($this->_where);

                $p_key = is_array($this->_primary)? $this->_primary[0] : $this->_primary;
                $pkeyval = $this->_collection->pluck($p_key)->first();
                //dd($pkeyval);exit();

                if (strpos($this->_where, $pkeyval)===false)
                {
                    //echo "NO HAY WHERE<br>";
                    $this->where($p_key, $pkeyval);
                }

                $res = $this->update($final_vals);

                $this->_fillableOff = false;
                return $res;
            }
        }


    } */

    /**
     * Save the model and all of its relationships
     * 
     * @return bool
     */
    public function push($values, $new)
    {
        /* dd($values); dd($this);
        exit(); */
        
        $relations = array();

        if(!$values)
            throw new Exception('No values asigned');

        $final_vals = array();

        //dump($values);
        foreach ($values as $key => $val)
        {
            if (is_object($val) && class_exists(get_class($val)) && $key!='_query')
            {
                $relation = array();
                foreach ($val as $k => $v)
                    $relation[$k] = $v;

                $class = get_class($values);
                $class = new $class;
                $class->getQuery($this);
                $class->getQuery()->varsOnly = true;
                $_key = $class->getRouteKeyName();
                $data = $class->$key();
                $relation[$data->_relationVars['foreign']] = $values->$_key;
                $relation['__key'] = $data->_relationVars['foreign'];

                $relations[get_class($val)] = $relation;

            }
            else
            {
                $final_vals[$key] = $val;
            }
        }

        /* dd($final_vals);
        dd($relations);
        dd($this);
        dd($this->_primary);
        exit(); */

        /* $key = $this->_primary;
        //unset($final_vals[$key]);
        if ( !$this->updateOrCreate(array($key => $final_vals[$key]), $final_vals)) return false;
            
        foreach ($relations as $model => $values)
        {
            $key = $values['__key'];
            unset($values['__key']);
            //dd($model); dd($key); dd($values[$key]); dd($values); exit();
            if (! $model::updateOrCreate(array($key => $values[$key]), $values))
                return false;
        }
        return true; */

        if ($new)
        {
            //return $this->_insert($final_vals);
            //die("CREATE");

            if ( !$this->create($final_vals)) return false;
            //dd($relations);
            foreach ($relations as $model => $values)
            {
                $key = $values['__key'];
                unset($values['__key']);
                //dd($model); dd($key); dd($values[$key]); dd($values); exit();
                //$m = new $model;

                //dd($values);
                //dd($m);exit();


                if (! Model::instance($model)->updateOrCreate(array($key => $values[$key]), $values))
                    return false;
                /* if (! $model::updateOrCreate(array($key => $values[$key]), $values))
                    return false; */
            }
            return true;
        }
        else
        {
            //$this->_fillableOff = true;
            $p_key = is_array($this->_primary)? $this->_primary[0] : $this->_primary;
            $this->where($p_key, $this->_collection->pluck($p_key)->first());
            //dd($this);            
            //die("UPDATE");


            if ( !$this->update($final_vals)) return false;
            
            foreach ($relations as $model => $values)
            {
                $key = $values['__key'];
                unset($values['__key']);
                //dd($model); dd($key); dd($values[$key]); dd($values); exit();
                //$m = new $model;
                if (! Model::instance($model)->updateOrCreate(array($key => $values[$key]), $values))
                    return false;
                /* if (! $model::updateOrCreate(array($key => $values[$key]), $values))
                    return false; */
            }

            //$this->_fillableOff = false;
            return true;
        }

    }

    /**
     * INSERT a record or an array of records in database
     * 
     * @param array $record
     * @return bool
     */
    public function insert($record)
    {
        $isarray = false;
        foreach ($record as $key => $val)
        {
            if (!is_array($val))
            {
                $this->set($key, $val);
            }
            else
            {
                $isarray = true;
                return $this->_insert($val);
            }
        }
        if (!$isarray)
            return $this->_insert(array());

    }

    private function _insert($record)
    {
        
        foreach ($record as $key => $val)
            $this->set($key, $val);

        $sql = 'INSERT INTO `' . $this->_table . '` (' . implode(', ', $this->_keys) . ')'
                . ' VALUES (' . implode(', ', $this->_values) . ')';

        //echo $sql."<br>";
        $query = $this->connector()->query($sql);
    
        $last = array();
        for ($i=0; $i<count($this->_keys); ++$i)
        {
            $last[$this->_keys[$i]] = $this->_values[$i];
        }
        $this->_lastInsert = $last;
        //dump($last);

        $this->clear();
        
        return $query; //$this->connector()->error;
    }


    /**
     * INSERT IGNORE a record or an array of records in database
     * 
     * @param array $record
     * @return bool
     */
    public function insertOrIgnore($record)
    {
        $isarray = false;
        foreach ($record as $key => $val)
        {
            if (!is_array($val))
            {
                $this->set($key, $val);
            }
            else
            {
                $isarray = true;
                $this->_insertIgnore($val);
            }
        }
        if (!$isarray)
            $this->_insertIgnore(array());
    }

    private function _insertIgnore($record)
    {
        
        foreach ($record as $key => $val)
            $this->set($key, $val);

        $sql = 'INSERT INTO `' . $this->_table . '` (' . implode(', ', $this->_keys) . ')'
                . ' VALUES (' . implode(', ', $this->_values) . ')';

        //echo $sql."<br>";
        $query = $this->connector()->query($sql);
    
        $this->clear();
        
        return $query; 
    }


    private function setValues($key, $val, $unset=false, $return=false)
    {
        global $preventSilentlyDiscardingAttributes;

        if (in_array($key, $this->_fillable) || $this->_fillableOff)
        {
            $this->set($key, $val);
        }
        else if (isset($this->_guarded) && !in_array($key, $this->_guarded))
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
        if ($return)
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
        #echo "CREATE<br>";
        #dump($record);
        if (is_object($record))
        {
            $arr = array();
            foreach ($record as $key => $val)
                $arr[$key] = $val;

            $record = $arr;
        }
        
        foreach ($record as $key => $val)
        {
            if (!is_object($val))
            {
                /* if (in_array($key, $this->_fillable) || $this->_fillableOff)
                    $this->set($key, $val, false);
                else if (isset($this->_guarded) && !in_array($key, $this->_guarded))
                    $this->set($key, $val, false);
                else
                    unset($record[$key]); */
                $this->setValues($key, $val, true, false);
            }
        }

        if (isset($this->_relationVars) && $this->_relationVars['relationship']=='morphOne'
            && isset($this->_relationVars['current_id']))
        {
            $this->set($this->_relationVars['foreign'], $this->_relationVars['current_id']);
            $this->set($this->_relationVars['relation_type'], $this->_relationVars['current_type']);
        }

        if(count($this->_values) == 0)
            return null;
        
        $this->checkObserver('creating', $record);

        if ($this->_insert(array()))
        {
            $this->checkObserver('created', $record);
            return $this->insertNewItem();
        }
        else
            return null;

    }

    /**
     * Updates a record in database
     * 
     * @param array|object $record
     * @return bool
     */
    public function update($record, $attributes=null)
    {
        //echo "UPDATE<br>";
        //dd($record); dd($attributes); dd($this); exit();

        if (isset($attributes))
        {
            foreach ($this->_primary as $primary)
            {
                if (!isset($attributes[$primary]))
                    throw new Exception("Error in model's primary key");
                    
                $this->where($primary, $attributes[$primary]);
            }
            //$key = $this->_primary;
            //if (isset($attributes->$key))
            //else throw new Exception("Error updating existent model");
        }

        foreach ($record as $key => $val)
        {
            /* if ($record->$key != $attributes[$key] || !isset($attributes[$key]))
            { */
            if (!is_object($val))
            {
                /* if (in_array($key, $this->_fillable) || $this->_fillableOff)
                    $this->set($key, $val, false);
                else if (isset($this->_guarded) && !in_array($key, $this->_guarded))
                    $this->set($key, $val, false); */
                $this->setValues($key, $val, false, false);
            }

        }

        //dd($this); exit();
        /* foreach ($record as $key => $val)
            $this->set($key, $val, false); */
    
        if ($this->_where == '')
            throw new Exception('WHERE not assigned. Use updateAll() if you want to update all records');

        if (!$this->_values)
           throw new Exception('No values assigned for update');


        $values = array();
        
        for ($i=0; $i < count($this->_keys); $i++) {
            array_push($values, $this->_keys[$i] . "=" . $this->_values[$i]);
        }

        if ($this->_softDelete && !$this->_withTrashed)
            $this->whereNull('deleted_at');

        $sql = 'UPDATE `' . $this->_table . '` SET ' . implode(', ', $values) . ' ' . $this->_where;

        //dd($sql); exit();
        #var_dump($this->_wherevals);
        #echo $sql."::";var_dump($this->_wherevals);echo "<br>";
        #exit();

        $this->checkObserver('updating', $record);

        $query = $this->connector()->query($sql); //, $this->_where, $this->_wherevals);

        if ($query)
            $this->checkObserver('updated', $record);

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

        $this->clear();
        foreach ($attributes as $key => $val)
        {
           $this->where($key, $val);
        }
        
        $sql = 'SELECT * FROM `' . $this->_table . '` '. $this->_where . ' LIMIT 0, 1';
        $res = $this->connector()->execSQL($sql, $this->_wherevals);
        $res = $res[0];

        if ($res)
        {
            return $this->update($values);
        }
        else
        {
            $new = array_merge($attributes, $values);
            return $this->insert($new);
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
        foreach ($values as $key => $val)
        {
            /* if (in_array($key, $this->_fillable) || $this->_fillableOff)
                $this->set($key, $val, false);
            else if (isset($this->_guarded) && !in_array($key, $this->_guarded))
                $this->set($key, $val, false);
            else
                unset($values[$key]); */
            $this->setValues($key, $val, true, false);
        }
        //var_dump($values);

        $this->clear();
        foreach ($attributes as $key => $val)
        {
           $this->where($key, $val);
        }
        
        $sql = 'SELECT * FROM `' . $this->_table . '` '. $this->_where . ' LIMIT 0, 1';
        //echo $sql;
        $res = $this->connector()->execSQL($sql, $this->_wherevals);
        $res = $res[0];

        if ($res)
        {
            //dd($res);dd($values);
            if ($this->update($values))
            {
                foreach($values as $key => $val)
                    $res->$key = $val;

                return $this->insertUnique($res);
            }
            else
                return null;

        }
        else
        {
            $new = array_merge($attributes, $values);
            return $this->create($new);
        }

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
        $isarray = false;
        #$where = $this->_where;
        #$wherevals = $this->_wherevals;

        foreach ($record as $key => $val)
        {
            if (!is_array($val))
            {
                $this->set($key, $val);
            }
            else
            {
                #$this->_where = $where;
                #$this->_wherevals = $wherevals;

                $isarray = true;
                $this->_insertReplace($val);
            }
        }
        if (!$isarray)
            $this->_insertReplace(array());
    }

    private function _insertReplace($record)
    {

        foreach ($record as $key => $val)
            $this->set($key, $val);
        
        $sql = 'REPLACE INTO `' . $this->_table . '` (' . implode(', ', $this->_keys) . ')'
                . ' VALUES (' . implode(', ', $this->_values) . ')';

        //echo $sql;
        $query = $this->connector()->execSQL($sql, $this->_wherevals);

        $this->clear();
        
        return $query;
    }



    /**
     * UDPATE the current records in database
     * 
     * @param array $values
     * @return bool
     */
    public function updateAll($values)
    {

        foreach ($values as $key => $val)
            $this->set($key, $val);

        $valores = array();

        for ($i=0; $i < count($this->_keys); $i++) {
            array_push($valores, $this->_keys[$i] . "=" . $this->_values[$i]);
        }

        $sql = 'UPDATE `' . $this->_table . '` SET ' . implode(', ', $valores);

        $query = $this->connector()->query($sql);

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

        $query = $this->connector()->query($sql); //, $this->_wherevals);

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

        $query = $this->connector()->query($sql); //, $this->_where, $this->_wherevals);

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

        $query = $this->connector()->query($sql); //, $this->_wherevals);

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
     * Returns the first record from query\
     * Returns 404 if not found
     * 
     * @return object
     */
    public function firstOrFail()
    {        
        if ($this->first())
            return $this->first();

        else
            abort(404);
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

        $sql = $this->_method . ' FROM `' . $this->_table . '` ' . $this->_join . ' ' . $this->_where 
                . $this->_union . ' LIMIT 0, 1';

        $this->connector()->execSQL($sql, $this->_wherevals, $this->_collection);

        if ($this->_collection->count()>0)
            $this->processEagerLoad();

        $item = null;

        if (!isset($this->_collection[0]))
        {
            $item = new $this->_parent; //stdClass();
            $this->clear();
            foreach ($attributes as $key => $val)
                $item->$key = $val;
        }
        else
        {
            $item = $this->_collection[0];
        }

        foreach ($values as $key => $val)
            $item->$key = $val;

        return $this->insertUnique($item, $this->_collection->count()==0);
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

        $sql = $this->_method . ' FROM `' . $this->_table . '` ' . $this->_join . ' ' . $this->_where 
                . $this->_union . ' LIMIT 0, 1';

        $this->connector()->execSQL($sql, $this->_wherevals, $this->_collection);

        if ($this->_collection->count()>0)
            $this->processEagerLoad();

        $item = null;

        if (!isset($this->_collection[0]))
        {
            $item = new $this->_parent; //stdClass();
            $item = $this->create(array_merge($attributes, $values));
            $item->_setRecentlyCreated(true);
        }
        else
        {
            $item = $this->insertUnique($this->_collection[0]);
            foreach ($values as $key => $val)
                $item->$key = $val;
        }

        return $item;

    }


    public function find($val)
    {
        //$this->_where = 'WHERE ' . $this->_primary . ' = "' . $val . '"';
        //$value = is_array($val)? $val : array($val);

        $this->_where = '';
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
        $this->_where = '';
        $i = 0;
        foreach ($this->_primary as $primary)
        {
            $this->where($primary, $val[$i]);
            ++$i;
        }

        $res = $this->first();

        if ($res)
            return $res;

        else
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
            //dump($this);
            $new = $this->first();

        }
        else
        {
            $new = $this->find($last);
        }
        
        return $this->insertUnique($new);
    }


    private function insertUnique($data, $new=false)
    {
        $class = $this->_parent;
        $item = new $class;

        //dump($data);

        /* $itemKey = $item->getRouteKeyName();

        if (!isset($data->$itemKey) && isset($new))
        {
            $data = $this->find($this->connector()->getLastId());
        } */

        foreach ($data as $key => $val)
        {
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
                if (isset($nval['get'])) $val = $nval['get'];
            }

            if ($key!='deleted_at' || ($key=='deleted_at' && !$this->_softDelete))
                $item->$key = $val;

            //if (in_array($key, is_array($this->_primary)? $this->_primary : array($this->_primary)))

            if (!is_object($val))
            {
                if ($key!='deleted_at' && !$new)
                    $item->_setOriginalKey($key, $val);

                elseif ($key=='deleted_at' && !$this->_softDelete && !$new)
                    $item->_setOriginalKey($key, $val);

                elseif ($key=='deleted_at' && $this->_softDelete)
                    $item->_setTrashed($val);
            }

        }
        $this->__new = false;
        $item->_setOriginalRelations($this->_eagerLoad);

        //ddd($item);

        return $item;
    }

    private function insertData($data)
    {
        $col = new Collection($this->_parent, $this->_hidden); //get_class($this->_parent));
        $col->_modelKeys = $this->_primary;
        $class = $this->_parent; //get_class($this->_parent);

        foreach ($data as $arr)
        {
            
            $item = new $class(true);
            foreach ($arr as $key => $val)
            {
                $camel = Helpers::snakeCaseToCamelCase($key);

                if (method_exists($this->_parent, 'get'.ucfirst($camel).'Attribute'))
                {
                    $fn = 'get'.ucfirst($camel).'Attribute';
                    /* $newmodel = new $this->_parent;
                    $val = $newmodel->$fn($val); */
                    $val = $item->$fn($val);
                }

                if (method_exists($this->_parent, $camel.'Attribute'))
                {
                    $fn = $camel.'Attribute';
                    $nval = $item->$fn($val, (array)$arr);
                    if (isset($nval['get'])) $val = $nval['get'];
                }

                if ($key!='deleted_at' || ($key=='deleted_at' && !$this->_softDelete))
                    $item->$key = $val;

                //dump($key); dump($val);

                if (!is_object($val))
                {
                    if ($key!='deleted_at')
                        $item->_setOriginalKey($key, $val);
    
                    elseif ($key=='deleted_at' && !$this->_softDelete)
                        $item->_setOriginalKey($key, $val);
    
                    elseif ($key=='deleted_at' && $this->_softDelete)
                        $item->_setTrashed($val);
                }
    
                $item->_setOriginalRelations($this->_eagerLoad);
            }
    
            $col[] = $item;
        }

        if (isset($data->pagination))
            $col->pagination = $data->pagination;

        return $col;
       
    }

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

        foreach($this->_model->global_scopes as $scope => $callback)
        {
            $this->_scopes[$scope] = $callback;
        }
    }

    private function applyGlobalScopes()
    {
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
                call_user_func_array(array($class, $method), array_merge(array($this), $params));
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

        $this->connector()->execSQL($sql, $this->_wherevals, $this->_collection);

        if ($this->_collection->count()==0)
            return NULL;

        $this->processEagerLoad();

        $this->clear();

        return $this->insertUnique($this->_collection[0]);
    }

    /**
     * Return all records from current query
     * 
     * @return Collection
     */
    public function get()
    {
        if ($this->_softDelete && !$this->_withTrashed)
            $this->whereNull('deleted_at');

        $this->applyGlobalScopes();

        $sql = $this->buildQuery();

        $this->connector()->execSQL($sql, $this->_wherevals, $this->_collection);

        if ($this->_collection->count()==0)
            return $this->_collection;

        $this->processEagerLoad();

        $this->clear();

        return $this->insertData($this->_collection);

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
        
        $this->connector()->execSQL($sql, $this->_wherevals, $this->_collection);

        if ($this->_collection->count()==0)
        {
            return $this->_collection;
        }
        
        $records = 'select count(*) AS total from (' . $this->buildQuery() .') final';
        
        $query = $this->connector()->execSQL($records, $this->_wherevals);

        $pages = isset($query[0])? $query[0]->total : 0;

        $pages = ceil($pages / $cant);

        $pagina = (int)$pagina;
        $pages = (int)$pages;
        
        if ($pages>1)
        {
            $pagination = new arrayObject();
            $pagination->first = $pagina<=1? null : 'p=1';
            $pagination->second = $pagina<=1? null : 'p='.($pagina-1);
            $pagination->third = $pagina==$pages? null : 'p='.($pagina+1);
            $pagination->fourth = $pagina==$pages? null : 'p='.$pages;
            $this->_collection->pagination = $pagination;
        }

        $this->processEagerLoad();
        
        $this->clear();

        return $this->insertData($this->_collection);

    }


    public function fresh($original, $relations=null)
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


    /**
     * Re-hydrate the existing model using fresh data from the database
     * 
     * @return Model
     */
    public function refresh($original, $relations=null)
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
        if (count($this->_eagerLoad) == 0) return;
        
        //dump($this->_eagerLoad);

        foreach ($this->_eagerLoad as $key => $val)
        {
            //echo "RELATION $key: ";var_dump($val); echo "<br>";
            /* $class = new $this->_parent;
            $class->setQuery($this);

            $class->getQuery()->_relationName = $key;
            $class->getQuery()->_relationColumns = '*';
            $class->getQuery()->_extraQuery = isset($val['_constraints']) ? $val['_constraints'] : null;
            $class->getQuery()->_nextRelation = $val;

            if (strpos($key, ':')>0) {
                list($key, $columns) = explode(':', $key);
                $class->getQuery()->_relationColumns = explode(',', $columns);
                $class->getQuery()->_relationName = $key;
            }

            $res = $class->$key(); */

            $this->_model->getQuery();
            $this->_model->getQuery()->_collection = $this->_collection;
            $this->_model->getQuery()->_relationName = $key;
            $this->_model->getQuery()->_relationColumns = '*';
            $this->_model->getQuery()->_extraQuery = isset($val['_constraints']) ? $val['_constraints'] : null;
            $this->_model->getQuery()->_nextRelation = $val;

            if (strpos($key, ':')>0) {
                list($key, $columns) = explode(':', $key);
                $this->_model->getQuery()->_relationColumns = explode(',', $columns);
                $this->_model->getQuery()->_relationName = $key;
            }

            $res = $this->_model->$key();            

            Relations::insertRelation($this->_model->getQuery(), $res, $key);

            unset($this->_model->_query);
        }

    }


    public function load($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;
        
        $this->with($relations);
        $this->processEagerLoad();

        

        return $this->_collection;
    }


    public function setHasConstraint($value)
    {
        if (!$value)
            unset($this->_hasConstraints);
        else
            $this->_hasConstraints = $value;
    }

    public function _has($relation, $constraints=null, $comparator=null, $value=null)
    {
        //echo "HAS: ".$relation. " :: ".$constraints."<br>";
        //dump($constraints);
        $data = null;
        
        $newparent = new $this->_parent;
        
        if (strpos($relation, '.')>0)
        {
            $data = explode('.', $relation);
            $relation = array_pop($data);
            $parent_relation = array_shift($data);
        }
        
        
        $newparent->getQuery()->varsOnly = true;
        //$newparent->$relation();
        $data = $newparent->$relation();
        //dump($data);

        $childtable = $data->_table;
        $foreign = $data->_relationVars['foreign'];
        $primary = $data->_relationVars['primary'];

        $filter = '';
        if (isset($constraints) && !is_array($constraints) && strpos($constraints, '@')!==false)
        {
            $this->getCallback($constraints, $data);

            // OLD
            //$new_where = str_replace('`'.$data->_table.'`.', '`'.$data->_table.'`.', $data->_where);
            //$new_where = str_replace('`_child_table_`.', '`'.$data->_table.'`.', $new_where);
            //echo "NEW_WHERE: $new_where<br>";
            $filter = str_replace('WHERE', ' AND', $data->_where);
        } 
        elseif (isset($constraints) && !is_array($constraints) && strpos($constraints, '@')===false)
        {
            $filter = " AND `$data->_table`.`$constraints` $comparator $value";
            $comparator = null;
        }
        else
        {
            $filter = str_replace('WHERE', ' AND', $data->_where);
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
        //    $this->_wherevals = $constraints->_wherevals;


        if (!$comparator)
            $where = 'EXISTS (SELECT * FROM `'.$childtable.'` WHERE `'.
                $this->_table.'`.`'.$primary.'` = `'.$childtable.'`.`'.$foreign.'`' . $filter . ')';
        else
            $where = ' (SELECT COUNT(*) FROM `'.$childtable.'` WHERE `'.
                $this->_table.'`.`'.$primary.'` = `'.$childtable.'`.`'.$foreign.'`' . $filter  . ') '.$comparator.' '.$value;

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
                    $this->_table.'`.`'.$primary.'` = `'.$ct.'`.`'.$cp.'`' . $filter . ') '.$comparator.' '.$value;

        }

        //$this->_extraquery = $extraquery;

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
    * @return Collection
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

            $temp = new Collection('stdClass');
            $temp = $this->connector()->execSQL($this->toSql(), $this->_wherevals, $temp);

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

        return $this->connector()->query($sql)->fetchObject()->aggregate;
    }



    /**
     * Sets the Query's factory
     * 
     * @return Factory
     */
    public function factory()
    {
        $class = $this->_parent.'Factory';
        $this->_factory = new $class;
        //$factory = call_user_func_array(array($class, 'newFactory'), array());
        //$factory = $class::newFactory();

        if (!$this->_factory)
        {
            if (env('APP_DEBUG')==1) throw new Exception('Error looking for '.$this->_parent);
            else return null;
        }

        return $this->_factory;

    }


    public function seed($data, $persist)
    {

        $this->_fillableOff = true;

        $col = new Collection($this->_parent);

        foreach ($data as $item)
        {
            if ($persist)
            {
                $col[] = $this->insert($item);
            }
            else
            {
                $col[] = $this->insertUnique($item);
            }
        }

        $this->_fillableOff = false;

        return $col;

    }
    
    public function attach($value)
    {
        //dd($this);
        if (is_array($value))
        {
            foreach ($value as $val)
                $this->attach($val);
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

            DB::table($this->_relationVars['classthrough'])
                ->insertOrIgnore($record);

        }
    }


    public function dettach($value)
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

    public function sync($value)
    {
        //dd($this->_relationVars); dd($roles);

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

        $this->attach($value);
        
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

    public function callScope($scope, $args)
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
            call_user_func_array(array($class, $method), array_merge(array($results), $params));

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
            call_user_func_array(array($class, $method), array_merge(array($results), $params));

            unset($results);
            unset($this->_collection);

            $this->_collection = new Collection($this->_parent, $this->_model->getHidden());

        }
        while ($countResults == $count);

        return true;
    }

}
