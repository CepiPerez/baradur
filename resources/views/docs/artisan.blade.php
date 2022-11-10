@extends('docs.main')

@section('content')

<h3>Artisan</h3>
<hr>
<h5>To check all available options run the following command:</h5>
<pre><code class="language-bash">php artisan</code></pre>
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
<br><b>Creating middleware:</b>
<pre><code class="language-bash">php artisan make:middleware MyMiddleware
</code></pre>
<br><b>Creating policies:</b>
<pre><code class="language-bash">php artisan make:policy MyPolicy
</code></pre>
<br>

<br>
<h4># Other resources</h4>
<br><b>Creating seeders:</b>
<pre><code class="language-bash">php artisan make:seeder PhotoSeeder
</code></pre>
<br><b>Creating factories:</b>
<pre><code class="language-bash">php artisan make:factory PhotoSeeder
</code></pre>
<br><b>Seeding:</b>
<pre><code class="language-bash">php artisan db:seed
</code></pre>
<br>

<br>
<h4># Other commands</h4>
<br><b>Reset migations:</b>
<pre><code class="language-bash">php artisan migrate:reset
</code></pre>
<br><b>Reset and re-apply migrations:</b>
<pre><code class="language-bash">php artisan migrate:fresh
</code></pre>
<br><b>Reset and re-apply migrations with seeds:</b>
<pre><code class="language-bash">php artisan migrate:reset --seed
</code></pre>
<br>

@endsection
