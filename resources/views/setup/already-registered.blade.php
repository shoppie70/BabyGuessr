@extends('layouts.app')

@section('title', '登録は完了しています')

@section('header_badge')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
        登録済み
    </span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-amber-100 text-center space-y-4">
        <div class="text-4xl">✅</div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-800">
            登録は完了しています
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-md mx-auto">
            赤ちゃん情報の初期登録は既に完了しています。<br>
            登録内容の確認やゲームの進行管理は、ご両親用の管理URLから行ってください。
        </p>

        @php
            $profile = \App\Models\BabyProfile::first();
        @endphp
        @if ($profile)
            <div class="mt-6 pt-6 border-t border-slate-100">
                <a 
                    href="{{ url('/manage/' . $profile->manage_token) }}" 
                    class="inline-block py-3 px-6 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl transition"
                >
                    管理画面を開く →
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
