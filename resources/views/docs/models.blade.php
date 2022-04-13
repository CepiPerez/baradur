@extends('docs.main')

@section('content')

<h3>Models</h3>
<hr>
<h5>Models works the same way (but not all features are implemented)</h5>
<br>
<b>Creating models:</b>
<pre><code class="language-php7">Class Product extends Model {

    // my functions here;
}</code></pre>

<br><b>Primary key:</b>
<pre><code class="language-php7">Class Product extends Model {

    protected $primaryKey = 'code';

}</code></pre>

<br><b>Fillable:</b>
<pre><code class="language-php7">Class Product extends Model {

    protected $fillable = array('id', 'description');

}</code></pre>

<br><b>Setting a custom SQL Connection:</b>
<pre><code class="language-php7">Class Product extends Model {

    protected $connector = array(
        'host' => '192.168.1.1',
        'user' => 'admin',
        'password' => 'admin',
        'database' => 'some_db',
        'port' => 3306);

}</code></pre><br>


@endsection
