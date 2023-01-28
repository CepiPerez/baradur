@extends('layouts.app')

@section('content')
<div class="w-full pt-4 grid place-items-center">

  <div class="bg-white dark:bg-zinc-800 border dark:border-zinc-700 pt-2 pb-4 px-5 rounded shadow w-full max-w-xl">
    <h1 class="text-2xl mt-2 dark:text-zinc-300">{{$title}}</h1>
    <hr class="mt-2 mb-3">

    <form action="{{ route('confirm_login') }}" method="post" class="pb-4">
        @csrf

        <div class="mb-4">
          <label class="block text-slate-700 dark:text-zinc-400 font-bold mb-2" for="username">{{ __('login.user_email') }}</label>
          <input class="border shadow dark:bg-zinc-900 dark:text-zinc-100 text-slate-700 rounded w-full py-1.5 px-3 focus:outline-none focus:ring
            @if (session('error')) border-red-500 focus:ring-red-400 dark:focus:ring-red-700 @else border-zinc-300 dark:border-zinc-600 focus:border-sky-500 @endif" 
            id="username" name="username" autofocus type="text" placeholder="{{ __('login.user_email') }}" value="{{$old->username}}">
        </div>

        <div class="mb-4">
          <label class="block text-slate-700 dark:text-zinc-400 font-bold mb-2" for="password">{{ __('login.password') }}</label>
          <input class="border shadow dark:bg-zinc-900 dark:text-zinc-100 text-slate-700 rounded w-full py-1.5 px-3 focus:outline-none focus:ring
            @if (session('error')) border-red-500 focus:ring-red-400 dark:focus:ring-red-700 @else border-zinc-300 dark:border-zinc-600 focus:border-sky-500 @endif" 
            id="password" name="password" type="password" placeholder="{{ __('login.password') }}">
        </div>

        <div class="flex items-center my-4">
          <input id="remember" name="remember" type="checkbox" value="true" class="w-4 h-4 text-cyan-600 bg-gray-100 border-gray-300 rounded focus:ring-cyan-500 dark:focus:ring-cyan-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
          <label class="block text-slate-700 dark:text-zinc-400 font-bold ml-2" for="remember">{{ __('login.remember_me') }}</label>
        </div>
         
        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white py-1.5 px-3 rounded">
          {{ __('login.login') }}
        </button>
    </form>
    <a class="text-cyan-600 hover:text-cyan-800" href="{{ route('reset_password') }}">{{ __('login.password_forgot') }}</a>
    
  </div>

</div>
@endsection
