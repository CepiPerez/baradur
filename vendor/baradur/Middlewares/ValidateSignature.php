<?php

class ValidateSignature
{
    protected $ignore = array(
        //
    );

    public function handle(Request $request, $next, $relative = null)
    {
        $ignore = property_exists($this, 'except') ? $this->except : $this->ignore;

        if ($request->hasValidSignatureWhileIgnoring($ignore, $relative !== 'relative')) {
            return $request;
        }

        throw new InvalidSignatureException;
    }
}