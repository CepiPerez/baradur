@extends('layouts.app')

@section('content')
<div class="container" style="margin-top: 16px;">


  <h2>{{$title}}</h2>
  <hr>

  <div class="card p-3 text-center">
    <h3 class="mb-0">{{ __('login.thanks_register') }}</h3><hr>
    <h6>{{$reg_message}}</h6>
  </div>
  
  

</div>

@endsection