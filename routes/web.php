<?php

# Default Home controller
Route::get('/', [HomeController::class, 'showHome']);

# Documentation routes
Route::view('docs', 'docs/index');

Route::get('docs/{page}', function() use($page) {
    return view("docs.$page"); 
});

# Auth routes
Auth::routes();

