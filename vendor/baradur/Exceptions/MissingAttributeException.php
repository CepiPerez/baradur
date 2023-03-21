<?php

class MissingAttributeException extends OutOfBoundsException
{  
    public function __construct($model, $key)
    {
        parent::__construct(sprintf(
            'The attribute [%s] either does not exist or was not retrieved for model [%s].',
            $key, get_class($model)
        ));
    }
}