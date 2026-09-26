<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '赤ちゃんの名前を当ててみて')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'M PLUS Rounded 1c', sans-serif; font-weight: 500; }
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
