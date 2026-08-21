@php
    $statePath = $getStatePath();
    $colorStatePath = $getColorStatePath();
    $colors = $getColors();
    $placeholder = $getPlaceholder() ?? 'Set a status';
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            color: $wire.$entangle('{{ $colorStatePath }}'),
            swatches: @js($colors),
            current() { return this.swatches[this.color] ?? this.swatches[Object.keys(this.swatches)[0]]; },
        }"
    >
        <x-filament::dropdown placement="bottom-start" width="xs" :close-on-click="false" teleport>
            <x-slot name="trigger">
                {{-- The pill: shows the status in its colour; click to edit --}}
                <button
                    type="button"
                    class="inline-flex max-w-full items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition"
                    :style="`background-color: ${current().swatch}; border-color: ${current().text}33; color: ${current().text};`"
                >
                    <span x-text="state || '{{ $placeholder }}'" class="truncate"></span>
                    <svg class="h-3.5 w-3.5 shrink-0 opacity-60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M2.5 14.5V17.5H5.5L14.3 8.7L11.3 5.7L2.5 14.5ZM16.6 6.4C16.9 6.1 16.9 5.6 16.6 5.3L14.7 3.4C14.4 3.1 13.9 3.1 13.6 3.4L12.2 4.8L15.2 7.8L16.6 6.4Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </x-slot>

            <div class="p-3">
                <input
                    type="text"
                    x-model="state"
                    placeholder="{{ $placeholder }}"
                    maxlength="255"
                    class="fi-input block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                />

                <div class="mt-3 flex items-center gap-2">
                    <template x-for="(swatch, key) in swatches" :key="key">
                        <button
                            type="button"
                            @click="color = key"
                            :title="swatch.label"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border transition"
                            :style="`background-color: ${swatch.swatch}; border-color: ${color === key ? swatch.text : swatch.text + '33'}; box-shadow: ${color === key ? '0 0 0 2px ' + swatch.text + '55' : 'none'};`"
                        >
                            <svg x-show="color === key" class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" :style="`color: ${swatch.text}`">
                                <path d="M5 10.5l3.2 3.2L15 7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </template>
                </div>
            </div>
        </x-filament::dropdown>
    </div>
</x-dynamic-component>
