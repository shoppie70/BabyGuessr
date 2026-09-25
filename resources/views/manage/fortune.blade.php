@extends('layouts.app')

@section('title', $babyName . 'ちゃんの鑑定')

@push('head')
<style>
    .fortune-read {
        font-family: "Zen Maru Gothic", sans-serif;
        font-size: 1.0625rem;
        line-height: 1.85;
        letter-spacing: 0;
        line-break: strict;
        color: #292524;
    }
    .fortune-read p + p,
    .fortune-read div + p,
    .fortune-read p + div { margin-top: 1rem; }
    .fortune-ui { font-family: "Zen Maru Gothic", sans-serif; }
</style>
@endpush

@section('content')
@php
    $reports = $data['reports'] ?? [];
    $tabs = [
        'integrated' => '総合鑑定',
        'four_pillars' => '四柱推命',
        'nine_star_ki' => '九星気学',
        'shukuyo' => '宿曜占星術',
        'numerology' => '数秘術',
        'sanmeigaku' => '算命学',
        'zi_wei_dou_shu' => '紫微斗数',
        'western_astrology' => '西洋占星術',
        'parenting' => '育成・開運ガイド',
    ];
@endphp

<div class="space-y-4">
    @if (session('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-bold">
            {{ session('status') }}
        </div>
    @endif

    <div class="fortune-ui flex items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-slate-800">{{ $babyName }}ちゃんの鑑定</h1>
            @if(!empty($data['summary']['catchphrase']))
                <p class="fortune-read text-amber-800 mt-2">{{ $data['summary']['catchphrase'] }}</p>
            @endif
        </div>
        <a href="{{ route('manage.show', ['token' => $token]) }}" class="text-sm font-bold text-slate-500 shrink-0">戻る</a>
    </div>

    @if(!empty($data['summary']['overview']))
        <p class="fortune-read bg-amber-50/60 border border-amber-100 rounded-xl p-4">
            {{ $data['summary']['overview'] }}
        </p>
    @endif

    <div class="space-y-2">
        @foreach($tabs as $key => $label)
            <details class="bg-white rounded-xl border border-amber-100 shadow-sm" @if($loop->first) open @endif>
                <summary class="fortune-ui cursor-pointer px-4 py-3 text-base font-bold text-slate-800 select-none flex items-center">
                    {{ $label }}
                </summary>
                <div class="fortune-read px-4 pb-5 border-t border-amber-100 pt-4">
                    @if($key === 'integrated')
                        @php $ir = $data['integrated_report'] ?? []; @endphp
                        <p><span class="font-bold text-slate-500 text-xs block mb-1">本質</span>{{ $ir['core_traits'] ?? '' }}</p>
                        @if(!empty($ir['strengths']))
                            <div>
                                <span class="font-bold text-slate-500 text-xs block mb-1">強み</span>
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach($ir['strengths'] as $s)
                                        <li>{{ $s }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <p><span class="font-bold text-slate-500 text-xs block mb-1">成長</span>{{ $ir['growth'] ?? '' }}</p>
                        <p><span class="font-bold text-slate-500 text-xs block mb-1">人間関係</span>{{ $ir['relationships'] ?? '' }}</p>
                        <p><span class="font-bold text-slate-500 text-xs block mb-1">これから</span>{{ $ir['future'] ?? '' }}</p>

                    @elseif($key === 'parenting')
                        @php $pg = $data['parenting_guide'] ?? []; @endphp
                        <p><span class="font-bold text-slate-500 text-xs block mb-1">乳幼児期</span>{{ $pg['early_childhood'] ?? '' }}</p>
                        <p><span class="font-bold text-slate-500 text-xs block mb-1">学童期</span>{{ $pg['school_age'] ?? '' }}</p>
                        <p><span class="font-bold text-slate-500 text-xs block mb-1">思春期</span>{{ $pg['teenage'] ?? '' }}</p>
                        @if(!empty($pg['advice']))
                            <div>
                                <span class="font-bold text-slate-500 text-xs block mb-1">アドバイス</span>
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach($pg['advice'] as $a)
                                        <li>{{ $a }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @php $le = $pg['lucky_elements'] ?? []; @endphp
                        <div class="space-y-1 bg-amber-50 rounded-lg p-3">
                            <p><span class="font-bold">ラッキー数字:</span> {{ implode(' / ', $le['numbers'] ?? []) }}</p>
                            <p><span class="font-bold">ラッキーカラー:</span> {{ implode(' / ', $le['colors'] ?? []) }}</p>
                            <p><span class="font-bold">おすすめ活動:</span> {{ implode(' / ', $le['activities'] ?? []) }}</p>
                        </div>

                    @else
                        @php $r = $reports[$key] ?? []; @endphp
                        @if(!empty($r['title']))
                            <h3 class="font-bold text-slate-800">{{ $r['title'] }}</h3>
                        @endif
                        <p>{{ $r['summary'] ?? '' }}</p>
                        <p><span class="font-bold text-slate-500 text-xs block mb-1">性格</span>{{ $r['personality'] ?? '' }}</p>
                        @if(!empty($r['strengths']))
                            <div>
                                <span class="font-bold text-slate-500 text-xs block mb-1">強み</span>
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach($r['strengths'] as $s)
                                        <li>{{ $s }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <p><span class="font-bold text-slate-500 text-xs block mb-1">人間関係</span>{{ $r['relationships'] ?? '' }}</p>
                        <p><span class="font-bold text-slate-500 text-xs block mb-1">これからの傾向</span>{{ $r['future_tendencies'] ?? '' }}</p>
                        <p class="bg-amber-50 rounded-lg p-3 text-amber-900">{{ $r['message'] ?? '' }}</p>
                    @endif
                </div>
            </details>
        @endforeach
    </div>

    <p class="fortune-ui text-xs text-slate-400 text-center pt-2">
        本鑑定は娯楽・エンタメ目的です。人生の断定や医療・進路の助言ではありません。
    </p>
</div>
@endsection
