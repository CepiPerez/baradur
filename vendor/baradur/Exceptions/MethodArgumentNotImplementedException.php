<?php

class MethodArgumentNotImplementedException extends NotImplementedException
{
    public function __construct($methodName, $argName)
    {
        $message = "The $methodName() method's argument $"
            . $argName . " behavior is not implemented.";
            
        parent::__construct($message);
    }
}