<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="NewRolIT">

    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <title>{{$app_name}}</title>

    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
    <link href="{{ asset('css/jquery.dataTables.css') }}" rel="stylesheet">
    <link href="{{ asset('css/pagination.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cards.css') }}" rel="stylesheet">

    <script src="{{ asset('js/jquery-3.5.1.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
 

  </head>
  <!-- FIN HEADER -->
  <body>

    <nav class="navbar justify-content-lg-between">
      <li class="navbar-brand text-white">{{$page_title}}</li>
      <div class="row mr-2" style="word-spacing: .5rem">
          @if ( Auth::user()->name )
          <span class="nav-item">
            {{ Auth::user()->name }}
          </span>
          <span class="nav-btn">
            <a href="salir" class="text-white nav-link">Salir</a>
          </span>
          @else
          <span class="nav-btn">
            <a href="ingreso" class="text-white nav-link">Ingresar</a>
          </span>
          @endif
      </div>
    </nav>

    @if ($breadcrumb)
    <ol class="breadcrumb">
      @foreach ($breadcrumb as $key => $value)
        @if ($value == '#')
        <li>{{$key}}</li>
        @else
        <li><a href="{{$value}}">{{$key}}</a></li>
        @endif
      @endforeach
    </ol>
    @endif

    @if ($message)
      <div class="container alert alert-success alert-dismissible" role="alert">{{$message}}
          <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
    @endif
    @if ($error)
    <div class="container alert alert-danger alert-dismissible" role="alert">{{$error}}
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif