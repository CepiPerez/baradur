<?php

Class Column {

    public $unique = false;
    public $nullable = false;
    public $name = null;
    public $type = null;
    public $increments = null;
    public $length = null;
    public $precision = null;
    public $scale = null;
    public $default = null;
    public $update = null;
    public $primary = null;
    public $unsigned = null;

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

Class ForeignKey
{
    public $name = null;
    public $type = null;
    public $unsigned = false;
    public $onDelete = null;
    public $onUpdate = null;
    public $nullable = false;
    public $references = null;
    public $on = null;

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

    public function nullable()
    {
        $this->nullable = true;
        return $this;
    }
}

Class Foreign extends ForeignKey
{
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
}

Class ForeignId extends ForeignKey
{
    private function _references($id)
    {
        $this->references = $id;
        return $this;
    }

    private function _on($table)
    {
        $this->on = $table;
        return $this;
    }

    public function constrained($table=null)
    {
        if (!isset($table))
        {
            $name = str_replace('_id', '', $this->name);
            $table = Str::plural($name);
        }
        return $this->_references('id')->_on($table);
    }

}

Class PrimaryKey
{
    public $name = null;
    public $value = null;
    public $type = null;
}

Class UniqueKey
{
    public $index_name = null;
    public $name = null;
    public $value = null;
    public $type = null;
}

class Blueprint extends ArrayObject
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

    public function foreignId($name)
    {
        $this->unsignedBigInteger($name);
        $foreign = new ForeignId;
        $foreign->name = $name;
        $foreign->type = 'foreign';
        $foreign->unsigned = true;
        $this[] = $foreign;
        return $foreign;
    }

    /* public function foreignIdFor($class)
    {
        $c = Model::instance($class);
        $foreign = new Foreign;
        $foreign->name = $class.'_id';
        $foreign->type = 'bigint';
        $foreign->unsigned = true;
        $foreign->references($c->_primary[0])->on($c->_table);
        $this[] = $foreign;
        return $foreign;
    } */

    public function primary($value, $name=null)
    {
        $primary = new PrimaryKey;
        $primary->value = $value;
        $primary->name = $name;
        $primary->type = 'primary';
        $this[] = $primary;
        return $primary;
    }

    public function unique($name, $index_name=null)
    {
        $unique = new UniqueKey;
        $unique->index_name = $index_name;
        $unique->name = $name;
        $unique->type = 'unique';
        $this[] = $unique;
        return $unique;
    }

    public function increments($name)
    {
        return $this->newColumn($name, 'int', true, null, null, null, null, null, true, true);
    }

    public function tinyIncrements($name)
    {
        return $this->newColumn($name, 'tinyint', true, null, null, null, null, null, true, true);
    }

    public function smallIncrements($name)
    {
        return $this->newColumn($name, 'smallint', true, null, null, null, null, null, true, true);
    }

    public function mediumIncrements($name)
    {
        return $this->newColumn($name, 'mediumint', true, null, null, null, null, null, true, true);
    }

    public function bigIncrements($name)
    {
        return $this->newColumn($name, 'bigint', true, null, null, null, null, false, true, true);
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

    /* public function longText($name)
    {
        return $this->newColumn($name, 'longtext');
    } */

    public function integer($name, $increments=false, $unsigned=false, $length=null)
    {
        return $this->newColumn($name, 'int', $increments, $length, null, null, null, null, null, $unsigned);
    }

    public function unsignedInteger($name, $increments=false, $length=null)
    {
        return $this->integer($name, $increments, true, $length);
    }

    public function tinyInteger($name, $increments=false, $unsigned=false, $length=null)
    {
        return $this->newColumn($name, 'tinyint', $increments, $length, null, null, null, null, null, $unsigned);
    }

    public function unsignedTinyInteger($name, $increments=false, $length=null)
    {
        return $this->tinyInteger($name, $increments, true, $length);
    }

    public function smallInteger($name, $increments=false, $unsigned=false, $length=null)
    {
        return $this->newColumn($name, 'smallint', $increments, $length, null, null, null, null, null, $unsigned);
    }

    public function unsignedSmallInteger($name, $increments=false, $length=null)
    {
        return $this->smallInteger($name, $increments, true, $length);
    }

    public function mediumInteger($name, $increments=false, $unsigned=false, $length=null)
    {
        return $this->newColumn($name, 'mediumint', $increments, $length, null, null, null, null, null, $unsigned);
    }

    public function unsignedMediumInteger($name, $increments=false, $length=null)
    {
        return $this->mediumInteger($name, $increments, true, $length);
    }

    public function bigInteger($name)
    {
        return $this->newColumn($name, 'bigint', false);
    }

    public function unsignedBigInteger($name)
    {
        return $this->newColumn($name, 'bigint', false, null, null, null, null, null, null, true);
    }

    public function decimal($name, $precision, $scale)
    {
        return $this->newColumn($name, 'decimal', false, null, $precision, $scale);
    }

    public function double($name, $precision, $scale)
    {
        return $this->newColumn($name, 'double', false, null, $precision, $scale);
    }

    public function float($name, $precision=8, $scale=2)
    {
        return $this->newColumn($name, 'float', false, null, $precision, $scale);
    }

    public function boolean($name)
    {
        return $this->newColumn($name, 'boolean');
    }

    public function timestamps()
    {
        $col = $this->newColumn('created_at', 'timestamp', false, null, null, null, 'CURRENT_TIMESTAMP', null)->nullable();
        $col2 = $this->newColumn('modified_at', 'timestamp', false, null, null, null, 'CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP')->nullable();
    }

    public function morphs($name)
    {
        $this->unsignedBigInteger($name.'_id');
        $this->char($name.'_type');
    }

    public function softDeletes($column = 'deleted_at', $precision = 0)
    {
        return $this->newColumn($column, 'timestamp', false, null, $precision)->nullable();
    }

    public function datetime($name, $precision=null)
    {
        return $this->newColumn($name, 'datetime', false, null, $precision);
    }
    
    public function date($name)
    {
        return $this->newColumn($name, 'date');
    }
    




    public function dropColumn($name)
    {
        return $this->newColumn($name, 'DROP');
    }

    public function dropIndex($name)
    {
        return $this->newColumn($name, 'DROP');
    }

    public function dropUnique($name)
    {
        return $this->newColumn($name, 'DROP');
    }

    public function dropForeign($name)
    {
        return $this->newColumn($name, 'DROP');
    }
    
}