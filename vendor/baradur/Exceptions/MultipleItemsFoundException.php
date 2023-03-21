<?php

class MultipleItemsFoundException extends RuntimeException
{
    public $count;

    public function __construct($count, $code = 0, $previous = null)
    {
        $this->count = $count;

        parent::__construct("$count records were found.", $code, $previous);
    }

    public function getCount()
    {
        return $this->count;
    }
}