<?php

class AuthResponse
{
    protected $allowed;
    protected $message;
    protected $code;
    protected $status;

    public function __construct($allowed, $message = '', $code = null)
    {
        $this->code = $code;
        $this->allowed = $allowed;
        $this->message = $message;
    }

    public static function allow($message = null, $code = null)
    {
        return new AuthResponse(true, $message, $code);
    }

    public static function deny($message = null, $code = null)
    {
        return new AuthResponse(false, $message, $code);
    }

    public static function denyWithStatus($status, $message = null, $code = null)
    {
        return self::deny($message, $code)->withStatus($status);
    }

    public static function denyAsNotFound($message = null, $code = null)
    {
        return self::denyWithStatus(404, $message, $code);
    }

    public function allowed()
    {
        return $this->allowed;
    }

    public function denied()
    {
        return ! $this->allowed();
    }

    public function message()
    {
        return $this->message;
    }

    public function code()
    {
        return $this->code;
    }

    public function authorize()
    {
        if ($this->denied()) {
            throw (new AuthorizationException($this->message(), $this->code()))
                ->setResponse($this)
                ->withStatus($this->status);
        }

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

    public function status()
    {
        return $this->status;
    }

    public function toArray()
    {
        return [
            'allowed' => $this->allowed(),
            'message' => $this->message(),
            'code' => $this->code(),
        ];
    }

    public function __toString()
    {
        return (string) $this->message();
    }

}