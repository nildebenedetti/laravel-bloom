<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield("title")</title>


        {{-- import @vite --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <link rel="icon" type="image/svg" href="{{ Vite::asset('resources/images/logos/bloom-logo.svg') }}">
    </head>
    <body class="d-flex flex-column min-vh-100">
        @include("partials.header")


        <main class="flex-grow-1 pt-5 mt-4">
            @yield('content')
        </main>

        @include("partials.footer")
    </body>
</html>