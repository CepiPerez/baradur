@extends('docs.main')

@section('content')

<h3>Localization</h3>
<hr>
<h5>Localization works with <b>json</b> or <b>php</b> translation files</h5>
<br>
<b>Using PHP files:</b>
<pre><code class="language-php7">return array(

    'home' => 'Inicio',
    'login' => 'Ingresar',
    'invalid' => 'Usuario o contraseña incorrecta',
    'validation_required' => 'Usted no ha validado el correo de confirmacion',
    'registered' => 'Usuario registrado',
    'restore' => 'Restaurar usuario'
);
</code></pre><br>

<br><b>Using JSON files:</b>
<pre><code class="language-php7">{
    "Home": "Inicio",
    "Login": "Ingresar",
    "Invalid user or password": "Usuario o contraseña incorrecta",
    "You need to validate confirmation email": "Usted no ha validado el correo de confirmacion",
    "User registered": "Usuario registrado",
    "Restore user": "Restaurar usuario"
}</code></pre><br>

<br><b>Getting translations</b>
<pre><code class="language-php7">echo __('login.invalid')</code></pre>
<pre><code class="language-php7">echo __('messages.welcome', ['name' => 'dayle']);</code></pre><br>

<br><b>Getting translations with plurals</b>
<pre><code class="language-php7">echo trans_choice('messages.apples', 10);</code></pre>
<pre><code class="language-php7">echo trans_choice('time.minutes_ago', 5, ['value' => 5]);</code></pre><br>


@endsection
