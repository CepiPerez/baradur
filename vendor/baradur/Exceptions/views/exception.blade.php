<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Exception Error</title>

        <link rel="shortcut icon" href="{{asset('/assets/favicon.ico')}}" type="image/x-icon">
        <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">

        <script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>

        <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark')
        }
        </script>
        
    </head>
    <body class="antialiased bg-slate-100 dark:bg-zinc-900">

        <div class="flex flex-col lg:flex-row bg-white dark:bg-zinc-700 my-5 pb-0 mx-10 shadow-md">
            <div class="px-3 py-3 m-3 grow">
                <div class="flex justify-between text-sm">
                    <div class="bg-red-500 px-3 py-1 align-middle text-white font-bold">{{ get_class($exception) }}</div>
                    <div class="flex flex-row w-fit">
                        <div class="flex text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                            <div class="align-middle py-1 mr-3">PHP {{ phpversion() }}</div>
                            <div class="py-1 mr-1">
                                <img class="grayscale brightness-75 dark:grayscale dark:brightness-100" style="padding-top:1px" height="14" width="14" src="{{asset('assets/logo.png')}}">
                            </div>
                            <div class="align-middle py-1">{{Application::VERSION}}</div>
                        </div>
                    </div>
                </div>
                <div class="w-fit mt-3 mb-0 font-semibold text-lg text-slate-700 dark:text-zinc-300">{{ $message }}</div>
                @if ($query)
                    <div class="bg-gray-100 dark:bg-zinc-600 mt-3 p-2 text-slate-700 dark:text-gray-300">
                        @if ($query)
                            <span class="font-semibold mb-2">SQL: </span>

                            <span class="font-mono text-sm ml-2">{{ $query }}</span>
                        @endif
                    </div>
                @endif
            </div>

            @if ($solution)
                <div class="bg-emerald-300 dark:bg-emerald-500 shadow-md p-6 m-0 w-full lg:w-fit">

                    @if ($solution['title'])
                        <p class="font-semibold text-sm pt-1">{{ $solution['title'] }}</p>
                    @endif
                    @if ($solution['button'])
                        <div class="flex flex-row mt-2">
                            <button class="flex bg-emerald-500 dark:bg-emerald-600 mt-1 pl-2 pr-3 py-1.5 hover:bg-emerald-600 
                                dark:hover:bg-emerald-700 text-white text-xs font-semibold"
                                id="command" run="{{ $solution['run'] }}">
                                <svg class="h-4 w-4 mr-2 pt-0.5" fill="lightgray" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" class="icon"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M865.3 244.7c-.3-.3-61.1 59.8-182.1 180.6l-84.9-84.9 180.9-180.9c-95.2-57.3-217.5-42.6-296.8 36.7A244.42 244.42 0 0 0 419 432l1.8 6.7-283.5 283.4c-6.2 6.2-6.2 16.4 0 22.6l141.4 141.4c6.2 6.2 16.4 6.2 22.6 0l283.3-283.3 6.7 1.8c83.7 22.3 173.6-.9 236-63.3 79.4-79.3 94.1-201.6 38-296.6z"></path> </g></svg>
                                {{ strtoupper($solution['button']) }}
                            </button>
                            <span class="text-red-500 pt-4 ml-3 hidden" id="errorbtn">Error! Try running the command from terminal</span>
                        </div>
                    @endif
                    <p class="pt-3 mb-1 font-light text-sm">{{$solution['description']}}</p>
                    <a href="https://laravel.com/docs/" 
                    class="mt-4 text-emerald-700 hover:text-emerald-900 underline text-xs">Laravel documentation</a>
                </div>
            @endif

        </div>


        <!-- TRACE -->
        <div class="bg-white dark:bg-zinc-700 my-5 mx-10 shadow-md">

            @php
                $fileinfo = pathinfo($exception->getFile());
                //$trace = $exception->getTrace();
            @endphp

            <div class="overflow-hidden">
                <div class="flex flex-row">
                    <div class="w-1/4">
                        <div class="py-3 px-4 text-slate-700 dark:text-zinc-400 border-b dark:border-zinc-500" id="tabs">
                            <button class="bg-gray-200 dark:bg-zinc-800 px-3 py-1 rounded text-xs
                                text-slate-600 hover:text-slate-800 hover:bg-gray-300 
                                dark:text-gray-400 hover:dark:text-gray-200 hover:dark:bg-zinc-900" 
                                onclick="toggleVendorFrames()" id="toggle_btn">
                                Show vendor frames
                            </button>
                        </div>

                        <!-- Code Sidebar -->
                        <div id=tabContent>
                            @foreach ($trace as $tr)
                            <div class="ml-0 py-3 px-4 text-ellipsis overflow-hidden text-sm 
                                border-b dark:border-zinc-500
                                @if($tr['vendor']==1) hidden @endif
                                @if($loop->index==$currentTab) 
                                    bg-red-500 dark:bg-red-700 text-white
                                @else 
                                    cursor-pointer hover:bg-slate-100 hover:dark:bg-zinc-600
                                    dark:text-gray-300
                                @endif"
                                id="tab_head_{{$loop->index}}" onclick="changeTab({{$loop->index}})" 
                                vendor="{{$tr['vendor']}}"
                                >
                                {{ $tr['basename'] }}
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Code content -->
                    @foreach ($trace as $tr)
                        <div class="w-3/4 border-l dark:border-zinc-500 
                            @if($loop->index!=$currentTab) hidden @endif"
                            id="tab_content_{{$loop->index}}">
                        
                            @php
                                $fileinfo = pathinfo($tr['file']);
                            @endphp

                            <div class="w-full text-right text-slate-600 dark:text-zinc-300 px-4 py-4 text-sm">
                                <span class="font-normal">
                                {{-- {{ Str::of($tr['file'])->replace('/var/www/html/', '') }} --}}
                                {{ ExceptionHandler::getClassFilename($tr['file']) }}
                                </span>
                                <span class="font-light">: {{ $tr['line'] }}</span>
                            </div>

                            <div class="overflow-hidden text-sm text-slate-700 dark:text-gray-400">
                                @php 
                                    $current = $tr['line']-10; 
                                    while ($current < 0) {
                                        $current++;
                                    }
                                @endphp
                                @foreach ($tr['content'] as $line_content)
                                <div class="flex flex-row text-xs">
                                    @php $current++; @endphp
                                    <div class="w-16 text-right max-h-6 @if($current==$tr['line']) bg-red-300 dark:bg-red-800 @endif pr-3 text-slate-500 dark:text-gray-500" style="padding-top:2px">{{$current}}</div>
                                    <pre class="w-full overflow-hidden pl-5 max-h-6 @if($current==$tr['line']) bg-red-200 dark:bg-red-900 @endif" style="padding: 4px 1rem 3px 1rem"><code>{{ $line_content }}</code></pre>
                                </div>
                                @endforeach

                                @if ($current < 21)
                                    @while ($current < 21)
                                        <div class="flex flex-row text-xs">
                                            @php $current++; @endphp
                                            <div class="w-16 max-h-6" style="padding-top:2px">&nbsp;</div>
                                            <pre class="w-full max-h-6" style="padding: 4px 1rem 3px 1rem"><code>&nbsp;</code></pre>
                                        </div>
                                    @endwhile
                                @endif


                                {{-- <pre class="w-full overflow-hidden pl-5 @if($current==$line) bg-red-200 dark:bg-red-900 @endif" style="padding: 2px 1rem"><code class="language-php7">{{ implode('', $content) }}</code></pre> --}}
                            </div>


                        </div>
                    @endforeach

                </div>
            </div>

        </div>


        <!-- REQUEST -->
        {{-- <div class="my-5 mx-10">

            <div class="overflow-hidden">
                <div class="flex flex-row">

                    <!-- Sidebar -->
                    <div class="w-1/6">
                        <div class="mt-4 text-slate-500">
                            <div class="ml-0 mt-5 py-2 px-4 text-sm font-bold">
                                REQUEST
                            </div>
                            <div class="flex ml-4 py-1">
                                <svg aria-hidden="true" focusable="false" class="mt-1.5 h-3 w-3" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M32 160h319.9l.0791 72c0 9.547 5.652 18.19 14.41 22c8.754 3.812 18.93 2.078 25.93-4.406l112-104c10.24-9.5 10.24-25.69 0-35.19l-112-104c-6.992-6.484-17.17-8.217-25.93-4.408c-8.758 3.816-14.41 12.46-14.41 22L351.9 96H32C14.31 96 0 110.3 0 127.1S14.31 160 32 160zM480 352H160.1L160 279.1c0-9.547-5.652-18.19-14.41-22C136.9 254.2 126.7 255.9 119.7 262.4l-112 104c-10.24 9.5-10.24 25.69 0 35.19l112 104c6.992 6.484 17.17 8.219 25.93 4.406C154.4 506.2 160 497.5 160 488L160.1 416H480c17.69 0 32-14.31 32-32S497.7 352 480 352z"></path></svg>
                                <div class="ml-3 text-slate-700">Headers</div>
                            </div>
                            <div class="flex ml-4 py-1">
                                <svg aria-hidden="true" focusable="false" class="mt-1.5 h-3 w-3" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M414.8 40.79L286.8 488.8C281.9 505.8 264.2 515.6 247.2 510.8C230.2 505.9 220.4 488.2 225.2 471.2L353.2 23.21C358.1 6.216 375.8-3.624 392.8 1.232C409.8 6.087 419.6 23.8 414.8 40.79H414.8zM518.6 121.4L630.6 233.4C643.1 245.9 643.1 266.1 630.6 278.6L518.6 390.6C506.1 403.1 485.9 403.1 473.4 390.6C460.9 378.1 460.9 357.9 473.4 345.4L562.7 256L473.4 166.6C460.9 154.1 460.9 133.9 473.4 121.4C485.9 108.9 506.1 108.9 518.6 121.4V121.4zM166.6 166.6L77.25 256L166.6 345.4C179.1 357.9 179.1 378.1 166.6 390.6C154.1 403.1 133.9 403.1 121.4 390.6L9.372 278.6C-3.124 266.1-3.124 245.9 9.372 233.4L121.4 121.4C133.9 108.9 154.1 108.9 166.6 121.4C179.1 133.9 179.1 154.1 166.6 166.6V166.6z"></path></svg>
                                <div class="ml-3 text-slate-700">Body</div>
                            </div>
                            <div class="ml-0 mt-5 py-2 px-4 text-sm font-bold">
                                APP
                            </div>
                            <div class="flex ml-4 py-1">
                                <svg aria-hidden="true" focusable="false" class="mt-1.5 h-3 w-3" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M424.1 287c-15.13-15.12-40.1-4.426-40.1 16.97V352H336L153.6 108.8C147.6 100.8 138.1 96 128 96H32C14.31 96 0 110.3 0 128s14.31 32 32 32h80l182.4 243.2C300.4 411.3 309.9 416 320 416h63.97v47.94c0 21.39 25.86 32.12 40.99 17l79.1-79.98c9.387-9.387 9.387-24.59 0-33.97L424.1 287zM336 160h47.97v48.03c0 21.39 25.87 32.09 40.1 16.97l79.1-79.98c9.387-9.391 9.385-24.59-.0013-33.97l-79.1-79.98c-15.13-15.12-40.99-4.391-40.99 17V96H320c-10.06 0-19.56 4.75-25.59 12.81L254 162.7L293.1 216L336 160zM112 352H32c-17.69 0-32 14.31-32 32s14.31 32 32 32h96c10.06 0 19.56-4.75 25.59-12.81l40.4-53.87L154 296L112 352z"></path></svg>
                                <div class="ml-3 text-slate-700">Routing</div>
                            </div>
                            <div class="ml-0 mt-5 py-2 px-4 text-sm font-bold">
                                CONTEXT
                            </div>
                            <div class="flex ml-4 py-1">
                                <svg aria-hidden="true" focusable="false" class="mt-1.5 h-3 w-3" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M224 256c70.7 0 128-57.31 128-128s-57.3-128-128-128C153.3 0 96 57.31 96 128S153.3 256 224 256zM274.7 304H173.3C77.61 304 0 381.6 0 477.3c0 19.14 15.52 34.67 34.66 34.67h378.7C432.5 512 448 496.5 448 477.3C448 381.6 370.4 304 274.7 304z"></path></svg>
                                <div class="ml-3 text-slate-700">User</div>
                            </div>
                            <div class="flex ml-4 py-1">
                                <svg aria-hidden="true" focusable="false" class="mt-1.5 h-3 w-3" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M0 416C0 398.3 14.33 384 32 384H86.66C99 355.7 127.2 336 160 336C192.8 336 220.1 355.7 233.3 384H480C497.7 384 512 398.3 512 416C512 433.7 497.7 448 480 448H233.3C220.1 476.3 192.8 496 160 496C127.2 496 99 476.3 86.66 448H32C14.33 448 0 433.7 0 416V416zM192 416C192 398.3 177.7 384 160 384C142.3 384 128 398.3 128 416C128 433.7 142.3 448 160 448C177.7 448 192 433.7 192 416zM352 176C384.8 176 412.1 195.7 425.3 224H480C497.7 224 512 238.3 512 256C512 273.7 497.7 288 480 288H425.3C412.1 316.3 384.8 336 352 336C319.2 336 291 316.3 278.7 288H32C14.33 288 0 273.7 0 256C0 238.3 14.33 224 32 224H278.7C291 195.7 319.2 176 352 176zM384 256C384 238.3 369.7 224 352 224C334.3 224 320 238.3 320 256C320 273.7 334.3 288 352 288C369.7 288 384 273.7 384 256zM480 64C497.7 64 512 78.33 512 96C512 113.7 497.7 128 480 128H265.3C252.1 156.3 224.8 176 192 176C159.2 176 131 156.3 118.7 128H32C14.33 128 0 113.7 0 96C0 78.33 14.33 64 32 64H118.7C131 35.75 159.2 16 192 16C224.8 16 252.1 35.75 265.3 64H480zM160 96C160 113.7 174.3 128 192 128C209.7 128 224 113.7 224 96C224 78.33 209.7 64 192 64C174.3 64 160 78.33 160 96z"></path></svg>
                                <div class="ml-3 text-slate-700">Versions</div>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="w-5/6 bg-white dark:bg-zinc-700 shadow-md">
                        
                        <!-- Request content -->
                        <div class="p3 mb-4">
                        
                            <div class="ml-0 mt-5 px-4 text-sm font-bold text-slate-500">
                                REQUEST
                            </div>
                            <div class="flex">
                                <div class="ml-0 mt-3 px-4 font-bold text-indigo-600">
                                    {{ request()->fullUrl() }}
                                </div>
                                <div class="ml-0 px-2 mt-3 pt-0.5 text-xs text-indigo-600 border border-indigo-400 rounded">
                                    {{ request()->method() }}
                                </div>
                            </div>
                            <div class="overflow-x-scroll text-sm bg-gray-100 dark:bg-zinc-600 mt-3 mx-4 py-2 px-4 text-indigo-600 dark:text-gray-300">
                                <span class="text-red-500">curl</span> "{{ request()->fullUrl() }}" <span class="ml-1 text-slate-800">\<span><br>  
                                <span class="pl-4 leading-6 text-indigo-800 mr-2">-X</span>
                                <span class="pl-1 leading-6 text-indigo-600">
                                    {{ request()->method() }}
                                </span><span class="ml-1 text-slate-800">\<span><br>
                                @foreach (request()->headers() as $key => $val)
                                    <p class="pl-4 leading-6 text-indigo-800 mr-2 whitespace-nowrap">-H
                                    <span class="pl-2 leading-6 text-indigo-600">
                                        '{{ $key.':  '.$val }}'
                                   </span><span class="ml-1 text-slate-800">\<span></p>
                                @endforeach
                            </div>
    
                            <div class="flex mx-4 mt-5 py-1 text-indigo-400">
                                <div class="mr-3 font-bold text-indigo-600">Headers</div>
                                <svg aria-hidden="true" focusable="false" class="mt-1.5 h-3 w-3" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M32 160h319.9l.0791 72c0 9.547 5.652 18.19 14.41 22c8.754 3.812 18.93 2.078 25.93-4.406l112-104c10.24-9.5 10.24-25.69 0-35.19l-112-104c-6.992-6.484-17.17-8.217-25.93-4.408c-8.758 3.816-14.41 12.46-14.41 22L351.9 96H32C14.31 96 0 110.3 0 127.1S14.31 160 32 160zM480 352H160.1L160 279.1c0-9.547-5.652-18.19-14.41-22C136.9 254.2 126.7 255.9 119.7 262.4l-112 104c-10.24 9.5-10.24 25.69 0 35.19l112 104c6.992 6.484 17.17 8.219 25.93 4.406C154.4 506.2 160 497.5 160 488L160.1 416H480c17.69 0 32-14.31 32-32S497.7 352 480 352z"></path></svg>
                            </div>
                            @foreach (request()->headers() as $key => $val)
                                <div class="flex space-y-2">
                                    <span class="w-1/6 pl-4 py-1 mt-2 mr-3 text-sm text-slate-700 truncate">{{ $key }}</span>
                                    <span class="w-5/6 pl-2 py-1 mr-4 pr-3 text-sm bg-gray-100 text-slate-700 truncate overflow-hidden">
                                        {{ $val }}
                                    </span>
                                </div>
                            @endforeach
    
                            <div class="flex mx-4 mt-5 py-1 text-indigo-400">
                                <div class="mr-3 font-bold text-indigo-600">Body</div>
                                <svg aria-hidden="true" focusable="false" class="mt-1.5 h-3 w-3" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M414.8 40.79L286.8 488.8C281.9 505.8 264.2 515.6 247.2 510.8C230.2 505.9 220.4 488.2 225.2 471.2L353.2 23.21C358.1 6.216 375.8-3.624 392.8 1.232C409.8 6.087 419.6 23.8 414.8 40.79H414.8zM518.6 121.4L630.6 233.4C643.1 245.9 643.1 266.1 630.6 278.6L518.6 390.6C506.1 403.1 485.9 403.1 473.4 390.6C460.9 378.1 460.9 357.9 473.4 345.4L562.7 256L473.4 166.6C460.9 154.1 460.9 133.9 473.4 121.4C485.9 108.9 506.1 108.9 518.6 121.4V121.4zM166.6 166.6L77.25 256L166.6 345.4C179.1 357.9 179.1 378.1 166.6 390.6C154.1 403.1 133.9 403.1 121.4 390.6L9.372 278.6C-3.124 266.1-3.124 245.9 9.372 233.4L121.4 121.4C133.9 108.9 154.1 108.9 166.6 121.4C179.1 133.9 179.1 154.1 166.6 166.6V166.6z"></path></svg>
                            </div>
                            <div class="text-sm bg-gray-100 dark:bg-zinc-600 mt-3 mx-4 py-2 px-4 text-indigo-600 dark:text-gray-300">
                                @php $body = request()->all(); unset($body['ruta']); @endphp
                                {{ json_encode($body) }}
                            </div>
    
                        </div>
    
                        <!-- App content -->
                        <div class="p3 mb-4 border-t">
                            
                            <div class="ml-0 mt-5 px-4 text-sm font-bold text-slate-500">
                                APP
                            </div>
                            <div class="flex mx-4 mt-2 py-1 text-indigo-400">
                                <div class="mr-3 font-bold text-indigo-600">Routing</div>
                                <svg aria-hidden="true" focusable="false" class="mt-1.5 h-3 w-3" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M424.1 287c-15.13-15.12-40.1-4.426-40.1 16.97V352H336L153.6 108.8C147.6 100.8 138.1 96 128 96H32C14.31 96 0 110.3 0 128s14.31 32 32 32h80l182.4 243.2C300.4 411.3 309.9 416 320 416h63.97v47.94c0 21.39 25.86 32.12 40.99 17l79.1-79.98c9.387-9.387 9.387-24.59 0-33.97L424.1 287zM336 160h47.97v48.03c0 21.39 25.87 32.09 40.1 16.97l79.1-79.98c9.387-9.391 9.385-24.59-.0013-33.97l-79.1-79.98c-15.13-15.12-40.99-4.391-40.99 17V96H320c-10.06 0-19.56 4.75-25.59 12.81L254 162.7L293.1 216L336 160zM112 352H32c-17.69 0-32 14.31-32 32s14.31 32 32 32h96c10.06 0 19.56-4.75 25.59-12.81l40.4-53.87L154 296L112 352z"></path></svg>
                            </div>

                            <div class="flex space-y-2">
                                <span class="w-1/6 pl-4 py-1 mt-2 mr-3 text-sm text-slate-700 truncate">Controller</span>
                                <span class="w-5/6 pl-2 py-1 mr-4 pr-3 text-sm bg-gray-100 text-slate-700 truncate overflow-hidden">
                                    {{ request()->route->controller.'@'.request()->route->func }}
                                </span>
                            </div>

                            <div class="flex space-y-2">
                                <span class="w-1/6 pl-4 py-1 mt-2 mr-3 text-sm text-slate-700 truncate">Route name</span>
                                <span class="w-5/6 pl-2 py-1 mr-4 pr-3 text-sm bg-gray-100 text-slate-700 truncate overflow-hidden">
                                    {{ request()->route->name }}
                                </span>
                            </div>

                            <div class="flex space-y-2">
                                <span class="w-1/6 pl-4 py-1 mt-2 mr-2 text-sm text-slate-700 truncate">Middleware</span>
                                <div class="w-5/6 grid grid-col-1 ml-0 space-y-2">
                                    @foreach(request()->route->middleware as $m)
                                        <span class="pl-2 py-1 mr-4 pr-3 text-sm bg-gray-100 text-slate-700 truncate overflow-hidden">
                                            {{ $m }}
                                        </span>
                                    @endforeach                                
                                </div>
                            </div>

                        </div>


                        <!-- Context content -->
                        <div class="p3 mb-4 border-t">
                                
                            <div class="ml-0 mt-5 px-4 text-sm font-bold text-slate-500">
                                CONTEXT
                            </div>

                            <!-- User -->
                            <div class="flex mx-4 mt-2 py-1 text-indigo-400">
                                <div class="mr-3 font-bold text-indigo-600">User</div>
                                <svg aria-hidden="true" focusable="false" class="mt-1.5 h-3 w-3" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M224 256c70.7 0 128-57.31 128-128s-57.3-128-128-128C153.3 0 96 57.31 96 128S153.3 256 224 256zM274.7 304H173.3C77.61 304 0 381.6 0 477.3c0 19.14 15.52 34.67 34.66 34.67h378.7C432.5 512 448 496.5 448 477.3C448 381.6 370.4 304 274.7 304z"></path></svg>
                            </div>

                            <div class="flex ml-3 mt-2">
                                <img class="inline-block h-9 w-9 mt-1 rounded-full" alt="admin@admin.com" src="https://gravatar.com/avatar/64e1b8d34f425d19e1ee2ea7236d3028/?s=240">
                                <div class="ml-3 mt-0 p-0">
                                    <p class="font-bold text-sm text-slate-700">{{Auth::user()->username}}</p>
                                    <p class="text-sm text-slate-700 truncate">{{Auth::user()->email}}</p>
                                </div>
                            </div>

                            <div class="m-4 p-3 bg-gray-100 text-slate-700">
                                {{ str_replace('{', '{<br><span class="ml-3">', 
                                    str_replace(',', ',</span><br><span class="ml-3">',
                                    str_replace('}', '</span><br>}',
                                    json_encode(Auth::user()->toArray())
                                ))) }}
                            </div>


                             <!-- Versions -->
                             <div class="flex mx-4 mt-2 py-1 text-indigo-400">
                                <div class="mr-3 font-bold text-indigo-600">Versions</div>
                                <svg aria-hidden="true" focusable="false" class="mt-1.5 h-3 w-3" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M0 416C0 398.3 14.33 384 32 384H86.66C99 355.7 127.2 336 160 336C192.8 336 220.1 355.7 233.3 384H480C497.7 384 512 398.3 512 416C512 433.7 497.7 448 480 448H233.3C220.1 476.3 192.8 496 160 496C127.2 496 99 476.3 86.66 448H32C14.33 448 0 433.7 0 416V416zM192 416C192 398.3 177.7 384 160 384C142.3 384 128 398.3 128 416C128 433.7 142.3 448 160 448C177.7 448 192 433.7 192 416zM352 176C384.8 176 412.1 195.7 425.3 224H480C497.7 224 512 238.3 512 256C512 273.7 497.7 288 480 288H425.3C412.1 316.3 384.8 336 352 336C319.2 336 291 316.3 278.7 288H32C14.33 288 0 273.7 0 256C0 238.3 14.33 224 32 224H278.7C291 195.7 319.2 176 352 176zM384 256C384 238.3 369.7 224 352 224C334.3 224 320 238.3 320 256C320 273.7 334.3 288 352 288C369.7 288 384 273.7 384 256zM480 64C497.7 64 512 78.33 512 96C512 113.7 497.7 128 480 128H265.3C252.1 156.3 224.8 176 192 176C159.2 176 131 156.3 118.7 128H32C14.33 128 0 113.7 0 96C0 78.33 14.33 64 32 64H118.7C131 35.75 159.2 16 192 16C224.8 16 252.1 35.75 265.3 64H480zM160 96C160 113.7 174.3 128 192 128C209.7 128 224 113.7 224 96C224 78.33 209.7 64 192 64C174.3 64 160 78.33 160 96z"></path></svg>
                            </div>

                            <div class="flex space-y-2">
                                <span class="w-1/6 pl-4 py-1 mt-2 mr-3 text-sm text-slate-700 truncate">PHP Version</span>
                                <span class="w-5/6 pl-2 py-1 mr-4 pr-3 text-sm bg-gray-100 text-slate-700 truncate overflow-hidden">
                                    {{ phpversion() }}
                                </span>
                            </div>

                            <div class="flex space-y-2">
                                <span class="w-1/6 pl-4 py-1 mt-2 mr-3 text-sm text-slate-700 truncate">Baradur Version</span>
                                <span class="w-5/6 pl-2 py-1 mr-4 pr-3 text-sm bg-gray-100 text-slate-700 truncate overflow-hidden">
                                    {{ Application::VERSION }}
                                </span>
                            </div>

                            <div class="flex space-y-2">
                                <span class="w-1/6 pl-4 py-1 mt-2 mr-3 text-sm text-slate-700 truncate">Baradur Locale</span>
                                <span class="w-5/6 pl-2 py-1 mr-4 pr-3 text-sm bg-gray-100 text-slate-700 truncate overflow-hidden">
                                    {{ config('app.locale') }}
                                </span>
                            </div>

                            <div class="flex space-y-2">
                                <span class="w-1/6 pl-4 py-1 mt-2 mr-2 text-sm text-slate-700 truncate">App Debug</span>
                                @if (config('app.debug'))
                                <div class="ml-0 px-2 mt-3 pt-2 w-fit text-xs font-mono bg-emerald-50 text-emerald-600 rounded">
                                    true
                                </div>
                                @else
                                <div class="ml-0 px-2 mt-3 pt-2 w-fit text-xs font-mono bg-red-50 text-red-600 rounded">
                                    false
                                </div>
                                @endif
                            </div>

                            <div class="flex space-y-2">
                                <span class="w-1/6 pl-4 py-1 mt-2 mr-3 text-sm text-slate-700 truncate">App Env</span>
                                <span class="w-5/6 pl-2 py-1 mr-4 pr-3 text-sm bg-gray-100 text-slate-700 truncate overflow-hidden">
                                    @endphp {{ config('app.env') }}
                                </span>
                            </div>

                        </div>


                    </div>

                    
                </div>
                    

                    
                    
                </div>
            </div>

        </div> --}}

        <script>

            var tabCount = {{ count($trace) }};

            var hideFrames = true;

            function changeTab(index) {
                
                for (let i=1; i <= tabCount; i++)
                {
                    if (i==index) {
                        $('#tab_head_'+i).addClass('bg-red-500').addClass('dark:bg-red-700').addClass('text-white')
                            .removeClass('cursor-pointer').removeClass('hover:bg-slate-100')
                            .removeClass('hover:dark:bg-zinc-600').removeClass('dark:text-gray-300')
                            .removeClass('text-slate-700').removeClass('dark:text-zinc-400')
                        $('#tab_content_'+i).removeClass('hidden')
                    } else {
                        $('#tab_head_'+i).removeClass('bg-red-500').removeClass('dark:bg-red-700').removeClass('text-white')
                            .addClass('cursor-pointer').addClass('hover:bg-slate-100')
                            .addClass('hover:dark:bg-zinc-600').addClass('dark:text-gray-300')
                            .addClass('text-slate-700').addClass('dark:text-zinc-400')
                        $('#tab_content_'+i).addClass('hidden')
                    }
                }
            }

            function toggleVendorFrames()
            {
                hideFrames = !hideFrames;

                var firstVisible = 0;

                $('#tabContent').children().each(function() {
                  if ($(this).attr('vendor')==1 && hideFrames) {
                    $(this).addClass('hidden')
                  } else {
                    $(this).removeClass('hidden')

                    if (firstVisible==0) {
                        firstVisible = $(this).attr('id')
                    }
                  }
                })

                if (hideFrames) {
                    $('#toggle_btn').text('Show vendor frames')
                    $('#'+firstVisible).click();
                } else {
                    $('#toggle_btn').text('Hide vendor frames')
                }

            }

            $(document).ready(function(e) {

                $("#command").on('click', function () {

                    var command = $('#command').attr('run').split('|');

                    if (command[0] == 'Artisan') {

                        $.ajax({
                            url: '{{config("app.url")}}/framework/artisan/'+command[1],
                            type: "GET",
                            dataType: 'json',
                            error: function (data) {
                               $('#errorbtn').removeClass('hidden');
                            },
                            success: function (data) {
                                console.log(data)
                                if (data.result=='true') {
                                    $('#errorbtn').addClass('hidden');
                                    location.reload();
                                } else {
                                    $('#errorbtn').removeClass('hidden');
                                }
                            }
                        })
                    }

                });
            })
        </script>
    
    </body>
</html>
