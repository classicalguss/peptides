<x-layouts.storefront :title="$heading.' — Powered Up Peptides'">
    <section class="relative overflow-hidden bg-electric">
        <div class="mx-auto max-w-3xl px-4 py-28 text-center">
            <p class="eyebrow text-gold">Next Up</p>
            <h1 class="display-title mt-4 text-5xl text-white sm:text-6xl">
                {{ $heading }}
            </h1>
            <p class="mx-auto mt-6 max-w-xl text-[15px] leading-relaxed text-white/55">
                {{ $body }}
            </p>
            <div class="mt-10 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}"
                   class="rounded-full bg-gold px-7 py-3.5 text-sm font-extrabold tracking-wider text-black uppercase transition hover:bg-gold-bright">
                    Back Home
                </a>
                <a href="{{ route('stack', 'healing-stack') }}"
                   class="rounded-full border border-white/15 px-7 py-3.5 text-sm font-extrabold tracking-wider text-white uppercase transition hover:border-gold/50 hover:text-gold">
                    View Healing Stack
                </a>
            </div>
        </div>
    </section>
</x-layouts.storefront>
