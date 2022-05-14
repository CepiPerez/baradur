<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <link rel="shortcut icon" href="{{ asset('logo.ico') }}" type="image/x-icon">

    <title>{{$app_name}}</title>

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">
    
    <script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    
    <link rel="stylesheet" href="{{ asset('assets/css/stackoverflow-light.css') }}">
    <link href="{{ asset('assets/css/style3.css') }}" rel="stylesheet">

  </head>
  
  <body>
    
    <div class="overlay"></div>


    <nav class="navbar">
        <div class="d-flex">
            <span class="dismiss fa fa-bars text-white ml-3 mr-1" style="margin-top:1.2rem;"></span>
            <a href="{{HOME}}"><li class="navbar-brand text-white">{{$app_name}}</li></a>
        </div>
    </nav>

    <main>
      <div class="m-0 p-0 page-container">
        <nav class="d-flex sidebar sidebar-fixed active">
          <div class="list-group bg-secondary">

            <div class="sidebar-header">
              <img src="{{ asset('assets/logo.ico') }}" alt="" height="18px" width="18px">
              <span>Documentation</span></div>
              <ul class="list-unstyled mt-2" id="submenu">

                <li class="sidebaritem">
                    <a href="/docs">Getting started</a>
                </li>

                <li class="sidebaritem">
                    <a href="/docs/routing">Routing</a>
                </li>

                <li class="sidebaritem">
                    <a href="/docs/controllers">Controllers</a>
                </li>

                <li class="sidebaritem">
                    <a href="/docs/models">Models</a>
                </li>

                <li class="sidebaritem">
                    <a href="/docs/eloquent">Eloquent</a>
                </li>

                <li class="sidebaritem">
                    <a href="/docs/localization">Localization</a>
                </li>
                  
                <li class="sidebaritem">
                    <a href="/docs/artisan">Artisan</a>
                </li>
  

              </ul>
            </div>

          <div class="mt-3 main-content">
          @yield('content')
          </div>
        </nav>

      </div>
    </main>



  </body>

  <script src="{{ asset('assets/js/highlight.min.js') }}"></script>
  <script>hljs.initHighlightingOnLoad();</script>


  <script type="text/javascript">

    $(document).ready(function () {

        $('.dismiss').on('click', function () {
            if ($('.sidebar-fixed').hasClass("active"))
            {
                $('.sidebar-fixed').removeClass('active');
                $('.overlay').removeClass('active');
                $('.dismiss').children('span').eq(0).removeClass('fa-arrow-left').addClass('fa-arrow-right');
            }
            else
            {
                $('.sidebar-fixed').addClass('active');
                //$('.overlay').addClass('active');
                $('.collapse.in').toggleClass('in');
                $('.dismiss').children('span').eq(0).removeClass('fa-arrow-right').addClass('fa-arrow-left');
                $('a[aria-expanded=true]').attr('aria-expanded', 'false');
            }
        });

        $('.overlay').on('click', function () {
            $('.sidebar-fixed').removeClass('active');
            $('.overlay').removeClass('active');
        });

    });

  </script>
</html>