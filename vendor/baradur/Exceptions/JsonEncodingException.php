<?php

class JsonEncodingException extends RuntimeException
{

    public static function forModel($model, $message)
    {
        return new JsonEncodingException('Error encoding model ['.get_class($model).'] with ID ['.$model->getKey().'] to JSON: '.$message);
    }

    public static function forResource($resource, $message)
    {
        $model = $resource->resource;

        return new JsonEncodingException('Error encoding resource ['.get_class($resource).'] with model ['.get_class($model).'] with ID ['.$model->getKey().'] to JSON: '.$message);
    }

    public static function forAttribute($model, $key, $message)
    {
        $class = get_class($model);

        return new JsonEncodingException("Unable to encode attribute [{$key}] for model [{$class}] to JSON: {$message}.");
    }
}