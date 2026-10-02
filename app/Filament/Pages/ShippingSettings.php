<?php

namespace App\Filament\Pages;

use App\Models\ShippingSetting;
use App\Shipping\EasyPostClient;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Where staff set the address labels ship from and the usual box size.
 * The EasyPost API key is a secret and stays in the environment file.
 */
class ShippingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Shipping Settings';

    protected static ?string $title = 'Shipping Settings';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.shipping-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = ShippingSetting::current();
        $from = $settings->fromAddress();
        $parcel = $settings->parcel();

        $this->form->fill([
            'from_name' => $from['name'] ?? null,
            'from_company' => $from['company'] ?? null,
            'from_street1' => $from['street1'] ?? null,
            'from_street2' => $from['street2'] ?? null,
            'from_city' => $from['city'] ?? null,
            'from_state' => $from['state'] ?? null,
            'from_zip' => $from['zip'] ?? null,
            'from_phone' => $from['phone'] ?? null,
            'parcel_weight_oz' => $parcel['weight_oz'],
            'parcel_length' => $parcel['length'],
            'parcel_width' => $parcel['width'],
            'parcel_height' => $parcel['height'],
        ]);
    }

    public function getSubheading(): ?string
    {
        $easyPost = app(EasyPostClient::class);

        return match (true) {
            ! $easyPost->isConfigured() => 'EasyPost is not connected yet — no API key is set on the server.',
            $easyPost->isTestMode() => 'EasyPost is connected in TEST mode: labels are samples and nothing is charged.',
            default => 'EasyPost is connected in LIVE mode: every label bought is charged.',
        };
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ship-from address')
                    ->description('Where parcels leave from. Printed on every label as the return address and used to price postage.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('from_name')->label('Contact name')->maxLength(255),
                        Forms\Components\TextInput::make('from_company')->label('Company')->maxLength(255),
                        Forms\Components\TextInput::make('from_street1')->label('Street address')->required()->maxLength(255),
                        Forms\Components\TextInput::make('from_street2')->label('Suite / unit')->maxLength(255),
                        Forms\Components\TextInput::make('from_city')->label('City')->required()->maxLength(255),
                        Forms\Components\Select::make('from_state')->label('State')->options(config('shipping.states'))->in(array_keys(config('shipping.states')))->searchable()->required(),
                        Forms\Components\TextInput::make('from_zip')->label('ZIP code')->required()->maxLength(10),
                        Forms\Components\TextInput::make('from_phone')->label('Phone')->helperText('USPS requires a sender phone number.')->tel()->required()->maxLength(20),
                    ]),
                Forms\Components\Section::make('Usual package')
                    ->description('Pre-filled when buying a label. It can be changed for each order.')
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('parcel_weight_oz')->label('Weight')->suffix('oz')->numeric()->minValue(0.1)->required(),
                        Forms\Components\TextInput::make('parcel_length')->label('Length')->suffix('in')->numeric()->minValue(0.1)->required(),
                        Forms\Components\TextInput::make('parcel_width')->label('Width')->suffix('in')->numeric()->minValue(0.1)->required(),
                        Forms\Components\TextInput::make('parcel_height')->label('Height')->suffix('in')->numeric()->minValue(0.1)->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        ShippingSetting::current()->fill($data)->save();

        Notification::make()
            ->title('Shipping settings saved')
            ->success()
            ->send();
    }
}
