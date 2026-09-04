<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="{!! asset('public/css/images/morich.gif') !!}" />
    <title>Morich Indo Fashion</title>
    @include('layout.css_global')
    @yield('css_custom')
</head>

<body>

    <div class="wrapper">

        <!-- Navbar -->
        @include('layout.navbar')
        <!-- End Navbar -->

        <!-- Content -->
        @include('modal.password')

        <div>
            @yield('content')
        </div>
    </div>
    @include('layout.js_global')
    @yield('js_custom')
    @yield('scripts')
</body>
</html>