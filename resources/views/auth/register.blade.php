@extends('layouts.app')

@section('content')
<div class="w-full pt-4 grid place-items-center">

  <div class="bg-white dark:bg-zinc-800 border dark:border-zinc-700 pt-2 pb-4 px-5 rounded shadow w-full max-w-xl">
    <h1 class="text-2xl mt-2 dark:text-zinc-300">{{$title}}</h1>
    <hr class="mt-2 mb-3">

    <form action="{{ route('confirm_registration') }}" method="post">
      @csrf
      <!-- <div class="form-group">
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
      </div> -->

      <div class="mb-4">
        <label class="block text-slate-700 dark:text-zinc-400 font-bold mb-2" for="username">{{ __('login.username') }}</label>
        <input class="border shadow dark:bg-zinc-900 dark:text-zinc-100 text-slate-700 rounded w-full py-1.5 px-3 focus:outline-none focus:ring
          @error('username') border-red-500 focus:ring-red-400 dark:focus:ring-red-700 @else border-zinc-300 dark:border-zinc-600 focus:border-sky-500 @enderror" 
          id="username" name="username" autofocus type="text" placeholder="{{ __('login.username') }}" value="{{$old->username}}">
      </div>

      <div class="mb-4">
        <label class="block text-slate-700 dark:text-zinc-400 font-bold mb-2" for="name">{{ __('login.full_name') }}</label>
        <input class="border shadow dark:bg-zinc-900 dark:text-zinc-100 text-slate-700 rounded w-full py-1.5 px-3 focus:outline-none focus:ring
          @error('name') border-red-500 focus:ring-red-400 dark:focus:ring-red-700 @else border-zinc-300 dark:border-zinc-600 focus:border-sky-500 @enderror" 
          id="name" name="name" autofocus type="text" placeholder="{{ __('login.full_name') }}" value="{{$old->name}}">
      </div>

      <div class="mb-4">
        <label class="block text-slate-700 dark:text-zinc-400 font-bold mb-2" for="email">{{ __('login.email') }}</label>
        <input class="border shadow dark:bg-zinc-900 dark:text-zinc-100 text-slate-700 rounded w-full py-1.5 px-3 focus:outline-none focus:ring
          @error('email') border-red-500 focus:ring-red-400 dark:focus:ring-red-700 @else border-zinc-300 dark:border-zinc-600 focus:border-sky-500 @enderror" 
          id="email" name="email" autofocus type="email" placeholder="{{ __('login.email') }}" value="{{$old->email}}">
      </div>

      <div class="mb-4">
        <label class="block text-slate-700 dark:text-zinc-400 font-bold mb-2" for="password">{{ __('login.password') }}</label>
        <input class="border shadow dark:bg-zinc-900 dark:text-zinc-100 text-slate-700 rounded w-full py-1.5 px-3 focus:outline-none focus:ring
          @error('password') border-red-500 focus:ring-red-400 dark:focus:ring-red-700 @else border-zinc-300 dark:border-zinc-600 focus:border-sky-500 @enderror" 
          id="password" name="password" type="password">
      </div>


      <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white py-1.5 px-3 rounded">
        {{ __('login.register') }}
      </button>
  </form>

</div>
@endsection
