@extends('docs.main')

@section('content')

<h3>Getting started</h3>
<hr>
<p>
    <b>{{$title}}</b> is a small framework for PHP 5.1<br>
    It allows you to use the same Laravel's functionallity.
    Of course, just a small part of their power is available here.
    <br><br>
    <b>Features list:</b><br>
    <ul>
        <li>Routing</li>
        <li>Models</li>
        <li>Eloquent</li>
        <li>Collections</li>
        <li>Middlewares</li>
        <li>Policies</li>
        <li>Localization</li>
        <li>Blade</li>
    </ul>
    
    Not all features from each component is available, but the basic ones are
    implemented to make this framework good enough to make decent apps.

</p>



@endsection
