@if (!empty($items))
    <div>
        <span class="inline-flex rounded-full bg-amber-100 text-amber-900 px-3 py-1 text-[15px] font-bold">{{ $heading ?? '強み' }}</span>
        <ul class="mt-2 space-y-2">
            @foreach ($items as $item)
                <li class="flex gap-3 rounded-xl bg-emerald-50 px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 mt-1 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    <span class="fortune-text">{!! $hl($item) !!}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
