<?php

Class QueryResult
{
    public $_where = '';
    public $_join = '';
    public $_table;
    public $_primary;
    public $_foreign;
    public $_relation;


    public function relation($relation)
    {
        $this->_relation = $relation;
        return $this;
    }

    public function toSql()
    {
        $res = '`' . $this->_table . '` ' . $this->_join . $this->_where;

        foreach ($this->_wherevals as $val)
        {
            foreach ($val as $k => $v)
                $res = preg_replace('/\?/', $v, $res, 1);
        }

        return $res;
    }

    public function where($column, $cond='', $val='', $ret=true)
    {
        if (is_array($column))
        {
            foreach ($column as $co)
            {
                list($var1, $var2, $var3) = $co;
                $this->where($var1, $var2, $var3);
            }
            return $this;
        }

        if ($val=='')
        {
            $val = $cond;
            $cond = '=';
        }

        if (is_string($val)) $val = "'".$val."'";

        list ($table, $col) = explode('.', $column);
        if ($col) $column = '`'.$table.'`.`'.$col.'`';
        else $column = '`'.$table.'`';

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ' ' . $cond . ' '.$val;
        else
            $this->_where .= ' AND ' . $column . ' ' .$cond . ' '.$val;

        return $this;
    }

    public function orWhere($column, $cond, $val='')
    {
        if (is_array($column))
        {
            foreach ($column as $co)
            {
                list($var1, $var2, $var3) = $co;
                $this->where($var1, $var2, $var3);
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


        if (is_string($val)) $val = "'".$val."'";

        if ($this->_where == '')
            $this->_where = 'WHERE ' . $column . ' ' . $cond . ' '.$val;
        else
            $this->_where .= ' OR ' . $column . ' ' .$cond . ' '.$val;

        return $this;
    }

    public function whereIn($column, $values)
    {
        $win = array();
        foreach (explode(',', $values) as $val)
        {
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

    public function on($column, $compare, $join_column)
    {
        list ($table, $col) = explode('.', $column);
        if ($col) $column = '`'.$table.'`.`'.$col.'`';
        else $column = '`'.$table.'`';

        list ($table, $col) = explode('.', $join_column);
        if ($col) $join_column = '`'.$table.'`.`'.$col.'`';
        else $join_column = '`'.$table.'`';


        if ($this->_join == '')
            $this->_join = 'ON ' . $column . ' '. trim($compare) . ' ' . $join_column;
        else
            $this->_join .= ' AND ' . $column . ' '. trim($compare) . ' ' . $join_column;

        return $this;
    }




}

Class Query
{
    public static function table($table)
    {
        $res = new QueryResult();
        $res->_table = $table;
        return $res;
    }

    public static function relation($relation)
    {
        $res = new QueryResult();
        $res->_relation = $relation;
        return $res;
    }

    public static function where($col, $cond='', $val='')
    {
        $res = new QueryResult();
        return $res->where($col, $cond, $val);        
    }

    public static function orWhere($col, $cond, $val='')
    {
        $res = new QueryResult();
        return $res->orWhere($col, $cond, $val);
    }

    public static function whereIn($col, $values)
    {
        $res = new QueryResult();
        return $res->whereIn($col, $values);
    }

    public static function whereNotIn($col, $values)
    {
        $res = new QueryResult();
        return $res->whereNotIn($col, $values);
    }

    public static function on($column, $compare, $join_column)
    {
        $res = new QueryResult();
        return $res->on($column, $compare, $join_column);
    }



}
