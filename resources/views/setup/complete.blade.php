@extends('layouts.app')

@section('title', '赤ちゃん情報の登録が完了しました！')

@section('header_badge')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
        登録完了
    </span>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Success Banner -->
    <div class="bg-gradient-to-br from-emerald-500 to-teal-500 text-white rounded-2xl p-6 sm:p-8 shadow-lg text-center space-y-2">
        <div class="text-4xl animate-bounce">🎉</div>
        <h1 class="text-2xl sm:text-3xl font-extrabold">
            登録が完了しました！
        </h1>
        <p class="text-emerald-100 text-xs sm:text-sm leading-relaxed max-w-md mx-auto">
            赤ちゃんの情報が安全に暗号化されて登録されました。<br>
            このURLをお友達やご家族に送って、名前を当ててもらいましょう！
        </p>
    </div>

    <!-- Share URL Card -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-amber-100 space-y-5">
        <div>
            <span class="inline-block px-2.5 py-1 bg-amber-50 text-amber-800 rounded-lg text-xs font-bold mb-2">
                🎮 お友達に送る参加URL
            </span>
            <p class="text-xs text-slate-500 mb-2">
                以下のURLをコピーして、LINEやSNSのメッセージ等でシェアしてください：
            </p>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <input 
                    type="text" 
                    id="game-url" 
                    readonly 
                    value="{{ $gameUrl }}" 
                    class="flex-1 bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-xs sm:text-sm font-mono text-slate-800 select-all"
                >
                <button 
                    type="button" 
                    id="copy-btn"
                    onclick="copyToClipboard('{{ $gameUrl }}', this)"
                    class="px-5 py-3 bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs transition cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
                >
                    <span>📋</span>
                    <span id="copy-btn-text">URLをコピー</span>
                </button>
            </div>
            <p id="copy-toast" class="text-xs font-bold text-emerald-600 mt-1.5 hidden flex items-center gap-1">
                <span>✓</span> クリップボードにコピーしました！
            </p>
        </div>

        <div class="pt-4 border-t border-slate-100">
            <a 
                href="{{ $gameUrl }}" 
                target="_blank" 
                class="w-full py-3.5 px-4 bg-slate-800 hover:bg-slate-900 text-white font-bold text-sm rounded-xl text-center block transition"
            >
                ゲーム画面を開いて確認してみる 🚀
            </a>
        </div>
    </div>

    <!-- Manage URL Card (For Parents) -->
    <div class="bg-purple-50/60 border border-purple-100 rounded-2xl p-6 space-y-3">
        <h2 class="text-sm font-bold text-purple-900 flex items-center gap-1.5">
            <span>⚙️</span> ご両親用 管理画面URL
        </h2>
        <p class="text-xs text-purple-800 leading-relaxed">
            友達の回答状況の確認や、AI鑑定の生成は管理画面から行えます。<br>
            このURLはご両親専用ですので大切に保管（ブックマーク）してください：
        </p>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <input 
                type="text" 
                readonly 
                value="{{ $manageUrl }}" 
                class="flex-1 bg-white border border-purple-200 px-3 py-2 rounded-xl text-xs font-mono text-purple-950 select-all"
            >
            <a 
                href="{{ $manageUrl }}" 
                class="px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs rounded-xl text-center shrink-0 transition"
            >
                管理画面へ（鑑定生成）
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.getElementById('copy-toast');
        const btnText = document.getElementById('copy-btn-text');
        if (toast) toast.classList.remove('hidden');
        if (btnText) btnText.textContent = 'コピーしました！';
        setTimeout(() => {
            if (toast) toast.classList.add('hidden');
            if (btnText) btnText.textContent = 'URLをコピー';
        }, 3000);
    }).catch(err => {
        console.error('Failed to copy', err);
    });
}
</script>
@endpush
