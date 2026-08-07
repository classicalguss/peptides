@props(['eyebrow' => null, 'title', 'subtitle' => null, 'align' => 'center'])

<div {{ $attributes->class([
        'reveal',
        $align === 'center' ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl',
    ]) }}>
    @if ($eyebrow)
        <p class="eyebrow text-gold">{{ $eyebrow }}</p>
    @endif

    <h2 class="display-title mt-3 text-4xl text-white sm:text-5xl">
        {!! $title !!}
    </h2>

    @if ($subtitle)
        <p class="mt-4 text-[15px] leading-relaxed text-white/50">{{ $subtitle }}</p>
    @endif
</div>
