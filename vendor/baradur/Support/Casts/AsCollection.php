<?php

class AsCollection
{
    public function get($model, $key, $value, $attributes)
    {
        if (! isset($attributes[$key])) {
            return;
        }

        $data = json_decode($attributes[$key], true);

        return is_array($data) ? collect($data) : null;
    }

    public function set($model, $key, $value, $attributes)
    {
        return array($key => json_encode($value));
    }
}