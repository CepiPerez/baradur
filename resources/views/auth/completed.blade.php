@extends('layouts.app')

@section('content')
<div class="w-full pt-4 grid place-items-center">

  <div class="bg-white dark:bg-zinc-800 border dark:border-zinc-700 pt-2 pb-4 px-5 rounded shadow w-full max-w-xl">
    <h1 class="text-2xl mt-2 dark:text-zinc-300">{{__('login.thanks_register')}}</h1>
    <hr class="mt-2 mb-3">

    <h3 class="text-xl dark:text-zinc-400">{{ __('login.thanks_confirmation') }}</h3><hr>
    <h6 class="dark:text-zinc-400">{{ __('login.can_login') }}</h6>
    <br>
    <button class="btn"><a href="{{ HOME }}">{{ __('login.login') }}</a></button>
  
  </div>  

</div>
@endsection
