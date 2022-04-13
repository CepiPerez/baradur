@extends('docs.main')

@section('content')

<h3>Controllers</h3>
<hr>
<h5>There's two types of Controllers:</h5>
<br>
<h4># Controller</h4>
<p>This controller includes a Token verification wich denies requests based on
the time it was created, or the times it's used.<br>
Both of them are defined in <b>.env</b> file:<br>
<div class="table-container">
    <table class="custom-table">
        <tr>
            <td>HTTP_TOKENS</td>
            <td>Sets the token lifetime</td>
        </tr>
        <tr>
            <td>HTTP_TOKENS_MAX_USE</td>
            <td>Sets the tokens max usage</td>
        </tr>
    </table>
</div>

<br>
<b>Usage:</b>
<pre><code class="language-php7">Class MyController extends Controller {

    // my functions here;
}
</code></pre>
<br>
<br>
<h4># ApiController</h4>
<p>This controller includes a Token verification wich denies requests based on
the time it was created, no matter how many times it's used.<br>
It's defined <b>.env</b> file:<br></p>
<div class="table-container">
    <table class="custom-table">
        <tr>
            <td>API_TOKENS</td>
            <td>Sets the token lifetime</td>
        </tr>
    </table>
</div>
<br>
<b>Usage:</b>
<pre><code class="language-php7">Class MyController extends ApiController {

    // my functions here;
}
</code></pre>
<br><br>
<h5>If you don't want to use tokenVerification, just don't <b>extend</b> your controller,
or set tokenVerification to false:</h5>
<pre><code class="language-php7">Class MyController extends Controller {

    protected $tokenVerification = false;

    // my functions here;
}
</code></pre>
<br>
@endsection
