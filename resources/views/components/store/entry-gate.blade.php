{{--
    Item 19: acknowledgment shown before a first-time visitor can use the
    site. Remembered in localStorage so returning visitors are not
    interrupted again. Pure CSS/JS, no framework — matches reveal.js.
--}}
<div id="entry-gate" class="fixed inset-0 z-[999] hidden items-center justify-center bg-black/95 p-6 backdrop-blur-sm">
    <div class="max-w-md rounded-2xl border border-gold/20 bg-panel p-8 text-center shadow-2xl">
        <p class="text-xs font-extrabold tracking-widest text-gold uppercase">{{ site_text('gate.heading') }}</p>
        <p class="mt-3 text-sm leading-relaxed text-white/70">{{ site_text('gate.body') }}</p>
        <button id="entry-gate-accept" type="button"
                class="mt-7 w-full rounded-full bg-gold px-6 py-3 text-xs font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
            {{ site_text('gate.button') }}
        </button>
    </div>
</div>
