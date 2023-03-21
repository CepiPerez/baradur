<?php
class TooManyRequestsHttpException extends HttpException
{
    public function __construct($retryAfter = null, $message = '', $previous = null, $code = 0, $headers = array())
    {
        if ($retryAfter) {
            $headers['Retry-After'] = $retryAfter;
        }

        parent::__construct(429, $message, $previous, $headers, $code);
    }
}
