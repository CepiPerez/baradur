<?php


class VerifyCsrfToken extends Middleware
{
    # The URIs that should be excluded from CSRF verification.
    protected $except = [
        //
    ];
}
