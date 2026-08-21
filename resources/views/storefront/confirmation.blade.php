@php
    use App\Support\Catalog;

    $shipping = $order->shippingAddress;
@endphp

<x-layouts.storefront :title="'Order '.$order->reference.' — Powered Up Peptides'">

    <section class="relative overflow-hidden bg-electric">
        <div class="mx-auto max-w-3xl px-4 py-20 text-center">
            <div class="mx-auto grid size-16 place-items-center rounded-full border border-gold/40 bg-gold/10">
                <svg class="size-8 text-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M5 12.5l4.5 4.5L19 7.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <h1 class="display-title mt-7 text-4xl text-white sm:text-5xl">
                Order <span class="text-foil">Confirmed</span>
            </h1>

            <p class="mt-5 text-[15px] leading-relaxed text-white/55">
                Thanks{{ $shipping?->first_name ? ', '.$shipping->first_name : '' }} &mdash; {{ site_text('confirmation.intro') }}
                {{ site_text('confirmation.email_note') }}
                <span class="text-white/80">{{ $shipping?->contact_email }}</span>.
            </p>

            <p class="mt-6 inline-block rounded-full border border-gold/30 bg-black/50 px-5 py-2.5">
                <span class="text-[10px] font-bold tracking-widest text-white/40 uppercase">Reference</span>
                <span class="ml-2 font-mono text-sm font-bold text-gold">{{ $order->reference }}</span>
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-4xl px-4 py-14">
        <div class="grid gap-6 sm:grid-cols-2">

            {{-- Items --}}
            <div class="rounded-2xl panel p-6 sm:col-span-2">
                <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Items</h2>

                <ul class="mt-5 divide-y divide-white/5">
                    @foreach ($order->lines as $line)
                        <li class="flex items-center gap-4 py-4">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-white">{{ $line->description }}</p>
                                <p class="mt-0.5 text-xs text-white/40">
                                    {{ $line->identifier }} &middot; Qty {{ $line->quantity }}
                                </p>
                            </div>
                            <p class="shrink-0 font-bold text-white">{{ Catalog::money($line->sub_total->value) }}</p>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-5 space-y-3 border-t border-white/8 pt-5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-white/55">Subtotal</dt>
                        <dd class="text-white/80">{{ Catalog::money($order->sub_total->value) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-white/55">Shipping</dt>
                        <dd class="text-white/80">{{ Catalog::money($order->shipping_total?->value ?? 0) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-white/55">Tax</dt>
                        <dd class="text-white/80">{{ Catalog::money($order->tax_total?->value ?? 0) }}</dd>
                    </div>
                    <div class="flex items-end justify-between border-t border-white/8 pt-4">
                        <dt class="text-[11px] font-extrabold tracking-widest text-white/45 uppercase">Total</dt>
                        <dd class="display-title text-2xl text-foil">{{ Catalog::money($order->total->value) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Shipping --}}
            @if ($shipping)
                <div class="rounded-2xl panel p-6">
                    <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Shipping To</h2>
                    <address class="mt-4 text-sm leading-relaxed text-white/60 not-italic">
                        {{ $shipping->first_name }} {{ $shipping->last_name }}<br>
                        @if ($shipping->company_name){{ $shipping->company_name }}<br>@endif
                        {{ $shipping->line_one }}<br>
                        @if ($shipping->line_two){{ $shipping->line_two }}<br>@endif
                        {{ $shipping->city }}, {{ $shipping->state }} {{ $shipping->postcode }}<br>
                        {{ $shipping->country?->name }}
                    </address>
                </div>
            @endif

            {{-- Status --}}
            <div class="rounded-2xl panel p-6">
                <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">{{ site_text('confirmation.steps_title') }}</h2>
                <ol class="mt-4 space-y-3 text-sm text-white/60">
                    @foreach (site_list('confirmation.steps') as $step)
                        <li class="flex gap-3">
                            <span class="font-mono text-xs text-gold">{{ sprintf('%02d', $loop->iteration) }}</span>
                            {{ $step->body }}
                        </li>
                    @endforeach
                </ol>
                <p class="mt-5 border-t border-white/8 pt-4 text-xs text-white/40">
                    Status: <span class="font-bold text-white/70">{{ Str::headline($order->status) }}</span>
                </p>
            </div>
        </div>

        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <a href="{{ route('lab-reports') }}"
               class="rounded-full border border-white/15 px-7 py-3.5 text-xs font-extrabold tracking-widest text-white uppercase transition hover:border-gold/50 hover:text-gold">
                {{ site_text('confirmation.lab_reports_button') }}
            </a>
            <a href="{{ route('shop') }}"
               class="rounded-full bg-gold px-7 py-3.5 text-xs font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                Continue Shopping
            </a>
        </div>
    </div>

</x-layouts.storefront>
