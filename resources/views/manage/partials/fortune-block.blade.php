@if (!empty($text))
    <div>
        <span class="inline-flex rounded-full bg-amber-100 text-amber-900 px-3 py-1 text-[15px] font-bold">{{ $heading }}</span>
        <p class="fortune-text mt-2">{!! $hl($text) !!}</p>
    </div>
@endif
