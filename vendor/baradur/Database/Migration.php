<?php

$table = new stdClass;

Class Migration
{
    
    public function __construct()
    {
        //$this->table = new stdClass;
        
    }



}


Class Column {

    public function unique()
    {
        $this->unique = true;
        return $this;
    }

    public function nullable()
    {
        $this->nullable = true;
        return $this;
    }

}

class Table extends ArrayObject
{

    public function id()
    {
        return $this->bigIncrements('id');
    }

    private function newColumn($name, $type, $increments=false, $length=null, 
        $precision=null, $scale=null, $default=null, $update=null, $primary=false)
    {
        $col = new Column();
        $col->name = $name;
        $col->type = $type;
        if ($increments) $col->incremntes = $increments;
        if ($length) $col->length = $length;
        if ($precision) $col->precision = $precision;
        if ($scale) $col->scale = $scale;
        if ($default) $col->default = $default;
        if ($update) $col->update = $update;
        if ($primary) $col->primary = $primary;
        return $col;

    }

    public function bigIncrements($name)
    {
        $col = $this->newColumn($name, 'bigint', true, null, null, null, null, null, true);
        $this[] = $col;
        return $col;
    }

    public function string($name, $length=100)
    {
        $col = $this->newColumn($name, 'varchar', false, $length);
        $this[] = $col;
        return $col;
    }

    public function char($name, $length=100)
    {
        $col = $this->newColumn($name, 'char', false, $length);
        $this[] = $col;
        return $col;
    }

    public function text($name)
    {
        $col = $this->newColumn($name, 'text');
        $this[] = $col;
        return $col;
    }

    public function integer($name)
    {
        $col = $this->newColumn($name, 'int', false);
        $this[] = $col;
        return $col;
    }

    public function bigInteger($name)
    {
        $col = $this->newColumn($name, 'bigint', false);
        $this[] = $col;
        return $col;
    }

    public function decimal($name, $precision, $scale)
    {
        $col = $this->newColumn($name, 'decimal', false, null, $precision, $scale);
        $this[] = $col;
        return $col;
    }

    public function double($name, $precision, $scale)
    {
        $col = $this->newColumn($name, 'double', false, null, $precision, $scale);
        $this[] = $col;
        return $col;
    }

    public function float($name, $precision, $scale)
    {
        $col = $this->newColumn($name, 'float', false, null, $precision, $scale);
        $this[] = $col;
        return $col;

    }

    public function timestamps()
    {
        $col = $this->newColumn('created_at', 'timestamp', false, null, null, null, 'CURRENT_TIMESTAMP', null);
        $this[] = $col;

        $col2 = $this->newColumn('modified_at', 'timestamp', false, null, null, null, 'CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP');
        $this[] = $col2;

        return array($col, $col2);
    }

    public function dropColumn($name)
    {
        $col = $this->newColumn($name, 'DROP');
        $this[] = $col;
        return $col;
    }

    /* public function unique($name)
    {
        $this->unique = $name;
        return $this;
    } */

    /* public function nullable()
    {
        $this->__current->nullable = true;
        return $this;
    } */


}

class Schema
{
    static $__classname;

    static $drop = false;

    static $unique = null;
    static $primary = null;

    public static function init($classname)
    {
        self::$__classname = $classname;
    }

    private static function checkMainTable()
    {
        $query = 'CREATE TABLE if not exists migrations (migration text, applied timestamp)';
        DB::table('migrations')->query($query);
    }


    private static function addColumn($column)
    {
        if ($column->type == 'DROP')
        {
            self::$drop = true;
            return '`'.$column->name.'`';
        }
        else
        {
            self::$drop = false;
        }

        $col = '`'.$column->name.'` '.$column->type;
        if (isset($column->length)) $col .= ' ('.$column->length.')';
        else if (isset($column->precision)) $col .= ' ('.$column->precision.','.$column->scale.')';

        if (isset($column->increments)) $col .= ' AUTO_INCREMENT';
        if (!isset($column->nullable)) $col .= ' NOT NULL';

        if (isset($column->default)) $col .= ' DEFAULT '.$column->default;
        if (isset($column->update)) $col .= ' ON UPDATE '.$column->update;

        if (isset($column->primary)) self::$primary = $column->name;
        if (isset($column->unique)) self::$unique = $column->name;

        return $col;
    }


    public static function create()
    {
        self::processTable('CREATE', func_get_args());        
    }

    public static function table()
    {
        self::processTable('ALTER', func_get_args());        
    }

    public static function dropIfExists($table)
    {
        self::checkMainTable();
        $query = 'DROP TABLE `'.$table.'`';
        DB::table($table)->query($query);
    }

    
    private static function processTable($action, $values)
    {
        self::checkMainTable();

        $table = array_shift($values);

        $columns = array();

        foreach ($values as $column)
        {
            if (is_array($column))
            {
                foreach ($column as $col)
                    $columns[] = self::addColumn($col);
            }
            else
            {
                $columns[] = self::addColumn($column);
            }
        }

        $query = null;
        if ($action == 'CREATE')
        {
            $query = 'CREATE TABLE `'.$table.'` ('. implode(', ', $columns);
            if (self::$primary) $query .= ', PRIMARY KEY ('. self::$primary . ')';
            if (self::$unique) $query .= ', UNIQUE ('. self::$unique . ')';
            $query .= ')';
        }
        elseif ($action == 'ALTER')
        {
            $query = 'ALTER TABLE `'.$table.'` ';
            if (self::$drop)
            {
                $query .= 'DROP '. implode(', DROP ', $columns);
            }
            else
            {
                $query .= 'ADD '. implode(', ADD ', $columns);
            }

        }

        //printf($query.PHP_EOL);
        DB::table($table)->query($query);
        
    }


}