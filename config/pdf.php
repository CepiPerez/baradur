<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baradur PDF Plugin
    |--------------------------------------------------------------------------
    */

    'path' => env('PDF_PATH', 'storage/app/public'),
    'bin' => env('PDF_BIN', 'xvfb-run wkhtmltopdf'),

];
