<?php

return [

    'default' => env('PAYMENTS_TYPE', 'cash-in-hand'),

    'types' => [
        'cash-in-hand' => [
            'driver' => 'offline',
            'authorized' => 'payment-offline',
        ],

        /*
         * Card-to-USDC via VERIFIED Crypto Checkout. Authorization happens
         * asynchronously off the relay callback once settlement is confirmed
         * on Polygon, so orders sit at the draft status until then.
         */
        'verified-crypto' => [
            'driver' => 'verified-crypto',
            'authorized' => 'payment-received',
        ],
    ],

];
