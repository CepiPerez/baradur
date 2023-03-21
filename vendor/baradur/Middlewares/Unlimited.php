<?php

class Unlimited extends Limit
{
    public function __construct()
    {
        parent::__construct('', PHP_INT_MAX, 1);
    }

}