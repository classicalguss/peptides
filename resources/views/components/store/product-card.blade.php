@props(['profile'])

@php
    use App\Support\Catalog;

    $product = $profile->product;
    $slug = $product->urls->first()?->slug ?? $profile->handle;
    $href = $profile->isStack() ? route('stack', $slug) : route('compound', $slug);
    $image = $product->getFirstMedia('images');
    $rating = Catalog::rating($profile);
    $from = Catalog::fromPrice($profile);
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge(['style' => $profile->accentStyle()])->class([
       'group relative flex flex-col overflow-hidden rounded-2xl panel hover-lift hover:accent-ring',
   ]) }}>

    @if ($profile->save_up_to)
        <span class="absolute top-3.5 right-3.5 z-10 rounded-full bg-black/70 px-2.5 py-1 text-[10px] font-extrabold tracking-wider text-[var(--accent)] uppercase ring-1 ring-[var(--accent)]/40 backdrop-blur">
            Save {{ rtrim(rtrim(number_format($profile->save_up_to, 1), '0'), '.') }}%
        </span>
    @endif

    <div class="relative aspect-[4/5] overflow-hidden bg-accent-electric">
        @if ($image)
            <img src="{{ $image->getUrl('medium') }}" alt="{{ $product->translateAttribute('name') }}"
                 loading="lazy"
                 class="absolute inset-0 size-full object-contain p-4 transition-transform duration-500 ease-out group-hover:scale-[1.07]">
        @else
            <div class="absolute inset-0 grid place-items-center">
                <span class="display-title text-3xl text-white/15">{{ Str::of($profile->handle)->explode('-')->first() }}</span>
            </div>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-5">
        <p class="eyebrow text-[var(--accent)]">{{ $profile->protocol_label ?? $profile->dose }}</p>

        <h3 class="display-title mt-2 text-xl text-white">
            {{ $product->translateAttribute('name') }}
        </h3>

        <p class="mt-2 line-clamp-2 text-[13px] leading-relaxed text-white/50">
            {{ $profile->tagline ?? $profile->subtitle }}
        </p>

        @if ($rating['count'])
            <div class="mt-3">
                <x-store.stars :rating="$rating['average']" :count="$rating['count']" />
            </div>
        @endif

        <div class="mt-4 flex items-end justify-between border-t border-white/5 pt-4">
            <div>
                <p class="text-[10px] font-bold tracking-widest text-white/35 uppercase">Starting at</p>
                <p class="display-title text-2xl text-foil">{{ Catalog::money($from) }}</p>
            </div>
            <span class="rounded-full border border-[var(--accent)]/35 px-3.5 py-1.5 text-[11px] font-extrabold tracking-wider text-[var(--accent)] uppercase transition duration-300 group-hover:bg-[var(--accent)] group-hover:text-black">
                View
            </span>
        </div>
    </div>
</a>
