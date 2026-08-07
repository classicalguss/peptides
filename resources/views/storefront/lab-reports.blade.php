<x-layouts.storefront title="Lab Reports & Certificates of Analysis — Powered Up Peptides">

    <section class="relative overflow-hidden bg-electric">
        <div class="mx-auto max-w-7xl px-4 pt-10 pb-16">
            <nav class="flex items-center gap-2 text-xs text-white/40">
                <a href="{{ route('home') }}" class="transition hover:text-gold">Home</a>
                <span>/</span>
                <span class="text-white/70">Lab Reports</span>
            </nav>

            <h1 class="display-title mt-6 text-5xl text-white sm:text-6xl">
                Lab <span class="text-foil">Reports</span>
            </h1>

            <p class="mt-5 max-w-2xl text-[15px] leading-relaxed text-white/55">
                Every batch we sell is tested by an independent, accredited laboratory using HPLC
                and mass spectrometry before it goes on sale. Look up any batch number printed
                on your vial to see its certificate of analysis.
            </p>

            <form method="GET" action="{{ route('lab-reports') }}" class="mt-9 flex max-w-xl gap-3">
                <input type="search" name="batch" value="{{ $search }}"
                       placeholder="Enter batch number or product name"
                       class="min-w-0 flex-1 rounded-full border border-white/12 bg-black/50 px-5 py-3.5 text-sm text-white placeholder:text-white/30 outline-none focus:border-gold/50">
                <button type="submit"
                        class="shrink-0 rounded-full bg-gold px-6 py-3.5 text-xs font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                    Look Up
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
            <div class="overflow-hidden rounded-2xl panel">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/8 bg-black/40">
                        <tr class="text-[10px] font-extrabold tracking-widest text-white/45 uppercase">
                            <th class="px-5 py-4">Product</th>
                            <th class="px-5 py-4">Batch Number</th>
                            <th class="hidden px-5 py-4 sm:table-cell">Test Date</th>
                            <th class="hidden px-5 py-4 lg:table-cell">Laboratory</th>
                            <th class="px-5 py-4">Purity</th>
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
                                <td class="px-5 py-4 font-mono text-xs text-white/55">{{ $report->batch_number }}</td>
                                <td class="hidden px-5 py-4 text-white/50 sm:table-cell">{{ $report->tested_on->format('M j, Y') }}</td>
                                <td class="hidden px-5 py-4 text-white/50 lg:table-cell">{{ $report->lab_name }}</td>
                                <td class="px-5 py-4">
                                    @if ($report->purity === 'N/A')
                                        <span class="text-white/40">&mdash;</span>
                                    @else
                                        <span class="font-bold text-gold">{{ $report->purity }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($report->pdf_path)
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ asset($report->pdf_path) }}" target="_blank" rel="noopener"
                                               title="Open in a new tab"
                                               class="rounded-full border border-white/12 px-3 py-1.5 text-[11px] font-extrabold tracking-widest text-white/60 uppercase transition duration-200 hover:border-gold/40 hover:text-gold">
                                                View
                                            </a>
                                            <a href="{{ asset($report->pdf_path) }}" download
                                               title="Download the certificate for batch {{ $report->batch_number }}"
                                               class="inline-flex items-center gap-1.5 rounded-full bg-gold px-3.5 py-1.5 text-[11px] font-extrabold tracking-widest text-black uppercase transition duration-200 hover:bg-gold-bright">
                                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                                                    <path d="M12 3v12m0 0l-4.5-4.5M12 15l4.5-4.5M4 19h16" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                PDF
                                            </a>
                                        </div>
                                    @else
                                        <span class="block text-right text-[11px] font-extrabold tracking-widest text-white/25 uppercase">Pending Upload</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-5 text-xs leading-relaxed text-white/35">
                Certificate PDFs are attached per batch in the admin. Rows marked
                &ldquo;pending upload&rdquo; have been tested and released, but the signed PDF has
                not been attached to this record yet.
                <span class="text-white/50">The certificates currently linked here are sample
                documents containing placeholder data, not real laboratory results.</span>
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
