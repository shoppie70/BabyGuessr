@extends('layouts.app')

@section('title', '入力内容の確認')

@section('header_badge')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
        登録確認
    </span>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Notice -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-amber-100 text-center">
        <span class="text-3xl mb-1 block">🔍</span>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-800">
            入力内容のご確認
        </h1>
        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            お名前に誤りがないかご確認ください。<br>
            登録後は変更できませんのでご注意ください。
        </p>
    </div>

    <!-- Confirmation Table Card -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-amber-100 space-y-6">
        <dl class="divide-y divide-slate-100 text-sm">
            <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <dt class="text-xs text-slate-400 font-bold">氏名（漢字）</dt>
                <dd class="text-lg font-extrabold text-slate-800">
                    {{ $data['family_name'] }} {{ $data['given_name'] }}
                </dd>
            </div>

            <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <dt class="text-xs text-slate-400 font-bold">読み（ひらがな）</dt>
                <dd class="text-sm font-bold text-slate-700">
                    {{ $data['family_name_kana'] }} {{ $data['given_name_kana'] }}
                </dd>
            </div>

            <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <dt class="text-xs text-slate-400 font-bold">性別</dt>
                <dd class="text-sm font-bold text-slate-800">
                    @if ($data['sex'] === 'female')
                        女の子 👧
                    @elseif ($data['sex'] === 'male')
                        男の子 👦
                    @else
                        その他
                    @endif
                </dd>
            </div>

            <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <dt class="text-xs text-slate-400 font-bold">生年月日</dt>
                <dd class="text-sm font-bold text-slate-800">
                    {{ \Carbon\Carbon::parse($data['birth_date'])->format('Y年n月j日') }}
                </dd>
            </div>

            <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <dt class="text-xs text-slate-400 font-bold">出生時刻</dt>
                <dd class="text-sm text-slate-700">
                    {{ !empty($data['birth_time']) ? $data['birth_time'] : '未設定' }}
                </dd>
            </div>

            <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <dt class="text-xs text-slate-400 font-bold">出生地</dt>
                <dd class="text-sm text-slate-700">
                    {{ !empty($data['birth_place']) ? $data['birth_place'] : '未設定' }}
                </dd>
            </div>

            <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <dt class="text-xs text-slate-400 font-bold">出生体重</dt>
                <dd class="text-sm text-slate-700">
                    {{ !empty($data['birth_weight']) ? number_format($data['birth_weight']) . 'g' : '未設定' }}
                </dd>
            </div>
        </dl>

        <!-- Actions -->
        <form action="{{ route('setup.store', ['token' => $token]) }}" method="POST" class="space-y-3 pt-4 border-t border-slate-100">
            @csrf
            @foreach ($data as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endforeach

            <button 
                type="submit" 
                class="w-full py-4 px-6 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 active:scale-[0.99] text-white font-bold text-lg rounded-xl shadow-md transition-all duration-200 cursor-pointer text-center block"
            >
                この内容で登録する！✨
            </button>

            <a 
                href="{{ route('setup.show', ['token' => $token]) }}" 
                class="w-full py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl transition text-center block"
            >
                ← 修正する
            </a>
        </form>
    </div>
</div>
@endsection
