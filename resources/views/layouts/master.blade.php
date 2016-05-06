<!DOCTYPE html>
<html lang="es-ar">
<head>
@include('layouts.head')
</head>
<body>
    <noscript>
        <div class="warning-message">Este sitio requiere javascript para su correcto funcionamiento</div>
    </noscript>
    <header class="navbar navbar-default navbar-static-top">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/reenvios">
                    <span class="glyphicon glyphicon-cog"></span> {{ config('app.name') }}
                </a>
            </div>
            @include('layouts.menu')
        </div>
    </header>
    <div class="container">
    @if (Session::has('message'))
    <div class="alert alert-info">{{ Session::get('message') }}</div>
    @endif
    @yield('content')
    </div>
    @include('layouts.scripts')
    @yield('scripts')
</body>
</html>
