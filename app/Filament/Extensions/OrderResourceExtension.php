<?php

namespace App\Filament\Extensions;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Lunar\Admin\Filament\Resources\OrderResource;
use Lunar\Admin\Support\Extending\ResourceExtension;

/**
 * Lunar's order list filters and dates on `placed_at`, which is only set once
 * a payment settles, and defaults that filter to the last six months. Any
 * order still awaiting payment has no placed date, so it was silently hidden.
 * The list here dates orders by when they were created and starts unfiltered.
 */
class OrderResourceExtension extends ResourceExtension
{
    public function extendTable(Table $table): Table
    {
        return $table
            ->columns(array_map(
                fn (TextColumn $column) => $column->getName() === 'placed_at'
                    ? TextColumn::make('created_at')
                        ->label(__('lunarpanel::order.table.date.label'))
                        ->toggleable()
                        ->sortable()
                        ->dateTime()
                    : $column,
                OrderResource::getTableColumns(),
            ))
            ->filters(array_map(
                fn (BaseFilter $filter) => $filter->getName() === 'placed_at' ? $this->createdDateFilter() : $filter,
                OrderResource::getTableFilters(),
            ));
    }

    protected function createdDateFilter(): Filter
    {
        return Filter::make('created_at')
            ->form([
                DatePicker::make('created_after')->label('Ordered after'),
                DatePicker::make('created_before')->label('Ordered before'),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when(
                    $data['created_after'] ?? null,
                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                )
                ->when(
                    $data['created_before'] ?? null,
                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                ))
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if ($data['created_after'] ?? null) {
                    $indicators[] = Indicator::make('Ordered after '.Carbon::parse($data['created_after'])->toFormattedDateString())
                        ->removeField('created_after');
                }

                if ($data['created_before'] ?? null) {
                    $indicators[] = Indicator::make('Ordered before '.Carbon::parse($data['created_before'])->toFormattedDateString())
                        ->removeField('created_before');
                }

                return $indicators;
            });
    }
}
