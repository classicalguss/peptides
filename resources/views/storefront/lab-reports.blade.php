<x-layouts.storefront :title="site_text('labs.meta_title')">

    <section class="relative overflow-hidden bg-electric">
        <div class="mx-auto max-w-7xl px-4 pt-10 pb-16">
            <nav class="flex items-center gap-2 text-xs text-white/40">
                <a href="{{ route('home') }}" class="transition hover:text-gold">Home</a>
                <span>/</span>
                <span class="text-white/70">Lab Reports</span>
            </nav>

            <h1 class="display-title mt-6 text-5xl text-white sm:text-6xl">
                {!! foil_last_words(site_text('labs.hero_title')) !!}
            </h1>

            <p class="mt-5 max-w-2xl text-[15px] leading-relaxed text-white/55">
                {{ site_text('labs.hero_description') }}
            </p>

            <form method="GET" action="{{ route('lab-reports') }}" class="mt-9 flex max-w-xl gap-3">
                <input type="search" name="batch" value="{{ $search }}"
                       placeholder="{{ site_text('labs.search_placeholder') }}"
                       class="min-w-0 flex-1 rounded-full border border-white/12 bg-black/50 px-5 py-3.5 text-sm text-white placeholder:text-white/30 outline-none focus:border-gold/50">
                <button type="submit"
                        class="shrink-0 rounded-full bg-gold px-6 py-3.5 text-xs font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                    {{ site_text('labs.search_button') }}
                </button>
            </form>

            @if ($search !== '')
                <p class="mt-4 text-xs text-white/45">
                    {{ $reports->count() }} of {{ $total }} batches match
                    &ldquo;<span class="text-gold">{{ $search }}</span>&rdquo;
                    &middot; <a href="{{ route('lab-reports') }}" class="underline transition hover:text-gold">clear</a>
                </p>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-14">
        @if ($reports->isEmpty())
            <div class="rounded-2xl panel p-14 text-center">
                <p class="display-title text-2xl text-white">No matching batch</p>
                <p class="mt-3 text-sm text-white/50">
                    Check the number printed on your vial label, or contact us and we will send the
                    certificate directly.
                </p>
                <a href="{{ route('contact') }}"
                   class="mt-7 inline-block rounded-full bg-gold px-6 py-3 text-xs font-extrabold tracking-widest text-black uppercase">
                    Contact Support
                </a>
            </div>
        @else
            <div class="overflow-x-auto rounded-2xl panel">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/8 bg-black/40">
                        <tr class="text-[10px] font-extrabold tracking-widest text-white/45 uppercase">
                            <th class="px-5 py-4">Product</th>
                            <th class="px-5 py-4">Batch Number</th>
                            <th class="hidden px-5 py-4 sm:table-cell">Analysis Date</th>
                            <th class="hidden px-5 py-4 lg:table-cell">Laboratory</th>
                            <th class="hidden px-5 py-4 sm:table-cell">HPLC Purity</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Report</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($reports as $report)
                            @php $slug = $report->product?->urls->first()?->slug; @endphp
                            <tr class="transition hover:bg-white/[0.02]">
                                <td class="px-5 py-4">
                                    @if ($slug)
                                        <a href="{{ route('compound', $slug) }}"
                                           class="font-bold text-white transition hover:text-gold">{{ $report->product_label }}</a>
                                    @else
                                        <span class="font-bold text-white">{{ $report->product_label }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-mono text-xs whitespace-nowrap text-white/55">{{ $report->batch_number ?? '—' }}</td>
                                <td class="hidden px-5 py-4 whitespace-nowrap text-white/50 sm:table-cell">{{ $report->tested_on?->format('M j, Y') ?? '—' }}</td>
                                <td class="hidden px-5 py-4 whitespace-nowrap text-white/50 lg:table-cell">{{ $report->lab_name ?? '—' }}</td>
                                <td class="hidden px-5 py-4 font-bold whitespace-nowrap text-white/80 sm:table-cell">{{ $report->purity ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    @if ($report->isPass())
                                        <span class="inline-block rounded-full bg-emerald-400/10 px-2.5 py-1 text-[10px] font-extrabold tracking-widest whitespace-nowrap text-emerald-300 uppercase ring-1 ring-emerald-400/30">Pass</span>
                                    @elseif ($report->isTesting())
                                        <span class="inline-block rounded-full bg-amber-400/10 px-3 py-1.5 text-[10px] font-extrabold tracking-widest text-amber-300 uppercase ring-1 ring-amber-400/30">{{ site_text('labs.testing_label') }}</span>
                                        <span class="mt-2 hidden max-w-56 text-[11px] leading-relaxed normal-case text-white/35 sm:block">{{ site_text('labs.testing_note') }}</span>
                                    @else
                                        <span class="inline-block rounded-full bg-white/5 px-3 py-1.5 text-[10px] font-extrabold tracking-widest text-white/40 uppercase ring-1 ring-white/15">{{ site_text('labs.pending_label') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($report->pdfUrl())
                                        <a href="{{ $report->pdfUrl() }}" target="_blank" rel="noopener"
                                           title="Open the certificate for batch {{ $report->batch_number }}"
                                           class="inline-flex items-center gap-1.5 rounded-full bg-gold px-3.5 py-1.5 text-[11px] font-extrabold tracking-widest whitespace-nowrap text-black uppercase transition duration-200 hover:bg-gold-bright">
                                            View COA
                                        </a>
                                    @else
                                        <span class="text-[11px] font-extrabold tracking-widest text-white/25 uppercase">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-5 text-xs leading-relaxed text-white/35">
                {{ site_text('labs.table_note') }}
            </p>
        @endif

        {{-- How we test --}}
        <div class="mt-16 grid gap-6 lg:grid-cols-3">
            @foreach ([
                ['step' => '01', 'title' => 'Independent Sampling', 'body' => 'A sealed vial is pulled at random from each production batch and shipped directly to the lab. We never send a prepared sample.'],
                ['step' => '02', 'title' => 'HPLC + Mass Spec', 'body' => 'Purity is measured by high performance liquid chromatography, with mass spectrometry confirming the peptide sequence and molecular weight.'],
                ['step' => '03', 'title' => 'Published Before Sale', 'body' => 'The certificate is uploaded against the batch number here before that batch is released for purchase. If it fails, it does not ship.'],
            ] as $item)
                <div class="rounded-2xl panel p-7">
                    <p class="display-title text-3xl text-foil">{{ $item['step'] }}</p>
                    <h3 class="mt-5 text-base font-extrabold tracking-wide text-white uppercase">{{ $item['title'] }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-white/50">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <x-store.trust-bar />

</x-layouts.storefront>
