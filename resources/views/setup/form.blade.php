@extends('layouts.app')

@section('title', '赤ちゃんの初期情報登録')

@section('header_badge')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
        初期セットアップ
    </span>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Notice Card -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-amber-100 text-center relative overflow-hidden">
        <div class="absolute -right-4 -bottom-4 text-7xl opacity-10 select-none">🍼</div>
        
        <span class="inline-block px-3 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-semibold tracking-wider mb-2">
            INITIAL SETUP
        </span>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">
            赤ちゃんの情報を登録する
        </h1>
        <p class="text-slate-500 text-xs sm:text-sm mt-2 leading-relaxed max-w-md mx-auto">
            ご出産おめでとうございます！<br>
            登録した情報をもとに、友達みんなで名前を当てるゲームを開始できます。
        </p>
    </div>

    <!-- Error Alert -->
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 text-xs">
            <p class="font-bold mb-1">入力内容をご確認ください：</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Setup Form -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-amber-100">
        <form action="{{ route('setup.confirm', ['token' => $token]) }}" method="POST" class="space-y-6">
            @csrf

            <!-- Name (Kanji) -->
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-slate-700 flex items-center gap-1.5 border-b border-slate-100 pb-2">
                    <span>✍️</span> お名前（漢字） <span class="text-rose-500 text-xs font-normal">※必須</span>
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="family_name" class="block text-xs font-bold text-slate-600 mb-1">
                            姓（苗字）
                        </label>
                        <input 
                            type="text" 
                            id="family_name" 
                            name="family_name" 
                            required 
                            value="{{ old('family_name', $formData['family_name'] ?? '') }}"
                            placeholder="例: 山田" 
                            maxlength="50"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 text-base font-bold text-slate-800 bg-white"
                        >
                    </div>
                    <div>
                        <label for="given_name" class="block text-xs font-bold text-slate-600 mb-1">
                            名（下の名前・正解）
                        </label>
                        <input 
                            type="text" 
                            id="given_name" 
                            name="given_name" 
                            required 
                            value="{{ old('given_name', $formData['given_name'] ?? '') }}"
                            placeholder="例: 花" 
                            maxlength="50"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 text-base font-bold text-slate-800 bg-white"
                        >
                    </div>
                </div>
                <p class="text-[11px] text-slate-400">※苗字はゲーム画面でヒントとして表示されます。下の名前は暗号化され、正解するまで誰にも見えません。</p>
            </div>

            <!-- Name (Kana) -->
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-slate-700 flex items-center gap-1.5 border-b border-slate-100 pb-2">
                    <span>🔤</span> お名前の読み（ひらがな） <span class="text-rose-500 text-xs font-normal">※必須</span>
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="family_name_kana" class="block text-xs font-bold text-slate-600 mb-1">
                            姓の読み
                        </label>
                        <input 
                            type="text" 
                            id="family_name_kana" 
                            name="family_name_kana" 
                            required 
                            value="{{ old('family_name_kana', $formData['family_name_kana'] ?? '') }}"
                            placeholder="例: やまだ" 
                            maxlength="50"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 text-sm font-medium text-slate-800 bg-white"
                        >
                    </div>
                    <div>
                        <label for="given_name_kana" class="block text-xs font-bold text-slate-600 mb-1">
                            名の読み
                        </label>
                        <input 
                            type="text" 
                            id="given_name_kana" 
                            name="given_name_kana" 
                            required 
                            value="{{ old('given_name_kana', $formData['given_name_kana'] ?? '') }}"
                            placeholder="例: はな" 
                            maxlength="50"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 text-sm font-medium text-slate-800 bg-white"
                        >
                    </div>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-slate-700 flex items-center gap-1.5 border-b border-slate-100 pb-2">
                    <span>🌟</span> 赤ちゃんの基本情報
                </h2>

                <!-- Sex -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">
                        性別 <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer has-checked:border-pink-500 has-checked:bg-pink-50/50 hover:bg-slate-50 transition text-xs font-bold">
                            <input type="radio" name="sex" value="female" class="mr-1.5 accent-pink-500" {{ old('sex', $formData['sex'] ?? 'female') === 'female' ? 'checked' : '' }}>
                            女の子 👧
                        </label>
                        <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer has-checked:border-blue-500 has-checked:bg-blue-50/50 hover:bg-slate-50 transition text-xs font-bold">
                            <input type="radio" name="sex" value="male" class="mr-1.5 accent-blue-500" {{ old('sex', $formData['sex'] ?? '') === 'male' ? 'checked' : '' }}>
                            男の子 👦
                        </label>
                        <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer has-checked:border-amber-500 has-checked:bg-amber-50/50 hover:bg-slate-50 transition text-xs font-bold">
                            <input type="radio" name="sex" value="other" class="mr-1.5 accent-amber-500" {{ old('sex', $formData['sex'] ?? '') === 'other' ? 'checked' : '' }}>
                            その他
                        </label>
                    </div>
                </div>

                <!-- Birth Date & Time -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="birth_date" class="block text-xs font-bold text-slate-600 mb-1">
                            生年月日 <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            id="birth_date" 
                            name="birth_date" 
                            required 
                            value="{{ old('birth_date', $formData['birth_date'] ?? '') }}"
                            max="{{ date('Y-m-d') }}"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 text-sm font-medium text-slate-800 bg-white"
                        >
                    </div>
                    <div>
                        <label for="birth_time" class="block text-xs font-bold text-slate-600 mb-1">
                            出生時刻 <span class="text-slate-400 font-normal">(任意)</span>
                        </label>
                        <input 
                            type="time" 
                            id="birth_time" 
                            name="birth_time" 
                            value="{{ old('birth_time', $formData['birth_time'] ?? '') }}"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 text-sm font-medium text-slate-800 bg-white"
                        >
                    </div>
                </div>

                <!-- Birth Place & Weight -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="birth_place" class="block text-xs font-bold text-slate-600 mb-1">
                            出生地 <span class="text-slate-400 font-normal">(任意)</span>
                        </label>
                        <input 
                            type="text" 
                            id="birth_place" 
                            name="birth_place" 
                            value="{{ old('birth_place', $formData['birth_place'] ?? '') }}"
                            placeholder="例: 検証県検証市中央区" 
                            maxlength="100"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 text-sm font-medium text-slate-800 bg-white"
                        >
                    </div>
                    <div>
                        <label for="birth_weight" class="block text-xs font-bold text-slate-600 mb-1">
                            出生体重 (g) <span class="text-slate-400 font-normal">(任意)</span>
                        </label>
                        <input 
                            type="number" 
                            id="birth_weight" 
                            name="birth_weight" 
                            value="{{ old('birth_weight', $formData['birth_weight'] ?? '') }}"
                            placeholder="例: 3000" 
                            min="500" 
                            max="10000"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 text-sm font-medium text-slate-800 bg-white"
                        >
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="pt-4">
                <button 
                    type="submit" 
                    class="w-full py-4 px-6 bg-gradient-to-r from-amber-500 to-rose-400 hover:from-amber-600 hover:to-rose-500 active:scale-[0.99] text-white font-bold text-base rounded-xl shadow-md transition-all duration-200 cursor-pointer text-center block"
                >
                    入力内容の確認へ進む →
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
