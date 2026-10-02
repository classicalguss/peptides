<?php

/*
|--------------------------------------------------------------------------
| Flat Rate Shipping
|--------------------------------------------------------------------------
|
| All values are in integer minor units (cents), matching how Lunar stores
| money. Env overrides exist so rates can be changed without a deploy —
| setting the free-shipping threshold to 0 makes every order ship free,
| which is how a low-value live payment test is run without paying $12 of
| shipping on a $2 order.
|
*/

return [

    'free_threshold' => (int) env('SHIPPING_FREE_THRESHOLD', 20000),

    'standard_rate' => (int) env('SHIPPING_STANDARD_RATE', 1200),

    'express_rate' => (int) env('SHIPPING_EXPRESS_RATE', 2500),

    /*
    |--------------------------------------------------------------------------
    | Shipping Destinations
    |--------------------------------------------------------------------------
    |
    | Orders ship within the United States only. Checkout fixes the country to
    | the one below and only accepts these states, stored by their two-letter
    | code — which is also what the carriers expect on a label.
    |
    */

    'country' => 'US',

    'states' => [
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'DC' => 'District of Columbia',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    ],

];
