<?php

class AsStringable
{
    public function get($model, $key, $value, $attributes)
    {
        return isset($value) ? Str::of($value) : null;
    }

    public function set($model, $key, $value, $attributes)
    {
        return isset($value) ? (string) $value : null;
    }
}