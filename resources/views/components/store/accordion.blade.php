@props([
    'question',
    'answer' => null,
    'open' => false,
])

@php $id = 'acc-'.Str::uuid(); @endphp

{{--
    Checkbox based rather than <details>, because <details> cannot animate its
    own collapse: the browser hides the content before a transition can run.
--}}
<div {{ $attributes->class(['group/acc overflow-hidden rounded-2xl panel transition-colors duration-300 hover:border-gold/25']) }}>
    <input type="checkbox" id="{{ $id }}" class="peer sr-only" @checked($open)>

    <label for="{{ $id }}"
           class="flex cursor-pointer items-center justify-between gap-4 px-6 py-5 text-sm font-bold text-white
                  transition-colors duration-200 select-none hover:text-gold
                  peer-checked:text-gold peer-focus-visible:ring-2 peer-focus-visible:ring-gold/60
                  peer-checked:[&>svg]:rotate-180">
        <span>{{ $question }}</span>

        <svg class="size-4 shrink-0 text-gold transition-transform duration-300 ease-out"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
            <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </label>

    <div class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-300 ease-out peer-checked:grid-rows-[1fr]">
        <div class="overflow-hidden">
            <div class="px-6 pb-5 text-sm leading-relaxed text-white/55">
                {{ $answer ?? $slot }}
            </div>
        </div>
    </div>
</div>
