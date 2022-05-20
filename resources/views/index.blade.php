@extends('layouts.app')

@section('content')

<link href="{{ asset('assets/css/index_styles.css') }}" rel="stylesheet">


<div class="center-screen">
    
    <img src="{{ asset('assets/logo.ico') }}" alt="" class="align-center h-[64px] lg:h-[128px]">
    <h1 class="main-title text-[300%] lg:text-[400%]">Baradur</h1>


    @if ($data)
    <navbar class="custom-menu">
        @foreach ($data as $key => $val)
        <li class="menu"><a href="{{HOME}}#">{{$key}}</a>
            <ul class="submenu">
                @foreach ($val as $value)
                <li><a href="{{HOME.'/'.$value['url']}}">{{$value['titulo']}}</a></li>
                @endforeach
            </ul>
        </li>
        @endforeach
    </navbar>

    @else

    <navbar class="custom-menu">
        <li class="menu"><a href="docs">Documentation</a></li>
        <li class="menu"><a href="https://github.com/CepiPerez/php-base" target="_blank">Github</a></li>
    </navbar>

    @endif


</div>

@endsection
