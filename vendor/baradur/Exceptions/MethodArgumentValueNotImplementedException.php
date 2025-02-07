<?php

class MethodArgumentValueNotImplementedException extends NotImplementedException
{

    public function __construct($methodName, $argName, $argValue, $additionalMessage = '')
    {
        $value = var_export($argValue, true);

        $message = "The $methodName() method's argument $"
            . $argName . " value " . $value 
            . " behavior is not implemented. "
            . $additionalMessage;

        parent::__construct($message);
    }
}