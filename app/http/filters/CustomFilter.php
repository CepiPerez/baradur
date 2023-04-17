<?php

class CustomFilter
{

    public function __invoke($query, $value)
    {
        return $query->where('categoria_id', $value);
    }


}