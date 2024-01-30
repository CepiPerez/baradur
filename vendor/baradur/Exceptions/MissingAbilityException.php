<?php

class MissingAbilityException extends AuthorizationException
{
    protected $abilities;

    public function __construct($abilities = array(), $message = 'Invalid ability provided.')
    {
        parent::__construct($message);

        $this->abilities = Arr::wrap($abilities);
    }

    public function abilities()
    {
        return $this->abilities;
    }
}