<?php

class AccessDeniedHttpException extends HttpException
{
    public function __construct($message = '', $previous = null, $code = 0, $headers = array())
    {
        parent::__construct(403, $message, $previous, $headers, $code);
    }
}
