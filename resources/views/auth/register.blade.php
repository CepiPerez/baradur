@extends('layouts.app')

@section('content')
<div class="container" style="margin-top: 16px;">


  <h2>{{$title}}</h2>
  <hr>

  <form action="{{ route('confirm_registration') }}" method="post">
      @csrf
      <div class="form-group">
        <label for="username">{{ __('login.username') }}</label>
        <input type="text" autofocus name="username" class="form-control" value="{{$old->username}}">
      </div>
      <div class="form-group">
        <label for="name">{{ __('login.full_name') }}</label>
        <input type="text" autofocus name="name" class="form-control" value="{{$old->name}}">
      </div>
      <div class="form-group">
        <label for="email">{{ __('login.email') }}</label>
        <input type="email" autofocus name="email" class="form-control" value="{{$old->email}}">
      </div>
      <div class="form-group">
        <label for="password">{{ __('login.password') }}</label>
        <input type="password" autofocus name="password" class="form-control" value="">
      </div>

      <button type="submit" class="btn btn-primary">{{ __('login.register') }}</button>
  </form>

</div>
@endsection
