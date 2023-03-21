<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">

        <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark')
        }
        </script>

    </head>
    <body class="antialiased bg-slate-50 dark:bg-zinc-900">
        <div class="relative flex items-top justify-center min-h-screen sm:items-center sm:pt-0">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="flex items-center pt-8 sm:justify-start sm:pt-0">
                    <div class="px-4 text-lg text-slate-500 dark:text-zinc-400 border-r border-slate-400 dark:border-zinc-600 tracking-wider">
                        @yield('code')
                    </div>

                    <div class="ml-4 text-lg text-slate-500 dark:text-zinc-400 uppercase tracking-wider">
                        @yield('message')
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
