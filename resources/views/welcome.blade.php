<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    {{-- this website is for testing purposes only --}}
    <meta name="robots" content="noindex, nofollow" />

    <title>{{ config('app.name', 'Laravel') }}</title>

    @fonts

    @vite('resources/js/app.ts')
</head>

<body class="antialiased">
    <div id="app"></div>
</body>
</html>
