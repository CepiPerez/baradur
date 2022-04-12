<?php

Class ExceptionHandler
{
    private $message;

    public function __construct($exeption)
    {
        $this->message = $exeption;
    }

    public function getMessage()
    {
        return $this->message;
    }

    
    public static function handleException(Exception $ex)
    {
        return error('', $ex->getMessage());
    }


}