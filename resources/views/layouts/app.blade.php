<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield("title")</title>
        {{-- fonts --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">

        {{-- import @vite --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <link rel="icon" type="image/svg" href="{{ Vite::asset('resources/images/logos/bloom-logo.svg') }}">
    </head>
    <body class="d-flex flex-column min-vh-100 flowerized">
        @include("partials.header")


        <main class="flex-grow-1 d-flex flex-column align-items-center w-100">
            @yield('content')
        </main>

        @include("partials.footer")
    </body>
</html>