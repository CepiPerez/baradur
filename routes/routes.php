<?php

# Default Home controller
Route::get('/', 'HomeController@showHome');


# Auth routes
Auth::routes();
