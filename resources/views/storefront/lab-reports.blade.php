<x-layouts.storefront :title="site_text('labs.meta_title')">

    <section class="relative overflow-hidden bg-electric">
        <div class="mx-auto max-w-7xl px-4 pt-10 pb-10">
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

    <div class="mx-auto max-w-7xl px-4 pt-6 pb-14">
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
                            <th class="px-3 py-4 sm:px-5">Product</th>
                            <th class="hidden px-3 py-4 sm:table-cell sm:px-5">Batch Number</th>
                            <th class="hidden px-3 py-4 sm:table-cell sm:px-5">Analysis Date</th>
                            <th class="hidden px-3 py-4 lg:table-cell sm:px-5">Laboratory</th>
                            <th class="px-2 py-4 sm:px-5"><span class="sm:hidden">Purity</span><span class="hidden sm:inline">HPLC Purity</span></th>
                            <th class="px-2 py-4 sm:px-5">Status</th>
                            <th class="hidden px-3 py-4 text-right sm:table-cell sm:px-5">Report</th>
                            <th class="w-8 sm:hidden"><span class="sr-only">Details</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($reports as $report)
                            @php
                                $slug = $report->product?->urls->first()?->slug;
                                $expandable = $report->isPass() || $report->isTesting();
                            @endphp
                            <tr @if ($expandable) data-coa-row @endif class="transition hover:bg-white/[0.02]">
                                <td class="px-3 py-4 sm:px-5">
                                    @if ($slug)
                                        <a href="{{ route('compound', $slug) }}"
                                           class="font-bold text-white transition hover:text-gold">{{ $report->product_label }}</a>
                                    @else
                                        <span class="font-bold text-white">{{ $report->product_label }}</span>
                                    @endif
                                </td>
                                <td class="hidden px-3 py-4 font-mono text-xs whitespace-nowrap text-white/55 sm:table-cell sm:px-5">{{ $report->batch_number ?? '—' }}</td>
                                <td class="hidden px-3 py-4 whitespace-nowrap text-white/50 sm:table-cell sm:px-5">{{ $report->tested_on?->format('M j, Y') ?? '—' }}</td>
                                <td class="hidden px-3 py-4 whitespace-nowrap text-white/50 lg:table-cell sm:px-5">{{ $report->lab_name ?? '—' }}</td>
                                <td class="px-2 py-4 font-bold whitespace-nowrap text-white/80 sm:px-5">{{ $report->purity ?? '—' }}</td>
                                <td class="px-2 py-4 sm:px-5">
                                    @if ($report->isPass())
                                        <span class="inline-block rounded-full bg-emerald-400/10 px-2.5 py-1 text-[10px] font-extrabold tracking-widest whitespace-nowrap text-emerald-300 uppercase ring-1 ring-emerald-400/30">Pass</span>
                                    @elseif ($report->isTesting())
                                        <span class="inline-block rounded-full bg-amber-400/10 px-2 py-1.5 sm:px-3 text-[10px] font-extrabold tracking-widest text-amber-300 uppercase ring-1 ring-amber-400/30">{{ site_text('labs.testing_label') }}</span>
                                        <span class="mt-2 hidden max-w-56 text-[11px] leading-relaxed normal-case text-white/35 sm:block">{{ site_text('labs.testing_note') }}</span>
                                    @else
                                        <span class="inline-block rounded-full bg-white/5 px-2 py-1.5 sm:px-3 text-[10px] font-extrabold tracking-widest text-white/40 uppercase ring-1 ring-white/15">{{ site_text('labs.pending_label') }}</span>
                                    @endif
                                </td>
                                <td class="hidden px-3 py-4 sm:px-5 text-right sm:table-cell">
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
                                <td class="pr-2 text-right sm:hidden">
                                    @if ($expandable)
                                        <button type="button" data-coa-toggle aria-expanded="false"
                                                aria-label="Show details for {{ $report->product_label }}"
                                                class="rounded-full p-2 text-white/40 transition hover:text-gold">
                                            <svg class="size-4 transition-transform duration-300 ease-out" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                                                <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @if ($expandable)
                                <tr data-coa-detail class="!border-t-0 sm:hidden">
                                    <td colspan="4" class="p-0">
                                        <div data-coa-panel class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-300 ease-out">
                                            <div class="min-h-0 overflow-hidden">
                                                <div data-coa-content class="bg-black/30 px-5 py-5 opacity-0 transition-opacity duration-300 ease-out">
                                                    @if ($report->isPass())
                                                        <dl class="grid grid-cols-2 gap-4 text-sm">
                                                            <div>
                                                                <dt class="text-[10px] tracking-widest text-white/40 uppercase">Batch Number</dt>
                                                                <dd class="mt-1 font-mono text-xs text-white/70">{{ $report->batch_number }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-[10px] tracking-widest text-white/40 uppercase">Analysis Date</dt>
                                                                <dd class="mt-1 text-white/70">{{ $report->tested_on?->format('M j, Y') }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-[10px] tracking-widest text-white/40 uppercase">Laboratory</dt>
                                                                <dd class="mt-1 text-white/70">{{ $report->lab_name }}</dd>
                                                            </div>
                                                            @if ($report->pdfUrl())
                                                                <div class="self-end">
                                                                    <a href="{{ $report->pdfUrl() }}" target="_blank" rel="noopener"
                                                                       class="inline-flex items-center gap-1.5 rounded-full bg-gold px-3.5 py-1.5 text-[11px] font-extrabold tracking-widest whitespace-nowrap text-black uppercase">
                                                                        View COA
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </dl>
                                                    @else
                                                        <p class="text-[12px] leading-relaxed text-white/45">{{ site_text('labs.testing_note') }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
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
                ['step' => '02', 'title' => 'Purity, Identity & Quantitation', 'body' => 'Purity, identity and quantitation are established by high performance liquid chromatography (HPLC), verified against the labeled content of each vial.'],
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
