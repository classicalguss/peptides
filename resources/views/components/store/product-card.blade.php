@props(['profile'])

@php
    use App\Support\Catalog;

    $product = $profile->product;
    $slug = $product->urls->first()?->slug ?? $profile->handle;
    $href = $profile->isStack() ? route('stack', $slug) : route('compound', $slug);
    $image = $product->getFirstMedia('images');
    $from = Catalog::fromPrice($profile);
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge(['style' => $profile->accentStyle()])->class([
       'group relative flex flex-col overflow-hidden rounded-2xl panel hover-lift hover:accent-ring',
   ]) }}>

    @if (\App\Support\Catalog::saveUpTo($profile) > 0)
        <span class="absolute top-2.5 right-2.5 z-10 rounded-full bg-black/70 px-2 py-0.5 text-[9px] font-extrabold tracking-wider text-[var(--accent)] uppercase ring-1 ring-[var(--accent)]/40 backdrop-blur sm:top-3.5 sm:right-3.5 sm:px-2.5 sm:py-1 sm:text-[10px]">
            Save {{ rtrim(rtrim(number_format(\App\Support\Catalog::saveUpTo($profile), 1), '0'), '.') }}%
        </span>
    @endif

    <div class="relative aspect-[4/5] overflow-hidden bg-accent-electric">
        @if ($image)
            <img src="{{ $image->getUrl('medium') }}" alt="{{ $product->translateAttribute('name') }}"
                 loading="lazy"
                 class="absolute inset-0 size-full object-contain p-2 transition-transform duration-500 ease-out group-hover:scale-[1.07] sm:p-4">
        @else
            <div class="absolute inset-0 grid place-items-center">
                <span class="display-title text-3xl text-white/15">{{ Str::of($profile->handle)->explode('-')->first() }}</span>
            </div>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-3 sm:p-5">
        <p class="eyebrow text-[9px] text-[var(--accent)] sm:text-[11px]">{{ $profile->protocol_label ?? $profile->dose }}</p>

        <h3 class="display-title mt-1.5 text-base text-white sm:mt-2 sm:text-xl">
            {{ $product->translateAttribute('name') }}
        </h3>

        <p class="mt-1.5 text-xs leading-relaxed text-white/50 sm:mt-2 sm:text-[13px]">
            {{ $profile->tagline ?? $profile->subtitle }}
        </p>

        <div class="mt-3 flex items-end justify-between border-t border-white/5 pt-3 sm:mt-4 sm:pt-4">
            <div>
                <p class="text-[9px] font-bold tracking-widest text-white/35 uppercase sm:text-[10px]">Starting at</p>
                <p class="display-title text-lg text-foil sm:text-2xl">{{ Catalog::money($from) }}</p>
            </div>
            <span class="hidden rounded-full border border-[var(--accent)]/35 px-3.5 py-1.5 text-[11px] font-extrabold tracking-wider text-[var(--accent)] uppercase transition duration-300 group-hover:bg-[var(--accent)] group-hover:text-black sm:inline-block">
                View
            </span>
        </div>
    </div>
</a>
