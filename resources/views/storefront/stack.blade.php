@php use App\Support\Catalog; @endphp

<x-layouts.storefront :title="$profile->product->translateAttribute('name').' — Powered Up Peptides'"
                      :description="$profile->summary">

    <div style="{{ $profile->accentStyle() }}">

        {{-- Hero / buy box --}}
        <section class="relative overflow-hidden bg-accent-electric">
            <div class="relative mx-auto max-w-7xl px-4 pt-8 pb-16 lg:pb-24">

                <nav class="flex items-center gap-2 text-xs text-white/40">
                    <a href="{{ route('home') }}" class="transition hover:text-gold">Home</a>
                    <span>/</span>
                    <a href="{{ route('stacks') }}" class="transition hover:text-gold">{{ site_text('collection_product.breadcrumb') }}</a>
                    <span>/</span>
                    <span class="text-white/70">{{ $profile->product->translateAttribute('name') }}</span>
                </nav>

                <div class="mt-8 grid gap-12 lg:grid-cols-2 lg:gap-16">

                    {{-- Gallery --}}
                    <div>
                        <div class="relative overflow-hidden rounded-3xl panel accent-ring">
                            <div class="relative aspect-square">
                                @if ($activeImage)
                                    <img src="{{ $activeImage->getUrl('large') }}"
                                         alt="{{ $profile->product->translateAttribute('name') }}"
                                         class="absolute inset-0 size-full object-contain p-8">
                                @endif
                            </div>
                            @if ($profile->save_up_to > 0)
                                <span class="absolute top-5 left-5 rounded-full bg-black/70 px-3 py-1.5 text-[10px] font-extrabold tracking-widest text-[var(--accent)] uppercase ring-1 ring-[var(--accent)]/40 backdrop-blur">
                                    Save up to {{ rtrim(rtrim(number_format($profile->save_up_to, 1), '0'), '.') }}%
                                </span>
                            @endif
                        </div>

                        @if ($images->count() > 1)
                            <div class="mt-4 grid grid-cols-4 gap-3 sm:grid-cols-6">
                                @foreach ($images as $index => $image)
                                    <a href="{{ route('stack', [$slug, 'image' => $index]) }}"
                                       class="relative aspect-square overflow-hidden rounded-xl border bg-black/50 transition
                                              {{ $index === $activeIndex ? 'border-[var(--accent)]' : 'border-white/8 hover:border-white/25' }}">
                                        <img src="{{ $image->getUrl('small') }}" alt=""
                                             class="absolute inset-0 size-full object-contain p-1.5">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Buy box --}}
                    <div>
                        <p class="eyebrow text-[var(--accent)]">{{ $profile->protocol_label }}</p>

                        <h1 class="display-title mt-3 text-4xl text-white sm:text-5xl">
                            {{ $profile->product->translateAttribute('name') }}
                        </h1>

                        <p class="mt-3 text-lg font-semibold text-white/70">{{ $profile->tagline }}</p>

                        @if ($rating['count'])
                            <div class="mt-4">
                                <x-store.stars :rating="$rating['average']" :count="$rating['count']" size="size-4" />
                            </div>
                        @endif

                        <p class="mt-6 text-[15px] leading-relaxed text-white/55">{{ $profile->summary }}</p>

                        {{-- Pillars --}}
                        <div class="mt-7 flex flex-wrap gap-2">
                            @foreach ($profile->pillars ?? [] as $pillar)
                                <span class="rounded-full border border-[var(--accent)]/25 bg-[var(--accent)]/[0.07] px-3 py-1.5 text-[11px] font-bold tracking-wide text-[var(--accent)] uppercase">
                                    {{ $pillar }}
                                </span>
                            @endforeach
                        </div>

                        {{-- Protocol tier selector --}}
                        <form method="POST" action="{{ route('cart.add') }}" class="mt-9">
                            @csrf

                            <div class="flex items-baseline justify-between">
                                <h2 class="text-[13px] font-extrabold tracking-widest text-white uppercase">{{ site_text('collection_product.choose_heading') }}</h2>
                                <span class="text-[11px] text-white/40">{{ site_text('collection_product.choose_help') }}</span>
                            </div>

                            <div class="mt-4 space-y-3">
                                @foreach ($tiers as $index => $tier)
                                    <label class="group relative flex cursor-pointer items-center gap-4 rounded-2xl border border-white/8 bg-panel p-4 transition
                                                  has-checked:accent-ring has-checked:bg-[var(--accent)]/[0.06] hover:border-white/20">
                                        <input type="radio" name="variant_id" value="{{ $tier->product_variant_id }}"
                                               @checked($index === 1)
                                               class="size-4 shrink-0 appearance-none rounded-full border-2 border-white/25 transition checked:border-[var(--accent)] checked:bg-[var(--accent)] checked:shadow-[inset_0_0_0_3px_#000]">

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="display-title text-lg text-white">{{ $tier->code }}</span>
                                                <span class="text-[11px] font-bold tracking-widest text-white/45 uppercase">{{ $tier->label }}</span>
                                                @if ($index === 1)
                                                    <span class="rounded-full bg-[var(--accent)]/15 px-2 py-0.5 text-[9px] font-extrabold tracking-widest text-[var(--accent)] uppercase">Popular</span>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-xs text-white/45">
                                                {{ $tier->supply_days }}-day supply
                                                @if ($tier->save_percent > 0)
                                                    &middot; save {{ rtrim(rtrim(number_format($tier->save_percent, 1), '0'), '.') }}%
                                                @endif
                                            </p>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <p class="display-title text-2xl text-white">{{ Catalog::money($tier->price) }}</p>
                                            <p class="text-[11px] text-white/40">
                                                or {{ Catalog::money($tier->subscribe_price) }} subscribed
                                            </p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            <button type="submit"
                                    class="mt-6 w-full rounded-full bg-[var(--accent)] px-8 py-4 text-sm font-extrabold tracking-widest text-black uppercase transition hover:brightness-110">
                                {{ site_text('collection_product.add_button') }}
                            </button>

                            <ul class="mt-5 grid gap-2.5 text-xs text-white/45 sm:grid-cols-2">
                                <li class="flex items-center gap-2">
                                    <span class="text-[var(--accent)]">&#10003;</span> Bacteriostatic water included
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-[var(--accent)]">&#10003;</span> COA published per batch
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-[var(--accent)]">&#10003;</span> Ships within 24 hours
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-[var(--accent)]">&#10003;</span> Free shipping over $200
                                </li>
                            </ul>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        {{-- What's included --}}
        <section class="border-y border-white/5 bg-black/40 py-20">
            <div class="mx-auto max-w-7xl px-4">
                <x-store.section-heading
                    align="left"
                    :eyebrow="site_text('collection_product.included_eyebrow')"
                    :title="foil_last_words(site_text('collection_product.included_title'), 1, 'text-accent-foil')"
                    :subtitle="site_text('collection_product.included_description')" />

                <div class="mt-12 overflow-hidden rounded-2xl panel">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-white/8 bg-black/40">
                            <tr class="text-[10px] font-extrabold tracking-widest text-white/45 uppercase">
                                <th class="px-5 py-4">Compound</th>
                                <th class="hidden px-5 py-4 md:table-cell">Research Focus</th>
                                @foreach ($tiers as $tier)
                                    <th class="px-5 py-4 text-center whitespace-nowrap">{{ $tier->code }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($components as $component)
                                @php
                                    $child = $component->component;
                                    $childSlug = $child->urls->first()?->slug;
                                @endphp
                                <tr class="transition hover:bg-white/[0.02]">
                                    <td class="px-5 py-4">
                                        <a href="{{ $childSlug ? route('compound', $childSlug) : '#' }}"
                                           class="font-bold text-white transition hover:text-[var(--accent)]">
                                            {{ $child->translateAttribute('name') }}
                                        </a>
                                    </td>
                                    <td class="hidden px-5 py-4 text-white/45 md:table-cell">{{ $component->benefit }}</td>
                                    @foreach ($tiers as $tier)
                                        <td class="px-5 py-4 text-center whitespace-nowrap">
                                            <span class="font-bold text-[var(--accent)]">
                                                {{ $component->base_quantity * $tier->multiplier() }}
                                            </span>
                                            <span class="text-[10px] tracking-widest text-white/35 uppercase">
                                                {{ Str::plural('vial', $component->base_quantity * $tier->multiplier()) }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-white/8 bg-black/40">
                            <tr>
                                <td class="px-5 py-4 text-[10px] font-extrabold tracking-widest text-white/45 uppercase" colspan="2">
                                    Retail value if bought separately
                                </td>
                                @foreach ($tiers as $tier)
                                    <td class="px-5 py-4 text-center whitespace-nowrap">
                                        @if ($tier->save_percent > 0)
                                            <span class="text-white/35 line-through">{{ Catalog::money($retailValues[$tier->code]) }}</span>
                                        @endif
                                        <span class="ml-1.5 font-bold text-[var(--accent)]">{{ Catalog::money($tier->price) }}</span>
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </section>

        {{-- Benefits + audience --}}
        <section class="mx-auto max-w-7xl px-4 py-20">
            <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <p class="eyebrow text-[var(--accent)]">{{ site_text('collection_product.benefits_eyebrow') }}</p>
                    <h2 class="display-title mt-3 text-3xl text-white sm:text-4xl">{{ site_text('collection_product.benefits_title') }}</h2>
                    <ul class="mt-7 space-y-4">
                        @foreach ($profile->benefits ?? [] as $benefit)
                            <li class="flex gap-3.5 rounded-xl panel p-4">
                                <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-[var(--accent)]/15 text-xs font-extrabold text-[var(--accent)]">
                                    &#10003;
                                </span>
                                <span class="text-sm leading-relaxed text-white/65">{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p class="eyebrow text-[var(--accent)]">{{ site_text('collection_product.audience_eyebrow') }}</p>
                    <h2 class="display-title mt-3 text-3xl text-white sm:text-4xl">{{ site_text('collection_product.audience_title') }}</h2>
                    <ul class="mt-7 space-y-4">
                        @foreach ($profile->audience ?? [] as $item)
                            <li class="flex gap-3.5 rounded-xl panel p-4">
                                <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-white/8 text-xs font-extrabold text-white/70">
                                    &rarr;
                                </span>
                                <span class="text-sm leading-relaxed text-white/65">{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        {{-- Compounds in this stack --}}
        <section class="border-y border-white/5 bg-black/40 py-20">
            <div class="mx-auto max-w-7xl px-4">
                <x-store.section-heading
                    align="left"
                    :eyebrow="site_text('collection_product.compounds_eyebrow')"
                    :title="foil_last_words(site_text('collection_product.compounds_title'), 1, 'text-accent-foil')"
                    :subtitle="site_text('collection_product.compounds_description')" />

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($componentProfiles as $componentProfile)
                        <x-store.product-card :profile="$componentProfile" />
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Lab results --}}
        @if ($coas->isNotEmpty())
            <section class="mx-auto max-w-7xl px-4 py-20">
                <x-store.section-heading
                    align="left"
                    eyebrow="Transparency"
                    :title="'Batch <span class=\'text-accent-foil\'>Lab Results</span>'"
                    :subtitle="site_text('collection_product.lab_description')" />

                <div class="mt-12 overflow-hidden rounded-2xl panel">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-white/8 bg-black/40">
                            <tr class="text-[10px] font-extrabold tracking-widest text-white/45 uppercase">
                                <th class="px-5 py-4">Product</th>
                                <th class="px-5 py-4">Batch</th>
                                <th class="hidden px-5 py-4 sm:table-cell">Tested</th>
                                <th class="px-5 py-4">Purity</th>
                                <th class="px-5 py-4 text-right">COA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($coas as $coa)
                                <tr class="transition hover:bg-white/[0.02]">
                                    <td class="px-5 py-4 font-bold text-white">{{ $coa->product_label }}</td>
                                    <td class="px-5 py-4 font-mono text-xs text-white/50">{{ $coa->batch_number }}</td>
                                    <td class="hidden px-5 py-4 text-white/50 sm:table-cell">{{ $coa->tested_on->format('M j, Y') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="font-bold text-[var(--accent)]">{{ $coa->purity }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        @if ($coa->pdf_path)
                                            <a href="{{ asset($coa->pdf_path) }}" download
                                               title="Download the certificate for batch {{ $coa->batch_number }}"
                                               class="inline-flex items-center gap-1.5 rounded-full border border-white/12 px-3 py-1.5 text-[11px] font-extrabold tracking-widest text-white/60 uppercase transition duration-200 hover:border-[var(--accent)]/50 hover:text-[var(--accent)]">
                                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                                                    <path d="M12 3v12m0 0l-4.5-4.5M12 15l4.5-4.5M4 19h16" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                PDF
                                            </a>
                                        @else
                                            <a href="{{ route('lab-reports', ['batch' => $coa->batch_number]) }}"
                                               class="text-xs font-extrabold tracking-widest text-white/60 uppercase transition hover:text-[var(--accent)]">
                                                View
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Reviews --}}
        @if ($reviews->isNotEmpty())
            <section class="border-t border-white/5 bg-black/40 py-20">
                <div class="mx-auto max-w-7xl px-4">
                    <x-store.section-heading
                        align="left"
                        eyebrow="Verified Buyers"
                        :title="'What Researchers <span class=\'text-accent-foil\'>Say</span>'" />

                    <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($reviews as $review)
                            <figure class="flex flex-col rounded-2xl panel p-6">
                                <x-store.stars :rating="$review->rating" />
                                <blockquote class="mt-4 flex-1">
                                    <p class="text-sm font-bold text-white">{{ $review->title }}</p>
                                    <p class="mt-2 text-sm leading-relaxed text-white/55">{{ $review->body }}</p>
                                </blockquote>
                                <figcaption class="mt-5 border-t border-white/5 pt-4 text-xs font-bold text-white/60">
                                    {{ $review->author_name }}
                                    <span class="ml-1 font-normal text-white/35">&middot; Verified buyer</span>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Other protocols --}}
        <section class="mx-auto max-w-7xl px-4 py-20">
            <x-store.section-heading
                align="left"
                eyebrow="Keep Looking"
                :title="foil_last_words(site_text('collection_product.other_title'))" />

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($otherStacks as $other)
                    <x-store.product-card :profile="$other" />
                @endforeach
            </div>
        </section>
    </div>

</x-layouts.storefront>
