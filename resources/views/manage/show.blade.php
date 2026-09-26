@extends('layouts.app')

@section('title', '管理')

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
            <p class="text-sm text-slate-500">できています。下のリンクを友人に送ってください。</p>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" readonly value="{{ $fortuneUrl }}" class="min-w-0 w-full flex-1 bg-amber-50 border border-amber-100 rounded-xl px-3 py-3 text-xs sm:text-sm break-all">
                <button type="button" data-copy="{{ $fortuneUrl }}" class="shrink-0 min-h-12 px-4 rounded-xl bg-slate-800 text-white text-sm font-bold cursor-pointer">コピー</button>
            </div>
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
        <h2 class="text-xl font-bold">名前当てゲームのリンク</h2>
        <p class="text-sm text-slate-500">友人に送って、赤ちゃんの名前を当ててもらいましょう。</p>
        <div class="flex flex-col sm:flex-row gap-2">
            <input type="text" readonly value="{{ $gameUrl }}" class="min-w-0 w-full flex-1 bg-amber-50 border border-amber-100 rounded-xl px-3 py-3 text-xs sm:text-sm break-all">
            <button type="button" data-copy="{{ $gameUrl }}" class="shrink-0 min-h-12 px-4 rounded-xl bg-slate-800 text-white text-sm font-bold cursor-pointer">コピー</button>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        await navigator.clipboard.writeText(btn.dataset.copy);
        const label = btn.textContent;
        btn.textContent = 'コピーしました';
        setTimeout(() => { btn.textContent = label; }, 1500);
    });
});
</script>
@endpush
