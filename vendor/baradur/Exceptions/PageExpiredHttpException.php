<?php

class PageExpiredHttpException extends HttpException
{
    public function __construct($message = '', $previous = null, $code = 0, $headers = array())
    {
        parent::__construct(419, $message, $previous, $headers, $code);
    }
}
