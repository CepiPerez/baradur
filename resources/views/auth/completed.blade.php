@extends('layouts.app')

@section('content')
<div class="container" style="margin-top: 16px;">


  <h2>{{$title}}</h2>
  <hr>

  <div class="card p-3 text-center">
    <h3 class="mb-0">{{ __('login.thanks_confirmation') }}</h3><hr>
    <h6>{{ __('login.can_login') }}<br><br>
    <button class="btn"><a href="{{ HOME }}">{{ __('login.login') }}</a></button>
    </h6>
  </div>
  
  

</div>
@endsection
