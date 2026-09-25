@extends('layouts.app')

@section('title', 'システム診断・運用確認')

@section('header_badge')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
        診断モード（ネタバレ防止）
    </span>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Notice Card -->
    <div class="bg-blue-50 border border-blue-200/70 rounded-2xl p-4 sm:p-5 flex items-start gap-3">
        <span class="text-2xl shrink-0">🛡️</span>
        <div>
            <h2 class="text-sm font-bold text-blue-900">ネタバレ防止診断画面</h2>
            <p class="text-xs text-blue-800 mt-1 leading-relaxed">
                この画面では、運用者が正解の名前を知らずにシステムとゲームの稼働状況を確認できるよう、個人情報や名前の平文を表示しません。
            </p>
        </div>
    </div>

    <!-- Status Overview -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-amber-100 space-y-6">
        <div>
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
                <span>📋</span> 赤ちゃん情報 登録状況
            </h2>
            <dl class="divide-y divide-slate-100 text-sm mt-2">
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-500 font-medium">プロフィール基本データ</dt>
                    <dd class="font-bold {{ $diagnostics['baby_profile']['registered'] ? 'text-emerald-600' : 'text-slate-400' }} flex items-center gap-1">
                        @if ($diagnostics['baby_profile']['registered'])
                            <span>✓</span> 登録済み
                        @else
                            <span>-</span> 未登録
                        @endif
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-500 font-medium">名前 (漢字HMAC)</dt>
                    <dd class="font-bold {{ $diagnostics['baby_profile']['has_name'] ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $diagnostics['baby_profile']['has_name'] ? '✓ 登録済み (平文非保持)' : '未登録' }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-500 font-medium">読み (かなHMAC)</dt>
                    <dd class="font-bold {{ $diagnostics['baby_profile']['has_reading'] ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $diagnostics['baby_profile']['has_reading'] ? '✓ 登録済み (平文非保持)' : '未登録' }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-500 font-medium">生年月日</dt>
                    <dd class="font-bold {{ $diagnostics['baby_profile']['has_birth_date'] ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $diagnostics['baby_profile']['has_birth_date'] ? '✓ 登録済み' : '未登録' }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-500 font-medium">出生時刻</dt>
                    <dd class="font-bold {{ $diagnostics['baby_profile']['has_birth_time'] ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $diagnostics['baby_profile']['has_birth_time'] ? '✓ 登録済み' : '未登録' }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-500 font-medium">出生地</dt>
                    <dd class="font-bold {{ $diagnostics['baby_profile']['has_birth_place'] ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $diagnostics['baby_profile']['has_birth_place'] ? '✓ 登録済み (暗号化)' : '未登録' }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-500 font-medium">出生体重</dt>
                    <dd class="font-bold {{ $diagnostics['baby_profile']['has_birth_weight'] ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $diagnostics['baby_profile']['has_birth_weight'] ? '✓ 登録済み' : '未登録' }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-500 font-medium">ゲーム受付状態</dt>
                    <dd class="font-bold text-slate-800">
                        {{ $diagnostics['baby_profile']['status'] }}
                    </dd>
                </div>
            </dl>
        </div>

        <div>
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
                <span>🎯</span> 回答・ゲーム進行状況
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
                <div class="bg-slate-50 p-3 rounded-xl text-center border border-slate-100">
                    <span class="text-xs text-slate-500 block">総回答数</span>
                    <strong class="text-xl font-bold text-slate-800">{{ $diagnostics['game_stats']['total_guesses'] }}</strong>
                </div>
                <div class="bg-emerald-50 p-3 rounded-xl text-center border border-emerald-100">
                    <span class="text-xs text-emerald-600 block">正解件数</span>
                    <strong class="text-xl font-bold text-emerald-700">{{ $diagnostics['game_stats']['correct_guesses'] }}</strong>
                </div>
                <div class="bg-amber-50 p-3 rounded-xl text-center border border-amber-100">
                    <span class="text-xs text-amber-600 block">読み一致件数</span>
                    <strong class="text-xl font-bold text-amber-700">{{ $diagnostics['game_stats']['reading_matches'] }}</strong>
                </div>
                <div class="bg-rose-50 p-3 rounded-xl text-center border border-rose-100">
                    <span class="text-xs text-rose-600 block">不正解件数</span>
                    <strong class="text-xl font-bold text-rose-700">{{ $diagnostics['game_stats']['wrong_guesses'] }}</strong>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
                <span>⚙️</span> システム環境情報
            </h2>
            <dl class="divide-y divide-slate-100 text-xs mt-2">
                <div class="py-2 flex justify-between items-center">
                    <dt class="text-slate-400">Laravel Version</dt>
                    <dd class="font-mono text-slate-700">{{ $diagnostics['system']['laravel_version'] }}</dd>
                </div>
                <div class="py-2 flex justify-between items-center">
                    <dt class="text-slate-400">PHP Version</dt>
                    <dd class="font-mono text-slate-700">{{ $diagnostics['system']['php_version'] }}</dd>
                </div>
                <div class="py-2 flex justify-between items-center">
                    <dt class="text-slate-400">Database Driver</dt>
                    <dd class="font-mono text-slate-700">{{ $diagnostics['system']['db_driver'] }}</dd>
                </div>
                <div class="py-2 flex justify-between items-center">
                    <dt class="text-slate-400">HMAC Secret設定</dt>
                    <dd class="font-bold {{ $diagnostics['system']['hmac_configured'] ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $diagnostics['system']['hmac_configured'] ? '正常 (設定済み)' : '未設定' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
