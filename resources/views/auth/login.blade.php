@extends('layouts.app')

@section('content')
<div class="container pt-2">


  <div class="card p-3 align-center m-auto">
    <h2>{{$title}}</h2>
    <hr class="mt-1">

    <form action="{{ route('confirm_login') }}" method="post">
        @csrf
        <div class="form-group">
          <label for="username">{{ __('login.user_email') }}</label>
          <input type="text" autofocus name="username" class="form-control" value="{{$old->username}}">
        </div>
        <div class="form-group">
          <label for="password">{{ __('login.password') }}</label>
          <input type="password" autofocus name="password" class="form-control" value="">
        </div>
        <button type="submit" class="btn btn-primary">{{ __('login.login') }}</button>
    </form>
    <a class="pt-3" href="{{ route('reset_password') }}">{{ __('login.password_forgot') }}</a>
    
  </div>

</div>
@endsection
