<?php

namespace App\Filament\Extensions;

use App\Models\ShippingSetting;
use App\Shipping\EasyPostClient;
use App\Shipping\EasyPostException;
use App\Shipping\ShipOrder;
use App\Shipping\ShippingLabel;
use App\Support\Catalog;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Lunar\Admin\Support\Extending\ViewPageExtension;
use Lunar\Models\Order;

/**
 * Adds EasyPost label buying to Lunar's order screen.
 */
class ManageOrderPageExtension extends ViewPageExtension
{
    public function headerActions(array $actions): array
    {
        return [
            $this->buyLabelAction(),
            $this->printLabelAction(),
            ...$actions,
        ];
    }

    /**
     * A two-step wizard: the package step prices the shipment with EasyPost
     * (free), the rate step shows what each service costs before anything is
     * bought. Only submitting the wizard spends money.
     */
    protected function buyLabelAction(): Action
    {
        $easyPost = app(EasyPostClient::class);

        return Action::make('buy_shipping_label')
            ->label('Buy Shipping Label')
            ->icon('heroicon-o-truck')
            ->color('primary')
            ->visible(fn (Order $record): bool => $easyPost->isConfigured()
                && $record->shippingAddress !== null
                && ShippingLabel::forOrder($record) === null)
            ->modalHeading('Buy shipping label')
            ->modalDescription($easyPost->isTestMode()
                ? 'TEST MODE — the label is a sample and nothing is charged.'
                : 'Buying a label charges the EasyPost account.')
            ->modalSubmitActionLabel('Buy label')
            ->steps([
                Step::make('Package')
                    ->description('Weight and size of the box')
                    ->schema([
                        TextInput::make('weight_oz')
                            ->label('Weight')
                            ->suffix('oz')
                            ->numeric()
                            ->minValue(0.1)
                            ->required()
                            ->default(fn (): float => ShippingSetting::current()->parcel()['weight_oz']),
                        Grid::make(3)->schema(
                            collect(['length', 'width', 'height'])
                                ->map(fn (string $side) => TextInput::make($side)
                                    ->suffix('in')
                                    ->numeric()
                                    ->minValue(0.1)
                                    ->required()
                                    ->default(fn (): float => ShippingSetting::current()->parcel()[$side]))
                                ->all()
                        ),
                        Hidden::make('shipment_id'),
                        Hidden::make('rates'),
                    ])
                    ->afterValidation(function (Get $get, Set $set, $livewire) use ($easyPost): void {
                        try {
                            $shipment = $easyPost->createShipment($livewire->getRecord(), [
                                'weight_oz' => $get('weight_oz'),
                                'length' => $get('length'),
                                'width' => $get('width'),
                                'height' => $get('height'),
                            ]);
                        } catch (EasyPostException $e) {
                            $this->notifyFailure('Could not get shipping rates', $e);

                            throw new Halt;
                        }

                        $set('shipment_id', $shipment['id']);
                        $set('rates', collect($shipment['rates'])->mapWithKeys(fn (array $rate) => [
                            $rate['id'] => $this->rateLabel($rate),
                        ])->all());
                        $set('rate_id', $shipment['rates'][0]['id']);
                    }),
                Step::make('Rate')
                    ->description('Choose the service to buy')
                    ->schema([
                        Radio::make('rate_id')
                            ->label('Shipping service')
                            ->helperText(fn (Order $record): string => 'Customer chose: '.($record->shippingLines->first()?->description ?: 'no shipping method recorded'))
                            ->options(fn (Get $get): array => (array) $get('rates'))
                            ->required(),
                    ]),
            ])
            ->action(function (array $data, Order $record, Action $action): void {
                try {
                    $label = app(ShipOrder::class)->handle($record, (string) $data['shipment_id'], (string) $data['rate_id']);
                } catch (EasyPostException $e) {
                    $this->notifyFailure('Could not buy the label', $e);

                    $action->halt();
                }

                Notification::make()
                    ->title('Label bought')
                    ->body("{$label->carrier} {$label->service} — tracking {$label->trackingCode}. The customer has been emailed.")
                    ->success()
                    ->send();
            });
    }

    protected function printLabelAction(): Action
    {
        return Action::make('print_shipping_label')
            ->label('Print Shipping Label')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->visible(fn (Order $record): bool => filled(ShippingLabel::forOrder($record)?->labelUrl))
            ->url(fn (Order $record): ?string => ShippingLabel::forOrder($record)?->labelUrl)
            ->openUrlInNewTab();
    }

    /**
     * @param  array{carrier: string, service: string, rate: int, delivery_days: int|null}  $rate
     */
    protected function rateLabel(array $rate): string
    {
        $label = "{$rate['carrier']} {$rate['service']} — ".Catalog::money($rate['rate']);

        if ($rate['delivery_days']) {
            $label .= " ({$rate['delivery_days']} day".($rate['delivery_days'] === 1 ? '' : 's').')';
        }

        return $label;
    }

    protected function notifyFailure(string $title, EasyPostException $e): void
    {
        Notification::make()
            ->title($title)
            ->body($e->getMessage())
            ->danger()
            ->persistent()
            ->send();
    }
}
