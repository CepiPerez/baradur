<?php

class RuleItem
{
    public $type;
    public $value;
    public $column;
    public $ignore;
    public $ignore_column;

    public function __construct($type, $value, $column=null)
    {
        $this->type = $type;
        $this->value = $value;
        $this->column = $column;
    }

    public function ignore($value, $column=null)
    {
        if ($this->type!='unique') {
            throw new Exception('Invalid method [ignore] for ['.$this->type.'] Rule');
        }

        $this->ignore = $value;
        $this->ignore_column = $column;

        return $this;
    }

}