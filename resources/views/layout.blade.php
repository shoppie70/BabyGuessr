<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('partials.style')

</head>
<body>
{{--        <header class="pt-4 mb-4">--}}
{{--            @include('front::partials.header')--}}
{{--        </header>--}}
<main class="">
    @include('partials.spinner')

    @yield('content')
</main>
<footer>
    <small class="text-center text-xs text-gray-400 w-full bg-white absolute bottom-0 left-0">
        Made with <span class="text-base" style="color: #e25555;">♥</span> by Sho Tsukamoto
    </small>
</footer>

@include('partials.script')
</body>
</html>
