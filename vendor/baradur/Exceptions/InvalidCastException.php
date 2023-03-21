<?php

class InvalidCastException extends RuntimeException
{
    public $model;
    public $column;
    public $castType;

    public function __construct($model, $column, $castType)
    {
        $class = get_class($model);

        parent::__construct("Call to undefined cast [{$castType}] on column [{$column}] in model [{$class}].");

        $this->model = $class;
        $this->column = $column;
        $this->castType = $castType;
    }
}