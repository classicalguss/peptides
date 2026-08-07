@props(['rating' => 0, 'count' => null, 'size' => 'size-3.5'])

<div class="flex items-center gap-1.5">
    <div class="flex items-center gap-0.5">
        @for ($i = 1; $i <= 5; $i++)
            <svg class="{{ $size }} {{ $i <= round($rating) ? 'text-gold' : 'text-white/15' }}"
                 viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10 1.5l2.6 5.3 5.9.85-4.25 4.15 1 5.85L10 14.9l-5.25 2.75 1-5.85L1.5 7.65l5.9-.85L10 1.5z"/>
            </svg>
        @endfor
    </div>
    @if ($count)
        <span class="text-xs text-white/45">{{ number_format($rating, 1) }} ({{ $count }})</span>
    @endif
</div>
