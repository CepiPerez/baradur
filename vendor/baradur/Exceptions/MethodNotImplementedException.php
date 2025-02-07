<?php

class MethodNotImplementedException extends NotImplementedException
{
    public function __construct($methodName)
    {
        parent::__construct("The $methodName() is not implemented.");
    }
}