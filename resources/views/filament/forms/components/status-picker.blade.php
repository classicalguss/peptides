@php
    use App\Models\CoaReport;

    $statePath = $getStatePath();
    $colorStatePath = $getColorStatePath();
    $colors = collect($getColors())->map(fn (array $color) => [
        'label' => $color['label'],
        'hex' => $color['hex'],
        'style' => CoaReport::pillStyle($color['hex']),
    ])->all();
    $placeholder = $getPlaceholder() ?? 'Set a status';
    $background = CoaReport::PREVIEW_BACKGROUND;
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
                {{-- Preview of the pill exactly as the website renders it, on the site's background --}}
                <button
                    type="button"
                    class="inline-flex max-w-full items-center gap-2 rounded-lg p-2"
                    style="background-color: {{ $background }};"
                >
                    <span
                        class="inline-block truncate rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-widest"
                        :style="current().style"
                        x-text="state || '{{ $placeholder }}'"
                    ></span>
                    <svg class="h-3.5 w-3.5 shrink-0 text-white/50" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
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

                {{-- Swatches are the same pills on the same background, so the choice is what visitors see --}}
                <div class="mt-3 flex items-center gap-1.5 rounded-lg p-1.5" style="background-color: {{ $background }};">
                    <template x-for="(swatch, key) in swatches" :key="key">
                        <button
                            type="button"
                            @click="color = key"
                            :title="swatch.label"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-[10px] font-extrabold uppercase"
                            :style="swatch.style + (color === key ? ' outline: 2px solid ' + swatch.hex + '; outline-offset: 2px;' : '')"
                        >
                            <svg x-show="color === key" class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M5 10.5l3.2 3.2L15 7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span x-show="color !== key" class="h-3 w-3 rounded-full" :style="`background-color: ${swatch.hex};`"></span>
                        </button>
                    </template>
                </div>
            </div>
        </x-filament::dropdown>
    </div>
</x-dynamic-component>
