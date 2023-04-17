<?php

trait HandlesAuthorization
{
    protected function allow($message = null, $code = null)
    {
        return AuthResponse::allow($message, $code);
    }

    protected function deny($message = null, $code = null)
    {
        return AuthResponse::deny($message, $code);
    }

    public function denyWithStatus($status, $message = null, $code = null)
    {
        return AuthResponse::denyWithStatus($status, $message, $code);
    }

    public function denyAsNotFound($message = null, $code = null)
    {
        return AuthResponse::denyWithStatus(404, $message, $code);
    }
}