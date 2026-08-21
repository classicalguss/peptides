<x-layouts.storefront :title="site_text('contact.meta_title')"
                      :description="site_text('contact.meta_description')">

    <section class="relative overflow-hidden bg-electric">
        <div class="mx-auto max-w-7xl px-4 pt-10 pb-8">
            <nav class="flex items-center gap-2 text-xs text-white/40">
                <a href="{{ route('home') }}" class="transition hover:text-gold">Home</a>
                <span>/</span>
                <span class="text-white/70">Contact</span>
            </nav>

            <h1 class="display-title mt-6 text-5xl text-white sm:text-6xl">
                {!! foil_last_words(site_text('contact.hero_title')) !!}
            </h1>

            <p class="mt-5 max-w-2xl text-[15px] leading-relaxed text-white/55">
                {{ site_text('contact.hero_description') }}
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8">
        <div class="grid gap-8 lg:grid-cols-[1.4fr_1fr] lg:items-start">

            {{-- Form --}}
            <div class="rounded-2xl panel p-6 sm:p-8">
                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-gold/30 bg-gold/[0.07] px-5 py-4 text-sm font-bold text-gold">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-500/30 bg-red-500/[0.07] px-5 py-4">
                        <ul class="space-y-1 text-xs text-red-200/85">
                            @foreach ($errors->all() as $error)
                                <li>&middot; {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Send A Message</h2>

                <form method="POST" action="{{ route('contact.store') }}" class="mt-6 space-y-4">
                    @csrf

                    {{-- Honeypot: hidden from people, irresistible to bots. --}}
                    <div class="hidden" aria-hidden="true">
                        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Your name</span>
                            <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Email</span>
                            <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">{{ site_text('contact.form_subject_label') }}</span>
                            <select name="topic"
                                    class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                                @foreach ($topics as $value => $label)
                                    <option value="{{ $value }}" @selected(old('topic') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">
                                Order reference <span class="text-white/25">(optional)</span>
                            </span>
                            <input type="text" name="order_reference" value="{{ old('order_reference') }}" placeholder="e.g. 00000012"
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white placeholder:text-white/25 outline-none focus:border-gold/50">
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Message</span>
                        <textarea name="message" rows="6" required
                                  class="mt-1.5 w-full resize-y rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm leading-relaxed text-white outline-none focus:border-gold/50">{{ old('message') }}</textarea>
                    </label>

                    <button type="submit"
                            class="w-full rounded-full bg-gold px-8 py-4 text-sm font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright sm:w-auto">
                        Send Message
                    </button>

                    <p class="text-[11px] leading-relaxed text-white/35">
                        {{ site_text('contact.form_disclaimer') }}
                    </p>
                </form>
            </div>

            {{-- Side info --}}
            <aside class="space-y-5">
                <div class="rounded-2xl panel p-6">
                    <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Faster Answers</h2>
                    <div class="mt-5 space-y-4">
                        <a href="{{ route('lab-reports') }}" class="block rounded-xl border border-white/8 bg-black/40 p-4 transition hover:border-gold/30">
                            <p class="text-sm font-bold text-white">{{ site_text('contact.certificate_help_title') }}</p>
                            <p class="mt-1 text-xs text-white/45">{{ site_text('contact.certificate_help_description') }}</p>
                        </a>
                        <a href="{{ auth()->check() ? route('account') : route('login') }}"
                           class="block rounded-xl border border-white/8 bg-black/40 p-4 transition hover:border-gold/30">
                            <p class="text-sm font-bold text-white">Checking an order?</p>
                            <p class="mt-1 text-xs text-white/45">{{ site_text('contact.account_help_description') }}</p>
                        </a>
                        <a href="{{ route('shop') }}" class="block rounded-xl border border-white/8 bg-black/40 p-4 transition hover:border-gold/30">
                            <p class="text-sm font-bold text-white">{{ site_text('contact.protocol_help_title') }}</p>
                            <p class="mt-1 text-xs text-white/45">{{ site_text('contact.protocol_help_description') }}</p>
                        </a>
                    </div>
                </div>

                <div class="rounded-2xl panel p-6">
                    <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Response Times</h2>
                    <dl class="mt-5 space-y-3.5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-white/55">Email</dt>
                            <dd class="text-right font-bold text-white">{{ site_text('contact.response_email') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-white/55">Order dispatch</dt>
                            <dd class="text-right font-bold text-white">Within 24 hours</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-white/55">Support hours</dt>
                            <dd class="text-right font-bold text-white">Mon&ndash;Fri, 9am&ndash;6pm</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-gold/25 bg-gold/[0.04] p-6">
                    <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Before You Write</h2>
                    <ul class="mt-4 space-y-2.5 text-xs leading-relaxed text-white/55">
                        @foreach (site_list('contact.write_tips') as $tip)
                            <li class="flex gap-2.5"><span class="text-gold">&#10003;</span> {{ $tip->body }}</li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>

        {{-- FAQ --}}
        <section id="faq" class="mt-8 scroll-mt-28">
            <x-store.section-heading :eyebrow="site_text('contact.faq_eyebrow')" :title="foil_last_words(site_text('contact.faq_title'))" />

            <div class="mx-auto mt-10 max-w-3xl space-y-3">
                @foreach (site_list('contact.faq') as $item)
                    <x-store.accordion :question="$item->heading" :answer="$item->body"
                                       class="reveal" style="--reveal-delay: {{ $loop->index * 60 }}ms" />
                @endforeach
            </div>
        </section>
    </div>

    <x-store.trust-bar />

</x-layouts.storefront>
