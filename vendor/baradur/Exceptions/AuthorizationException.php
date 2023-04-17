<?php

class AuthorizationException extends Exception
{
    protected $response;
    protected $status;

    public function __construct($message = null, $code = null, Throwable $previous = null)
    {
        parent::__construct($message ? $message : 'This action is unauthorized.', 0, $previous);

        $this->code = $code ?: 0;
    }

    public function response()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    public function withStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    public function asNotFound()
    {
        return $this->withStatus(404);
    }

    public function hasStatus()
    {
        return $this->status !== null;
    }

    public function status()
    {
        return $this->status;
    }

    public function toResponse()
    {
        return AuthResponse::deny($this->message, $this->code)->withStatus($this->status);
    }
}