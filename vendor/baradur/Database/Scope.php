<?php

interface Scope
{
    public function apply(Builder $builder, Model $model);
}