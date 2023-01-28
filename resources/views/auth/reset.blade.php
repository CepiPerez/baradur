@extends('layouts.app')

@section('content')
<div class="w-full pt-4 grid place-items-center">

  <div class="bg-white dark:bg-zinc-800 border dark:border-zinc-700 pt-2 pb-4 px-5 rounded shadow w-full max-w-xl">
    <h1 class="text-2xl mt-2 dark:text-gray-100">{{$title}}</h1>
    <hr class="mt-2 mb-3">

    <form action="{{ route('send_reset_password') }}" method="post">
        @csrf

        <div class="mb-4">
          <label class="block text-slate-700 dark:text-zinc-400 font-bold mb-2" for="username">{{ __('login.user_email') }}</label>
          <input class="border shadow dark:bg-zinc-800 dark:text-zinc-100 text-slate-700 rounded w-full py-1.5 px-3 focus:outline-none focus:ring
            @error('id') border-red-500 focus:ring-red-400 dark:focus:ring-red-700 @else border-zinc-300 dark:border-zinc-600 focus:border-sky-500 @enderror" 
            id="username" name="username" autofocus type="text" placeholder="{{ __('login.user_email') }}" value="{{$old->username}}">
        </div>

        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white py-1.5 px-3 rounded">
          {{ __('login.continue') }}
        </button>
    </form>
    
  </div>

</div>
@endsection
