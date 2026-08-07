<?php

use App\Media\PeptideMediaDefinitions;
use Lunar\Base\StandardMediaDefinitions;

return [

    'definitions' => [
        'asset' => StandardMediaDefinitions::class,
        'brand' => PeptideMediaDefinitions::class,
        'collection' => PeptideMediaDefinitions::class,
        'product' => PeptideMediaDefinitions::class,
        'product-option' => StandardMediaDefinitions::class,
        'product-option-value' => StandardMediaDefinitions::class,
    ],

    'collection' => 'images',

    'fallback' => [
        'url' => env('FALLBACK_IMAGE_URL', null),
        'path' => env('FALLBACK_IMAGE_PATH', null),
    ],

];
