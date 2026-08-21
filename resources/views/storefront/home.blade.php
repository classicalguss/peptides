<x-layouts.storefront :title="site_text('home.meta_title')">

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-electric">
        <div class="absolute inset-0 bg-[url('/assets/brand/hero-banner.png')] bg-cover bg-center opacity-25"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-ink/40 via-ink/70 to-ink"></div>

        <div class="relative mx-auto grid max-w-7xl gap-12 px-4 pt-16 pb-20 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:pt-24 lg:pb-28">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-gold/25 bg-gold/[0.06] px-3.5 py-1.5">
                    <span class="size-1.5 rounded-full bg-gold"></span>
                    <span class="eyebrow text-gold">{{ site_text('home.hero_eyebrow') }}</span>
                </div>

                <h1 class="display-title mt-6 text-5xl sm:text-6xl lg:text-7xl">
                    <span class="text-white">{{ site_text('home.hero_title_line_1') }}</span><br>
                    <span class="text-foil">{{ site_text('home.hero_title_line_2') }}</span>
                </h1>

                <p class="animate-in mt-6 max-w-xl text-base leading-relaxed text-white/60 sm:text-lg" style="--reveal-delay: 120ms">
                    {{ site_text('home.hero_description') }}
                </p>

                <div class="animate-in mt-9 flex flex-wrap items-center gap-3" style="--reveal-delay: 220ms">
                    <a href="{{ route('stacks') }}"
                       class="rounded-full bg-gold px-7 py-3.5 text-sm font-extrabold tracking-wider text-black uppercase transition hover:bg-gold-bright hover:shadow-[0_0_40px_-8px_var(--color-gold)]">
                        {{ site_text('home.hero_primary_button') }}
                    </a>
                    <a href="{{ route('lab-reports') }}"
                       class="rounded-full border border-white/15 px-7 py-3.5 text-sm font-extrabold tracking-wider text-white uppercase transition hover:border-gold/50 hover:text-gold">
                        {{ site_text('home.hero_secondary_button') }}
                    </a>
                </div>

                <dl class="animate-in mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-white/10 pt-7" style="--reveal-delay: 320ms">
                    <div>
                        <dt class="display-title text-3xl text-foil">100%</dt>
                        <dd class="mt-1 text-[11px] font-bold tracking-widest text-white/40 uppercase">Third-Party Tested</dd>
                    </div>
                    <div>
                        <dt class="display-title text-3xl text-foil">{{ $compoundCount }}</dt>
                        <dd class="mt-1 text-[11px] font-bold tracking-widest text-white/40 uppercase">Compounds</dd>
                    </div>
                    <div>
                        <dt class="display-title text-3xl text-foil">24h</dt>
                        <dd class="mt-1 text-[11px] font-bold tracking-widest text-white/40 uppercase">Dispatch</dd>
                    </div>
                </dl>
            </div>

            {{-- Featured stack --}}
            @if ($featured)
                @php
                    $featuredImage = $featured->product->getFirstMedia('images');
                    $featuredSlug = $featured->product->urls->first()?->slug ?? $featured->handle;
                @endphp
                <div style="{{ $featured->accentStyle() }}" class="relative">
                    <div class="absolute -inset-8 rounded-full bg-[var(--accent)]/15 blur-3xl"></div>
                    <a href="{{ route('stack', $featuredSlug) }}"
                       class="relative block overflow-hidden rounded-3xl panel accent-ring">
                        <div class="relative aspect-[5/6] bg-accent-electric">
                            @if ($featuredImage)
                                <img src="{{ $featuredImage->getUrl('large') }}"
                                     alt="{{ $featured->product->translateAttribute('name') }}"
                                     class="absolute inset-0 size-full object-contain p-6">
                            @endif
                            <span class="absolute top-5 left-5 rounded-full bg-black/70 px-3 py-1.5 text-[10px] font-extrabold tracking-widest text-[var(--accent)] uppercase ring-1 ring-[var(--accent)]/40 backdrop-blur">
                                Best Seller
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-white/5 p-6">
                            <div>
                                <p class="eyebrow text-[var(--accent)]">{{ $featured->protocol_label }}</p>
                                <h2 class="display-title mt-1.5 text-2xl text-white">{{ $featured->product->translateAttribute('name') }}</h2>
                                <p class="mt-1 text-[13px] text-white/50">{{ $featured->tagline }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-[10px] font-bold tracking-widest text-white/35 uppercase">From</p>
                                <p class="display-title text-3xl text-accent-foil">{{ \App\Support\Catalog::money($featuredFrom) }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            @endif
        </div>
    </section>

    <x-store.trust-bar />

    {{-- Stack protocols --}}
    <section class="mx-auto max-w-7xl px-4 py-10 sm:py-12">
        <x-store.section-heading
            :eyebrow="site_text('home.collections_eyebrow')"
            :title="foil_last_words(site_text('home.collections_title'))"
            :subtitle="site_text('home.collections_description')" />

        <div class="mt-10 grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-3">
            @foreach ($stacks as $i => $stack)
                <x-store.product-card :profile="$stack"
                                      class="reveal" style="{{ $stack->accentStyle() }} --reveal-delay: {{ ($i % 3) * 90 }}ms" />
            @endforeach
        </div>
    </section>

    {{-- Individual compounds --}}
    <section class="border-y border-white/5 bg-black/40 py-10 sm:py-12">
        <div class="mx-auto max-w-7xl px-4">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <x-store.section-heading
                    align="left"
                    :eyebrow="site_text('home.compounds_eyebrow')"
                    :title="foil_last_words(site_text('home.compounds_title'))"
                    :subtitle="site_text('home.compounds_description')" />

                <a href="{{ route('shop') }}"
                   class="rounded-full border border-gold/30 px-5 py-2.5 text-xs font-extrabold tracking-widest text-gold uppercase transition hover:bg-gold hover:text-black">
                    View All
                </a>
            </div>

            <div class="mt-10 grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-4">
                @foreach ($compounds as $i => $compound)
                    <x-store.product-card :profile="$compound"
                                          class="reveal" style="{{ $compound->accentStyle() }} --reveal-delay: {{ ($i % 4) * 80 }}ms" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- Why us --}}
    <section class="mx-auto max-w-7xl px-4 py-10 sm:py-12">
        <x-store.section-heading
            :eyebrow="site_text('home.why_eyebrow')"
            :title="foil_last_words(site_text('home.why_title'))"
            :subtitle="site_text('home.why_description')" />

        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            @foreach (site_list('home.why') as $item)
                <div class="reveal hover-lift rounded-2xl panel p-7 hover:gold-ring" style="--reveal-delay: {{ $loop->index * 90 }}ms">
                    <p class="display-title text-4xl text-foil">{{ $item->extra }}</p>
                    <h3 class="mt-5 text-lg font-extrabold tracking-wide text-white uppercase">{{ $item->heading }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-white/50">{{ $item->body }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Closing CTA --}}
    <section class="relative overflow-hidden">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:py-12">
            <div class="relative overflow-hidden rounded-3xl panel gold-ring px-6 py-14 text-center sm:px-14">
                <div class="absolute inset-0 bg-electric opacity-70"></div>
                <div class="relative">
                    <h2 class="display-title text-4xl text-white sm:text-5xl">
                        {!! foil_last_words(site_text('home.closing_title'), 2) !!}
                    </h2>
                    <p class="mx-auto mt-5 max-w-xl text-[15px] leading-relaxed text-white/55">
                        {{ site_text('home.closing_description') }}
                    </p>
                    <div class="mt-9 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('stacks') }}"
                           class="rounded-full bg-gold px-8 py-3.5 text-sm font-extrabold tracking-wider text-black uppercase transition hover:bg-gold-bright">
                            {{ site_text('home.closing_primary_button') }}
                        </a>
                        <a href="{{ route('contact') }}"
                           class="rounded-full border border-white/15 px-8 py-3.5 text-sm font-extrabold tracking-wider text-white uppercase transition hover:border-gold/50 hover:text-gold">
                            Talk To Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-layouts.storefront>
