<footer class="mt-12 border-t border-white/5 bg-black">
    <div class="mx-auto max-w-7xl px-4 py-14">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr_1.1fr]">
            <div>
                <img src="{{ asset('assets/brand/logo-wordmark.png') }}" alt="Powered Up Peptides" class="h-11 w-auto">
                <p class="mt-5 max-w-sm text-sm leading-relaxed text-white/45">
                    {{ site_text('global.footer_description') }}
                </p>
            </div>

            <div>
                <h3 class="eyebrow text-gold">Shop</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/55">
                    <li><a href="{{ route('stacks') }}" class="transition hover:text-gold">{{ site_text('global.footer_collections_link') }}</a></li>
                    <li><a href="{{ route('shop') }}" class="transition hover:text-gold">Individual Compounds</a></li>
                    <li><a href="{{ route('shop', ['category' => 'supplies']) }}" class="transition hover:text-gold">Supplies</a></li>
                </ul>
            </div>

            <div>
                <h3 class="eyebrow text-gold">Transparency</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/55">
                    <li><a href="{{ route('lab-reports') }}" class="transition hover:text-gold">{{ site_text('global.footer_labs_link') }}</a></li>
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

            <div>
                <h3 class="eyebrow text-gold">Policies</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/55">
                    @foreach (\App\Models\Policy::navigation() as $policy)
                        <li><a href="{{ route('policy', $policy['slug']) }}" class="transition hover:text-gold">{{ $policy['title'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="mt-12 rounded-xl border border-gold/20 bg-gold/[0.04] p-5">
            <p class="text-xs leading-relaxed text-white/50">
                <span class="font-bold text-gold">{{ site_text('global.footer_disclaimer_heading') }}</span>
                {{ site_text('global.footer_disclaimer') }}
            </p>
        </div>

        <div class="mt-8 flex flex-col gap-3 border-t border-white/5 pt-6 text-xs text-white/35 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} Powered Up Peptides. All rights reserved.</p>
            <p class="flex flex-wrap gap-x-3 gap-y-1">
                <a href="{{ route('policy', 'terms-and-conditions') }}" class="transition hover:text-gold">Terms</a>
                <span aria-hidden="true">&middot;</span>
                <a href="{{ route('policy', 'privacy-policy') }}" class="transition hover:text-gold">Privacy</a>
                <span aria-hidden="true">&middot;</span>
                <a href="{{ route('policy', 'research-use-only-policy') }}" class="transition hover:text-gold">Research Use Only</a>
            </p>
        </div>
    </div>
</footer>
