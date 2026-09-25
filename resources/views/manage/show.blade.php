@extends('layouts.app')

@section('title', '管理・回答確認')

@section('header_badge')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
        管理者・ご両親用
    </span>
@endsection

@section('content')
<div class="space-y-6">
    @if (session('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-bold">
            {{ session('status') }}
        </div>
    @endif

    <!-- Profile Overview Card -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-amber-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <span>👶</span> 赤ちゃん登録情報
            </h2>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $profile->isOpen() ? 'bg-emerald-100 text-emerald-800' : ($profile->isRevealed() ? 'bg-pink-100 text-pink-800' : 'bg-slate-100 text-slate-700') }}">
                状態: {{ $profile->isOpen() ? '回答受付中' : ($profile->isRevealed() ? '名前公開中' : '終了') }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-4 text-xs sm:text-sm">
            <div>
                <span class="text-slate-400 block text-xs">氏名 (漢字)</span>
                <strong class="text-base text-slate-800">{{ $profile->full_name }}</strong>
            </div>
            <div>
                <span class="text-slate-400 block text-xs">氏名 (読み)</span>
                <strong class="text-base text-slate-800">{{ $profile->full_name_kana }}</strong>
            </div>
            <div>
                <span class="text-slate-400 block text-xs">生年月日・時刻</span>
                <span class="text-slate-700">{{ $profile->birth_date->format('Y年n月j日') }} {{ $profile->birth_time ? substr($profile->birth_time, 0, 5) : '' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-xs">出生地 / 体重</span>
                <span class="text-slate-700">{{ $profile->birth_place ?? '未設定' }} / {{ $profile->birth_weight ? number_format($profile->birth_weight) . 'g' : '未設定' }}</span>
            </div>
        </div>

        <!-- Status Change Form -->
        <form action="{{ route('manage.status', ['token' => $token]) }}" method="POST" class="pt-4 border-t border-slate-100 flex flex-wrap items-center gap-3">
            @csrf
            <label for="status" class="text-xs font-bold text-slate-600">ゲーム状態の変更:</label>
            <select name="status" id="status" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-amber-400 focus:outline-none">
                <option value="open" {{ $profile->isOpen() ? 'selected' : '' }}>回答受付中 (open)</option>
                <option value="revealed" {{ $profile->isRevealed() ? 'selected' : '' }}>正解公開中 (revealed)</option>
                <option value="closed" {{ $profile->isClosed() ? 'selected' : '' }}>受付停止 (closed)</option>
            </select>
            <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-bold cursor-pointer transition">
                変更する
            </button>
        </form>
    </div>

    <!-- URLs Card -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-amber-100 space-y-4">
        <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
            <span>🔗</span> 共有URL
        </h2>
        
        <div class="space-y-3">
            <div>
                <span class="text-xs font-bold text-slate-600 block mb-1">友人・家族用 参加URL（名前当てゲーム）</span>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ $gameUrl }}" class="flex-1 bg-slate-50 border border-slate-200 px-3 py-2 rounded-xl text-xs font-mono text-slate-700 select-all" id="game-url-input">
                    <a href="{{ $gameUrl }}" target="_blank" class="px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shrink-0">開く</a>
                </div>
            </div>

            <div>
                <span class="text-xs font-bold text-slate-600 block mb-1">運用者向け 診断URL（ネタバレ防止）</span>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ $diagnosticsUrl }}" class="flex-1 bg-slate-50 border border-slate-200 px-3 py-2 rounded-xl text-xs font-mono text-slate-700 select-all">
                    <a href="{{ $diagnosticsUrl }}" target="_blank" class="px-3 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-xl text-xs font-bold shrink-0">開く</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Guesses Log Table -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-amber-100 space-y-4">
        <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
            <span>📝</span> 回答ログ履歴 (最新順)
        </h2>

        @if ($guesses->isEmpty())
            <p class="text-xs text-slate-400 text-center py-6">まだ回答はありません。</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-medium">
                            <th class="py-2">日時</th>
                            <th class="py-2">ニックネーム</th>
                            <th class="py-2">試行回数</th>
                            <th class="py-2">判定結果</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($guesses as $g)
                            <tr>
                                <td class="py-2.5 text-slate-400 font-mono">{{ $g->created_at->format('m/d H:i') }}</td>
                                <td class="py-2.5 font-bold text-slate-700">{{ $g->challenger_name ?? '名無しさん' }}</td>
                                <td class="py-2.5 text-slate-600">{{ $g->attempt_no }}回目</td>
                                <td class="py-2.5">
                                    @if ($g->isCorrect())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                            🎉 正解
                                        </span>
                                    @elseif ($g->isReadingMatch())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                            💡 読み一致
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700">
                                            不正解
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pt-2">
                {{ $guesses->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
