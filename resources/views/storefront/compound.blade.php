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
                    <a href="{{ route('shop') }}" class="transition hover:text-gold">Shop</a>
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
                                @else
                                    <div class="absolute inset-0 grid place-items-center">
                                        <span class="display-title text-5xl text-white/12">{{ $profile->dose }}</span>
                                    </div>
                                @endif
                            </div>

                            @if ($coa)
                                <span class="absolute top-5 left-5 rounded-full bg-black/70 px-3 py-1.5 text-[10px] font-extrabold tracking-widest text-[var(--accent)] uppercase ring-1 ring-[var(--accent)]/40 backdrop-blur">
                                    {{ $coa->purity }} Purity
                                </span>
                            @endif
                        </div>

                        @if ($images->count() > 1)
                            <div class="mt-4 grid grid-cols-4 gap-3 sm:grid-cols-6">
                                @foreach ($images as $index => $image)
                                    <a href="{{ route('compound', [$profile->handle, 'image' => $index]) }}"
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
                        <p class="eyebrow text-[var(--accent)]">{{ $profile->subtitle }}</p>

                        <h1 class="display-title mt-3 text-4xl text-white sm:text-5xl">
                            {{ $profile->product->translateAttribute('name') }}
                        </h1>

                        <div class="mt-4 flex flex-wrap items-center gap-4">
                            @if ($rating['count'])
                                <x-store.stars :rating="$rating['average']" :count="$rating['count']" size="size-4" />
                            @endif
                            @if ($variant)
                                <span class="text-xs text-white/40">SKU {{ $variant->sku }}</span>
                            @endif
                        </div>

                        <p class="mt-6 text-[15px] leading-relaxed text-white/55">{{ $profile->overview }}</p>

                        {{-- Benefit chips --}}
                        <div class="mt-7 grid grid-cols-2 gap-3">
                            @foreach ($profile->benefits ?? [] as $benefit)
                                <div class="rounded-xl border border-white/8 bg-panel px-4 py-3">
                                    <p class="text-[13px] font-extrabold tracking-wide text-[var(--accent)] uppercase">{{ $benefit['title'] }}</p>
                                    <p class="mt-0.5 text-[11px] text-white/45">{{ $benefit['detail'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        {{-- Quantity breaks --}}
                        <form method="POST" action="{{ route('cart.add') }}" class="mt-9">
                            @csrf
                            <input type="hidden" name="variant_id" value="{{ $variant?->id }}">

                            <div class="flex items-baseline justify-between">
                                <h2 class="text-[13px] font-extrabold tracking-widest text-white uppercase">Choose Quantity</h2>
                                <span class="text-[11px] text-white/40">Unit price drops with volume</span>
                            </div>

                            <div class="mt-4 space-y-2.5">
                                @foreach ($priceTiers as $index => $tier)
                                    @php
                                        $qty = $tier->min_quantity;
                                        $unit = $tier->price->value;
                                        $lineTotal = $unit * $qty;
                                        $discount = $unitPrice > 0 ? round((1 - ($unit / $unitPrice)) * 100) : 0;
                                    @endphp
                                    <label class="group flex cursor-pointer items-center gap-4 rounded-2xl border border-white/8 bg-panel p-4 transition
                                                  has-checked:accent-ring has-checked:bg-[var(--accent)]/[0.06] hover:border-white/20">
                                        <input type="radio" name="quantity" value="{{ $qty }}"
                                               @checked($index === 0)
                                               class="size-4 shrink-0 appearance-none rounded-full border-2 border-white/25 transition checked:border-[var(--accent)] checked:bg-[var(--accent)] checked:shadow-[inset_0_0_0_3px_#000]">

                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-white">
                                                {{ $qty }}{{ $loop->last ? '+' : '' }} {{ Str::plural('vial', $qty) }}
                                            </p>
                                            <p class="mt-0.5 text-xs text-white/45">
                                                {{ Catalog::money($unit) }} per vial
                                                @if ($discount > 0)
                                                    &middot; <span class="text-[var(--accent)]">save {{ $discount }}%</span>
                                                @endif
                                            </p>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <p class="display-title text-xl text-white">{{ Catalog::money($lineTotal) }}</p>
                                            @if ($qty > 1)
                                                <p class="text-[11px] text-white/35 line-through">{{ Catalog::money($unitPrice * $qty) }}</p>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            <button type="submit"
                                    class="mt-6 w-full rounded-full bg-[var(--accent)] px-8 py-4 text-sm font-extrabold tracking-widest text-black uppercase transition hover:brightness-110">
                                Add To Cart
                            </button>

                            <ul class="mt-5 grid gap-2.5 text-xs text-white/45 sm:grid-cols-2">
                                <li class="flex items-center gap-2"><span class="text-[var(--accent)]">&#10003;</span> Third-party HPLC tested</li>
                                <li class="flex items-center gap-2"><span class="text-[var(--accent)]">&#10003;</span> Ships within 24 hours</li>
                                <li class="flex items-center gap-2"><span class="text-[var(--accent)]">&#10003;</span> Lyophilised powder</li>
                                <li class="flex items-center gap-2"><span class="text-[var(--accent)]">&#10003;</span> Free shipping over $200</li>
                            </ul>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        {{-- Highlights + research detail --}}
        <section class="border-y border-white/5 bg-black/40 py-20">
            <div class="mx-auto max-w-7xl px-4">
                <div class="grid gap-12 lg:grid-cols-[1fr_1.2fr] lg:gap-16">
                    <div>
                        <p class="eyebrow text-[var(--accent)]">Why Researchers Use It</p>
                        <h2 class="display-title mt-3 text-3xl text-white sm:text-4xl">Highlights</h2>
                        <ul class="mt-7 space-y-3">
                            @foreach ($profile->highlights ?? [] as $highlight)
                                <li class="flex gap-3.5 rounded-xl panel p-4">
                                    <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-[var(--accent)]/15 text-xs font-extrabold text-[var(--accent)]">&#10003;</span>
                                    <span class="text-sm leading-relaxed text-white/65">{{ $highlight }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-2xl panel p-6">
                            <h3 class="text-[11px] font-extrabold tracking-widest text-[var(--accent)] uppercase">Research Background</h3>
                            <p class="mt-3 text-sm leading-relaxed text-white/60">{{ $profile->research_info }}</p>
                        </div>

                        <div class="rounded-2xl panel p-6">
                            <h3 class="text-[11px] font-extrabold tracking-widest text-[var(--accent)] uppercase">Reference Range In Literature</h3>
                            <p class="mt-3 text-sm leading-relaxed text-white/60">{{ $profile->dosage }}</p>
                        </div>

                        <div class="rounded-2xl panel p-6">
                            <h3 class="text-[11px] font-extrabold tracking-widest text-[var(--accent)] uppercase">Storage &amp; Handling</h3>
                            <p class="mt-3 text-sm leading-relaxed text-white/60">{{ $profile->storage }}</p>
                        </div>

                        @if ($coa)
                            <div class="rounded-2xl border border-[var(--accent)]/25 bg-[var(--accent)]/[0.05] p-6">
                                <h3 class="text-[11px] font-extrabold tracking-widest text-[var(--accent)] uppercase">Current Batch</h3>
                                <dl class="mt-4 grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <dt class="text-[10px] tracking-widest text-white/40 uppercase">Batch</dt>
                                        <dd class="mt-1 font-mono text-xs text-white/80">{{ $coa->batch_number }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] tracking-widest text-white/40 uppercase">Tested</dt>
                                        <dd class="mt-1 text-white/80">{{ $coa->tested_on->format('M j, Y') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] tracking-widest text-white/40 uppercase">Purity</dt>
                                        <dd class="mt-1 font-bold text-[var(--accent)]">{{ $coa->purity }}</dd>
                                    </div>
                                </dl>
                                <div class="mt-5 flex flex-wrap items-center gap-3">
                                    @if ($coa->pdf_path)
                                        <a href="{{ asset($coa->pdf_path) }}" download
                                           class="inline-flex items-center gap-1.5 rounded-full bg-[var(--accent)] px-4 py-2 text-[11px] font-extrabold tracking-widest text-black uppercase transition duration-200 hover:brightness-110">
                                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                                                <path d="M12 3v12m0 0l-4.5-4.5M12 15l4.5-4.5M4 19h16" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Download COA
                                        </a>
                                    @endif

                                    <a href="{{ route('lab-reports', ['batch' => $coa->batch_number]) }}"
                                       class="text-[11px] font-extrabold tracking-widest text-white uppercase underline decoration-[var(--accent)] decoration-2 underline-offset-4 transition hover:text-[var(--accent)]">
                                        All Batch Records
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- Used in stacks --}}
        @if ($usedInStacks->isNotEmpty())
            <section class="mx-auto max-w-7xl px-4 py-20">
                <x-store.section-heading
                    align="left"
                    eyebrow="Bundle &amp; Save"
                    :title="'Included In These <span class=\'text-accent-foil\'>Protocols</span>'"
                    subtitle="Buying this compound as part of a stack is cheaper than buying it alone, and every stack ships with the bacteriostatic water you need." />

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($usedInStacks as $stack)
                        <x-store.product-card :profile="$stack" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- FAQ --}}
        @if (! empty($profile->faq))
            <section class="border-y border-white/5 bg-black/40 py-20">
                <div class="mx-auto max-w-3xl px-4">
                    <x-store.section-heading eyebrow="Questions" title='Frequently <span class="text-foil">Asked</span>' />

                    <div class="mt-12 space-y-3">
                        @foreach ($profile->faq as $i => $item)
                            <x-store.accordion :question="$item['q']" :answer="$item['a']"
                                               class="reveal" style="--reveal-delay: {{ $i * 60 }}ms" />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Reviews --}}
        @if ($reviews->isNotEmpty())
            <section class="mx-auto max-w-7xl px-4 py-20">
                <x-store.section-heading
                    align="left"
                    eyebrow="Verified Buyers"
                    :title="'Researcher <span class=\'text-accent-foil\'>Reviews</span>'" />

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
            </section>
        @endif

        {{-- Related --}}
        <section class="border-t border-white/5 bg-black/40 py-20">
            <div class="mx-auto max-w-7xl px-4">
                <x-store.section-heading align="left" eyebrow="Keep Looking" title='Other <span class="text-foil">Compounds</span>' />

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $item)
                        <x-store.product-card :profile="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    </div>

</x-layouts.storefront>
