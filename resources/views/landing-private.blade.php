@extends('layouts.app')

@section('title', '限定公開ゲーム')

@section('header_badge')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
        非公開
    </span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-amber-100 text-center space-y-4">
        <div class="text-4xl">🔒</div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-800">
            限定公開Webアプリケーション
        </h1>
        <p class="text-sm text-slate-500 leading-relaxed max-w-md mx-auto">
            当アプリは一般公開トップページを持たない1ゲーム限定のサービスです。<br>
            共有された専用URL（招待URL）からアクセスしてください。
        </p>

        @if (config('app.env') === 'local')
            <!-- ローカル開発環境向け案内 -->
            <div class="mt-6 pt-6 border-t border-slate-100 text-left bg-amber-50/60 rounded-xl p-4">
                <span class="text-xs font-bold text-amber-800 block mb-2">🛠️ ローカル開発用ショートカット</span>
                <ul class="space-y-2 text-xs">
                    <li>
                        <a href="{{ url('/setup/demo-setup-token-2026') }}" class="font-bold text-emerald-700 hover:underline flex items-center gap-1.5">
                            <span>👶</span> 初期セットアップ画面 (ご両親登録用)
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/g/demo-game-token-2026') }}" class="font-bold text-amber-700 hover:underline flex items-center gap-1.5">
                            <span>🎮</span> 名前当てゲーム画面 (参加者用)
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/manage/demo-manage-token-2026') }}" class="font-bold text-purple-700 hover:underline flex items-center gap-1.5">
                            <span>⚙️</span> 管理画面 (ご両親・運営用)
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/diagnostics/demo-diag-token-2026') }}" class="font-bold text-slate-700 hover:underline flex items-center gap-1.5">
                            <span>🛡️</span> 診断画面 (ネタバレ防止・稼働確認用)
                        </a>
                    </li>
                </ul>
                <p class="text-[11px] text-slate-400 mt-2">※テストデータ（山田花）投入時に利用可能です。</p>
            </div>
        @endif
    </div>
</div>
@endsection
