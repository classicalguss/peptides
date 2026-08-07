@php $categoryLabel = $categories[$activeCategory]['label'] ?? null; @endphp

<x-layouts.storefront :title="($categoryLabel ?? 'Shop Peptides').' — Powered Up Peptides'"
                      description="Individual research peptides with published third-party analysis and quantity-break pricing on every vial.">

    <section class="relative overflow-hidden bg-electric">
        <div class="mx-auto max-w-7xl px-4 pt-10 pb-14">
            <nav class="flex items-center gap-2 text-xs text-white/40">
                <a href="{{ route('home') }}" class="transition hover:text-gold">Home</a>
                <span>/</span>
                <a href="{{ route('shop') }}" class="transition hover:text-gold">Shop</a>
                @if ($categoryLabel)
                    <span>/</span>
                    <span class="text-white/70">{{ $categoryLabel }}</span>
                @endif
            </nav>

            <p class="mt-8 eyebrow text-gold">Individual Compounds</p>

            <h1 class="display-title mt-3 text-5xl text-white sm:text-6xl">
                @if ($categoryLabel)
                    <span class="text-foil">{{ $categoryLabel }}</span>
                @else
                    Shop <span class="text-foil">Compounds</span>
                @endif
            </h1>

            <p class="mt-5 max-w-2xl text-[15px] leading-relaxed text-white/55">
                Single vials for researchers building their own protocol. Every compound is
                third-party tested with the certificate published against its batch number, and
                unit prices drop automatically as quantity goes up.
            </p>

            <a href="{{ route('stacks') }}"
               class="mt-7 inline-flex items-center gap-2 text-xs font-extrabold tracking-widest text-gold uppercase transition hover:text-gold-bright">
                Looking for complete protocols? Browse stacks &rarr;
            </a>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-12">

        <x-store.catalog-filters route="shop" all-label="All Compounds"
                                 :categories="$categories" :active-category="$activeCategory"
                                 :sort="$sort" :total-count="$totalCount" />

        <p class="mt-6 text-xs text-white/40">
            Showing <span class="font-bold text-white/70">{{ $profiles->count() }}</span>
            {{ Str::plural('compound', $profiles->count()) }}
        </p>

        {{-- Grid --}}
        @if ($profiles->isEmpty())
            <div class="mt-10 rounded-2xl panel p-14 text-center">
                <p class="display-title text-2xl text-white">Nothing here yet</p>
                <p class="mt-3 text-sm text-white/50">Try a different category.</p>
                <a href="{{ route('shop') }}"
                   class="mt-7 inline-block rounded-full bg-gold px-6 py-3 text-xs font-extrabold tracking-widest text-black uppercase">
                    Show All Compounds
                </a>
            </div>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($profiles as $i => $profile)
                    <x-store.product-card :profile="$profile"
                                          class="reveal" style="{{ $profile->accentStyle() }} --reveal-delay: {{ ($i % 4) * 80 }}ms" />
                @endforeach
            </div>
        @endif

        {{-- Cross-sell to the protocol page --}}
        <section class="mt-16 overflow-hidden rounded-3xl bg-electric p-10 text-center sm:p-14">
            <h2 class="display-title text-3xl text-white sm:text-4xl">
                Rather Not <span class="text-foil">Build It Yourself?</span>
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-white/55">
                Our stack protocols pair these compounds into complete programmes with a set
                supply length, so you do not have to work out the combination.
            </p>
            <a href="{{ route('stacks') }}"
               class="mt-8 inline-block rounded-full bg-gold px-8 py-3.5 text-xs font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                View Stack Protocols
            </a>
        </section>
    </div>

    <x-store.trust-bar />

</x-layouts.storefront>
