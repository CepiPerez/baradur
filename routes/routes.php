<?php

# Default Home controller
Route::get('/', 'HomeController@showHome');

# Documentation routes
Route::view('docs', 'docs/index');
Route::view('docs/{page}', 'docs/{page}');

# Auth routes
Auth::routes();
