<x-layouts.storefront :title="$policy->title.' — Powered Up Peptides'">

    <section class="relative overflow-hidden bg-electric">
        <div class="mx-auto max-w-7xl px-4 pt-10 pb-12">
            <nav class="flex items-center gap-2 text-xs text-white/40">
                <a href="{{ route('home') }}" class="transition hover:text-gold">Home</a>
                <span>/</span>
                <span class="text-white/70">{{ $policy->title }}</span>
            </nav>

            <p class="mt-8 eyebrow text-gold">Policies</p>
            <h1 class="display-title mt-3 text-4xl text-white sm:text-5xl">
                {{ $policy->title }}
            </h1>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-12 lg:py-16">
        <div class="grid gap-10 lg:grid-cols-[14rem_1fr] lg:gap-16">
            <aside class="lg:sticky lg:top-28 lg:self-start">
                <p class="eyebrow text-white/35">All policies</p>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach (\App\Models\Policy::navigation() as $item)
                        <li>
                            <a href="{{ route('policy', $item['slug']) }}"
                               class="block rounded-lg px-3 py-2 transition {{ $item['slug'] === $policy->slug ? 'bg-gold/10 font-bold text-gold' : 'text-white/55 hover:text-gold' }}">
                                {{ $item['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </aside>

            <article class="policy-prose max-w-3xl">
                {!! $policy->body !!}
                <p class="mt-10 border-t border-white/8 pt-5 text-xs text-white/35">
                    Last updated {{ $policy->updated_at->format('F j, Y') }}
                </p>
            </article>
        </div>
    </div>

</x-layouts.storefront>
