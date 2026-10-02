<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | EasyPost issues two keys per account. A test key (prefix "EZTK") returns
    | real rates but its labels are free, watermarked samples that no carrier
    | will accept. A production key (prefix "EZAK") charges the account for
    | every label bought. With no key set the label actions are hidden from
    | the admin panel entirely, so the code is safe to deploy before the key.
    |
    */
    'api_key' => env('EASYPOST_API_KEY'),

    'api_base' => env('EASYPOST_API_BASE', 'https://api.easypost.com/v2'),

    'timeout' => (int) env('EASYPOST_TIMEOUT', 20),

    /*
    |--------------------------------------------------------------------------
    | Ship-From Address
    |--------------------------------------------------------------------------
    |
    | Printed on every label as the return address and used by the carrier to
    | price the shipment. USPS requires a phone number on the sender.
    |
    */
    'from' => [
        'name' => env('EASYPOST_FROM_NAME'),
        'company' => env('EASYPOST_FROM_COMPANY', env('APP_NAME')),
        'street1' => env('EASYPOST_FROM_STREET1'),
        'street2' => env('EASYPOST_FROM_STREET2'),
        'city' => env('EASYPOST_FROM_CITY'),
        'state' => env('EASYPOST_FROM_STATE'),
        'zip' => env('EASYPOST_FROM_ZIP'),
        'country' => env('EASYPOST_FROM_COUNTRY', 'US'),
        'phone' => env('EASYPOST_FROM_PHONE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Parcel
    |--------------------------------------------------------------------------
    |
    | Pre-fills the package step when buying a label; staff can change it per
    | order. Weight is in ounces and dimensions in inches, as EasyPost expects.
    |
    */
    'parcel' => [
        'weight_oz' => (float) env('EASYPOST_PARCEL_WEIGHT_OZ', 8),
        'length' => (float) env('EASYPOST_PARCEL_LENGTH', 6),
        'width' => (float) env('EASYPOST_PARCEL_WIDTH', 4),
        'height' => (float) env('EASYPOST_PARCEL_HEIGHT', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Label Format
    |--------------------------------------------------------------------------
    |
    | PDF prints on any printer; use ZPL for a thermal label printer.
    |
    */
    'label_format' => env('EASYPOST_LABEL_FORMAT', 'PDF'),

];
