@php
    use App\Support\Catalog;

    $countries = \Lunar\Models\Country::orderBy('name')->get(['id', 'name']);
    $defaultCountry = old('country_id', \Lunar\Models\Country::where('iso2', 'US')->value('id'));
@endphp

<x-layouts.storefront title="Checkout — Powered Up Peptides">

    <div class="mx-auto max-w-7xl px-4 py-12">
        <nav class="flex items-center gap-2 text-xs text-white/40">
            <a href="{{ route('cart') }}" class="transition hover:text-gold">Cart</a>
            <span>/</span>
            <span class="text-white/70">Checkout</span>
        </nav>

        <h1 class="display-title mt-6 text-4xl text-white sm:text-5xl">
            Secure <span class="text-foil">Checkout</span>
        </h1>

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-500/30 bg-red-500/[0.07] px-5 py-4">
                <p class="text-sm font-bold text-red-300">Please fix the following:</p>
                <ul class="mt-2 space-y-1 text-xs text-red-200/80">
                    @foreach ($errors->all() as $error)
                        <li>&middot; {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" class="mt-10 grid gap-8 lg:grid-cols-[1.6fr_1fr] lg:items-start">
            @csrf

            <div class="space-y-6">

                {{-- Contact --}}
                <section class="rounded-2xl panel p-6">
                    <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Contact Details</h2>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">First name</span>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Last name</span>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Email</span>
                            <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Phone <span class="text-white/25">(optional)</span></span>
                            <input type="tel" name="contact_phone" value="{{ old('contact_phone') }}"
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                    </div>
                </section>

                {{-- Address --}}
                <section class="rounded-2xl panel p-6">
                    <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Shipping Address</h2>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="block sm:col-span-2">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Institution / company <span class="text-white/25">(optional)</span></span>
                            <input type="text" name="company_name" value="{{ old('company_name') }}"
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Address line 1</span>
                            <input type="text" name="line_one" value="{{ old('line_one') }}" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Address line 2 <span class="text-white/25">(optional)</span></span>
                            <input type="text" name="line_two" value="{{ old('line_two') }}"
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">City</span>
                            <input type="text" name="city" value="{{ old('city') }}" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">State / region</span>
                            <input type="text" name="state" value="{{ old('state') }}" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Postal code</span>
                            <input type="text" name="postcode" value="{{ old('postcode') }}" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Country</span>
                            <select name="country_id" required
                                    class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @selected((int) $defaultCountry === $country->id)>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </section>

                {{-- Shipping method --}}
                <section class="rounded-2xl panel p-6">
                    <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Shipping Method</h2>

                    <div class="mt-5 space-y-3">
                        @foreach ($shippingOptions as $index => $option)
                            <label class="flex cursor-pointer items-center gap-4 rounded-xl border border-white/10 bg-black/40 p-4 transition
                                          has-checked:gold-ring has-checked:bg-gold/[0.06] hover:border-white/25">
                                <input type="radio" name="shipping_option" value="{{ $option->getIdentifier() }}"
                                       @checked(old('shipping_option') ? old('shipping_option') === $option->getIdentifier() : $index === 0)
                                       required
                                       class="size-4 shrink-0 appearance-none rounded-full border-2 border-white/25 transition checked:border-gold checked:bg-gold checked:shadow-[inset_0_0_0_3px_#000]">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-white">{{ $option->getDescription() ? $option->name : $option->name }}</p>
                                    <p class="mt-0.5 text-xs text-white/45">{{ $option->getDescription() }}</p>
                                </div>
                                <p class="shrink-0 font-bold text-white">
                                    {{ $option->getPrice()->value === 0 ? 'Free' : Catalog::money($option->getPrice()->value) }}
                                </p>
                            </label>
                        @endforeach
                    </div>
                </section>

                {{-- Compliance --}}
                <section class="rounded-2xl border border-gold/25 bg-gold/[0.04] p-6">
                    <label class="flex cursor-pointer gap-3.5">
                        <input type="checkbox" name="research_use_confirmed" value="1" @checked(old('research_use_confirmed'))
                               class="mt-0.5 size-4 shrink-0 appearance-none rounded border-2 border-white/25 transition checked:border-gold checked:bg-gold">
                        <span class="text-xs leading-relaxed text-white/60">{{ site_text('checkout.disclaimer_text') }}</span>
                    </label>
                </section>
            </div>

            {{-- Summary --}}
            <aside class="rounded-2xl panel gold-ring p-6 lg:sticky lg:top-28">
                <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Order Summary</h2>

                <ul class="mt-5 divide-y divide-white/5">
                    @foreach ($cart->lines as $line)
                        @php $display = Catalog::variantDisplay($line->purchasable); @endphp
                        <li class="flex gap-3 py-3.5">
                            <div class="relative size-14 shrink-0 overflow-hidden rounded-lg bg-black/50">
                                @if ($display['image'])
                                    <img src="{{ $display['image'] }}" alt="" class="absolute inset-0 size-full object-contain p-1">
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-white">{{ $display['name'] }}</p>
                                <p class="text-xs text-white/40">Qty {{ $line->quantity }}</p>
                            </div>
                            <p class="shrink-0 text-sm font-bold text-white">{{ Catalog::money($line->subTotal->value) }}</p>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-5 space-y-3 border-t border-white/8 pt-5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-white/55">Subtotal</dt>
                        <dd class="font-bold text-white">{{ Catalog::money($cart->subTotal?->value ?? 0) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-white/55">Tax</dt>
                        <dd class="text-white/70">{{ Catalog::money($cart->taxTotal?->value ?? 0) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-white/55">Shipping</dt>
                        <dd class="text-white/70">Added on selection</dd>
                    </div>
                </dl>

                <div class="mt-5 flex items-end justify-between border-t border-white/8 pt-5">
                    <span class="text-[11px] font-extrabold tracking-widest text-white/45 uppercase">Total</span>
                    <span class="display-title text-3xl text-foil">{{ Catalog::money($cart->total?->value ?? 0) }}</span>
                </div>

                <button type="submit"
                        class="mt-6 w-full rounded-full bg-gold px-8 py-4 text-sm font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                    Place Order
                </button>

                <p class="mt-4 text-center text-[11px] text-white/35">
                    No payment is taken in this build &mdash; the order is created in Lunar as
                    awaiting payment.
                </p>
            </aside>
        </form>
    </div>

</x-layouts.storefront>
