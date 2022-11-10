@extends('docs.main')

@section('content')

<h3>Routing</h3>
<hr>
<p>
<h5>Routes works just like in Laravel, including Closures.<br>
They can be added in old way or new way</h5>
<br>
<b>Adding routes:</b>
<pre><code class="">Route::get('users', 'UsersController@index');</code></pre>
<pre><code class="language-php7">Route::post('users', 'UsersController@store');</code></pre>
<pre><code class="language-php7">Route::get('users/{id}', [UsersController:class, 'show']);</code></pre>

<br><b>Addint routes with names:</b>
<pre><code class="language-php7">Route::get('users', 'UsersController@index')->name('users');</code></pre>
<pre><code class="language-php7">Route::put('users/{id}', 'UsersController@update')->name('users.update');</code></pre>

<br><b>Adding routes with assigned middleware:</b>
<pre><code class="language-php7">Route::get('users', 'UsersController@index')->middleware(['auth', 'web']);</code></pre>
<pre><code class="language-php7">Route::put('users/{id}', 'UsersController@update')->name('users.update')->middleware(MyMiddlerare:class);</code></pre>

<br><b>Grouping routes using same controller:</b>
<pre><code class="language-php7">Route::controller('ProductsController')->group(
    Route::get('products', 'index')->name('products.index'),
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
   <b>NOTE:</b> Although you can declare this in routes.php, it's better to use app/Config.php 
   since <b>Artisan</b> checks this one when making controllers.
</p>

<pre><code class="language-php7">Route::resourceVerbs([
    'create' => 'crear',
    'edit' => 'editar',
]);</code></pre>
<br>



@endsection
