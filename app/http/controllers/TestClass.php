<?php

class TestClass
{
    protected $value = 0;

    public function increase()
    {
        $this->value++;

        return $this->value;
    }
}