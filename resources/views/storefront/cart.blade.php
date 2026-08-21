@php
    use App\Support\Catalog;

    $lines = $cart?->lines ?? collect();
    $subTotal = $cart?->subTotal?->value ?? 0;
    $remaining = max(0, $freeShippingThreshold - $subTotal);
    $progress = $freeShippingThreshold > 0 ? min(100, (int) round($subTotal / $freeShippingThreshold * 100)) : 100;
@endphp

<x-layouts.storefront :title="site_text('cart.meta_title')">

    <div class="mx-auto max-w-7xl px-4 py-12">
        <nav class="flex items-center gap-2 text-xs text-white/40">
            <a href="{{ route('home') }}" class="transition hover:text-gold">Home</a>
            <span>/</span>
            <span class="text-white/70">Cart</span>
        </nav>

        <h1 class="display-title mt-6 text-4xl text-white sm:text-5xl">
            Your <span class="text-foil">Cart</span>
        </h1>

        @if (session('status'))
            <div class="mt-6 rounded-xl border border-gold/25 bg-gold/[0.06] px-5 py-3.5 text-sm text-gold">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-500/30 bg-red-500/[0.07] px-5 py-4 text-sm text-red-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($lines->isEmpty())
            <div class="mt-10 rounded-2xl panel p-16 text-center">
                <p class="display-title text-3xl text-white">{{ site_text('cart.empty_title') }}</p>
                <p class="mx-auto mt-4 max-w-md text-sm leading-relaxed text-white/50">
                    {{ site_text('cart.empty_description') }}
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('stacks') }}"
                       class="rounded-full bg-gold px-7 py-3.5 text-xs font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                        {{ site_text('cart.empty_collections_button') }}
                    </a>
                    <a href="{{ route('shop') }}"
                       class="rounded-full border border-white/15 px-7 py-3.5 text-xs font-extrabold tracking-widest text-white uppercase transition hover:border-gold/50 hover:text-gold">
                        Browse Compounds
                    </a>
                </div>
            </div>
        @else
            <div class="mt-10 grid gap-8 lg:grid-cols-[1.6fr_1fr] lg:items-start">

                {{-- Lines --}}
                <div class="space-y-4">
                    @if ($remaining > 0)
                        <div class="rounded-2xl panel p-5">
                            <p class="text-sm text-white/70">
                                Add <span class="font-bold text-gold">{{ Catalog::money($remaining) }}</span>
                                more to unlock <span class="font-bold text-white">free standard shipping</span>.
                            </p>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/8">
                                <div class="h-full rounded-full bg-gold" style="width: {{ $progress }}%"></div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-gold/25 bg-gold/[0.06] p-5">
                            <p class="text-sm font-bold text-gold">{{ site_text('cart.shipping_unlocked') }}</p>
                        </div>
                    @endif

                    @foreach ($lines as $line)
                        @php $display = Catalog::variantDisplay($line->purchasable); @endphp
                        <div style="--accent: {{ $display['accent'] }}"
                             class="flex gap-4 rounded-2xl panel p-4 sm:gap-5 sm:p-5">

                            <div class="relative size-24 shrink-0 overflow-hidden rounded-xl bg-black/50 sm:size-28">
                                @if ($display['image'])
                                    <img src="{{ $display['image'] }}" alt="{{ $display['name'] }}"
                                         class="absolute inset-0 size-full object-contain p-2">
                                @endif
                            </div>

                            <div class="flex min-w-0 flex-1 flex-col">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        @if ($display['url'])
                                            <a href="{{ $display['url'] }}"
                                               class="display-title text-lg text-white transition hover:text-[var(--accent)]">
                                                {{ $display['name'] }}
                                            </a>
                                        @else
                                            <p class="display-title text-lg text-white">{{ $display['name'] }}</p>
                                        @endif

                                        @if ($display['meta'])
                                            <p class="mt-1 text-xs text-[var(--accent)]">{{ $display['meta'] }}</p>
                                        @endif

                                        <p class="mt-1 text-xs text-white/40">
                                            {{ Catalog::money($line->unitPrice->value) }} each
                                            &middot; SKU {{ $line->purchasable?->sku }}
                                        </p>
                                    </div>

                                    <p class="display-title shrink-0 text-xl text-white">
                                        {{ Catalog::money($line->subTotal->value) }}
                                    </p>
                                </div>

                                <div class="mt-auto flex items-center justify-between gap-3 pt-4">
                                    <form method="POST" action="{{ route('cart.line.update', $line) }}"
                                          class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <label for="qty-{{ $line->id }}" class="text-[10px] font-bold tracking-widest text-white/40 uppercase">Qty</label>
                                        <input type="number" name="quantity" id="qty-{{ $line->id }}"
                                               value="{{ $line->quantity }}" min="1" max="100"
                                               class="w-16 rounded-lg border border-white/12 bg-black/50 px-2.5 py-1.5 text-sm text-white outline-none focus:border-gold/50">
                                        <button type="submit"
                                                class="rounded-full border border-white/12 px-3.5 py-1.5 text-[10px] font-extrabold tracking-widest text-white/60 uppercase transition hover:border-gold/40 hover:text-gold">
                                            Update
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('cart.line.remove', $line) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-[10px] font-extrabold tracking-widest text-white/35 uppercase transition hover:text-red-400">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('shop') }}"
                           class="text-[11px] font-extrabold tracking-widest text-white/50 uppercase transition hover:text-gold">
                            &larr; Continue Shopping
                        </a>
                        <form method="POST" action="{{ route('cart.clear') }}">
                            @csrf
                            <button type="submit"
                                    class="text-[11px] font-extrabold tracking-widest text-white/35 uppercase transition hover:text-red-400">
                                Clear Cart
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Summary --}}
                <aside class="rounded-2xl panel gold-ring p-6 lg:sticky lg:top-28">
                    <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Order Summary</h2>

                    <dl class="mt-6 space-y-3.5 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-white/55">Subtotal</dt>
                            <dd class="font-bold text-white">{{ Catalog::money($subTotal) }}</dd>
                        </div>

                        @if ($cart?->discountTotal?->value)
                            <div class="flex items-center justify-between">
                                <dt class="text-white/55">Discount</dt>
                                <dd class="font-bold text-gold">-{{ Catalog::money($cart->discountTotal->value) }}</dd>
                            </div>
                        @endif

                        <div class="flex items-center justify-between">
                            <dt class="text-white/55">Shipping</dt>
                            <dd class="text-white/70">
                                @if ($remaining > 0)
                                    Calculated at checkout
                                @else
                                    <span class="font-bold text-gold">Free</span>
                                @endif
                            </dd>
                        </div>

                        <div class="flex items-center justify-between">
                            <dt class="text-white/55">Tax</dt>
                            <dd class="text-white/70">{{ Catalog::money($cart?->taxTotal?->value ?? 0) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 flex items-end justify-between border-t border-white/8 pt-5">
                        <span class="text-[11px] font-extrabold tracking-widest text-white/45 uppercase">Total</span>
                        <span class="display-title text-3xl text-foil">{{ Catalog::money($cart?->total?->value ?? 0) }}</span>
                    </div>

                    <form method="POST" action="{{ route('checkout.begin') }}" class="mt-6">
                        @csrf

                        <div class="rounded-xl border border-gold/25 bg-gold/[0.05] p-4">
                            <p class="text-[10px] font-extrabold tracking-widest text-gold uppercase">{{ site_text('cart.disclaimer_heading') }}</p>
                            <label class="mt-3 flex cursor-pointer gap-3">
                                <input type="checkbox" name="research_disclaimer_accepted" value="1" required
                                       @checked(old('research_disclaimer_accepted'))
                                       class="mt-0.5 size-4 shrink-0 appearance-none rounded border-2 border-white/30 transition checked:border-gold checked:bg-gold focus:outline-none focus:ring-2 focus:ring-gold/40">
                                <span class="text-xs leading-relaxed text-white/60">{{ site_text('cart.disclaimer_text') }}</span>
                            </label>
                        </div>

                        <button type="submit"
                                class="mt-4 w-full rounded-full bg-gold px-8 py-4 text-center text-sm font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                            {{ site_text('cart.disclaimer_button') }}
                        </button>
                    </form>

                    <ul class="mt-6 space-y-2.5 text-xs text-white/45">
                        @foreach (site_list('cart.trust') as $line)
                            <li class="flex items-center gap-2"><span class="text-gold">&#10003;</span> {{ $line->body }}</li>
                        @endforeach
                    </ul>

                </aside>
            </div>
        @endif
    </div>

</x-layouts.storefront>
