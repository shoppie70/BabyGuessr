@extends('layouts.app')

@section('title', '赤ちゃんの名前を当てよう！')

@section('content')
<div class="space-y-4">
    <section class="bg-white rounded-2xl border border-amber-100 shadow-sm p-6 space-y-2">
        <h1 class="text-xl font-bold">赤ちゃんの名前を当てよう！</h1>
        <p class="text-sm text-slate-500">下の名前は出しません。友人には「名前当て」のリンクだけ送ってください。</p>
    </section>

    @forelse ($babies as $baby)
        <section class="bg-white rounded-2xl border border-amber-100 shadow-sm p-6 space-y-3">
            <h2 class="text-lg font-bold">{{ $baby['label'] }}</h2>
            <nav class="grid gap-2 sm:grid-cols-3">
                <a href="{{ $baby['gameUrl'] }}" class="min-h-12 flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 font-bold text-amber-900 cursor-pointer hover:bg-amber-100">名前当て</a>
                <a href="{{ $baby['manageUrl'] }}" class="min-h-12 flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 font-bold text-amber-900 cursor-pointer hover:bg-amber-100">鑑定</a>
                <a href="{{ $baby['diagnosticsUrl'] }}" class="min-h-12 flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-700 cursor-pointer hover:bg-slate-100">診断</a>
            </nav>
        </section>
    @empty
        <section class="bg-white rounded-2xl border border-amber-100 shadow-sm p-6">
            <p class="text-sm text-slate-500">まだ登録がありません。</p>
        </section>
    @endforelse

    @if ($setupUrl)
        <a href="{{ $setupUrl }}" class="w-full min-h-12 flex items-center justify-center rounded-xl bg-gradient-to-r from-amber-500 to-rose-400 text-white font-bold shadow-md cursor-pointer">新規登録</a>
    @endif
</div>
@endsection
