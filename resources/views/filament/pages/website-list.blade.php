<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-check">
                Save changes
            </x-filament::button>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Changes appear on the website as soon as you save.
            </span>
        </div>
    </form>
</x-filament-panels::page>
