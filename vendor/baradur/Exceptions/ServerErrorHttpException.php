<?php

class ServerErrorHttpException extends HttpException
{
    public function __construct($message = '', $previous = null, $code = 0, $headers = array())
    {
        parent::__construct(500, $message, $previous, $headers, $code);
    }
}
