@php $categoryLabel = $categories[$activeCategory]['label'] ?? null; @endphp

<x-layouts.storefront :title="$categoryLabel ? $categoryLabel.' Protocols — Powered Up Peptides' : site_text('collections.meta_title')"
                      :description="site_text('collections.meta_description')">

    <section class="relative overflow-hidden bg-electric">
        <div class="mx-auto max-w-7xl px-4 pt-10 pb-14">
            <nav class="flex items-center gap-2 text-xs text-white/40">
                <a href="{{ route('home') }}" class="transition hover:text-gold">Home</a>
                <span>/</span>
                <a href="{{ route('stacks') }}" class="transition hover:text-gold">{{ site_text('collections.breadcrumb') }}</a>
                @if ($categoryLabel)
                    <span>/</span>
                    <span class="text-white/70">{{ $categoryLabel }}</span>
                @endif
            </nav>

            <p class="mt-8 eyebrow text-gold">{{ site_text('collections.hero_eyebrow') }}</p>

            <h1 class="display-title mt-3 text-5xl text-white sm:text-6xl">
                @if ($categoryLabel)
                    <span class="text-foil">{{ $categoryLabel }}</span> {{ site_text('collections.category_suffix') }}
                @else
                    {!! foil_last_words(site_text('collections.hero_title')) !!}
                @endif
            </h1>

            <p class="mt-5 max-w-2xl text-[15px] leading-relaxed text-white/55">
                {{ site_text('collections.hero_description') }}
            </p>

            <a href="{{ route('shop') }}"
               class="mt-7 inline-flex items-center gap-2 text-xs font-extrabold tracking-widest text-gold uppercase transition hover:text-gold-bright">
                {{ site_text('collections.shop_link') }}
            </a>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-12">

        <x-store.catalog-filters route="stacks" :all-label="site_text('collections.all_filter')"
                                 :categories="$categories" :active-category="$activeCategory"
                                 :sort="$sort" :total-count="$totalCount" />

        <p class="mt-6 text-xs text-white/40">
            Showing <span class="font-bold text-white/70">{{ $profiles->count() }}</span>
            {{ $profiles->count() === 1 ? site_text('collections.item_singular') : site_text('collections.item_plural') }}
        </p>

        @if ($profiles->isEmpty())
            <div class="mt-10 rounded-2xl panel p-14 text-center">
                <p class="display-title text-2xl text-white">{{ site_text('collections.empty_title') }}</p>
                <p class="mt-3 text-sm text-white/50">{{ site_text('collections.empty_description') }}</p>
                <a href="{{ route('stacks') }}"
                   class="mt-7 inline-block rounded-full bg-gold px-6 py-3 text-xs font-extrabold tracking-widest text-black uppercase">
                    {{ site_text('collections.empty_button') }}
                </a>
            </div>
        @else
            {{-- Wide protocol rows: a stack needs more explaining than a product tile. --}}
            <div class="mt-8 space-y-6">
                @foreach ($profiles as $i => $profile)
                    @php
                        $slug = $profile->product->urls->first()?->slug;
                        $url = $slug ? route('stack', $slug) : route('stacks');
                        $stackTiers = $tiers[$profile->product_id] ?? collect();
                        $from = \App\Support\Catalog::fromPrice($profile);
                        $image = $profile->product->getFirstMedia('images');
                        $componentCount = $componentCounts[$profile->product_id] ?? 0;
                    @endphp

                    <article style="{{ $profile->accentStyle() }} --reveal-delay: {{ $i * 70 }}ms"
                             class="reveal hover-lift group grid gap-6 overflow-hidden rounded-3xl panel accent-ring p-6 lg:grid-cols-[16rem_1fr_15rem] lg:p-7">

                        {{-- Image --}}
                        <a href="{{ $url }}" class="relative block overflow-hidden rounded-2xl bg-black/50">
                            <div class="absolute inset-0 opacity-30"
                                 style="background: radial-gradient(circle at 50% 45%, var(--accent-glow), transparent 68%);"></div>
                            @if ($image)
                                <img src="{{ $image->getUrl('medium') }}"
                                     alt="{{ $profile->product->translateAttribute('name') }}"
                                     class="relative mx-auto h-52 w-full object-contain p-4 transition-transform duration-500 ease-out group-hover:scale-[1.07]"
                                     loading="lazy">
                            @else
                                <div class="grid h-52 place-items-center text-xs text-white/25">No image</div>
                            @endif
                        </a>

                        {{-- Detail --}}
                        <div class="flex flex-col">
                            <div class="flex flex-wrap items-center gap-2.5">
                                @if ($profile->protocol_label)
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-extrabold tracking-widest uppercase"
                                          style="background: color-mix(in srgb, var(--accent) 15%, transparent); color: var(--accent);">
                                        {{ $profile->protocol_label }}
                                    </span>
                                @endif
                                @if ($componentCount)
                                    <span class="text-[10px] font-bold tracking-widest text-white/35 uppercase">
                                        {{ $componentCount }} {{ Str::plural('compound', $componentCount) }}
                                    </span>
                                @endif
                            </div>

                            <h2 class="mt-3 text-2xl font-extrabold text-white sm:text-3xl">
                                <a href="{{ $url }}" class="transition hover:text-gold">
                                    {{ $profile->product->translateAttribute('name') }}
                                </a>
                            </h2>

                            @if ($profile->tagline)
                                <p class="mt-1.5 text-sm font-semibold" style="color: var(--accent);">
                                    {{ $profile->tagline }}
                                </p>
                            @endif

                            @if ($profile->summary)
                                <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-white/50">
                                    {{ $profile->summary }}
                                </p>
                            @endif

                            @if (filled($profile->pillars))
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach (array_slice($profile->pillars, 0, 4) as $pillar)
                                        <span class="rounded-full border border-white/10 px-3 py-1 text-[11px] font-semibold text-white/55">
                                            {{ $pillar }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                        </div>

                        {{-- Buy panel --}}
                        <div class="flex flex-col justify-between gap-4 rounded-2xl border border-white/8 bg-black/40 p-5">
                            <div>
                                <p class="text-[10px] font-extrabold tracking-widest text-white/35 uppercase">Starting at</p>
                                <p class="display-title mt-1 text-3xl text-white">
                                    {{ \App\Support\Catalog::money($from) }}
                                </p>

                                @if ($profile->save_up_to > 0)
                                    <p class="mt-1.5 text-[11px] font-bold tracking-wide text-gold uppercase">
                                        Save up to {{ (int) $profile->save_up_to }}%
                                    </p>
                                @endif

                                @if ($stackTiers->isNotEmpty())
                                    <ul class="mt-4 space-y-1.5 border-t border-white/8 pt-4">
                                        @foreach ($stackTiers as $tier)
                                            <li class="flex items-baseline justify-between gap-3 text-xs">
                                                <span class="text-white/50">{{ $tier->code }} {{ $tier->label }}</span>
                                                <span class="font-bold text-white/80">
                                                    {{ \App\Support\Catalog::money($tier->price) }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <a href="{{ $url }}"
                               class="block rounded-full bg-gold px-5 py-3 text-center text-[11px] font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                                {{ site_text('collections.view_button') }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        {{-- Cross-sell to compounds --}}
        <section class="mt-16 overflow-hidden rounded-3xl bg-electric p-10 text-center sm:p-14">
            <h2 class="display-title text-3xl text-white sm:text-4xl">
                {!! foil_last_words(site_text('collections.cross_sell_title'), 3) !!}
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-white/55">
                {{ site_text('collections.cross_sell_description') }}
            </p>
            <a href="{{ route('shop') }}"
               class="mt-8 inline-block rounded-full bg-gold px-8 py-3.5 text-xs font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                {{ site_text('collections.cross_sell_button') }}
            </a>
        </section>
    </div>

    <x-store.trust-bar />

</x-layouts.storefront>
