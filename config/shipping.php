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

];
