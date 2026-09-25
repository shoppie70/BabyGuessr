<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '赤ちゃんの名前を当てよう！')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=zen-maru-gothic:400,500,700&display=swap" rel="stylesheet" />

    <style>
        body {
            font-family: 'Zen Maru Gothic', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-amber-50/40 text-slate-800 min-h-screen flex flex-col justify-between antialiased selection:bg-rose-200">
    <!-- Header -->
    <header class="w-full border-b border-amber-100 bg-white/80 backdrop-blur-sm sticky top-0 z-20 shadow-xs">
        <div class="max-w-2xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2 font-bold text-slate-700 text-lg">
                <span class="text-2xl">🍼</span>
                <span>Guess Baby Name</span>
            </div>
            @yield('header_badge')
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-2xl w-full mx-auto px-4 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="w-full py-4 text-center text-xs text-slate-400 border-t border-amber-100/60 bg-white/40">
        <p>© 2026 GuessBabyName. 限定公開ゲーム</p>
    </footer>

    @stack('scripts')
</body>
</html>
