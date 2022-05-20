<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <link rel="shortcut icon" href="{{ asset('assets/logo.ico') }}" type="image/x-icon">

    <title>{{$app_name}}</title>

    
    @stack('css')
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->

    <script src="{{ asset('assets/js/alpine-3.10.2.js') }}" defer></script>

    @verbatim
    <style>
      * { font-size: 15px; }
      .disclaimer { display: none; }
      html { position:relative; min-height: 100vh; }
      .pagination { margin-bottom: 4.5rem; }
      @media print { .no_print {display: none; } }
    </style>
    @endverbatim

    <script>
      var currentTheme;
      if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
        currentTheme = 'dark';
      } else {
        document.documentElement.classList.remove('dark')
        currentTheme = 'light';
      }
    </script>

  </head>
  
  <body class="bg-slate-50 dark:bg-zinc-900">

  
    
    <main class="mb-6">

      <!-- Success toast -->
      @if ( session('message') )
      <x-toast-success>
        {{ session('message') }}
      </x-toast-success>
      @endif

      <!-- Error toast -->
      @if ( session('error') )
      <x-toast-danger>
        {{ session('error') }}
      </x-toast-danger>
      @endif

      <!-- Multiple errors toast -->
      @if ( $errors->any() )
      <x-toast-danger>
        @foreach ($errors->all() as $errortext)
          {{ $errortext }} <br>
        @endforeach
      </x-toast-danger>
      @endif


      <!-- Navigation bar -->
      <nav class="no_print bg-sky-600 dark:bg-zinc-800 px-2 md:px-4">

          <div class="relative flex items-center justify-between h-14 ">

              <div class="flex justify-start">
                <img class="block h-8 w-auto ml-1 pt-1.5" src="{{asset('assets/logo.ico')}}" alt="">
                <p class="text-2xl text-white py-1 px-3">{{$app_name}}</p>
              </div>
              
              <div class="flex justify-end mx-0 px-0">
                
                <!-- Theme toggle -->
                <button id="theme-toggle"
                  type="button" class="p-2 text-xs font-medium rounded-lg border
                  border-sky-500 text-sky-300 hover:text-sky-200 hover:bg-sky-500 
                  dark:border-zinc-600 dark:text-zinc-400 dark:hover:text-zinc-300 dark:hover:bg-zinc-600 
                  focus:z-10 focus:outline-none" style="width:32px; height:32px;">

                  <svg id="theme-toggle-dark-icon" class="hidden w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                  <svg id="theme-toggle-light-icon" class="hidden w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>                      

                <!-- User menu -->
                @if ( Route::has('login') )
                <div class="w-fit right-0 flex items-center pl-2 pr-2 sm:static ml-1">

                    @if ( Auth::user() )
                    <!-- Profile dropdown -->
                    <div class="ml-1 relative" x-data="{dropdown:false}" style="z-index:9;">
                        <button x-on:click="dropdown=!dropdown" type="button" class="max-w-xs rounded-full flex items-center text-sm focus:outline-none focus:ring-1 
                            focus:ring-offset-2 text-sky-200 hover:text-white-100 dark:text-zinc-300 hover:dark:text-zinc-100 
                            bg-sky-700 hover:bg-sky-500 dark:bg-zinc-700 hover:dark:bg-zinc-600 focus:ring-offset-sky-800" 
                            id="user-menu-button" aria-expanded="false" aria-haspopup="false">
                            <p class="py-1.5 pl-4 pr-2 hidden sm:block">{{ Auth::user()->name }}</p>
                            <img class="h-8 w-8 rounded-full" src="{{ Auth::user()->image }}" alt=""/>
                        </button>
                        <div x-show="dropdown" x-on:click.away="dropdown=false" :class="{'hidden':!dropdown}" 
                            class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white 
                            dark:bg-zinc-600 ring-1 ring-black ring-opacity-5 focus:outline-none overflow-hidden" 
                            role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">

                            <!-- Active: "bg-slate-100", Not Active: "" -->
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-sky-100 hover:text-sky-600 
                            dark:text-zinc-200 hover:dark:text-zinc-100 dark:hover:bg-zinc-500" 
                            role="menuitem" tabindex="-1" id="user-menu-item-0">Your Profile</a>

                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-sky-100 hover:text-sky-600 
                            dark:text-zinc-200 hover:dark:text-zinc-100 dark:hover:bg-zinc-500" 
                            role="menuitem" tabindex="-1" id="user-menu-item-1">Settings</a>

                            <a href="{{ route('logout') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-sky-100 hover:text-sky-600 
                            dark:text-zinc-200 hover:dark:text-zinc-100 dark:hover:bg-zinc-500" 
                            role="menuitem" tabindex="-1" id="user-menu-item-2">@lang('login.logout')</a>
                        </div>
                    </div>
                    @else
                    <a href="{{ route('login') }}" class="text-sky-200 bg-sky-700 hover:bg-sky-800 
                        hover:text-sky-100 dark:bg-zinc-900 hover:dark:bg-zinc-600
                        dark:text-zinc-400 hover:dark:text-zinc-200 
                        ml-1 px-3 py-2 rounded-md text-sm font-medium hover:no-underline" 
                        aria-current="page">@lang('login.login')</a>

                    @endif

                </div>
                @endif

              </div>  


          </div>
          
      </nav>

      <!-- Breadcrumb -->
      @if ($breadcrumb)
      <ol class="flex h-9 items-center bg-slate-200 px-3 md:px-5 dark:bg-zinc-700 text-sm">
        @foreach ($breadcrumb as $key => $value)
          @if (!$loop->first)
          <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
          @endif
          @if ($value == '#')
          <li class="breadcrumb-item dark:text-gray-200">{{$key}}</li>
          @else
          <li class="breadcrumb-item active">
            <a class="text-sky-700 dark:text-cyan-500 hover:no-underline" href="{{HOME.$value}}">{{$key}}</a>
          </li>
          @endif
        @endforeach
      </ol>
      @endif

      <!-- Main content -->
      <div class="mx-2 pr-0.5 md:mx-5">

        @yield('content')

      </div>
      
      
    </main>
    
    <footer class="bg-gray-600 dark:bg-zinc-800 w-full absolute bottom-0 pt-2 pb-3 px-5">
      <div class="flex justify-center md:justify-between text-gray-400 dark:text-zinc-500">
        <div class="hidden md:flex my-1">
          <span>© 2022 Matias Perez - cepiperez@gmail.com</span>
        </div>

        <div class="flex pt-2">
          <a href="" class="no-underline hover:text-gray-300 dark:hover:text-zinc-400"> <!-- Facebook -->
            <svg class="w-5 h-5 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M400 32H48A48 48 0 0 0 0 80v352a48 48 0 0 0 48 48h137.25V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.27c-30.81 0-40.42 19.12-40.42 38.73V256h68.78l-11 71.69h-57.78V480H400a48 48 0 0 0 48-48V80a48 48 0 0 0-48-48z"/></svg>
          </a>
          <a href="" class="no-underline hover:text-gray-300 dark:hover:text-zinc-400"> <!-- Twitter -->
            <svg class="w-5 h-5 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm-48.9 158.8c.2 2.8.2 5.7.2 8.5 0 86.7-66 186.6-186.6 186.6-37.2 0-71.7-10.8-100.7-29.4 5.3.6 10.4.8 15.8.8 30.7 0 58.9-10.4 81.4-28-28.8-.6-53-19.5-61.3-45.5 10.1 1.5 19.2 1.5 29.6-1.2-30-6.1-52.5-32.5-52.5-64.4v-.8c8.7 4.9 18.9 7.9 29.6 8.3a65.447 65.447 0 0 1-29.2-54.6c0-12.2 3.2-23.4 8.9-33.1 32.3 39.8 80.8 65.8 135.2 68.6-9.3-44.5 24-80.6 64-80.6 18.9 0 35.9 7.9 47.9 20.7 14.8-2.8 29-8.3 41.6-15.8-4.9 15.2-15.2 28-28.8 36.1 13.2-1.4 26-5.1 37.8-10.2-8.9 13.1-20.1 24.7-32.9 34z"/></svg>
          </a>
          <a href="" class="no-underline hover:text-gray-300 dark:hover:text-zinc-400"> <!-- Instagram -->
            <svg class="w-5 h-5 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224,202.66A53.34,53.34,0,1,0,277.36,256,53.38,53.38,0,0,0,224,202.66Zm124.71-41a54,54,0,0,0-30.41-30.41c-21-8.29-71-6.43-94.3-6.43s-73.25-1.93-94.31,6.43a54,54,0,0,0-30.41,30.41c-8.28,21-6.43,71.05-6.43,94.33S91,329.26,99.32,350.33a54,54,0,0,0,30.41,30.41c21,8.29,71,6.43,94.31,6.43s73.24,1.93,94.3-6.43a54,54,0,0,0,30.41-30.41c8.35-21,6.43-71.05,6.43-94.33S357.1,182.74,348.75,161.67ZM224,338a82,82,0,1,1,82-82A81.9,81.9,0,0,1,224,338Zm85.38-148.3a19.14,19.14,0,1,1,19.13-19.14A19.1,19.1,0,0,1,309.42,189.74ZM400,32H48A48,48,0,0,0,0,80V432a48,48,0,0,0,48,48H400a48,48,0,0,0,48-48V80A48,48,0,0,0,400,32ZM382.88,322c-1.29,25.63-7.14,48.34-25.85,67s-41.4,24.63-67,25.85c-26.41,1.49-105.59,1.49-132,0-25.63-1.29-48.26-7.15-67-25.85s-24.63-41.42-25.85-67c-1.49-26.42-1.49-105.61,0-132,1.29-25.63,7.07-48.34,25.85-67s41.47-24.56,67-25.78c26.41-1.49,105.59-1.49,132,0,25.63,1.29,48.33,7.15,67,25.85s24.63,41.42,25.85,67.05C384.37,216.44,384.37,295.56,382.88,322Z"/></svg>
          </a>
          <a href="" class="no-underline hover:text-gray-300 dark:hover:text-zinc-400"> <!-- Linkedin -->
            <svg class="w-5 h-5 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M416 32H31.9C14.3 32 0 46.5 0 64.3v383.4C0 465.5 14.3 480 31.9 480H416c17.6 0 32-14.5 32-32.3V64.3c0-17.8-14.4-32.3-32-32.3zM135.4 416H69V202.2h66.5V416zm-33.2-243c-21.3 0-38.5-17.3-38.5-38.5S80.9 96 102.2 96c21.2 0 38.5 17.3 38.5 38.5 0 21.3-17.2 38.5-38.5 38.5zm282.1 243h-66.4V312c0-24.8-.5-56.7-34.5-56.7-34.6 0-39.9 27-39.9 54.9V416h-66.4V202.2h63.7v29.2h.9c8.9-16.8 30.6-34.5 62.9-34.5 67.2 0 79.7 44.3 79.7 101.9V416z"/></svg>
          </a>
          <a href="" class="no-underline hover:text-gray-300 dark:hover:text-zinc-400"> <!-- Github -->
            <svg class="w-5 h-5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zM277.3 415.7c-8.4 1.5-11.5-3.7-11.5-8 0-5.4.2-33 .2-55.3 0-15.6-5.2-25.5-11.3-30.7 37-4.1 76-9.2 76-73.1 0-18.2-6.5-27.3-17.1-39 1.7-4.3 7.4-22-1.7-45-13.9-4.3-45.7 17.9-45.7 17.9-13.2-3.7-27.5-5.6-41.6-5.6-14.1 0-28.4 1.9-41.6 5.6 0 0-31.8-22.2-45.7-17.9-9.1 22.9-3.5 40.6-1.7 45-10.6 11.7-15.6 20.8-15.6 39 0 63.6 37.3 69 74.3 73.1-4.8 4.3-9.1 11.7-10.6 22.3-9.5 4.3-33.8 11.7-48.3-13.9-9.1-15.8-25.5-17.1-25.5-17.1-16.2-.2-1.1 10.2-1.1 10.2 10.8 5 18.4 24.2 18.4 24.2 9.7 29.7 56.1 19.7 56.1 19.7 0 13.9.2 36.5.2 40.6 0 4.3-3 9.5-11.5 8-66-22.1-112.2-84.9-112.2-158.3 0-91.8 70.2-161.5 162-161.5S388 165.6 388 257.4c.1 73.4-44.7 136.3-110.7 158.3zm-98.1-61.1c-1.9.4-3.7-.4-3.9-1.7-.2-1.5 1.1-2.8 3-3.2 1.9-.2 3.7.6 3.9 1.9.3 1.3-1 2.6-3 3zm-9.5-.9c0 1.3-1.5 2.4-3.5 2.4-2.2.2-3.7-.9-3.7-2.4 0-1.3 1.5-2.4 3.5-2.4 1.9-.2 3.7.9 3.7 2.4zm-13.7-1.1c-.4 1.3-2.4 1.9-4.1 1.3-1.9-.4-3.2-1.9-2.8-3.2.4-1.3 2.4-1.9 4.1-1.5 2 .6 3.3 2.1 2.8 3.4zm-12.3-5.4c-.9 1.1-2.8.9-4.3-.6-1.5-1.3-1.9-3.2-.9-4.1.9-1.1 2.8-.9 4.3.6 1.3 1.3 1.8 3.3.9 4.1zm-9.1-9.1c-.9.6-2.6 0-3.7-1.5s-1.1-3.2 0-3.9c1.1-.9 2.8-.2 3.7 1.3 1.1 1.5 1.1 3.3 0 4.1zm-6.5-9.7c-.9.9-2.4.4-3.5-.6-1.1-1.3-1.3-2.8-.4-3.5.9-.9 2.4-.4 3.5.6 1.1 1.3 1.3 2.8.4 3.5zm-6.7-7.4c-.4.9-1.7 1.1-2.8.4-1.3-.6-1.9-1.7-1.5-2.6.4-.6 1.5-.9 2.8-.4 1.3.7 1.9 1.8 1.5 2.6z"/></svg>
          </a>

        </div>
      </div>
    </footer>

    @stack('js')

    @push('js')
    <script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>
    @endpush

    <script>
      
      var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
      var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

      // Change the icons inside the button based on previous settings
      if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
          themeToggleLightIcon.classList.remove('hidden');
      } else {
          themeToggleDarkIcon.classList.remove('hidden');
      }

      var themeToggleBtn = document.getElementById('theme-toggle');

      themeToggleBtn.addEventListener('click', function() {

          // toggle icons inside button
          themeToggleDarkIcon.classList.toggle('hidden');
          themeToggleLightIcon.classList.toggle('hidden');

          // if set via local storage previously
          if (localStorage.getItem('color-theme')){
              if (localStorage.getItem('color-theme') === 'light') {
                  document.documentElement.classList.add('dark');
                  localStorage.setItem('color-theme', 'dark');
                  currentTheme = 'dark';
              } else {
                  document.documentElement.classList.remove('dark');
                  localStorage.setItem('color-theme', 'light');
                  currentTheme = 'light';
              }

          // if NOT set via local storage previously
          } else {
              if (document.documentElement.classList.contains('dark')) {
                  document.documentElement.classList.remove('dark');
                  localStorage.setItem('color-theme', 'light');
                  currentTheme = 'dark';
              } else {
                  document.documentElement.classList.add('dark');
                  localStorage.setItem('color-theme', 'dark');
                  currentTheme = 'light';
              }
          }
          
      });

    </script>

  </body>
</html>