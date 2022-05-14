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
    <!-- <link href="{{ asset('assets/css/pagination.css') }}" rel="stylesheet"> -->  <!-- For Bootstrap only -->

    <script src="{{ asset('assets/js/alpine-3.10.2.js') }}" defer></script>

    @verbatim
    <style>
      * { font-size: 15px; }
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

  
    
    <main class="mb-3">

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
                            <p class="py-1.5 px-3 hidden sm:block">{{ Auth::user()->name }}</p>
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
    
    <!-- <footer class="text-center text-lg-start bg-dark text-muted">
      <section
        class="d-flex justify-content-center justify-content-lg-between">
        <div class="me-5 d-none d-lg-block">
          <span>© 2022 Matias Perez - cepiperez@gmail.com</span>
        </div>

        <div>
          <div style="word-spacing: .5rem">
            <a href="" class="me-4 text-reset text-decoration-none">
              <i class="fab fa-facebook-f"></i></a>
            <a href="" class="me-4 text-reset text-decoration-none">
              <i class="fab fa-twitter"></i></a>
            <a href="" class="me-4 text-reset text-decoration-none">
              <i class="fab fa-google"></i></a>
            <a href="" class="me-4 text-reset text-decoration-none">
              <i class="fab fa-instagram"></i></a>
            <a href="" class="me-4 text-reset text-decoration-none">
              <i class="fab fa-linkedin"></i></a>
            <a href="" class="me-4 text-reset text-decoration-none">
              <i class="fab fa-github"></i></a>
          </div>
        </div>
      </section>
    </footer> -->

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