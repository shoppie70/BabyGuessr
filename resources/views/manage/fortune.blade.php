@extends('layouts.app')

@section('title', $babyName . 'ちゃんの鑑定')

@push('head')
<style>
    @media (prefers-reduced-motion: no-preference) { html { scroll-behavior: smooth; } }
    .fortune-text { font-size: 17px; line-height: 1.85; font-weight: 500; line-break: strict; }
    .no-scrollbar { scrollbar-width: none; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    @keyframes nudge { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(3px); } }
    @media (prefers-reduced-motion: no-preference) { .animate-nudge { animation: nudge 1.4s ease-in-out infinite; } }
</style>
@endpush

@section('content')
@php
    $reports = $data['reports'] ?? [];
    $sections = [
        'integrated' => ['総合鑑定', 'bg-amber-100 text-amber-900'],
        'four_pillars' => ['四柱推命', 'bg-rose-100 text-rose-900'],
        'nine_star_ki' => ['九星気学', 'bg-sky-100 text-sky-900'],
        'shukuyo' => ['宿曜占星術', 'bg-violet-100 text-violet-900'],
        'numerology' => ['数秘術', 'bg-emerald-100 text-emerald-900'],
        'sanmeigaku' => ['算命学', 'bg-orange-100 text-orange-900'],
        'zi_wei_dou_shu' => ['紫微斗数', 'bg-indigo-100 text-indigo-900'],
        'western_astrology' => ['西洋占星術', 'bg-teal-100 text-teal-900'],
        'parenting' => ['育成・開運ガイド', 'bg-pink-100 text-pink-900'],
    ];
    $hl = fn (?string $text) => preg_replace(
        '/「([^」]{1,40})」/u',
        '<mark class="bg-amber-100 text-amber-950 font-bold rounded px-0.5 box-decoration-clone">「$1」</mark>',
        e($text ?? '')
    );
@endphp

