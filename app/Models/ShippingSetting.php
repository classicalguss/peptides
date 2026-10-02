<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The store's ship-from address and default parcel, edited in the admin.
 *
 * There is only ever one row. Anything left blank falls back to the
 * EASYPOST_* values in config/easypost.php.
 */
class ShippingSetting extends Model
{
    protected $fillable = [
        'from_name',
        'from_company',
        'from_street1',
        'from_street2',
        'from_city',
        'from_state',
        'from_zip',
        'from_phone',
        'parcel_weight_oz',
        'parcel_length',
        'parcel_width',
        'parcel_height',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? new self;
    }

    /**
     * The sender address in EasyPost's field names, blanks removed.
     *
     * @return array<string, string>
     */
    public function fromAddress(): array
    {
        $address = (array) config('easypost.from');

        foreach (['name', 'company', 'street1', 'street2', 'city', 'state', 'zip', 'phone'] as $field) {
            if (filled($this->{"from_{$field}"})) {
                $address[$field] = $this->{"from_{$field}"};
            }
        }

        return array_filter($address, fn ($value) => filled($value));
    }

    /**
     * Default parcel: weight in ounces, dimensions in inches.
     *
     * @return array{weight_oz: float, length: float, width: float, height: float}
     */
    public function parcel(): array
    {
        return [
            'weight_oz' => (float) ($this->parcel_weight_oz ?? config('easypost.parcel.weight_oz')),
            'length' => (float) ($this->parcel_length ?? config('easypost.parcel.length')),
            'width' => (float) ($this->parcel_width ?? config('easypost.parcel.width')),
            'height' => (float) ($this->parcel_height ?? config('easypost.parcel.height')),
        ];
    }
}
