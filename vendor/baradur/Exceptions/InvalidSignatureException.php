<?php

class InvalidSignatureException extends HttpException
{
    public function __construct()
    {
        parent::__construct(403, 'Invalid signature.');
    }
}