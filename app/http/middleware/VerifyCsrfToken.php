<?php


class VerifyCsrfToken extends CsrfMiddleware
{
    # The URIs that should be excluded from CSRF verification.
    protected $except = [
        'productos*'
    ];
}

?>