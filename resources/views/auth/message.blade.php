@extends('layouts.app')

@section('content')
<div class="w-full pt-4 grid place-items-center">

  <div class="bg-white dark:bg-zinc-800 border dark:border-zinc-700 pt-2 pb-4 px-5 rounded shadow w-full max-w-xl">
    <h1 class="text-2xl mt-2 dark:text-zinc-300">{{ $title }}</h1>
    <hr class="mt-2 mb-3">

    <h6 class="dark:text-zinc-400">{{$reg_message}}</h6>
  </div>  

</div>

@endsection