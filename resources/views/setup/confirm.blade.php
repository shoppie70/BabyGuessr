@extends('layouts.app')

@section('title', 'この内容で登録します')

@section('content')
<div class="space-y-6">
    <div class="text-center space-y-2">
        <h1 class="text-2xl font-bold">この内容で登録します</h1>
        <p class="text-sm text-slate-500">登録したあとは、名前を直せません。</p>
    </div>

    <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-6">
        <p class="text-3xl font-bold text-center">{{ $data['family_name'] }} {{ $data['given_name'] }}</p>
        <p class="text-center text-[#57534E] mt-1">{{ $data['family_name_kana'] }} {{ $data['given_name_kana'] }}</p>

        <dl class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">性別</dt>
                <dd class="font-bold">{{ ['female' => '女の子', 'male' => '男の子', 'other' => '答えたくない'][$data['sex']] ?? '' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">生まれた日</dt>
                <dd class="font-bold">{{ \Carbon\Carbon::parse($data['birth_date'])->format('Y年n月j日') }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">生まれた時刻</dt>
                <dd class="font-bold">{{ !empty($data['birth_time']) ? $data['birth_time'] : 'わからない' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">生まれた場所</dt>
                <dd class="font-bold text-right">{{ !empty($data['birth_place']) ? $data['birth_place'] : 'わからない' }}</dd>
            </div>
        </dl>

        <form action="{{ route('setup.store', ['token' => $token]) }}" method="POST" class="mt-6 space-y-3">
            @csrf
            @foreach ($data as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endforeach
            <button type="submit" class="w-full min-h-12 rounded-xl bg-gradient-to-r from-amber-500 to-rose-400 text-white font-bold shadow-md cursor-pointer transition-all duration-200 hover:from-amber-600 hover:to-rose-500">この内容で登録する</button>
            <a href="{{ route('setup.show', ['token' => $token]) }}" class="w-full min-h-12 rounded-xl border border-slate-200 bg-slate-50 font-bold flex items-center justify-center">戻って直す</a>
        </form>
    </div>
</div>
@endsection
