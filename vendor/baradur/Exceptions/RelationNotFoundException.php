<?php

class RelationNotFoundException extends RuntimeException
{
    public $model;

    public $relation;

    public static function make($model, $relation, $type = null)
    {
        $class = is_string($model) ? $model : get_class($model);

        $instance = new RelationNotFoundException(
            is_null($type)
                ? "Call to undefined relationship [{$relation}] on model [{$class}]."
                : "Call to undefined relationship [{$relation}] on model [{$class}] of type [{$type}].",
            404
        );

        $instance->model = $class;
        $instance->relation = $relation;

        return $instance;
    }
}
