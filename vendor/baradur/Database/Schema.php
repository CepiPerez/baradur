<?php

class Schema
{
    static $__classname;

    static $drop = false;

    static $unique = array();
    static $primary = array();

    public static function init($classname)
    {
        self::$__classname = $classname;
    }

    public static function checkMainTable()
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

        if (isset($column->primary)) self::$primary[] = $column->name;
        if (isset($column->unique)) self::$unique[] = $column->name;

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
        $foreigns = array();
        $primary = array();
        $unique = array();

        foreach ($values as $column)
        {
            if (is_array($column))
            {
                foreach ($column as $col)
                {
                    if ($col->type == 'foreign')
                        $foreigns[] = $col;
                    elseif ($col->type == 'primary')
                        $primary[] = $col->name;
                    elseif ($col->type == 'unique')
                        $unique[] = $col->name;
                    else
                        $columns[] = self::addColumn($col);
                }
            }
            else
            {
                if ($column->type == 'foreign')
                    $foreigns[] = $column;
                elseif ($column->type == 'primary')
                    $primary[] = array('value'=>$column->value, 'name'=>$column->name);
                elseif ($column->type == 'unique')
                    $unique[] = array('value'=>$column->value, 'name'=>$column->name);
                else
                    $columns[] = self::addColumn($column);
            }
        }

        $foreigntext = '';
        if (count($foreigns)>0)
        {
            foreach ($foreigns as $foreign)
            {
                $foreigntext .= ', FOREIGN KEY (' . $foreign->name .') '.
                    'REFERENCES ' . $foreign->on . ' (' . $foreign->references . ')';
                
                if (isset($foreign->onDelete))
                    $foreigntext .= ' ON DELETE '.$foreign->onDelete;
                
                if (isset($foreign->onUpdate))
                    $foreigntext .= ' ON UPDATE '.$foreign->onUpdate;
            }
        }

        $primarytext = array();
        foreach ($primary as $f)
        {

            $text = '';
            if (isset($f['name']))
                $text .= ', CONSTRAINT '.$f['name'].' PRIMARY KEY ('. implode(', ',$f['value']) .')';
            else if (!isset($f['name']) && is_array($f['value']))
                $text .= ', CONSTRAINT '. implode('_', $f['value']) .' PRIMARY KEY ('. implode(', ',$f['value']) .')';
            else
                $text .= ', PRIMARY KEY ('. $f['value'] .')';
            $primarytext[] =  $text;
        }
        foreach (self::$primary as $f)
            $primarytext[] =  ', PRIMARY KEY ('.$f.')';

        $uniquetext = array();
        foreach ($unique as $f)
        {
            $text = '';
            if (isset($f['name']))
                $text .= ', CONSTRAINT '.$f['name'].' UNIQUE ('. implode(', ',$f['value']) .')';
            else if (!isset($f['name']) && is_array($f['value']))
                $text .= ', CONSTRAINT '. implode('_', $f['value']) .' UNIQUE ('. implode(', ',$f['value']) .')';
            else
                $text .= ', UNIQUE ('. $f['value'] .')';
            $uniquetext[] =  $text;
        }
        foreach (self::$unique as $f)
            $uniquetext[] =  ', UNIQUE ('. $f .')';

        self::$primary = array();
        self::$unique = array();
                
        $query = null;
        if ($action == 'CREATE')
        {
            $query = 'CREATE TABLE `'.$table.'` ('. implode(', ', $columns);
            //if (self::$primary) $query .= ', PRIMARY KEY ('. self::$primary . ')';
            if (count($primarytext)>0) $query .= implode('', $primarytext);
            if (count($uniquetext)>0) $query .= implode('', $uniquetext);
            if ($foreigntext!='') $query .= $foreigntext;
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


        printf($query.PHP_EOL);
        DB::table($table)->query($query);
        
    }


}