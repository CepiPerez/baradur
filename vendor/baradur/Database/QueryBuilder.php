<?php

Class QueryBuilder
{
    public $_parent = null;
    public $_table;
    public $_primary;
    public $_foreign;
    public $_fillable;
    public $_guarded;

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

    public function __construct($connector, $table, $primary, $parent, $fillable, $guarded)
    {
        //global $database;
        
        $this->_connector = $connector;
        $this->_table = $table;
        $this->_primary = $primary;
        $this->_parent = $parent;
        $this->_fillable = $fillable;
        $this->_guarded = $guarded;
        $this->_collection = new Collection($parent);

        //echo "PARENT:";var_dump($this->_parent);

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

        $vtype = 'i';
        if (is_string($val))
        {
            $vtype = 's';   
        }

        $this->_wherevals[] = array($vtype => $val);

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ' ' . $cond . ' ?';
        else
            $this->_where .= ' AND ' . $column . ' ' .$cond . ' ?';

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

        $vtype = 'i';
        if (is_string($val))
        {
            $vtype = 's';   
        }

        $this->_wherevals[] = array($vtype => $val);

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ' ' . $cond . ' ?';
        else
            $this->_where .= ' OR ' . $column . ' ' .$cond . ' ?';

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

        $vtype = 'i';
        if (is_string($value))
        {
            $vtype = 's';   
        }

        $this->_wherevals[] = array($vtype => $value);

        if ($this->_having == '')
            $this->_having = 'HAVING ' . $reference . ' ' . $operator . ' ?';
        else
            $this->_having .= ' AND ' . $reference . ' ' .$operator . ' ?';

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
        var_dump($filter);
        $side = trim($side);
        $filter = $filter->_join;
        $alias = '`' . trim($alias) . '`';

        if ($this->_join == '')
            $this->_join = $side.'JOIN (' . $query->toSql() . ') as ' . $alias . ' ' . $filter;
        else
            $this->_join .= ' '.$side.' JOIN (' . $query->toSql() . ') as ' . $alias . ' ' . $filter;
  
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
    public function joinSub($query, $alias, $filter)
    {
        return $this->_joinSubResult('INNER', $query, $alias, $filter);
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
    public function leftJoinSub($query, $alias, $filter)
    {
        return $this->_joinSubResult('LEFT', $query, $alias, $filter);
    }

    /**
     * RIGHT Joins as subquery\
     * Returns the Query builder
     * 
     * @param string $query 
     * @param string $alias
     * @param Query $filter
     * @return QueryBuilder
     */
    public function rightJoinSub($query, $alias, $filter)
    {
        return $this->_joinSubResult('RIGHT', $query, $alias, $filter);
    }


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
                if (is_string($v)) $v = "'".$v."'";
                array_push($this->_values, $v? $v : "NULL");
            }
        }
        else
        {
            array_push($this->_keys, $key);
            if (is_string($val)) $val = "'".$val."'";
            array_push($this->_values, $val? $val : "NULL");
        }

        if ($ret) return $this;
    }

    /**
     * Saves the current record in database\
     * Uses INSERT for new record\
     * Uses UPDATE for retrieved record\
     * Returns error or empty string if ok
     * 
     * @return string
     */
    public function save($values)
    {

        if(!$values)
            return 'No se definieron datos';

        if ($this->_where == '')
            return $this->_insert($values);
        else
            return $this->update($values);

    }

    /**
     * INSERT a record or an array of records in database\
     * Returns error or empty string if ok
     * 
     * @param array $record
     * @return string
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
        
        return $this->_connector->error;
    }


    /**
     * INSERT IGNORE a record or an array of records in database\
     * Returns error or empty string if ok
     * 
     * @param array $record
     * @return string
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
        
        return $this->_connector->error;
    }


   /**
     * Creates a new record in database\
     * Returns new record
     * 
     * @param array $record
     * @return Model
     */
    public function create($record)
    {
        foreach ($record as $key => $val)
        {
            if (in_array($key, $this->_fillable))
                $this->set($key, $val, false);
            else
                unset($record[$key]);
        }

        if(count($this->_values) == 0)
            return null;

        if ($this->_insert(array()) == '')
            return $this->insertUnique($record);
        else
            return null;

    }

    /**
     * Updates a record in database\
     * Returns error or empty string if ok
     * 
     * @param array $record
     * @return string
     */
    /* public function update($record)
    {
        $isarray = false;
        $where = $this->_where;
        $wherevals = $this->_wherevals;

        foreach ($record as $key => $val)
        {
            if (!is_array($val))
            {
                $this->set($key, $val, false);
            }
            else
            {
                $this->_where = $where;
                $this->_wherevals = $wherevals;

                $isarray = true;
                $this->_update($val);
            }
        }
        if (!$isarray)
            $this->_update(array());

    } */

    /**
     * Updates a record in database\
     * Returns error or empty string if ok
     * 
     * @param array $record
     * @return string
     */
    public function update($record)
    {

        foreach ($record as $key => $val)
            $this->set($key, $val, false);

        if ($this->_where == '')
            return 'WHERE not assigned. Use updateAll() if you want to update all records';
            
        if (!$this->_values)
            return 'No values assigned for update';

        $valores = array();
        
        for ($i=0; $i < count($this->_keys); $i++) {
            array_push($valores, $this->_keys[$i] . "=" . $this->_values[$i]);
        }

        $sql = 'UPDATE `' . $this->_table . '` SET ' . implode(', ', $valores) . ' ' . $this->_where;

        //echo $sql."::";var_dump($this->_wherevals);echo "<br>";
        $this->_connector->execSQL($sql, $this->_wherevals);

        $this->clear();
        
        return $this->_connector->error;
    }

    /**
     * Create or update a record matching the attributes, and fill it with values\
     * Returns error or empty string if ok
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
            if (in_array($key, $this->_fillable))
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
            if ($this->update($values) == '')
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
     * If the record doesn't exists then creates a new one\
     * Returns error or empty string if ok
     * 
     * @param array $record
     * @return string
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
        
        return $this->_connector->error;
    }



    /**
     * UDPATE the current records in database\
     * Returns error or empty string if ok
     * 
     * @param array $values
     * @return string
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
        
        return $this->_connector->error;
    }

    /**
     * DELETE the current records from database\
     * Returns error if WHERE clause was not specified\
     * Returns error or empty string if ok
     * 
     * @return string
     */
    public function delete()
    {
        //global $database;

        if ($this->_where == '')
            return 'WHERE no asignado. Utilice deleteAll() si desea eliminar todos los registros';

        $sql = 'DELETE FROM `' . $this->_table . '` ' . $this->_where;

        $query = $this->_connector->execSQL($sql, $this->_wherevals);

        $this->clear();
        
        return $this->_connector->error;
    }



    /**
     * Truncates the current table
     * Returns error or empty string if ok
     * 
     * @return string
     */
    public function truncate()
    {
        $sql = 'TRUNCATE TABLE `' . $this->_table . '`';

        $query = $this->_connector->query($sql);

        $this->clear();
        
        return $this->_connector->error;
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


    private function insertUnique($data)
    {
        $item = new $this->_parent;
        foreach ($data as $key => $val)
        {
            $item->$key = $val;
        }
        $item->setQuery($this);

        return $item;
    }

    private function insertData($data)
    {
        $col = new Collection($this->_parent); //get_class($this->_parent));

        foreach ($data as $arr)
        {
            $class = $this->_parent; //get_class($this->_parent);

            $item = new $class(true);
            foreach ($arr as $key => $val)
            {
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
     * @return array
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
     * @return array
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
        {
            //call_user_func_array(array($class, 'initialize'), array($class));
            $res = call_user_func_array(array($class, 'select'), array($columns));
        }
        else
            $res = DB::table(Helpers::getTableNameFromClass($class, false))->select($columns);

        
        if ($relationship=='belongsTo')
        {
            if (!$foreign) $foreign = 'id';
            if (!$primary) $primary = Helpers::getTableNameFromClass($res->_parent, false).'_id';
        }
        else if ($relationship=='hasOne' || $relationship=='hasMany')
        {
            if (!$foreign) $foreign = ($this->_original?$this->_original :
                                        Helpers::getTableNameFromClass($this->_parent, false)).'_id';
            if (!$primary) $primary = $this->_primary;
        }

        //echo $class.":".$foreign.":".$primary.":".$relationship."<br>";

        $res->_relationVars = array(
            'foreign' => $foreign,
            'primary' => $primary,
            'relationship' => $relationship);

        if ($this->varsOnly)
            return $res;

        $res->setConnector($this->_connector);

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

        $columns = '*';
        if (strpos($class, ':')>0) {
            list($class, $columns) = explode(':', $class);
            $columns = explode(',', $columns);
        }

        /* $primarytable = getTableNameFromClass($class);
        if (class_exists($class))
            $primarytable = call_user_func(array($class, 'getTable'), array()); */

        $secondarytable = Helpers::getTableNameFromClass($classthrough, false);
        if (class_exists($classthrough))
            $secondarytable = call_user_func(array($classthrough, 'getTable'), array());

        $res = null;
        if (class_exists($class))
            $res = call_user_func_array(array($class, 'select'), array($columns));
        else
            $res = DB::table(Helpers::getTableNameFromClass($class, false))->select($columns);

        $res->setConnector($this->_connector);

        $res = $res->join($secondarytable, $primarythrough, '=', $foreign);

        $res->_relationVars = array('classthrough' => $classthrough,
            'foreignthrough' => $foreignthrough,
            'primarythrough' => $primarythrough,
            'foreign' => $foreign,
            'primary' => $primary,
            'relationship' => $relationship);

        #var_dump($res->_relationVars); echo "<br>";

        if ($this->varsOnly)
            return $res;

        
        $wherein = array();
        //$newArray = array();
        
        //$key = $this->_rparent!='DB'? $primarythrough : $primary;
        //$keythrough = $this->_rparent!='DB'? $foreign : $foreignthrough;

        $wherein = $this->recusiveSearch($this->_collection, $primary, null, $wherein);

        //echo "WHEREIN: ".implode(',',$wherein)."<br><br>";
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
        if (!$this->_eagerLoad) return;

        $processed = array();
        foreach ($this->_eagerLoad as $extra)
        {
            //echo "EXTRA: "; var_dump($extra); echo "<br>";
            /* if (strpos($extra['relation'], '.')>0)
            {
                $extras = explode('.', $extra['relation']);
                $controller = null;
                $step = 0;
                while ($step < count($extras))
                {
                    if (!in_array($extras[$step], $processed))
                    {
                        array_push($processed, $extras[$step]);

                        if ($step==0) $controller = null;
                        else 
                        {
                            $controller = substr($extra['relation'], 0, strpos($extra['relation'], '.'.$extras[$step]));
                        }

                        $this->addWith($extras[$step], $controller, isset($extra['constraints'])? $extra['constraints']:null);
                    }
                    ++$step;
                }
            } 
            else
            { */
                
                if (!in_array($extra['relation'], $processed))
                {
                    array_push($processed, $extra['relation']);
                    $this->addWith($extra['relation'], null, isset($extra['constraints'])? $extra['constraints']:null);
                }
            //}
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

    private function addWith($relation, $parent=null, $extrawhere)
    {
        //echo "ADDING WITH: ".$relation."<br>";
        //echo "TABLE: ".$this->_parent->getTable()."<br>";

        #echo "function: ".$function."<br>";
        //echo "parent: ".$this->_parent."<br>";
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
            $current = array_shift(explode('.', $relation));
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


        $relationship = $extra->_relationVars['relationship'];
        $foreign = $extra->_relationVars['foreign'];
        $primary = $extra->_relationVars['primary'];
        $foreignthrough = $extra->_relationVars['foreignthrough'];
        $primarythrough = $extra->_relationVars['primarythrough'];

        /* if (strpos($relationship, 'Through')>0)
        {
            $foreign = $foreignthrough;
            $primary = $primarythrough;
        } */

        //if ($columns && !in_array($foreign, $columns)) $columns[]=$foreign;
        //if ($columns) $extra->select($columns);

        if (strpos($relationship, 'Through')>0)
        {
            //$classthrough = $extra->_relationVars['classthrough'];
            //$primary = $primarythrough;
            $foreign = $foreignthrough;
            //if (!$parent)
            //    $extra->addSelect($classthrough.'.'.$primarythrough);
        }
                
        
        /* if (isset($this->_eagerLoad))
        {
            foreach ($this->_eagerLoad as $rel)
            {
                //echo "EXTRAQUERY:::::";var_dump($rel['constraints']);echo"<br>";
                list($rel_parent, $rel_son) = explode('.', $rel['relation']);

                if (isset($rel['constraints']))
                {
                    $exw = $rel['constraints'];
                    if (isset($rel_son) && $rel_parent == $relation)
                    {
                        $newparent = new $this->_parent;
                        $newparent->getQuery()->varsOnly = true;
                        $son = $newparent->$rel_son();
                        $where = ' EXISTS (SELECT 1 FROM `' . $son->_table . '` '
                        . $exw->_where . ' AND `' . $son->_relationVars['classthrough'] . '`.`' .
                        $son->_relationVars['primarythrough'] .'` = `'.
                        $son->_table . '`.`' . $son->_relationVars['foreign'] .'`'
                        .')';
                        $extra->whereRaw($where);
                        $extra->_wherevals = array_merge($extra->_wherevals, $this->_wherevals);
                    }
                }
            }
        } */
        

        #echo "table: ".$extra->_table.":: main: ".$this->_table."<br>";
        #echo "<br>primary: ".$primary."<br>";
        #echo "foreign: ".$foreign."<br>";
        #echo "relationship: ".$relationship."<br><br>";

        //var_dump($extra->_relationVars);

        //$newArray = array();

        //$extra->_wherevals = $this->_wherevals;
        //var_dump($extra);
        $extra = $extra->get();
        //$extra = (array)$extra;
        //dd($extra);

        foreach ($this->_collection as $current)
        {
            //echo "CURF: ".$current->$primary."::: ";var_dump($extra[$current->$foreign]);echo"<br>";

            if ($relationship=='hasMany' || $relationship=='hasManyThrough')
                $current->$relation = $extra->where($foreign, $current->$primary);
            
            elseif ($relationship=='hasOne' || $relationship=='belongsTo')
                $current->$relation = $extra->where($foreign, $current->$primary)->first();
        }
        

        /* foreach ($extra as $ex)
        {
            if (strpos($relationship, 'Through')>0 && !$parent)
            {
                $newArray[$ex->$foreignthrough][] = $ex;
            }
            else
                $newArray[$ex->$foreign][] = $ex;

        }
        
        if (strpos($relationship, 'Through')>0)
        {
            $this->recusiveInsert($this->_collection, $function, $newArray, 
                ($parent? $primarythrough : $primary), 
                $parent, str_replace('Through', '', $relationship));
        }
        else
        {
            $this->recusiveInsert($this->_collection, $function, $newArray, 
                $primary, $parent, $relationship);
        } */
        

    }

    public function _has($relation, $constraints=null, $comparator=null, $value=null)
    {
        //echo "HAS: ".$relation. " :: ".$this->_parent."<br>";
        $data = null;
        
        $newparent = new $this->_parent;
        
        $parent_relation = null;
        if (strpos($relation, '.')>0)
        {
            $data = explode('.', $relation);
            $relation = array_pop($data);
            $parent_relation = array_shift($data);
        }
        
        $newparent->getQuery()->varsOnly = true;
        $data = $newparent->$relation();
        //var_dump($data->_relationVars); echo "<br>";


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

        # withWhereHas()
        /* elseif (isset($this->_eagerLoad))
        {
            foreach ($this->_eagerLoad as $relation)
            {
                if (isset($relation['constraints']))
                {
                    $exw = $relation['constraints'];

                    echo "EX::::".$relation."::".$parent_relation."<br>";
                    if ($exw->_relation == $relation)
                        $filter .= str_replace('WHERE ', ' AND ', $exw->_where);
                    elseif ($exw->_relation == $parent_relation)
                        $filter .= str_replace('WHERE ', ' AND ', $exw->_where);

                }
            }

        } */


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
    public function withWherehas($relation, $filters=null)
    {
        return $this->_withWhereHas($relation, $filters);
    }


    public function callScope($scope, $args)
    {
        //echo "<br>SCOPE: ".$this->_parent."::scope".ucfirst($scope)."<br>";
        $func = 'scope'.ucfirst($scope);
        $res = new $this->_parent;
        return call_user_func_array(array($res, $func), array_merge(array($this), $args));
    }

}
