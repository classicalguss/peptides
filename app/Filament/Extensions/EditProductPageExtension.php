<?php

namespace App\Filament\Extensions;

use App\Models\Product;
use App\Models\StackComponent;
use App\Models\StackTier;
use App\Models\StackTierQuantity;
use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Lunar\Admin\Support\Extending\EditPageExtension;

class EditProductPageExtension extends EditPageExtension
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $pendingIncludedItems = null;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $pendingCollectionSizes = null;

    public function headerActions(array $actions): array
    {
        $url = $this->product()?->storefrontUrl();

        if (! $url) {
            return $actions;
        }

        return [
            Actions\Action::make('preview_storefront')
                ->label('Preview Storefront Page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url($url)
                ->openUrlInNewTab(),
            ...$actions,
        ];
    }

    public function beforeFill(array $data): array
    {
        $product = $this->product();

        if (! $product?->isStack()) {
            return $data;
        }

        $data['included_items']['components'] = StackComponent::query()
            ->where('stack_product_id', $product->id)
            ->orderBy('position')
            ->with('tierQuantities')
            ->get()
            ->map(fn (StackComponent $component) => [
                'id' => $component->id,
                'component_product_id' => $component->component_product_id,
                'quantities' => $component->tierQuantities
                    ->mapWithKeys(fn (StackTierQuantity $quantity) => [$quantity->stack_tier_id => $quantity->quantity])
                    ->all(),
            ])
            ->all();

        $data['collection_sizes']['tiers'] = StackTier::query()
            ->where('product_id', $product->id)
            ->orderBy('position')
            ->get(['id', 'code', 'label'])
            ->map(fn (StackTier $tier) => [
                'id' => $tier->id,
                'code' => $tier->code,
                'label' => $tier->label,
            ])
            ->all();

        return $data;
    }

    public function beforeUpdate(array $data, Model $record): array
    {
        $includedItems = Arr::pull($data, 'included_items');
        $this->pendingIncludedItems = is_array($includedItems) ? ($includedItems['components'] ?? null) : null;

        $collectionSizes = Arr::pull($data, 'collection_sizes');
        $this->pendingCollectionSizes = is_array($collectionSizes) ? ($collectionSizes['tiers'] ?? null) : null;

        return $data;
    }

    public function afterUpdate(Model $record, array $data): Model
    {
        if ($this->pendingIncludedItems !== null) {
            $this->syncIncludedItems($record, $this->pendingIncludedItems);
            $this->pendingIncludedItems = null;
        }

        if ($this->pendingCollectionSizes !== null) {
            foreach ($this->pendingCollectionSizes as $tier) {
                StackTier::query()
                    ->where('id', $tier['id'] ?? 0)
                    ->where('product_id', $record->getKey())
                    ->update([
                        'code' => (string) ($tier['code'] ?? ''),
                        'label' => (string) ($tier['label'] ?? ''),
                    ]);
            }

            $this->pendingCollectionSizes = null;
        }

        return $record;
    }

    /**
     * Replace the collection's components with the submitted rows, in the
     * submitted order. Rows are rewritten atomically so reordering or
     * swapping compounds never collides with the unique (stack, compound)
     * constraint part-way through.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncIncludedItems(Model $record, array $items): void
    {
        $stackId = $record->getKey();

        DB::transaction(function () use ($stackId, $items): void {
            StackComponent::query()->where('stack_product_id', $stackId)->delete();

            $tierIds = StackTier::query()->where('product_id', $stackId)->pluck('id');
            $seen = [];

            foreach (array_values($items) as $position => $item) {
                $componentId = (int) ($item['component_product_id'] ?? 0);

                if ($componentId <= 0 || in_array($componentId, $seen, true)) {
                    continue;
                }

                $seen[] = $componentId;

                $component = StackComponent::create([
                    'stack_product_id' => $stackId,
                    'component_product_id' => $componentId,
                    'unit' => 'VIAL',
                    'position' => $position + 1,
                ]);

                foreach ($tierIds as $tierId) {
                    StackTierQuantity::create([
                        'stack_tier_id' => $tierId,
                        'stack_component_id' => $component->id,
                        'quantity' => max(0, (int) ($item['quantities'][$tierId] ?? 1)),
                    ]);
                }
            }
        });
    }

    private function product(): ?Product
    {
        $record = $this->caller?->getRecord();

        return $record ? Product::query()->with('urls')->find($record->getKey()) : null;
    }
}
