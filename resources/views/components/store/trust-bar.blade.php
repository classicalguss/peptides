<div class="border-y border-white/5 bg-black/60">
    <div class="mx-auto grid max-w-7xl gap-px px-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach (config('theme.trust') as $item)
            <div class="flex items-start gap-3 py-6 sm:px-4">
                <svg class="mt-0.5 size-5 shrink-0 text-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l7.5 3.5v6c0 4.5-3.2 8.3-7.5 10.5C7.7 19.8 4.5 16 4.5 11.5v-6L12 2z" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8.8 12.2l2.2 2.2 4.2-4.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div>
                    <p class="text-[13px] font-extrabold tracking-wide text-white uppercase">{{ $item['label'] }}</p>
                    <p class="mt-0.5 text-xs text-white/45">{{ $item['detail'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
