<?php

class ServiceUnavailableHttpException extends HttpException
{
    public function __construct($message = '', $previous = null, $code = 0, $headers = array())
    {
        parent::__construct(503, $message, $previous, $headers, $code);
    }
}
