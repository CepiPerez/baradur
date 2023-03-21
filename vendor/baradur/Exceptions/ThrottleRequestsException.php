<?php

class ThrottleRequestsException extends TooManyRequestsHttpException
{
    public function __construct($message = '', $previous = null, $headers = array(), $code = 0)
    {
        parent::__construct(null, $message, $previous, $code, $headers);
    }
}
