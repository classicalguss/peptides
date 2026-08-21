<x-layouts.storefront title="About — Powered Up Peptides"
                      :description="site_text('about.meta_description')">

    <section class="relative overflow-hidden bg-electric">
        <div class="mx-auto max-w-7xl px-4 pt-10 pb-20">
            <nav class="flex items-center gap-2 text-xs text-white/40">
                <a href="{{ route('home') }}" class="transition hover:text-gold">Home</a>
                <span>/</span>
                <span class="text-white/70">About</span>
            </nav>

            <p class="mt-8 eyebrow text-gold">{{ site_text('about.hero_eyebrow') }}</p>
            <h1 class="display-title mt-4 max-w-3xl text-5xl leading-[0.95] text-white sm:text-6xl lg:text-7xl">
                {!! foil_last_words(site_text('about.hero_title'), 5) !!}
            </h1>

            <p class="mt-6 max-w-2xl text-base leading-relaxed text-white/55">
                {{ site_text('about.hero_description') }}
            </p>
        </div>
    </section>

    {{-- Stats --}}
    <section class="border-y border-white/5 bg-black/40">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-px px-4 py-10 sm:grid-cols-4">
            @foreach ([
                ['COA', 'Published for every batch'],
                ['100%', 'Batches third-party tested'],
                ['24h', 'Typical dispatch time'],
                ['0', 'Batches shipped without a COA'],
            ] as $i => [$stat, $label])
                <div class="reveal px-2 text-center" style="--reveal-delay: {{ $i * 90 }}ms">
                    <p class="display-title text-4xl text-gold sm:text-5xl">{{ $stat }}</p>
                    <p class="mt-2 text-[11px] leading-relaxed tracking-wide text-white/40 uppercase">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-16">

        {{-- Story --}}
        <section class="grid gap-10 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="eyebrow text-gold">{{ site_text('about.story_eyebrow') }}</p>
                <h2 class="display-title mt-3 text-4xl text-white sm:text-5xl">
                    {!! foil_last_words(site_text('about.story_title')) !!}
                </h2>
                <div class="mt-6 space-y-4 text-[15px] leading-relaxed text-white/55">
                    <p>{{ site_text('about.story_paragraph_1') }}</p>
                    <p>{{ site_text('about.story_paragraph_2') }}</p>
                    <p>{{ site_text('about.story_paragraph_3') }}</p>
                </div>
            </div>

            <div class="reveal rounded-2xl panel p-8">
                <p class="eyebrow text-gold">The Commitments</p>
                <ul class="mt-6 space-y-5">
                    @foreach ([
                        ['Every batch tested', 'Independent HPLC purity, identity and quantitation analysis, published against the batch number on your vial.'],
                        ['No hidden failures', 'Reports stay public regardless of the result. Batches under threshold are destroyed, not discounted.'],
                        ['Batch documentation', 'Lyophilised and sealed, with the certificate of analysis published against the batch number.'],
                        ['Honest pricing', 'Volume discounts are applied automatically. No inflated list price to make a fake sale look generous.'],
                    ] as $i => [$title, $body])
                        <li class="reveal flex gap-4" style="--reveal-delay: {{ $i * 80 }}ms">
                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-gold/15 text-xs font-black text-gold">&#10003;</span>
                            <div>
                                <p class="text-sm font-bold text-white">{{ $title }}</p>
                                <p class="mt-1 text-[13px] leading-relaxed text-white/50">{{ $body }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Process --}}
        <section id="process" class="mt-20 scroll-mt-28">
            <x-store.section-heading eyebrow="How It Works"
                                     title='From Synthesis <span class="text-foil">To Your Bench</span>'
                                     subtitle="Four checkpoints, and a batch has to clear all of them." />

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['01', 'Sourcing', 'We work with a small number of vetted synthesis partners operating under documented quality systems. New suppliers go through sample testing long before they get an order.'],
                    ['02', 'Independent testing', 'Every batch goes to a third-party lab, where HPLC analysis establishes purity, identity and quantitation against the labeled content.'],
                    ['03', 'Review', 'Results are checked against our 99% threshold. Anything that falls short is rejected outright. Passing batches get their COA published.'],
                    ['04', 'Dispatch', 'Vials are packed in plain outer packaging with no product branding, and dispatched typically within 24 hours.'],
                ] as $i => [$num, $title, $body])
                    <div class="reveal hover-lift rounded-2xl panel p-6 hover:gold-ring" style="--reveal-delay: {{ $i * 90 }}ms">
                        <p class="display-title text-3xl text-gold/30">{{ $num }}</p>
                        <h3 class="mt-3 text-base font-extrabold text-white">{{ $title }}</h3>
                        <p class="mt-2.5 text-[13px] leading-relaxed text-white/50">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Compliance --}}
        <section class="mt-20">
            <div class="mx-auto max-w-2xl rounded-2xl border border-gold/25 bg-gold/[0.04] p-8">
                <p class="eyebrow text-gold">{{ site_text('about.compliance_eyebrow') }}</p>
                <h2 class="display-title mt-3 text-3xl text-white sm:text-4xl">{{ site_text('about.compliance_title') }}</h2>
                <div class="mt-5 space-y-4 text-sm leading-relaxed text-white/55">
                    <p>{{ site_text('about.compliance_paragraph_1') }}</p>
                    <p>{{ site_text('about.compliance_paragraph_2') }}</p>
                    <p class="text-white/70">{{ site_text('about.compliance_paragraph_3') }}</p>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="mt-20 overflow-hidden rounded-3xl bg-electric p-10 text-center sm:p-16">
            <h2 class="display-title text-4xl text-white sm:text-5xl">
                Check Us <span class="text-foil">Before You Buy</span>
            </h2>
            <p class="mx-auto mt-5 max-w-xl text-[15px] leading-relaxed text-white/55">
                Do not take our word for any of this. Pull a batch certificate and read the numbers
                yourself.
            </p>
            <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('lab-reports') }}"
                   class="rounded-full bg-gold px-9 py-4 text-xs font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                    View Lab Reports
                </a>
                <a href="{{ route('contact') }}"
                   class="rounded-full border border-white/20 px-9 py-4 text-xs font-extrabold tracking-widest text-white uppercase transition hover:border-gold/50 hover:text-gold">
                    Talk To Us
                </a>
            </div>
        </section>
    </div>

    <x-store.trust-bar />

</x-layouts.storefront>