<div class="space-y-5 text-slate-800">
    <a href="{{ route('manage.show', ['token' => $token]) }}" class="inline-flex items-center min-h-11 text-[15px] font-bold text-slate-600 cursor-pointer">← 戻る</a>

    <header class="rounded-2xl bg-gradient-to-br from-rose-400 via-amber-400 to-amber-300 text-white p-6 shadow-md">
        <p class="text-[15px] font-bold text-white/90">{{ $babyName }}ちゃんの鑑定</p>
        @if (!empty($data['summary']['catchphrase']))
            <p class="mt-2 text-2xl font-extrabold leading-snug">{{ $data['summary']['catchphrase'] }}</p>
        @endif
    </header>

    @if (!empty($data['summary']['overview']))
        <p class="fortune-text bg-white border-l-4 border-amber-400 rounded-xl p-5 shadow-sm">{!! $hl($data['summary']['overview']) !!}</p>
    @endif

    <div id="fortune-tabs" class="sticky top-0 z-10 -mx-4 px-4 py-2 bg-amber-50/95 backdrop-blur-sm">
        <p class="px-1 pb-2 text-[15px] font-bold text-slate-700">
            読みたい占いを押してください
            <span class="block text-sm font-medium text-slate-500">全部で{{ count($sections) }}つあります。右にもあります。</span>
        </p>
        <div class="relative bg-white rounded-2xl border border-amber-100 shadow-sm p-1.5">
            <div id="fortune-tablist" class="no-scrollbar flex gap-1 overflow-x-auto pr-12" role="tablist" aria-label="占術">
                @foreach ($sections as $key => [$label, $accent])
                    <button
                        type="button"
                        role="tab"
                        id="tab-{{ $key }}"
                        aria-controls="sec-{{ $key }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        data-tab="{{ $key }}"
                        class="shrink-0 inline-flex items-center min-h-11 px-4 rounded-xl text-[15px] font-bold text-slate-600 cursor-pointer transition-colors duration-200 hover:bg-amber-50 hover:text-slate-800 aria-selected:bg-gradient-to-r aria-selected:from-amber-500 aria-selected:to-rose-400 aria-selected:text-white aria-selected:shadow-sm"
                    >{{ $label }}</button>
                @endforeach
            </div>
            <div class="pointer-events-none absolute inset-y-1.5 right-12 w-8 bg-gradient-to-l from-white" aria-hidden="true"></div>
            <button
                type="button"
                id="fortune-more"
                class="absolute inset-y-1.5 right-1.5 w-11 rounded-xl bg-amber-100 text-amber-900 flex items-center justify-center cursor-pointer transition-colors duration-200 hover:bg-amber-200"
                aria-label="ほかの占いを表示"
            >
                <svg class="w-6 h-6 animate-nudge" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
    </div>

    @foreach ($sections as $key => [$label, $accent])
        <section
            id="sec-{{ $key }}"
            role="tabpanel"
            aria-labelledby="tab-{{ $key }}"
            data-panel="{{ $key }}"
            class="bg-white rounded-2xl shadow-sm border border-amber-100 overflow-hidden"
        >
            <h2 class="px-5 py-4 text-xl font-extrabold flex items-center justify-between gap-2 {{ $accent }}">
                <span>{{ $label }}</span>
                <span class="text-sm font-bold opacity-80">{{ $loop->iteration }} / {{ $loop->count }}</span>
            </h2>

            <div class="p-5 space-y-6">
                @if ($key === 'integrated')
                    @php $ir = $data['integrated_report'] ?? []; @endphp
                    @include('manage.partials.fortune-block', ['heading' => '本質', 'text' => $ir['core_traits'] ?? null])
                    @include('manage.partials.fortune-strengths', ['items' => $ir['strengths'] ?? []])
                    @include('manage.partials.fortune-block', ['heading' => '成長', 'text' => $ir['growth'] ?? null])
                    @include('manage.partials.fortune-block', ['heading' => '人間関係', 'text' => $ir['relationships'] ?? null])
                    @include('manage.partials.fortune-block', ['heading' => 'これから', 'text' => $ir['future'] ?? null])

                @elseif ($key === 'parenting')
                    @php $pg = $data['parenting_guide'] ?? []; $le = $pg['lucky_elements'] ?? []; @endphp
                    @include('manage.partials.fortune-block', ['heading' => '乳幼児期', 'text' => $pg['early_childhood'] ?? null])
                    @include('manage.partials.fortune-block', ['heading' => '学童期', 'text' => $pg['school_age'] ?? null])
                    @include('manage.partials.fortune-block', ['heading' => '思春期', 'text' => $pg['teenage'] ?? null])
                    @include('manage.partials.fortune-strengths', ['items' => $pg['advice'] ?? [], 'heading' => 'アドバイス'])

                    <div class="space-y-3">
                        @foreach (['numbers' => 'ラッキー数字', 'colors' => 'ラッキーカラー', 'activities' => 'おすすめの過ごし方'] as $field => $title)
                            @if (!empty($le[$field]))
                                <div>
                                    <span class="inline-flex rounded-full bg-amber-100 text-amber-900 px-3 py-1 text-[15px] font-bold">{{ $title }}</span>
                                    <ul class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($le[$field] as $item)
                                            <li class="rounded-full bg-white border border-amber-200 px-4 py-1.5 text-base font-bold">{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endforeach
                    </div>

                @else
                    @php $r = $reports[$key] ?? []; @endphp
                    @if (!empty($r['title']))
                        <h3 class="text-lg font-extrabold leading-snug">{{ $r['title'] }}</h3>
                    @endif
                    @if (!empty($r['summary']))
                        <p class="fortune-text">{!! $hl($r['summary']) !!}</p>
                    @endif
                    @include('manage.partials.fortune-block', ['heading' => '性格', 'text' => $r['personality'] ?? null])
                    @include('manage.partials.fortune-strengths', ['items' => $r['strengths'] ?? []])
                    @include('manage.partials.fortune-block', ['heading' => '人間関係', 'text' => $r['relationships'] ?? null])
                    @include('manage.partials.fortune-block', ['heading' => 'これからの傾向', 'text' => $r['future_tendencies'] ?? null])
                    @if (!empty($r['message']))
                        <p class="fortune-text rounded-2xl bg-rose-50 border border-rose-100 p-4 font-bold text-rose-900">{!! $hl($r['message']) !!}</p>
                    @endif
                @endif

                @php
                    $keys = array_keys($sections);
                    $nextKey = $keys[$loop->index + 1] ?? null;
                @endphp
                <div class="pt-2">
                    @if ($nextKey)
                        <button type="button" data-go="{{ $nextKey }}" class="w-full min-h-14 rounded-xl bg-gradient-to-r from-amber-500 to-rose-400 text-white text-base font-bold shadow-md flex items-center justify-center gap-2 cursor-pointer transition-all duration-200 hover:from-amber-600 hover:to-rose-500">
                            次は「{{ $sections[$nextKey][0] }}」を読む
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    @else
                        <p class="text-center text-base font-bold text-slate-700 mb-3">これで全部読み終わりました</p>
                        <button type="button" data-go="{{ $keys[0] }}" class="w-full min-h-14 rounded-xl border-2 border-amber-300 bg-amber-50 text-amber-900 text-base font-bold cursor-pointer transition-colors duration-200 hover:bg-amber-100">
                            最初の「{{ $sections[$keys[0]][0] }}」に戻る
                        </button>
                    @endif
                </div>
            </div>
        </section>
    @endforeach

    <p class="text-sm text-slate-500 text-center pt-2">
        この鑑定は楽しむためのものです。人生の断定や、医療・進路の助言ではありません。
    </p>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const bar = document.getElementById('fortune-tabs');
    const tabs = [...document.querySelectorAll('[data-tab]')];
    const panels = [...document.querySelectorAll('[data-panel]')];

    const list = document.getElementById('fortune-tablist');
    const more = document.getElementById('fortune-more');

    const show = (key) => {
        tabs.forEach((t) => t.setAttribute('aria-selected', String(t.dataset.tab === key)));
        panels.forEach((p) => { p.hidden = p.dataset.panel !== key; });
    };

    const open = (key) => {
        show(key);
        const tab = tabs.find((t) => t.dataset.tab === key);
        list.scrollTo({ left: tab.offsetLeft - list.clientWidth / 2 + tab.clientWidth / 2 });
        const top = bar.offsetTop;
        if (window.scrollY > top) window.scrollTo({ top });
    };

    tabs.forEach((tab) => tab.addEventListener('click', () => open(tab.dataset.tab)));
    document.querySelectorAll('[data-go]').forEach((btn) => btn.addEventListener('click', () => open(btn.dataset.go)));

    more.addEventListener('click', () => {
        const atEnd = list.scrollLeft + list.clientWidth >= list.scrollWidth - 4;
        list.scrollTo({ left: atEnd ? 0 : list.scrollLeft + list.clientWidth * 0.7 });
    });

    show(tabs[0].dataset.tab);
});
</script>
@endpush
