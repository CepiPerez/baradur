@extends('layouts.app')


@section('content')

<link href="{{ asset('assets/css/index_styles.css') }}" rel="stylesheet">

<div class="center-screen">
    
    <img src="{{ asset('assets/logo.png') }}" alt="" class="align-center h-[64px] lg:h-[128px]">
    <h1 class="main-title text-[300%] lg:text-[400%]">Baradur</h1>


    <div class="custom-menu">
        <li class="menu"><a href="docs">Documentation</a></li>
        <li class="menu"><a href="https://github.com/CepiPerez/baradur" target="_blank">Github</a></li>
    </div>

</div>

@endsection
