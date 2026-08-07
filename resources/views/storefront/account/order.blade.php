@php
    use App\Support\Catalog;

    $shipping = $order->shippingAddress;
    $billing = $order->billingAddress;
@endphp

<x-layouts.storefront :title="'Order '.$order->reference.' — Powered Up Peptides'">

    <div class="mx-auto max-w-4xl px-4 py-12">
        <nav class="flex items-center gap-2 text-xs text-white/40">
            <a href="{{ route('account') }}" class="transition hover:text-gold">Account</a>
            <span>/</span>
            <span class="text-white/70">{{ $order->reference }}</span>
        </nav>

        <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="display-title text-4xl text-white">
                    Order <span class="text-foil">{{ $order->reference }}</span>
                </h1>
                <p class="mt-2 text-sm text-white/50">Placed {{ $order->created_at->format('F j, Y \a\t g:ia') }}</p>
            </div>

            <span class="rounded-full bg-gold/15 px-4 py-2 text-[10px] font-extrabold tracking-widest text-gold uppercase">
                {{ Str::headline($order->status) }}
            </span>
        </div>

        <div class="mt-8 rounded-2xl panel p-6">
            <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Items</h2>

            <ul class="mt-5 divide-y divide-white/5">
                @foreach ($order->lines as $line)
                    <li class="flex items-center gap-4 py-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-white">{{ $line->description }}</p>
                            <p class="mt-0.5 text-xs text-white/40">
                                {{ $line->identifier }} &middot; Qty {{ $line->quantity }}
                                &middot; {{ Catalog::money($line->unit_price->value) }} each
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

        <div class="mt-6 grid gap-6 sm:grid-cols-2">
            @foreach ([['Shipping Address', $shipping], ['Billing Address', $billing]] as [$label, $address])
                @if ($address)
                    <div class="rounded-2xl panel p-6">
                        <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">{{ $label }}</h2>
                        <address class="mt-4 text-sm leading-relaxed text-white/60 not-italic">
                            {{ $address->first_name }} {{ $address->last_name }}<br>
                            @if ($address->company_name){{ $address->company_name }}<br>@endif
                            {{ $address->line_one }}<br>
                            @if ($address->line_two){{ $address->line_two }}<br>@endif
                            {{ $address->city }}, {{ $address->state }} {{ $address->postcode }}<br>
                            {{ $address->country?->name }}
                        </address>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('account') }}"
               class="rounded-full border border-white/15 px-6 py-3 text-[11px] font-extrabold tracking-widest text-white uppercase transition hover:border-gold/50 hover:text-gold">
                &larr; Back To Account
            </a>
            <a href="{{ route('lab-reports') }}"
               class="rounded-full border border-white/15 px-6 py-3 text-[11px] font-extrabold tracking-widest text-white uppercase transition hover:border-gold/50 hover:text-gold">
                Batch Lab Reports
            </a>
        </div>
    </div>

</x-layouts.storefront>
