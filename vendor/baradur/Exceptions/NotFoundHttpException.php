<?php

class NotFoundHttpException extends HttpException
{
    public function __construct($message = '', $previous = null, $code = 0, $headers = array())
    {
        parent::__construct(404, $message, $previous, $headers, $code);
    }
}
