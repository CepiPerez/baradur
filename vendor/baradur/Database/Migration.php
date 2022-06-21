<?php

$table = new stdClass;


Class Migration
{
    
    public function __construct()
    {
        //$this->table = new stdClass;
        
    }

    private static function checkMainTable()
    {
        Schema::checkMainTable();
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

Class Foreign {

    public function references($id)
    {
        $this->references = $id;
        return $this;
    }

    public function on($table)
    {
        $this->on = $table;
        return $this;
    }

    public function onDelete($action)
    {
        $this->onDelete = $action;
        return $this;
    }

    public function onUpdate($action)
    {
        $this->onUpdate = $action;
        return $this;
    }

    public function cascadeOnUpdate()
    {
        return $this->onUpdate('cascade');
    }

    public function restrictOnUpdate()
    {
        return $this->onUpdate('restrict');
    }

    public function cascadeOnDelete()
    {
        return $this->onDelete('cascade');
    }

    public function restrictOnDelete()
    {
        return $this->onDelete('restrict');
    }

    public function nullOnDelete()
    {
        return $this->onDelete('set null');
    }

}

Class PrimaryKey {

}

Class UniqueKey {

}


class Table extends ArrayObject
{

    public function id()
    {
        return $this->bigIncrements('id');
    }

    private function newColumn($name, $type, $increments=false, $length=null, 
        $precision=null, $scale=null, $default=null, $update=null, $primary=false, $unsigned=false)
    {
        $col = new Column();
        $col->name = $name;
        $col->type = $type;
        if ($increments) $col->increments = $increments;
        if ($length) $col->length = $length;
        if ($precision) $col->precision = $precision;
        if ($scale) $col->scale = $scale;
        if ($default) $col->default = $default;
        if ($update) $col->update = $update;
        if ($primary) $col->primary = $primary;
        if ($unsigned) $col->unsigned = $unsigned;

        $this[] = $col;
        return $col;

    }

    public function foreign($name)
    {
        $foreign = new Foreign;
        $foreign->name = $name;
        $foreign->type = 'foreign';
        $this[] = $foreign;
        return $foreign;
    }

    public function primary($value, $name=null)
    {
        $primary = new PrimaryKey;
        $primary->value = $value;
        $primary->name = $name;
        $primary->type = 'primary';
        $this[] = $primary;
        return $primary;
    }

    public function unique($value, $name=null)
    {
        $unique = new UniqueKey;
        $unique->value = $value;
        $unique->name = $name;
        $unique->type = 'unique';
        $this[] = $unique;
        return $unique;
    }

    /* public function primary($value)
    {
        return $this->newColumn(func_get_args(), 'primary');
    } */


    public function bigIncrements($name)
    {
        return $this->newColumn($name, 'bigint', true, null, null, null, null, null, true);
    }

    public function string($name, $length=100)
    {
        return $this->newColumn($name, 'varchar', false, $length);
    }

    public function char($name, $length=100)
    {
        return $this->newColumn($name, 'char', false, $length);
    }

    public function text($name)
    {
        return $this->newColumn($name, 'text');
    }

    public function integer($name, $increments=false, $unsigned=false, $length=null)
    {
        return $this->newColumn($name, 'int', $increments, $length, null, null, null, null, null,);
    }

    public function bigInteger($name)
    {
        return $this->newColumn($name, 'bigint', false);
    }

    public function decimal($name, $precision, $scale)
    {
        return $this->newColumn($name, 'decimal', false, null, $precision, $scale);
    }

    public function double($name, $precision, $scale)
    {
        return $this->newColumn($name, 'double', false, null, $precision, $scale);
    }

    public function float($name, $precision, $scale)
    {
        return $this->newColumn($name, 'float', false, null, $precision, $scale);
    }

    public function timestamps()
    {
        $col = $this->newColumn('created_at', 'timestamp', false, null, null, null, 'CURRENT_TIMESTAMP', null);
        $col2 = $this->newColumn('modified_at', 'timestamp', false, null, null, null, 'CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP');
        return array($col, $col2);
    }

    public function dropColumn($name)
    {
        return $this->newColumn($name, 'DROP');
    }

    public function datetime($name, $precision=null)
    {
        return $this->newColumn($name, 'datetime', false, null, $precision);
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

