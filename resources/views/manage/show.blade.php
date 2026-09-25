@extends('layouts.app')

@section('title', '鑑定と名前当て')

@section('content')
@php
    $status = $fortuneReport?->status ?? 'not_generated';
    $ready = $fortuneReport && $fortuneReport->hasReportBody();
@endphp
<div class="space-y-4">
    @if (session('status'))
        <p class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-bold" role="status">{{ session('status') }}</p>
    @endif

    <section class="bg-white rounded-2xl border border-amber-100 shadow-sm p-6 space-y-3">
        <h1 class="text-xl font-bold">鑑定</h1>
        @if ($ready)
            <p class="text-sm text-slate-500">できています。この文章を友人に送ってください。</p>
            <a href="{{ $fortuneUrl }}" class="w-full min-h-12 rounded-xl bg-gradient-to-r from-amber-500 to-rose-400 text-white font-bold shadow-md flex items-center justify-center">鑑定を見る</a>
        @elseif ($status === 'failed')
            <p class="text-sm text-slate-500">鑑定を作れませんでした。もう一度渡せます。</p>
            <form action="{{ route('manage.fortune.generate', ['token' => $token]) }}" method="POST">
                @csrf
                <button type="submit" class="w-full min-h-12 rounded-xl bg-gradient-to-r from-amber-500 to-rose-400 text-white font-bold shadow-md cursor-pointer">もう一度渡す</button>
            </form>
        @else
            <p class="text-sm text-slate-500">準備しています。できたころに、このページを開き直してください。</p>
        @endif
    </section>

    <section class="bg-white rounded-2xl border border-amber-100 shadow-sm p-6 space-y-3">
        <h2 class="text-xl font-bold">名前を知らない人に送るリンク</h2>
        <input type="text" readonly value="{{ $gameUrl }}" class="w-full bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 text-sm">
    </section>

    <section class="bg-white rounded-2xl border border-amber-100 shadow-sm p-6">
        <h2 class="text-xl font-bold">名前当て</h2>
        <p class="text-sm text-slate-500 mt-2">
            {{ $winner ? $winner->challenger_name.'さんが当てました' : 'まだ当たっていません' }}
        </p>
    </section>
</div>
@endsection
