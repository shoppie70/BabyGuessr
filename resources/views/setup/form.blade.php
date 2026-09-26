@extends('layouts.app')

@section('title', '赤ちゃんのことを教えてください')

@section('content')
<div class="space-y-6">
    <div class="text-center space-y-2">
        <h1 class="text-2xl font-bold">赤ちゃんのことを教えてください</h1>
        <p class="text-sm text-slate-500">入れた内容で、名前当てと鑑定を作ります。</p>
    </div>

    @if ($errors->any())
        <div class="bg-white border border-rose-200 text-rose-800 rounded-[20px] p-4 text-sm" role="alert">
            <p class="font-bold mb-1">入力を確認してください</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('setup.confirm', ['token' => $token]) }}" method="POST" class="bg-white rounded-2xl border border-amber-100 shadow-sm p-6 space-y-5">
        @csrf

        @foreach ([
            'family_name' => ['苗字', '山田'],
            'given_name' => ['下の名前', '陽葵'],
            'family_name_kana' => ['苗字のよみ（ひらがな）', 'やまだ'],
            'given_name_kana' => ['下の名前のよみ（ひらがな）', 'ひまり'],
        ] as $name => [$label, $example])
            <div>
                <label for="{{ $name }}" class="block text-sm font-bold mb-1">{{ $label }}</label>
                <input
                    type="text"
                    id="{{ $name }}"
                    name="{{ $name }}"
                    required
                    maxlength="50"
                    value="{{ old($name, $formData[$name] ?? '') }}"
                    placeholder="例: {{ $example }}"
                    class="w-full min-h-12 px-4 rounded-xl border border-slate-200 text-[16px] bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400"
                >
            </div>
        @endforeach

        <fieldset>
            <legend class="text-sm font-bold mb-2">性別</legend>
            <div class="grid grid-cols-1 gap-2">
                @foreach (['female' => '女の子', 'male' => '男の子', 'other' => '答えたくない'] as $value => $label)
                        <label class="min-h-12 flex items-center px-4 rounded-xl border border-slate-200 cursor-pointer has-checked:border-amber-500 has-checked:bg-amber-50">
                        <input type="radio" name="sex" value="{{ $value }}" class="mr-2 accent-amber-500" {{ old('sex', $formData['sex'] ?? '') === $value ? 'checked' : '' }} required>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div>
            <label for="birth_date" class="block text-sm font-bold mb-1">生まれた日</label>
            <input
                type="date"
                id="birth_date"
                name="birth_date"
                required
                max="{{ date('Y-m-d') }}"
                value="{{ old('birth_date', $formData['birth_date'] ?? '') }}"
                class="w-full max-w-full min-h-12 box-border px-3 sm:px-4 py-3 rounded-xl border border-slate-200 text-[16px] leading-normal bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400"
            >
        </div>

        <div>
            <label for="birth_time" class="block text-sm font-bold mb-1">生まれた時刻</label>
            <input
                type="time"
                id="birth_time"
                name="birth_time"
                value="{{ old('birth_time', $formData['birth_time'] ?? '') }}"
                class="w-full max-w-full min-h-12 box-border px-3 sm:px-4 py-3 rounded-xl border border-slate-200 text-[16px] leading-normal bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400"
            >
            <p class="text-xs text-slate-500 mt-1">わからないときは空欄のままで大丈夫です。</p>
        </div>

        <div>
            <label for="birth_place" class="block text-sm font-bold mb-1">生まれた場所</label>
            <input type="text" id="birth_place" name="birth_place" maxlength="100" value="{{ old('birth_place', $formData['birth_place'] ?? '') }}" placeholder="例: 東京都港区" class="w-full min-h-12 px-4 rounded-xl border border-slate-200 text-[16px] bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400">
            <p class="text-xs text-slate-500 mt-1">わかれば。鑑定に使います。</p>
        </div>

        <button type="submit" class="w-full min-h-12 rounded-xl bg-gradient-to-r from-amber-500 to-rose-400 text-white font-bold shadow-md cursor-pointer transition-all duration-200 hover:from-amber-600 hover:to-rose-500">確認する</button>
    </form>
</div>
@endsection
