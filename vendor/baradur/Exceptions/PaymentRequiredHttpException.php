<?php

class PaymentRequiredHttpException extends HttpException
{
    public function __construct($message = '', $previous = null, $code = 0, $headers = array())
    {
        parent::__construct(402, $message, $previous, $headers, $code);
    }
}
