<?php

class LazyLoadingViolationException extends RuntimeException
{
    public $model;
    public $relation;
   
    public function __construct($model, $relation)
    {
        $class = get_class($model);

        parent::__construct("Attempted to lazy load [{$relation}] on model [{$class}] but lazy loading is disabled.");

        $this->model = $class;
        $this->relation = $relation;
    }

}