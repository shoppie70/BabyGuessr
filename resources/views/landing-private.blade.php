@extends('layouts.app')

@section('title', 'ページがありません')

@section('content')
<div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-8 text-center space-y-6">
    <h1 class="text-xl font-bold">このページはありません</h1>

    @if (!empty($links))
        <nav class="text-left space-y-2" aria-label="開発用">
            @foreach ($links as $label => $href)
                <a href="{{ $href }}" class="min-h-12 flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 font-bold text-amber-900 cursor-pointer transition-colors duration-200 hover:bg-amber-100">{{ $label }}</a>
            @endforeach
        </nav>
    @endif
</div>
@endsection
