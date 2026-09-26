@extends('layouts.app')

@section('title', '赤ちゃんの名前、当ててみて')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border border-amber-100 shadow-sm text-center">
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
            赤ちゃんの名前、当ててみて
        </h1>
        <p class="text-slate-500 text-sm mt-2">
            生まれたばかりの赤ちゃんの「<strong class="text-slate-700 font-semibold">下の名前</strong>」を予想してみてね！
        </p>

        <!-- Hints (Sex, Birth Date) -->
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
            @if ($sexLabel)
                <div class="inline-flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">
                    <span>性別:</span>
                    <span>{{ $sexLabel }}</span>
                </div>
            @endif

            @if ($birthDateLabel)
                <div class="inline-flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700">
                    <span>{{ $birthDateLabel }} 生まれ</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Closed Game State -->
    @if ($profile->isClosed())
        <div class="bg-slate-100 border border-slate-200 rounded-2xl p-6 text-center text-slate-600">
            <h2 class="text-lg font-bold">この名前当ては終わりました</h2>
            <p class="text-sm text-slate-500 mt-1">ご参加ありがとうございました！</p>
        </div>
    @else
        <!-- Revealed or Already Won Banner -->
        <div id="revealed-container" class="{{ $hasWon || $profile->isRevealed() ? '' : 'hidden' }} space-y-4">
            <div class="bg-gradient-to-br from-rose-500 to-pink-500 text-white rounded-2xl p-6 shadow-lg text-center relative overflow-hidden">
                <h2 class="text-2xl sm:text-3xl font-black mb-1">
                    大正解！！
                </h2>
                <p class="text-pink-100 text-sm mb-4">
                    赤ちゃんの名前はこちらです！
                </p>
                
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 inline-block w-full max-w-sm">
                    <p id="revealed-kana" class="text-xs tracking-widest text-pink-200">
                        {{ $revealedFullNameKana ?? '' }}
                    </p>
                    <p id="revealed-name" class="text-3xl sm:text-4xl font-extrabold text-white mt-1 tracking-wider">
                        {{ $revealedFullName ?? '' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Guess Form Card -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-amber-100 space-y-5">
            <!-- Result Alert Message Area -->
            <div id="result-alert" class="hidden transition-all duration-300">
                <div id="result-box" class="rounded-xl p-4 flex items-start gap-3 border">
                    <span id="result-icon" class="text-2xl shrink-0"></span>
                    <div>
                        <h3 id="result-title" class="font-bold text-sm"></h3>
                        <p id="result-message" class="text-xs mt-0.5"></p>
                    </div>
                </div>
            </div>

            <form id="guess-form" class="space-y-5">
                <!-- Baby Name Input -->
                <div>
                    <label for="baby_name" class="block text-sm font-bold text-slate-700 mb-1.5">
                        赤ちゃんの名前（下の名前） <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="baby_name" 
                            name="baby_name" 
                            required 
                            autofocus 
                            class="w-full px-4 py-4 rounded-xl border-2 border-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 text-xl font-bold text-slate-800 placeholder:text-slate-300 placeholder:text-base placeholder:font-normal bg-white transition text-center sm:text-left"
                            placeholder="例: 陽葵 または ひなた" 
                            maxlength="50"
                        >
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5">
                        漢字だけでなく、ひらがな（読み）でも判定できます！
                    </p>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    id="submit-button"
                    class="w-full py-4 px-6 bg-gradient-to-r from-amber-500 to-rose-400 hover:from-amber-600 hover:to-rose-500 active:scale-[0.99] text-white font-bold text-lg rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span id="button-text">答える</span>
                </button>
            </form>

            <!-- Counter and Status -->
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span id="attempt-display" class="font-medium">
                    現在 <strong id="attempt-count" class="text-amber-600 font-bold text-sm">{{ $attemptCount }}</strong> 回目の挑戦
                </span>
                <span class="text-slate-400">何回でも回答できます</span>
            </div>
        </div>

        <!-- Guess History -->
        <div id="guess-history-card" class="bg-white rounded-2xl p-6 shadow-sm border border-amber-100 space-y-3 {{ $guessHistory->isEmpty() ? 'hidden' : '' }}">
            <h2 class="text-sm font-bold text-slate-700">みんなの回答</h2>
            <ul id="guess-history-list" class="space-y-2">
                @foreach ($guessHistory as $guess)
                    @php
                        $resultLabel = match ($guess->result) {
                            'correct' => '正解',
                            'reading_match' => '読み一致',
                            default => 'はずれ',
                        };
                        $resultClass = match ($guess->result) {
                            'correct' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'reading_match' => 'bg-amber-50 text-amber-700 border-amber-200',
                            default => 'bg-slate-50 text-slate-600 border-slate-200',
                        };
                    @endphp
                    <li class="flex items-center justify-between gap-3 rounded-xl border px-3 py-2 {{ $resultClass }}">
                        <span class="font-bold text-sm text-slate-800 {{ !empty($guess->is_mosaic) ? 'tracking-widest select-none' : '' }}" @if (!empty($guess->is_mosaic)) title="正解のため非表示" @endif>{{ $guess->guess_display ?? $guess->guess }}</span>
                        <span class="text-xs font-bold shrink-0">{{ $resultLabel }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('guess-form');
    if (!form) return;

    const babyNameInput = document.getElementById('baby_name');
    const submitBtn = document.getElementById('submit-button');
    const btnText = document.getElementById('button-text');

    const resultAlert = document.getElementById('result-alert');
    const resultBox = document.getElementById('result-box');
    const resultIcon = document.getElementById('result-icon');
    const resultTitle = document.getElementById('result-title');
    const resultMessage = document.getElementById('result-message');
    const attemptCount = document.getElementById('attempt-count');

    const revealedContainer = document.getElementById('revealed-container');
    const revealedName = document.getElementById('revealed-name');
    const revealedKana = document.getElementById('revealed-kana');

    const historyCard = document.getElementById('guess-history-card');
    const historyList = document.getElementById('guess-history-list');

    const resultMeta = {
        correct: { label: '正解', row: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
        reading_match: { label: '読み一致', row: 'bg-amber-50 text-amber-700 border-amber-200' },
        wrong: { label: 'はずれ', row: 'bg-slate-50 text-slate-600 border-slate-200' },
    };

    let canSeeSpoilers = @json($canSeeSpoilers);

    function mosaicGuess(name) {
        const len = Math.max(2, Array.from(name || '').length);
        return '●'.repeat(len);
    }

    function displayGuess(name, result) {
        if (result === 'correct' && !canSeeSpoilers) {
            return mosaicGuess(name);
        }
        return name;
    }

    function appendHistory(name, result) {
        if (!historyList || !historyCard) return;
        const meta = resultMeta[result] || resultMeta.wrong;
        const shown = displayGuess(name, result);
        const li = document.createElement('li');
        li.className = `flex items-center justify-between gap-3 rounded-xl border px-3 py-2 ${meta.row}`;
        li.innerHTML = `<span class="font-bold text-sm text-slate-800"></span><span class="text-xs font-bold shrink-0"></span>`;
        li.children[0].textContent = shown;
        if (result === 'correct' && shown !== name) {
            li.children[0].classList.add('tracking-widest', 'select-none');
            li.children[0].title = '正解のため非表示';
        }
        li.children[1].textContent = meta.label;
        historyList.appendChild(li);
        historyCard.classList.remove('hidden');
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const babyName = babyNameInput.value.trim();

        if (!babyName) {
            babyNameInput.focus();
            return;
        }

        // 送信中UI
        submitBtn.disabled = true;
        btnText.textContent = '確認しています';
        resultAlert.classList.add('hidden');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch("{{ route('game.guess', ['token' => $token]) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    baby_name: babyName,
                })
            });

            const data = await response.json();

            if (!response.ok) {
                showResult('error', 'エラーが発生しました', data.message || 'もう一度お試しください。');
                return;
            }

            // 試行回数の更新
            if (data.attempt_no) {
                attemptCount.textContent = data.attempt_no;
            }

            if (data.is_correct) {
                canSeeSpoilers = true;
            }

            appendHistory(data.guess || babyName, data.result);

            // 判定結果に応じた表示
            if (data.is_correct) {
                showResult('correct', '大正解です', data.message);
                
                // 正解情報の表示 (正式氏名・読みのみ)
                if (data.revealed_name) {
                    revealedName.textContent = data.revealed_name.full_name;
                    revealedKana.textContent = data.revealed_name.full_name_kana;
                    revealedContainer.classList.remove('hidden');
                    revealedContainer.scrollIntoView({ behavior: 'smooth' });
                }
            } else if (data.is_reading_match) {
                showResult('reading_match', '読みは合っています', data.message);
                babyNameInput.focus();
                babyNameInput.select();
            } else {
                showResult('wrong', 'ちがう名前です', data.message);
                babyNameInput.focus();
                babyNameInput.select();
            }

        } catch (err) {
            console.error(err);
            showResult('error', '通信エラー', '通信に失敗しました。接続をご確認の上もう一度お試しください。');
        } finally {
            submitBtn.disabled = false;
            btnText.textContent = '答える';
        }
    });

    function showResult(type, title, message) {
        resultAlert.classList.remove('hidden');
        resultBox.className = 'rounded-xl p-4 flex items-start gap-3 border transition-all duration-300';

        if (type === 'correct') {
            resultBox.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-900');
            resultIcon.textContent = '🎉';
            resultTitle.className = 'font-bold text-sm text-emerald-800';
            resultMessage.className = 'text-xs mt-0.5 text-emerald-700';
        } else if (type === 'reading_match') {
            resultBox.classList.add('bg-amber-50', 'border-amber-300', 'text-amber-900');
            resultIcon.textContent = '💡';
            resultTitle.className = 'font-bold text-sm text-amber-800';
            resultMessage.className = 'text-xs mt-0.5 text-amber-700';
        } else if (type === 'wrong') {
            resultBox.classList.add('bg-rose-50', 'border-rose-200', 'text-rose-900');
            resultIcon.textContent = '🙅';
            resultTitle.className = 'font-bold text-sm text-rose-800';
            resultMessage.className = 'text-xs mt-0.5 text-rose-700';
        } else {
            resultBox.classList.add('bg-slate-50', 'border-slate-200', 'text-slate-800');
            resultIcon.textContent = '⚠️';
            resultTitle.className = 'font-bold text-sm text-slate-800';
            resultMessage.className = 'text-xs mt-0.5 text-slate-700';
        }

        resultTitle.textContent = title;
        resultMessage.textContent = message;
    }
});
</script>
@endpush
