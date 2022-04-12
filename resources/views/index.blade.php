@extends('common.app')

@section('content')

@if ($data)<div class="center-screen">
@else<div class="center-screen pt-5">@endif
    
    <div class="text-center">
        <img src="{{ asset('logo.ico') }}" alt="" height="64px">
        <h1 class="main-title">Baradur</h1>
    </div>

    @if ($data)
    <navbar class="custom-menu">
        @foreach ($data as $key => $val)
        <li class="menu"><a href="#">{{$key}}</a>
            <ul class="submenu">
                @foreach ($val as $value)
                <li><a href="{{$value['url']}}">{{$value['titulo']}}</a></li>
                @endforeach
            </ul>
        </li>
        @endforeach
    </navbar>

    @else

    <navbar class="custom-menu">
        <li class="menu"><a href="docs">Documentation</a>
        <li class="menu"><a href="https://github.com/CepiPerez/php-base" target="_blank">Github</a>
        <li class="menu"><a href="admin">Admin tools</a>
    </navbar>

    @endif


</div>

@endsection
