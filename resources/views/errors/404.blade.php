@extends('layouts.app')

@section('content')
<div class="w-full mt-5">
    <div class="py-3 flex justify-center">

    <!-- <div class="bg-red-600 pl-2 pr-2.5 pt-2 pb-3 mb-3 rounded-full">
        <svg class="h-12 w-12" fill="white" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1490 1322q0 40-28 68l-136 136q-28 28-68 28t-68-28l-294-294-294 294q-28 28-68 28t-68-28l-136-136q-28-28-28-68t28-68l294-294-294-294q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 294 294-294q28-28 68-28t68 28l136 136q28 28 28 68t-28 68l-294 294 294 294q28 28 28 68z"/></svg>
    </div> -->
    
      <svg class="w-20 h-20" viewBox="0 0 24 24" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/">
        <g transform="translate(0 -1028.4)">
          <path d="m3 1031.4v10c0 4.2 3.6322 8 9 10 5.368-2 9-5.8 9-10v-10h-18z" fill="#95a5a6"/>
          <path d="m3 1030.4v10c0 4.2 3.6322 8 9 10 5.368-2 9-5.8 9-10v-10h-18z" fill="#ecf0f1"/>
          <path d="m3 1030.4v10c0 4.2 3.6322 8 9 10v-20h-9z" fill="#bdc3c7"/>
          <path d="m5 1032.4v8c0 3.4 2.8251 6.4 7 8 4.175-1.6 7-4.6 7-8v-8h-14z" fill="#c0392b"/>
          <path d="m12 1032.4v16c4.175-1.6 7-4.6 7-8v-8h-7z" fill="#e74c3c"/>
          <path d="m9.1562 1036.5-1.4062 1.4 2.844 2.9-2.844 2.8 1.4062 1.4 2.8438-2.8 2.844 2.8 1.406-1.4-2.844-2.8 2.844-2.8-1.438-1.5-2.812 2.9-2.8438-2.9z" fill="#c0392b"/>
          <path d="m9.1562 1035.5-1.4062 1.4 2.844 2.9-2.844 2.8 1.4062 1.4 2.8438-2.8 2.844 2.8 1.406-1.4-2.844-2.8 2.844-2.8-1.438-1.5-2.812 2.9-2.8438-2.9z" fill="#ecf0f1"/>
        </g>
      </svg>


    </div>
    <p class="text-center text-red-600">Error {{$error_code}}</p>
    <p class="text-center text-slate-600">@lang('errors.not_found')</p>
</div>
@endsection