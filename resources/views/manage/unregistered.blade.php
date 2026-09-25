@extends('layouts.app')

@section('title', '管理画面 - 未登録')

@section('header_badge')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
        管理者用
    </span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-amber-100 text-center space-y-4">
        <div class="text-4xl">📝</div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-800">
            赤ちゃん情報は未登録です
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-md mx-auto">
            赤ちゃん情報がまだ登録されていません。<br>
            初期セットアップURLから情報の登録を行ってください。
        </p>

        @if (config('app.env') === 'local' && config('game.setup_token'))
            <div class="mt-6 pt-6 border-t border-slate-100">
                <a 
                    href="{{ url('/setup/' . config('game.setup_token')) }}" 
                    class="inline-block py-3 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition"
                >
                    初期セットアップ画面を開く →
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
