<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name>

    {{-- import @vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" type="image/svg" href="{{ Vite::asset('resources/images/logos/bloom-logo.svg') }}">

    <title>Records</title>

</head>
    <body class="d-flex flex-column min-vh-100">
        @include("partials.header")

        <div class="container px-2 pt-4">
            <h1 class="px-2">
                @yield("title")
            </h1>
        </div>

        <main class="flex-grow-1">
            @yield('content')
        </main>

        @include("partials.footer")
    </body>
</html>