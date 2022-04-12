@extends('common.app')

@section('content')
<div class="container pt-2">


  <div class="card p-3 align-center m-auto">
    <h2>{{$title}}</h2>
    <hr class="mt-1">

    <form action="{{ route('confirm_reset_password') }}" method="post">
        @csrf
        <div class="form-group">
          <label for="username">{{ __('login.user_email') }}</label>
          <input type="text" autofocus name="username" class="form-control" value="{{$old->username}}">
        </div>
        <div class="form-group">
          <label for="password">{{ __('login.new_password') }}</label>
          <input type="password" autofocus name="password" class="form-control" value="">
        </div>
        <button type="submit" class="btn btn-primary">{{ __('login.continue') }}</button>
    </form>
  </div>

</div>
@endsection
