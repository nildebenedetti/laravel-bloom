<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name>

    {{-- import @vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" type="image/svg" href="{{ Vite::asset('resources/images/logos/bloom-logo.svg') }}">

    <title>Emotions</title>

</head>
    <body class="d-flex flex-column min-vh-100 flowerized ">
        @include("partials.header")

        <div class="container align-items-center px-2 pt-4">
            <h1 class="px-2 title-color">
                @yield("title")
            </h1>
        </div>

        <main class="flex-grow-1 d-flex flex-column align-items-center">
            @yield('content')
        </main>

        @include("partials.footer")
    </body>
</html>