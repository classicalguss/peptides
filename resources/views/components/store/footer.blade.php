<footer class="mt-24 border-t border-white/5 bg-black">
    <div class="mx-auto max-w-7xl px-4 py-14">
        <div class="grid gap-10 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
            <div>
                <img src="{{ asset('assets/brand/logo-wordmark.png') }}" alt="Powered Up Peptides" class="h-11 w-auto">
                <p class="mt-5 max-w-sm text-sm leading-relaxed text-white/45">
                    Research-grade peptides with published third-party analysis on every batch.
                    Built for laboratories and researchers who need to trust what is in the vial.
                </p>
            </div>

            <div>
                <h3 class="eyebrow text-gold">Shop</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/55">
                    <li><a href="{{ route('stacks') }}" class="transition hover:text-gold">Stack Protocols</a></li>
                    <li><a href="{{ route('shop') }}" class="transition hover:text-gold">Individual Compounds</a></li>
                    <li><a href="{{ route('shop', ['category' => 'supplies']) }}" class="transition hover:text-gold">Supplies</a></li>
                </ul>
            </div>

            <div>
                <h3 class="eyebrow text-gold">Transparency</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/55">
                    <li><a href="{{ route('lab-reports') }}" class="transition hover:text-gold">Lab Reports &amp; COAs</a></li>
                    <li><a href="{{ route('lab-reports') }}" class="transition hover:text-gold">Batch Lookup</a></li>
                    <li><a href="{{ route('about') }}#process" class="transition hover:text-gold">How We Test</a></li>
                </ul>
            </div>

            <div>
                <h3 class="eyebrow text-gold">Company</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/55">
                    <li><a href="{{ route('about') }}" class="transition hover:text-gold">About Us</a></li>
                    <li><a href="{{ route('contact') }}" class="transition hover:text-gold">Contact Us</a></li>
                    <li><a href="{{ route('contact') }}#faq" class="transition hover:text-gold">Shipping &amp; FAQ</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 rounded-xl border border-gold/20 bg-gold/[0.04] p-5">
            <p class="text-xs leading-relaxed text-white/50">
                <span class="font-bold text-gold">RESEARCH USE ONLY.</span>
                All products sold by Powered Up Peptides are intended strictly for in-vitro laboratory
                research and are <strong class="text-white/70">not for human or veterinary consumption</strong>.
                These products are not drugs, foods, or cosmetics and have not been evaluated by the FDA.
                Any reference to dosages reflects published third-party literature and is not a
                recommendation for use in humans. By purchasing, you confirm you are a qualified
                researcher aged 21 or over.
            </p>
        </div>

        <div class="mt-8 flex flex-col gap-3 border-t border-white/5 pt-6 text-xs text-white/35 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} Powered Up Peptides. All rights reserved.</p>
            <p>Terms &middot; Privacy &middot; Compliance</p>
        </div>
    </div>
</footer>
