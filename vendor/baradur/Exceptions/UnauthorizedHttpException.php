<?php

class UnauthorizedHttpException extends HttpException
{
    public function __construct($challenge, $message = '', $previous = null, $code = 0, $headers = array())
    {
        $headers['WWW-Authenticate'] = $challenge;

        parent::__construct(401, $message, $previous, $headers, $code);
    }
}
