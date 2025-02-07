<?php

class NotImplementedException extends RuntimeException
{
    const INTL_INSTALL_MESSAGE = 'Please install the "intl" extension for full localization capabilities.';

    public function __construct($message)
    {
        parent::__construct($message.' '.self::INTL_INSTALL_MESSAGE);
    }
}