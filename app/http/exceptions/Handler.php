<?php

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     * 
     * NOTE: Not implemented yet
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * NOTE: Not implemented yet
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'descripcion'
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * NOTE: use renderable() only, reportable() method not implemented yet
     */
    public function register()
    {
        $this->renderable(function (ModelNotFoundException $e, $request) {

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Object not found'], 404);
            }
        });
    }
}
