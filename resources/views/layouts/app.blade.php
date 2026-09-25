<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '赤ちゃんの名前を当ててみて')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=zen-maru-gothic:400,500,700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Zen Maru Gothic', sans-serif; }
    </style>
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-amber-50/40 text-slate-800 min-h-screen antialiased selection:bg-rose-200">
    <main class="max-w-2xl w-full mx-auto px-4 py-8">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
