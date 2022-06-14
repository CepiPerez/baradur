<?php

Class QueryBuilder
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

    public $_method = 'SELECT * FROM';
    public $_where = '';
    public $_wherevals = array();
    public $_join = '';
    public $_limit = '';
    public $_order = '';
    public $_group = '';
    public $_having = '';
    public $_union = '';
    public $_keys = array();
    public $_values = array();

    public $_eagerLoad = array();

    public $_collection = array();
    public $_connector;
    public $_extraquery = null;
    public $_original = null;

    public $_relationVars = null;

    public function __construct($connector, $table, $primary, $parent, $fillable, $guarded, $hidden, $routeKey='id')
    {        
        $this->_connector = $connector;
        $this->_table = $table;
        $this->_primary = $primary;
        $this->_parent = $parent;
        $this->_fillable = $fillable;
        $this->_guarded = $guarded;
        $this->_hidden = $hidden;
        $this->_routeKey = $routeKey;
        $this->_collection = new Collection($parent, $hidden);

    }

    public function clear()
    {
        $this->_method = 'SELECT * FROM';
        $this->_where = '';
        $this->_join = '';
        $this->_limit = '';
        $this->_group = '';
        $this->_union = '';
        $this->_having = '';
        $this->_order = '';
        $this->_keys = array();
        $this->_values = array();
        $this->_wherevals = array();
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
        $res = $this->_method . ' `' . $this->_table . '` ';
        if ($this->_join != '') $res .= $this->_join . ' ';
        if ($this->_where != '') $res .= $this->_where . ' ';
        if ($this->_union != '') $res .= $this->_union . ' ';
        if ($this->_group != '') $res .= $this->_group . ' ';
        if ($this->_having != '') $res .= $this->_having . ' ';
        if ($this->_order != '') $res .= $this->_order . ' ';
        if ($this->_limit != '') $res .= $this->_limit . ' ';

        return $res;
    }

    private function buildQueryPaginator()
    {
        $res = 'SELECT COUNT(*) AS total FROM `' . $this->_table . '` ';
        if ($this->_join != '') $res .= $this->_join . ' ';
        if ($this->_where != '') $res .= $this->_where . ' ';
        if ($this->_union != '') $res .= $this->_union . ' ';
        if ($this->_group != '') $res .= $this->_group . ' ';
        if ($this->_having != '') $res .= $this->_having . ' ';
        if ($this->_order != '') $res .= $this->_order . ' ';
        if ($this->_limit != '') $res .= $this->_limit . ' ';

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
     * @return QueryBuilder
     */
    public function selectRaw($val = '*')
    {
        $this->_method = 'SELECT ' . $val . ' FROM';
        return $this;
    }

    /**
     * Specifies the SELECT clause\
     * Returns the Query builder
     * 
     * @param string $columns String containing colums divided by comma
     * @return QueryBuilder
     */
    public function select($val)
    {
        if (!is_array($val)) $val = func_get_args();

        //dd($val); exit();

        $columns = array();
        foreach($val as $column)
        {
            list($col, $as, $alias) = explode(' ', $column);
            list($db, $col) = explode('.', $col);

            $col = trim($col);
            $as = trim($as); 
            $alias = trim($alias); 
            $db = trim($db);

            $columns[] = ($db=='*'? '*' : '`'.$db.'`') . 
                         ($col? '.' . ($col=='*'? '*' : '`'.$col.'`') : '') . 
                        (trim(strtolower($as))=='as'? ' as `'.$alias.'`':'');
        }

        $this->_method = 'SELECT ' . implode(', ', $columns) . ' FROM';
        return $this;
    }

    /**
     * Adds columns the SELECT clause\
     * Returns the Query builder
     * 
     * @param string $columns String containing colums divided by comma
     * @return QueryBuilder
     */
    public function addSelect($val = '*')
    {
        $this->_method = str_replace(' FROM', '', $this->_method);
        $this->_method .= ', ' . $val . ' FROM';
        return $this;
    }


    /**
     * Specifies the WHERE clause\
     * Returns the Query builder
     * 
     * @param string $where
     * @return QueryBuilder
     */
    public function whereRaw($where)
    {
        if ($this->_where == '')
            $this->_where = 'WHERE ' . $where ;
        else
            $this->_where .= ' AND ' . $where;

        return $this;
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
    public function where($column, $cond='', $val='', $ret=true)
    {
        if (is_array($column))
        {
            foreach ($column as $co)
            {
                //var_dump($co); echo "<br>";
                list($var1, $var2, $var3) = $co;
                $this->where($var1, $var2, $var3, false);
            }
            return $this;
        }


        if ($val=='')
        {
            $val = $cond;
            $cond = '=';
        }

        if (strpos($column, '.')>1)
        {
            list ($table, $col) = explode('.', $column);
            $column = '`'.$table.'`.`'.$col.'`';
        }

        /* $vtype = 'i';
        if (is_string($val))
        {
            $vtype = 's';   
        }

        $this->_wherevals[] = array($vtype => $val); */

        if (is_string($val)) $val = "'$val'";

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ' ' . $cond . ' ' . $val; // ' ?';
        else
            $this->_where .= ' AND ' . $column . ' ' .$cond . ' ' . $val; // ' ?';

        if ($ret) return $this;
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
    public function orWhere($column, $cond, $val='', $ret=true)
    {
        if (is_array($column))
        {
            foreach ($column as $co)
            {
                //var_dump($co); echo "<br>";
                list($var1, $var2, $var3) = $co;
                $this->orWhere($var1, $var2, $var3, false);
            }
            return $this;
        }

        if ($val=='')
        {
            $val = $cond;
            $cond = '=';
        }

        list ($table, $col) = explode('.', $column);
        if ($col) $column = '`'.$table.'`.`'.$col.'`';
        else $column = '`'.$table.'`';

        /* $vtype = 'i';
        if (is_string($val))
        {
            $vtype = 's';   
        }

        $this->_wherevals[] = array($vtype => $val); */
        if (is_string($val)) $val = "'$val'";

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ' ' . $cond . ' ' . $val; // ' ?';
        else
            $this->_where .= ' OR ' . $column . ' ' .$cond . ' ' . $val; // ' ?';

        if ($ret) return $this;
    }

    /**
     * Specifies the WHERE IN clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $values
     * @return QueryBuilder
     */
    public function whereIn($column, $values)
    {
        $win = array();
        foreach (explode(',', $values) as $val)
        {
            //$val = trim($val);
            if (is_string($val)) $val = "'".$val."'";
            array_push($win, $val);
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
     * Specifies the WHERE NOT IT clause\
     * Returns the Query builder
     * 
     * @param string $column 
     * @param string $values
     * @return QueryBuilder
     */
    public function whereNotIn($column, $values)
    {
        $win = array();
        foreach (explode(',', $values) as $val)
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
     * @return QueryBuilder
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
            $this->_where = ' AND ' . $column . ' BETWEEN '. $win[0] . ' AND ' . $win[1];

        return $this;
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
    public function having($reference, $operator, $value)
    {
        if (is_array($reference))
        {
            foreach ($reference as $co)
            {
                //var_dump($co); echo "<br>";
                list($var1, $var2, $var3) = $co;
                $this->where($var1, $var2, $var3, false);
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

        if ($this->_having == '')
            $this->_having = 'HAVING ' . $reference . ' ' . $operator . ' ' . $value; // ' ?';
        else
            $this->_having .= ' AND ' . $reference . ' ' .$operator . ' ' . $value; // ' ?';

        return $this;
    }

    /**
     * Specifies the HAVING clause between to values\
     * Returns the Query builder
     * 
     * @param string $reference
     * @param array $values
     * @return QueryBuilder
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

    private function _joinResult($side, $name, $column, $comparator, $ncolumn)
    {
        list($name, $as, $alias) = explode(' ', $name);

        $side = trim($side);
        $as = trim($as);
        $name = '`' . trim($name) . '`';
        $alias = ' `' . trim($alias) . '`';
        $column = '`' . trim($column) . '`';
        $ncolumn = '`' . trim($ncolumn) . '`';

        if ($this->_join == '')
            $this->_join = $side.' JOIN ' . $name . ($as?' as '.$alias:'') . ' on `' . $this->_table.'`.'.$ncolumn .' '.$comparator.' ' . ($as?$alias:$name).'.'.$column;
        else
            $this->_join .= ' ' . $side.' JOIN ' . $name . ($as?' as '.$alias:'') . ' on `' . $this->_table.'`.'.$ncolumn .' '.$comparator.' ' . ($as?$alias:$name).'.'.$column;
  
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
     * @return QueryBuilder
     */
    public function join($join_table, $column, $comparator, $join_column)
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
     * @return QueryBuilder
     */
    public function leftJoin($join_table, $column, $comparator, $join_column)
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
     * @return QueryBuilder
     */
    public function rightJoin($join_table, $column, $comparator, $join_column)
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
     * @return QueryBuilder
     */
    public function crossJoin($join_table, $column, $comparator, $join_column)
    {
        return $this->_joinResult('CROSS', $join_table, $column, $comparator, $join_column);
    }


    private function _joinSubResult($side, $query, $alias, $filter)
    {
        //var_dump($filter);
        $side = trim($side);
        $filter = $filter->_join;
        $alias = '`' . trim($alias) . '`';

        if ($this->_join == '')
            $this->_join = $side.' JOIN (' . $query . ') as ' . $alias . ' ' . $filter;
        else
            $this->_join .= ' '.$side.' JOIN (' . $query . ') as ' . $alias . ' ' . $filter;
  
        return $this;
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
    /* public function joinSub($query, $alias, $filter)
    {
        return $this->_joinSubResult('INNER', $query, $alias, $filter);
    } */
    public function joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        $side = trim($type);

        $filter = '';

        if (!isset($second) && isset($operator))
        {
            $operator = '=';
            $second = $operator;
        }
        if (isset($second) && isset($operator))
        {
            $filter = ' ON ' . $first . $operator . $second;  
        }

        $alias = '`' . trim($as) . '`';

        if ($this->_join == '')
            $this->_join .= ' ' . $side.' JOIN (' . $query . ') as ' . $alias . $filter;
        else
            $this->_join .= ' ' . $side.' JOIN (' . $query . ') as ' . $alias . $filter;
  
        return $this;
    }

    /**
     * LEFT Joins as subquery\
     * Returns the Query builder
     * 
     * @param string $query 
     * @param string $alias
     * @param Query $filter
     * @return QueryBuilder
     */
    /* public function leftJoinSub($query, $alias, $filter)
    {
        return $this->_joinSubResult('LEFT', $query, $alias, $filter);
    } */

    /**
     * RIGHT Joins as subquery\
     * Returns the Query builder
     * 
     * @param string $query 
     * @param string $alias
     * @param Query $filter
     * @return QueryBuilder
     */
    /* public function rightJoinSub($query, $alias, $filter)
    {
        return $this->_joinSubResult('RIGHT', $query, $alias, $filter);
    } */


    /**
     * Specifies the UNION clause\
     * Returns the Query builder
     * 
     * @param QueryBuilder $query
     * @return QueryBuilder
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
     * @param QueryBuilder $query
     * @return QueryBuilder
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
     * @return QueryBuilder
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
     * Specifies the ORDER BY clause\
     * Returns the Query builder
     * 
     * @param string $order
     * @return QueryBuilder
     */
    public function orderBy($val)
    {
        if ($this->_order == '')
            $this->_order = 'ORDER BY ' . $val;
        else
        $this->_order .= ' , ' . $val;

        return $this;
    }

    /**
     * Specifies the LIMIT clause\
     * Returns the Query builder
     * 
     * @param string $limit
     * @return QueryBuilder
     */
    public function limit($val)
    {
        $this->_limit = 'LIMIT ' . $val;
        return $this;
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
    public function set($key, $val=null, $ret=true)
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

        if ($ret) return $this;
    }

    /**
     * Saves the model in database
     * 
     * @return bool
     */
    public function save($values)
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
                //die("UPDATE");
                $this->_fillableOff = true;
                $res = $this->where($this->_primary, $this->_collection->pluck($this->_primary)->first())
                            ->update($final_vals);
                $this->_fillableOff = false;
                return $res;
            }
        }


    }

    /**
     * Save the model and all of its relationships
     * 
     * @return bool
     */
    public function push($values)
    {
        /* dd($values); dd($this);
        exit(); */
        
        $relations = array();

        if(!$values)
            throw new Exception('No values asigned');

        $final_vals = array();
        foreach ($values as $key => $val)
        {
            if (is_object($val) && class_exists(get_class($val)))
            {
                $relation = array();
                foreach ($val as $k => $v)
                    $relation[$k] = $v;

                $class = get_class($values);
                $class = new $class;
                $class->getInstance()->getQuery()->varsOnly = true;
                $_key = $class->getInstance()->getRouteKeyName();
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
        dd($this->_primary); */
        //exit();

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

        if (!$this->_collection->first())
        {
            //return $this->_insert($final_vals);
            //die("CREATE");

            if ( !$this->save($final_vals)) return false;
            
            foreach ($relations as $model => $values)
            {
                $key = $values['__key'];
                unset($values['__key']);
                //dd($model); dd($key); dd($values[$key]); dd($values); exit();
                $m = new $model;
                if (! $m->updateOrCreate(array($key => $values[$key]), $values))
                    return false;
                /* if (! $model::updateOrCreate(array($key => $values[$key]), $values))
                    return false; */
            }
            return true;
        }
        else
        {
            //$this->_fillableOff = true;
            
            $this->where($this->_primary, $this->_collection->pluck($this->_primary)->first());
            //dd($this);            
            //die("UPDATE");


            if ( !$this->update($final_vals)) return false;
            
            foreach ($relations as $model => $values)
            {
                $key = $values['__key'];
                unset($values['__key']);
                //dd($model); dd($key); dd($values[$key]); dd($values); exit();
                $m = new $model;
                if (! $m->updateOrCreate(array($key => $values[$key]), $values))
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
                $this->set($key, $val, false);
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
            $this->set($key, $val, false);

        $sql = 'INSERT INTO `' . $this->_table . '` (' . implode(', ', $this->_keys) . ')'
                . ' VALUES (' . implode(', ', $this->_values) . ')';

        //echo $sql."<br>";
        $query = $this->_connector->query($sql);
    
        $this->clear();
        
        return $query; //$this->_connector->error;
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
                $this->set($key, $val, false);
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
            $this->set($key, $val, false);

        $sql = 'INSERT INTO `' . $this->_table . '` (' . implode(', ', $this->_keys) . ')'
                . ' VALUES (' . implode(', ', $this->_values) . ')';

        //echo $sql."<br>";
        $query = $this->_connector->query($sql);
    
        $this->clear();
        
        return $query; //$this->_connector->error;
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
        
        foreach ($record as $key => $val)
        {
            if (in_array($key, $this->_fillable) || $this->_fillableOff)
                $this->set($key, $val, false);
            else if (isset($this->_guarded) && !in_array($key, $this->_guarded))
                $this->set($key, $val, false);
            else
                unset($record[$key]);
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
     * @param array $record
     * @return bool
     */
    public function update($record, $attributes=null)
    {

        if (isset($attributes))
        {
            $key = $this->_primary;
            if (isset($attributes->$key))
                $this->where($key, $attributes->$key, false);
            else throw new Exception("Error updating existent model");
        }

        foreach ($record as $key => $val)
        {
            if (in_array($key, $this->_fillable) || $this->_fillableOff)
                $this->set($key, $val, false);
            else if (isset($this->_guarded) && !in_array($key, $this->_guarded))
                $this->set($key, $val, false);
            /* else
                unset($record[$key]); */
        }

        /* foreach ($record as $key => $val)
            $this->set($key, $val, false); */
    
        if ($this->_where == '')
            return 'WHERE not assigned. Use updateAll() if you want to update all records';

        if (!$this->_values)
            return 'No values assigned for update';


        $valores = array();
        
        for ($i=0; $i < count($this->_keys); $i++) {
            array_push($valores, $this->_keys[$i] . "=" . $this->_values[$i]);
        }

        $sql = 'UPDATE `' . $this->_table . '` SET ' . implode(', ', $valores) . ' ' . $this->_where;

        #var_dump($this->_wherevals);
        #echo $sql."::";var_dump($this->_wherevals);echo "<br>";
        #exit();
        $query = $this->_connector->execSQL($sql, $this->_where, $this->_wherevals);

        $this->clear();
        
        return $query; //$this->_connector->error;
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
           $this->where($key, $val, false);
        }
        
        $sql = 'SELECT * FROM `' . $this->_table . '` '. $this->_where . ' LIMIT 0, 1';
        $res = $this->_connector->execSQL($sql, $this->_wherevals);
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
            if (in_array($key, $this->_fillable) || $this->_fillableOff)
                $this->set($key, $val, false);
            else if (isset($this->_guarded) && !in_array($key, $this->_guarded))
                $this->set($key, $val, false);
            else
                unset($values[$key]);
        }
        //var_dump($values);

        $this->clear();
        foreach ($attributes as $key => $val)
        {
           $this->where($key, $val, false);
        }
        
        $sql = 'SELECT * FROM `' . $this->_table . '` '. $this->_where . ' LIMIT 0, 1';
        //echo $sql;
        $res = $this->_connector->execSQL($sql, $this->_wherevals);
        $res = $res[0];

        if ($res)
        {
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
                $this->set($key, $val, false);
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

    public function _insertReplace($record)
    {
        //global $database;

        foreach ($record as $key => $val)
            $this->set($key, $val, false);
        
        $sql = 'REPLACE INTO `' . $this->_table . '` (' . implode(', ', $this->_keys) . ')'
                . ' VALUES (' . implode(', ', $this->_values) . ')';

        //echo $sql;
        $query = $this->_connector->execSQL($sql, $this->_wherevals);

        $this->clear();
        
        return $query; //$this->_connector->error;
    }



    /**
     * UDPATE the current records in database
     * 
     * @param array $values
     * @return bool
     */
    public function updateAll($values)
    {
        //global $database;

        foreach ($values as $key => $val)
            $this->set($key, $val, false);

        $valores = array();

        for ($i=0; $i < count($this->_keys); $i++) {
            array_push($valores, $this->_keys[$i] . "=" . $this->_values[$i]);
        }

        $sql = 'UPDATE `' . $this->_table . '` SET ' . implode(', ', $valores);

        $query = $this->_connector->execSQL($sql, $this->_wherevals);

        $this->clear();
        
        return $query; //$this->_connector->error;
    }

    /**
     * DELETE the current records from database\
     * Returns error if WHERE clause was not specified
     * 
     * @return bool
     */
    public function delete()
    {
        //global $database;

        if ($this->_where == '')
            return 'WHERE no asignado. Utilice deleteAll() si desea eliminar todos los registros';

        $sql = 'DELETE FROM `' . $this->_table . '` ' . $this->_where;

        $query = $this->_connector->execSQL($sql, $this->_wherevals);

        $this->clear();
        
        return $query; //$this->_connector->error;
    }



    /**
     * Truncates the current table
     * 
     * @return bool
     */
    public function truncate()
    {
        $sql = 'TRUNCATE TABLE `' . $this->_table . '`';

        $query = $this->_connector->query($sql);

        $this->clear();
        
        return $query; //$this->_connector->error;
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
     * Returns the first record from query
     * 
     * @return object
     */
    public function first()
    {
        //$this->_collection = array();

        $sql = $this->_method . ' `' . $this->_table . '` ' . $this->_join . ' ' . $this->_where 
                . $this->_union . ' LIMIT 0, 1';

        //echo $sql."<br>";
        $this->_connector->execSQL($sql, $this->_wherevals, $this->_collection);

        //var_dump($this->_collection);

        if ($this->_collection->count()>0)
            $this->processEagerLoad();

        //$this->clear();

        if ($this->_collection->count()==0)
            return NULL;

        return $this->insertUnique($this->_collection[0]);
    }


    /**
     * Retrieves the first record matching the attributes, and fill it with values (if asssigned)\
     * If the record doesn't exists creates a new one\
     * 
     * @param  array  $attributes
     * @param  array  $values
     * @return object
     */
    public function firstOrNew($attributes, $values=null)
    {
        //$this->_collection = array();

        $this->clear();
        foreach ($attributes as $key => $val)
        {
            $this->where($key, $val, false);
        }

        $sql = $this->_method . ' `' . $this->_table . '` ' . $this->_join . ' ' . $this->_where 
                . $this->_union . ' LIMIT 0, 1';

        //echo $sql."<br>";
        $this->_connector->execSQL($sql, $this->_wherevals, $this->_collection);

        if ($this->_collection->count()>0)
            $this->processEagerLoad();

        //$this->clear();

        //$new = new stdClass();
        $new = null;

        if (!isset($this->_collection[0]))
        {
            $new = new $this->_parent; //stdClass();
            $this->clear();
            foreach ($attributes as $key => $val)
                $new->$key = $val;
        }
        else
        {
            $new = $this->_collection[0];
        }

        foreach ($values as $key => $val)
            $new->$key = $val;

        return $this->insertUnique($new);
    }


    public function find($val)
    {
        $this->_where = 'WHERE ' . $this->_primary . ' = "' . $val . '"';

        return $this->first();
    }

    public function findOrFail($val)
    {
        $this->_where = 'WHERE ' . $this->_primary . ' = "' . $val . '"';

        if ($this->first())
            return $this->first();

        else
            abort(404);
    }


    private function insertNewItem()
    {
        $new = $this->find($this->_connector->getLastId());
        return $this->insertUnique($new);

    }

    private function insertUnique($data, $new=null)
    {
        $item = new $this->_parent;

        /* $itemKey = $item->getRouteKeyName();

        if (!isset($data->$itemKey) && isset($new))
        {
            $data = $this->find($this->_connector->getLastId());
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

            $item->$key = $val;
        }
        $this->__new = false;
        $item->setQuery($this);

        //dd($item->getQuery());

        return $item;
    }

    private function insertData($data)
    {
        $col = new Collection($this->_parent, $this->_hidden); //get_class($this->_parent));
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

                $item->$key = $val;
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
    public function get()
    {

        //$this->_collection = array();

        //$sql = $this->_method . ' `' . $this->_table . '` ' . $this->_join . ' ' . $this->_where 
        //        . $this->_union . $this->_group . $this->_having . $this->_order . ' ' . $this->_limit;

        //var_dump($this);
        $sql = $this->buildQuery();

        //echo $sql."<br>"; var_dump($this->_wherevals);

        $this->_connector->execSQL($sql, $this->_wherevals, $this->_collection);

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
        //global $pagination; //, $database;

        $filtros = $_GET;

        $pagina = $filtros['p']>0? $filtros['p'] : 1;
        $offset = ($pagina-1) * $cant; 

        //$this->_collection = array();

        $sql = $this->buildQuery() . ' LIMIT ' . $offset . ', ' . $cant;
        

        $this->_connector->execSQL($sql, $this->_wherevals, $this->_collection);

        if ($this->_collection->count()==0)
        {
            //View::setPagination(null);
            return $this->_collection;
        }
        
        $records = $this->buildQueryPaginator();
        
        $query = $this->_connector->execSQL($records, $this->_wherevals);

        $pages = isset($query[0])? $query[0]->total : 0;

        $pages = ceil($pages / $cant);

        /* unset($filtros['ruta']);

        $otros = $filtros;
        unset($otros['pagina']);
        foreach($filtros as $key => $value)
            if (!$value) unset($otros[$key]); */

        $pagina = (int)$pagina;
        $pages = (int)$pages;
        
        //$pagination = new arrayObject();
        if ($pages>1)
        {
            $pagination = new arrayObject();
            $pagination->first = $pagina<=1? null : 'p=1';
            $pagination->second = $pagina<=1? null : 'p='.($pagina-1);
            $pagination->third = $pagina==$pages? null : 'p='.($pagina+1);
            $pagination->fourth = $pagina==$pages? null : 'p='.$pages;
            //View::setPagination($pagination);
            $this->_collection->pagination = $pagination;
        }
        /* else
        {
            View::setPagination(null);
        } */


        $this->processEagerLoad();
        
        $this->clear();

        //return $this->_collection;
        return $this->insertData($this->_collection);

    }

    /**
     * Executes the SQL $query
     * 
     * @param string $query
     * @return msqli_result|bool
     */
    public function query($sql)
    {
        //global $database;
        
        /* $arraySQL = array();

        //echo $sql."<br>";
        $query = $this->_connector->query($sql);

        if (!$query) {
            return $arraySQL;
        }
        
        while( $r = $query->fetch_object() )
        {
            $arraySQL[] = $r; //$this->arrayToObject($r);
        }

        return $arraySQL; */
        return $this->_connector->query($sql);
    }


    public function setForeign($key)
    {
        $this->_foreign = $key;
        return $this;
    }

    public function setPrimary($key)
    {
        $this->_primary = $key;
        return $this;
    }

    public function setRelationship($key)
    {
        $this->_relationship = $key;
        return $this;
    }

    /* public function setParent($key)
    {
        $this->_parent = $key;
        //return $this;
    } */

    public function setConnector($connector)
    {
        $this->_connector = $connector;
        return $this;
    }


    private function recusiveSearch($arraydata, $value, $parent, $matches)
    {
        #echo "RECURSIVE SEARCH: ".$value."::".$parent."<br>";
        foreach ($arraydata as $current)
        {
            //echo "Processing:"; var_dump($current);echo "<br>";
            if (!$parent) // || strpos($parent, '.')==false)
            {
                if (isset($current->$value))
                {
                    if (!in_array($current->$value, $matches))
                        array_push($matches, $current->$value);
                }
            }
            else 
            {
                if (strpos($parent, '.')>0)
                {
                    $temp = explode('.', $parent);
                    $child = array_shift($temp);
                    $newparent = str_replace($child.'.', '', $parent);
                }
                else
                {
                    $child = $parent;
                    $newparent = null;
                }
                $matches = $this->recusiveSearch($current->$child, $value, $newparent, $matches);
            }
        }
        return $matches;
    }

    public function processMorphRelationship($class, $method, $type)
    {
        //echo "<br>Morph relationship:".$class."::".$method."::".$type."<br>";
        $c = new $class; 
        $res = $c->$method();

        if (count($res)==0)
        {
            $res[] = $method.'_id';
            $res[] = $method.'_type';
        }
        elseif (count($res)==1)
        {
            $res[] = $method.'_type';
        }

        $wherein = $this->_collection->pluck($this->_primary)->toarray();
        $newmodel = call_user_func_array(array($c, 'whereIn'), array($res[0], implode(',', $wherein)));
        $result = $newmodel->where($res[1], $this->_parent);

        if ($type == 'morphOne') $result->limit(1);

        $result->_relationVars = array(
            'foreign' => $res[0],
            'primary' => $this->_primary,
            'relationship' => $type);

        return $result;

    }


    public function belongsToMany($class, $foreign, $primary)
    {
        $array = array($this->_parent, $class);
        sort($array);                 
        $classthrough = Helpers::camelCaseToSnakeCase(implode('', $array), false);
        //$foreignthrough = Helpers::camelCaseToSnakeCase($this->_parent, false).'_id';
        //$primarythrough = Helpers::camelCaseToSnakeCase($class, false).'_id';
        $foreignthrough = Helpers::camelCaseToSnakeCase($this->_parent, false).'_'.$this->_routeKey;
        $primarythrough = Helpers::camelCaseToSnakeCase($class, false).'_'.$this->_routeKey;

        if (!$foreign) $foreign = 'id';
        if (!$primary) $primary = $this->_primary;

        return $this->processRelationshipThrough($class, $classthrough, $foreignthrough, 
                    $foreign, $primary, $primarythrough, 'belongsToMany');

    }


    public function processRelationship($class, $foreign, $primary, $relationship)
    {
        //echo "<br>Processing relationship:".$class."<br>";
        //var_dump($this);

        $columns = '*';
        if (strpos($class, ':')>0) {
            list($class, $columns) = explode(':', $class);
            $columns = explode(',', $columns);
        }

        $res = null;

        if (class_exists($class))
            $res = call_user_func_array(array($class, 'select'), array($columns));
        else
        {
            $res = DB::table(Helpers::camelCaseToSnakeCase($class, false))->select($columns);
            $res->setConnector($this->_connector);
        }
        
        if ($relationship=='belongsTo')
        {
            if (!$foreign) $foreign = 'id';
            if (!$primary) $primary = Helpers::camelCaseToSnakeCase($res->_parent, false).'_id';
        }
        else if ($relationship=='hasOne' || $relationship=='hasMany')
        {
            if (!$foreign) $foreign = ($this->_original?$this->_original :
                                        Helpers::camelCaseToSnakeCase($this->_parent, false)).'_id';
            if (!$primary) $primary = $this->_primary;
        }

        //echo $class.":".$foreign.":".$primary.":".$relationship."<br>";

        $res->_relationVars = array(
            'foreign' => $foreign,
            'primary' => $primary,
            'relationship' => $relationship,
            'relation_class' => $this->_parent,
            'relation_current' => $this->_collection->first()->$primary);

        if ($this->varsOnly)
            return $res;

        //$res = $res //->setConnector($this->_connector)
        //        ->setPrimary($foreign)->setForeign($primary)->setRelationship($relationship);
        
        $wherein = array();
        //$newArray = array();

        $wherein = $this->recusiveSearch($this->_collection, $primary, null, $wherein);
        //echo "WHEREIN: ".implode(',',$wherein)."<br><br>";
        $res = $res->whereIn($foreign, implode(',',$wherein));
        //var_dump($res->toSql()); echo "<br><br>";
        return $res;
    }


    public function processRelationshipThrough($class, $classthrough, $foreignthrough, $foreign, $primary, $primarythrough, $relationship)
    {
        //echo "RELATIONSHIP THROUGH : ".$this->_rparent.":".$this->_parent."<br>";
        //var_dump(func_get_args());
        //var_dump($this);

        $columns = '*';
        if (strpos($class, ':')>0) {
            list($class, $columns) = explode(':', $class);
            $columns = explode(',', $columns);
        }

        $secondarytable = Helpers::camelCaseToSnakeCase($classthrough, false);
        if (class_exists($classthrough))
            $secondarytable = call_user_func(array($classthrough, 'getTable'), array());

        $res = null;
        if (class_exists($class))
            $res = call_user_func_array(array($class, 'select'), array($columns));
        else
        {
            $res = DB::table(Helpers::camelCaseToSnakeCase($class, false))->select($columns);
            $res->setConnector($this->_connector);
        }


        $res = $res->join($secondarytable, $primarythrough, '=', $foreign);

        $res->_relationVars = array('classthrough' => $classthrough,
            'foreignthrough' => $foreignthrough,
            'primarythrough' => $primarythrough,
            'foreign' => $foreign,
            'primary' => $primary,
            'relationship' => $relationship);


        if ($this->varsOnly)
            return $res;
    
        #var_dump($res->_relationVars); echo "<br>";
        
        $wherein = array();
        $wherein = $this->recusiveSearch($this->_collection, $primary, null, $wherein);

        if ($relationship=='belongsToMany')
        {
            if (count($wherein)==1)
                $res->_relationVars['current'] = $wherein[0];

        }

        //echo "WHEREIN: ".implode(',',$wherein)." :: ".$this->_parent."<br><br>";
        return $res->whereIn($foreignthrough, implode(',',$wherein));
        //echo $res->toSql() ."<br>";
        //return $res;
    }

    /**
     * Adds records from a sub-query inside the current records\
     * Check Laravel documentation
     * 
     * @return QueryBuilder
     */
    public function with($relations)
    {

        if (is_string($relations))
            $relations = func_get_args();

            
        foreach ($relations as $relation => $values)
        {
            if (!is_array($relation) && is_string($values))
            {
                #echo "<br>Addingwith: ";var_dump($values); echo "<br>";
                array_push($this->_eagerLoad, array('relation' => $values));
            }
            else if (!is_array($relation))
            {
                if (isset($values)) 
                {
                    if (!isset($values->_relation))
                        $values->_relation = $relation;

                    //$this->_extraquery[] = array($relation => $values);
                }
                array_push($this->_eagerLoad, array('relation' => $relation, 'constraints' => $values));
            }
            else
            {
                foreach ($values as $rel => $filters)
                {
                    if (isset($filters) && !isset($filters->_relation))
                        $filters->_relation = $rel;
    
                    if (isset($filters) && !is_array($filters))
                        $filters = array($filters);
                
                    //$this->_extraquery[] = array($rel => $filters);
    
                    array_push($this->_eagerLoad, array('relation' => trim($rel), 'constraints' => $filters ));
                    
                }

            }
        }
        //echo "<br>";var_dump($this->_eagerLoad);echo "<br>";
        return $this;
    }


    private function processEagerLoad()
    {
        if (count($this->_eagerLoad) == 0) return;

        $processed = array();
        foreach ($this->_eagerLoad as $extra)
        {
        
            if (!in_array($extra['relation'], $processed))
            {
                $processed[] = $extra['relation'];
                $this->addWith($extra['relation'], null, isset($extra['constraints'])? $extra['constraints']:null);
            }
        }

    }

    private function recusiveInsert($arraydata, $function, $newArray, $foreign, $parent, $relationship, $primarythrough=null)
    {
        //echo "<br>RECURSIVE INSERT:: ".$function." :: ".$foreign." :: ".$parent." :: ".$relationship."<br>";
        //var_dump($newArray); echo "<br>FIN<br>";

        foreach ($arraydata as $current)
        {
            if (!$parent) //) || strpos($parent, '.')==false)
            {
                //echo "CURF: ".$current->$foreign."::: ";var_dump($newArray[$current->$foreign]);echo"<br>";

                if ($relationship=='hasMany')
                    $current->$function = isset($newArray[$current->$foreign]) ? $newArray[$current->$foreign] : array();
                
                elseif ($relationship=='hasOne' || $relationship=='belongsTo')
                    $current->$function = isset($newArray[$current->$foreign]) ? $newArray[$current->$foreign][0] : new stdClass;

            }
            else 
            {
                if (strpos($parent, '.')>0)
                {
                    $temp = explode('.', $parent);
                    $child = array_shift($temp);
                    $newparent = str_replace($child.'.', '', $parent);
                }
                else
                {
                    $child = $parent;
                    $newparent = null;
                }
                $this->recusiveInsert($current->$child, $function, $newArray, $foreign, $newparent, $relationship);
            }
        }
    }

    private function recusiveRemove($arraydata, $item, $parent=null, $main=null)
    {
        $ind = 0;
        foreach ($arraydata as $current)
        {
            echo "checking:".$current->$item ."::".$item. "<br>";
            if (isset($current->$item))
            {
                echo "found item: ". count($current->$item)."<br>";
                if (count($current->$item) == 0)
                {
                    echo "parent: ". $parent . "<br>";
                    //echo "current: ";var_dump($current);echo ":".$ind. "<br>";

                    echo "REMOVING: ".$ind."<br>";
                    dd($parent);
                    if (isset($parent)) {
                        unset($parent[$ind]);
                        dd($parent);
                        break;
                    }

                }

            }
            else
            {
                $this->recusiveRemove($current, $item, $current, $arraydata);
            }
            ++$ind;
        }
        return $arraydata;
    }

    public function load($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;
        
        foreach ($relations as $extra)
        {
            $this->addWith($extra, null, null);
        }
        return $this->_collection;
    }

    private function addWith($relation, $parent, $extrawhere)
    {
        #echo "ADDING WITH: ".$relation."<br>";
        #echo "TABLE: ".$this->_parent->getTable()."<br>";

        #echo "function: ".$function."<br>";
        #echo "parent: ".$this->_parent."<br>";
        #echo "extrawhere: "; var_dump($extrawhere); echo "<br>";
        #echo "extraquery: "; var_dump($this->_extraquery); echo "<br>";
        

        $columns = null;
        if (strpos($relation, ':')>0) {
            list($relation, $columns) = explode(':', $relation);
            $columns = explode(',', $columns);
        }
        
        $parent = isset($this->_rparent) ? $this->_rparent : $this->_parent;
        $extra = new $parent;
        $extra->getQuery()->_collection = $this->_collection;
        //$extra->getQuery()->_rparent = $this->_parent;

        $nextrelation = null;
        if (strpos($relation, '.')>0)
        {
            $temp = explode('.', $relation);
            $current = array_shift($temp);
            $next = str_replace($current.'.', '', $relation);
            if (isset($extrawhere))
                $nextrelation = array($next => $extrawhere);
            else
                $nextrelation = $next;
            $relation = $current;
        }
        //echo "extra: "; var_dump($extra->getQuery()); echo "<br>";

        $extra = $extra->$relation();

        if (isset($extrawhere->_eagerLoad))
            $extra->_eagerLoad = $extrawhere->_eagerLoad;

        if (isset($extrawhere) && !$nextrelation) 
        {
            $extra->whereRaw(str_replace('WHERE','', $extrawhere->_where));
            $extra->_wherevals = array_merge($extra->_wherevals, $extrawhere->_wherevals);
        }
        if (isset($nextrelation)) 
        {
            $extra->with($nextrelation);
            //if (isset($extrawhere))
            //    $extra->_wherevals = array_merge($extra->_wherevals, $extrawhere->_wherevals);
        }

        //echo "extra: "; var_dump($extra->_wherevals); echo "<br>";
        //dd($extra);

        $relationship = $extra->_relationVars['relationship'];
        $foreign = $extra->_relationVars['foreign'];
        $primary = $extra->_relationVars['primary'];
        $foreignthrough = $extra->_relationVars['foreignthrough'];
        #$primarythrough = $extra->_relationVars['primarythrough'];


        if (strpos($relationship, 'Through')>0 || $relationship=='belongsToMany')
        {
            $foreign = $foreignthrough;
        }

        //if ($relationship=='hasOne' || $relationship=='belongsTo' || $relationship=='morphOne')
        //    $extra->limit(1);

                
        $extra = $extra->get();
        //dd($extra);

        foreach ($this->_collection as $current)
        {
            if ($relationship=='hasMany' || $relationship=='hasManyThrough' || $relationship=='morphMany' || $relationship=='belongsToMany')
                $current->$relation = $extra->where($foreign, $current->$primary);
            
            elseif ($relationship=='hasOne' || $relationship=='belongsTo' || $relationship=='morphOne')
                $current->$relation = $extra->where($foreign, $current->$primary)->first();
        }
        
    }

    public function _has($relation, $constraints=null, $comparator=null, $value=null)
    {
        //echo "HAS: ".$relation. " :: ".$this->_parent."<br>";
        $data = null;
        
        $newparent = new $this->_parent;
        
        if (strpos($relation, '.')>0)
        {
            $data = explode('.', $relation);
            $relation = array_pop($data);
            $parent_relation = array_shift($data);
        }
        
        $newparent->getQuery()->varsOnly = true;
        $data = $newparent->$relation();

        $childtable = $data->_table;
        $foreign = $data->_relationVars['foreign'];
        $primary = $data->_relationVars['primary'];

        $filter = '';
        if (isset($constraints) && !is_array($constraints))
        {
            $filter = str_replace('WHERE', ' AND', $constraints->_where);
        } 
        elseif (isset($constraints) && is_array($constraints))
        {
            foreach ($constraints as $exq)
            {
                $filter .= str_replace('WHERE', ' AND', $exq->_where);
            }
        } 

        if (isset($constraints))
            $this->_wherevals = $constraints->_wherevals;


        if (!$comparator)
            $where = 'EXISTS (SELECT * FROM `'.$childtable.'` WHERE `'.
                $this->_table.'`.`'.$primary.'` = `'.$childtable.'`.`'.$foreign.'`' . $filter . ')';
        else
            $where = ' (SELECT COUNT(*) FROM `'.$childtable.'` WHERE `'.
                $this->_table.'`.`'.$primary.'` = `'.$childtable.'`.`'.$foreign.'`' . $filter  . ') '.$comparator.' '.$value;

        if (isset($data->_relationVars['classthrough']))
        {
            $ct = $data->_relationVars['classthrough'];
            $cp = $data->_relationVars['foreignthrough'];
            $cf = $data->_relationVars['primarythrough'];

            if (!$comparator)
            $where = 'EXISTS (SELECT * FROM `'.$childtable.'` INNER JOIN `'.$ct.'` ON `'.$ct.'`.`'.$cf.
                    '` = `'.$childtable.'`.`'.$foreign.'` WHERE `'.
                $this->_table.'`.`'.$primary.'` = `'.$ct.'`.`'.$cp.'`' . $filter . ')';

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
     * @return QueryBuilder
     */
    public function has($relation, $comparator=null, $value=null)
    {
        return $this->_has($relation, null, $comparator, $value);
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
    public function wherehas($relation, $filter=null, $comparator=null, $value=null)
    {
        return $this->_has($relation, $filter, $comparator, $value);
    }

    /* public function _withWhereHas($function, $filters=null)
    {
        return $this->with(array($function => $filters))
                ->_has($function, $filters);
    } */

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
     * @param Query $filter 
     * @param string $comparator
     * @param string|int $value
     * @return QueryBuilder
     */
    /* public function withWherehas($relation, $filters=null)
    {
        return $this->_withWhereHas($relation, $filters);
    } */


    /**
     * Sets the Query's factory
     * 
     * @return Factory
     */
    public function factory()
    {
        $class = $this->_parent;
        $factory = call_user_func_array(array($class, 'newFactory'), array());
        //$factory = $class::newFactory();

        if (!$factory)
        {
            if (env('APP_DEBUG')==true) throw new Exception('Error looking for '.$class);
            else return null;
        }

        return $factory;

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
    
    public function attach($roles)
    {
        if (is_array($roles))
        {
            foreach ($roles as $role)
                $this->attach($role);
        }
        else
        {
            $record = array(
                $this->_relationVars['foreignthrough'] => $this->_relationVars['current'],
                $this->_relationVars['primarythrough'] => $roles
            );

            DB::table($this->_relationVars['classthrough'])
                ->insertOrIgnore($record);

        }
    }


    public function dettach($roles)
    {
        if (is_array($roles))
        {
            foreach ($roles as $role)
                $this->dettach($role);
        }
        else
        {
            DB::table($this->_relationVars['classthrough'])
                ->where($this->_relationVars['foreignthrough'], $this->_relationVars['current'])
                    ->where($this->_relationVars['primarythrough'], $roles)
                        ->delete();
        }
    }

    public function sync($roles, $remove = true)
    {
        //dd($this->_relationVars);

        if ($remove)
            DB::table($this->_relationVars['classthrough'])
                ->where($this->_relationVars['foreignthrough'], $this->_relationVars['current'])
                    ->delete();

        if (is_array($roles))
        {
            foreach ($roles as $role)
                $this->sync($role, false);
        }
        else
        {
            $record = array(
                $this->_relationVars['foreignthrough'] => $this->_relationVars['current'],
                $this->_relationVars['primarythrough'] => $roles
            );

            DB::table($this->_relationVars['classthrough'])
                ->insertOrIgnore($record);
        }
    }





    public function callScope($scope, $args)
    {
        //echo "<br>SCOPE: ".$this->_parent."::scope".ucfirst($scope)."<br>";
        $func = 'scope'.ucfirst($scope);
        $res = new $this->_parent;
        return call_user_func_array(array($res, $func), array_merge(array($this), $args));
    }

}
