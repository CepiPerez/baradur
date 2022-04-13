@extends('docs.main')

@section('content')

<h3>Artisan</h3>
<hr>
<h5>Available commands</h5>
<br>
<h4># Migrations</h4>
<br><b>Creating migrations:</b>
<pre><code class="language-bash">php artisan make:migration create_users_table
</code></pre>
<pre><code class="language-bash">php artisan make:migration add_comecolumn_to_users
</code></pre>
<br><b>Applying migrations:</b>
<pre><code class="language-bash">php artisan migrate
</code></pre>
<br><b>Rolling back:</b>
<pre><code class="language-bash">php artisan migrate:rollback
</code></pre>
<br>

<br>
<h4># Crating resources</h4>
<br><b>Creating controllers:</b>
<pre><code class="language-bash">php artisan make:controller PhotoController
</code></pre>
<pre><code class="language-bash">php artisan make:controller PhotoController --model=Photo --resource
</code></pre>
<br><b>Creating models:</b>
<pre><code class="language-bash">php artisan make:model Photo
</code></pre>
<pre><code class="language-bash">php artisan make:model Photo --controller --resource --migration
</code></pre>
<pre><code class="language-bash">php artisan make:model Photo -mcr
</code></pre>
<br>

@endsection
