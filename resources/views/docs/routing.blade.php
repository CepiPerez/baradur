@extends('docs.main')

@section('content')

<h3>Routing</h3>
<hr>
<p>
<h5>Routes works just like in Laravel, but Closures only works in PHP >= 5.3<br>
They are defined in <b>web/routes.php</b></h5>
<br>
<p class="card-warning">
   <b>NOTE:</b> Routes should be added using old Laravel method (controller@function)
</p>
<br>
<b>Adding routes:</b>
<pre><code class="language-php7">Route::get('users', 'UsersController@index');</code></pre>
<pre><code class="language-php7">Route::post('users', 'UsersController@store');</code></pre>
<pre><code class="language-php7">Route::get('users/{id}', 'UsersController@show');</code></pre>

<br><b>Addint routes with names:</b>
<pre><code class="language-php7">Route::get('users', 'UsersController@index')->name('users');</code></pre>
<pre><code class="language-php7">Route::put('users/{id}', 'UsersController@update')->name('users.update');</code></pre>

<br><b>Adding routes with assigned middleware:</b>
<pre><code class="language-php7">Route::get('users', 'UsersController@index')->middleware('MyMiddlerare');</code></pre>
<pre><code class="language-php7">Route::put('users/{id}', 'UsersController@update')->name('users.update')->middleware('MyMiddlerare');</code></pre>

<br><b>Grouping routes using same controller:</b>
<pre><code class="language-php7">Route::controller('ProductsController')->group(
    Route::get('products', 'inicio')->name('products.index'),
    Route::get('products/create', 'create')->name('products.create'),
    Route::post('products', 'store')->name('products.store'),
    Route::put('products/{id}', 'update')->name('products.update'),
    Route::get('products/{id}/edit', 'edit'),
    Route::delete('products/{id}', 'destroy')
);</code></pre>

<br><b>Using a view directly:</b>
<pre><code class="language-php7">Route::view('products', 'products_template');</code></pre>
<pre><code class="language-php7">Route::view('docs/{page}', 'docs_{page}');</code></pre>

<br><b>Creating a group using resource:</b>
<pre><code class="language-php7">Route::resource('products', 'ProductsController');</code></pre>

<br><b>Localization for resources</b>
<br><br>
<p class="card-warning">
   <b>NOTE:</b> Although it can be added in routes.php, you can use app/config.php
</p>

<pre><code class="language-php7">Route::resourceVerbs(array(
    'index' => 'inicio',
    'create' => 'crear',
    'store' => 'guardar',
    'show' => 'mostrar',
    'edit' => 'editar',
    'update' => 'modificar',
    'destroy' => 'eliminar',
));</code></pre>
<br>



@endsection
