@extends('layouts.app')

@section('content')
<div class="w-full pt-4 grid place-items-center">

  <div class="bg-white dark:bg-zinc-800 border dark:border-zinc-700 pt-2 pb-4 px-5 rounded shadow w-full max-w-xl">
    <h1 class="text-2xl mt-2 dark:text-zinc-300">{{$title}}</h1>
    <hr class="mt-2 mb-3">

    <h3 class="text-xl dark:text-zinc-400 my-4">{{ __('login.thanks_confirmation') }}</h3>
    <h6 class="dark:text-zinc-400">{{ __('login.can_login') }}</h6>
    <br>
    <button class="bg-cyan-600 hover:bg-cyan-700 text-white py-1.5 px-3 rounded">
      <a href="{{ env('APP_URL') }}">{{ __('login.login') }}</a>
    </button>
  
  </div>  

</div>
@endsection
