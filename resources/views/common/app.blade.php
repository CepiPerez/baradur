<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <link rel="shortcut icon" href="{{ asset('logo.ico') }}" type="image/x-icon">

    <title>{{$app_name}}</title>

    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
    <link href="{{ asset('css/pagination.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

    <script src="{{ asset('js/jquery-3.5.1.min.js') }}"></script>
    <script src="{{ asset('js/popper.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>

  </head>
  
  <body>

    <main>
      
      <nav class="navbar justify-content-lg-between">
        <li class="navbar-brand text-white">{{$app_name}}</li>
        @if ( Route::has('login') )
        <div class="row mr-2">
            @if ( Auth::user() )
            <span class="nav-item">
              {{ Auth::user()->name }}
            </span>
            <span class="nav-btn">
              <a href="{{ route('logout') }}" class="text-white nav-link">
              <i class="fa fa-sign-out-alt" aria-hidden="true"></i></a>
            </span>
            @else
            <span class="nav-btn">
              <a href="{{ route('login') }}" class="text-white nav-link">
              <i class="fa fa-sign-in-alt mr-2" aria-hidden="true"></i>@lang('login.login')</a>
            </span>
            @endif
        </div>
        @endif
      </nav>

      @if ($breadcrump)
      <ol class="breadcrumb">
        @foreach ($breadcrump as $key => $value)
          @if ($value == '#')
          <li class="breadcrumb-item">{{$key}}</li>
          @else
          <li class="breadcrumb-item active"><a href="{{$value}}">{{$key}}</a></li>
          @endif
        @endforeach
      </ol>
      @endif

      @if ( session('message') )
        <div class="container alert alert-success alert-dismissible" role="alert">{{session('message')}}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
      @endif
      @if ( session('error') )
      <div class="container alert alert-danger alert-dismissible" role="alert">{{session('error')}}
          <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
      @endif
      @if ( $errors->any() )
      <div class="container alert alert-danger alert-dismissible" role="alert">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
          <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
      @endif

      @yield('content')
      

    </main>
    
    <footer class="text-center text-lg-start bg-dark text-muted">
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
    </footer>


  </body>
</html>