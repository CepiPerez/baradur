<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{config('app.name')}}</title>

        <link rel="shortcut icon" href="{{asset('/assets/favicon.ico')}}" type="image/x-icon">
        <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">

        <link rel="stylesheet" href="{{ asset('assets/css/codehighlight.css') }}">

        <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark')
        }
        </script>

    </head>
    <body class="antialiased bg-gray-100 dark:bg-zinc-900">

        <div class="flex flex-row bg-red-200 dark:bg-red-900 my-4 mx-10 shadow-md">
            <div class="px-3 py-3 w-full m-3">
                <div class="flex justify-between text-sm">
                    <div class="bg-red-500 px-3 py-1 align-middle text-white font-bold">{{Str::title($etype)}} Error</div>
                    <div class="flex flex-row w-fit">
                        <div class="flex text-xs text-slate-600 dark:text-zinc-300 mt-0.5">
                            <div class="align-middle py-1 mr-3">PHP {{ phpversion() }}</div>
                            <div class="py-1 mr-1">
                                <img class="grayscale brightness-75 dark:grayscale dark:brightness-100" style="padding-top:1px" height="14" width="14" src="{{asset('assets/logo.png')}}">
                            </div>
                            <div class="align-middle py-1">{{Application::VERSION}}</div>
                        </div>
                    </div>
                </div>
                <div class="w-fit mt-3 font-semibold text-lg text-slate-900 dark:text-zinc-300">{{ $message }}</div>
            </div>
            {{-- <div class="bg-sky-400 px-6 py-6 w-1/3">
                <div class="py-1 w-fit text-sm font-semibold">
                    {{ Str::of( get_class($exception) )->snake()
                        ->replace('_', ' ')->title()->replace('Exception', '')
                        ->replace('P D O', 'PDO') }}
                </div>
                <div class="w-fit mt-3 font-semibold">:D</div>
            </div> --}}
        </div>

        <div class="bg-white dark:bg-zinc-700 my-4 mx-10 shadow-md">

            @php
                $fileinfo = pathinfo($file);
            @endphp

            <div class="w-full text-right text-slate-600 dark:text-zinc-300 px-4 py-4 text-sm">
                <span class="font-normal">
                {{ ExceptionHandler::getClassFilename($file) }}
                </span>
                <span class="font-light">: {{ $line }}</span>
            </div>

            <div class="overflow-hidden text-sm text-slate-700 dark:text-gray-400">
                @php 
                    $current = $line-10; 
                    while ($current < 0) {
                        $current++;
                    }
                @endphp
                @foreach ($content as $line_content)
                <div class="flex flex-row text-xs">
                    @php $current++; @endphp
                    <div class="w-16 text-right max-h-6 @if($current==$line) bg-red-300 dark:bg-red-800 @endif pr-3 text-slate-500 dark:text-gray-500" style="padding-top:2px">{{$current}}</div>
                    <pre class="w-full overflow-hidden pl-5 max-h-6 @if($current==$line) bg-red-200 dark:bg-red-900 @endif" style="padding: 4px 1rem 3px 1rem"><code>{{ $line_content }}</code></pre>
                </div>
                @endforeach
                {{-- <pre class="w-full overflow-hidden pl-5 @if($current==$line) bg-red-200 dark:bg-red-900 @endif" style="padding: 2px 1rem"><code class="language-php7">{{ implode('', $content) }}</code></pre> --}}
            </div>

        </div>

        {{-- <script src="{{ asset('assets/js/highlight.min.js') }}"></script>
        <script>hljs.initHighlightingOnLoad();</script> --}}
    
    </body>
</html>
