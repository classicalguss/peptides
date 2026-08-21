<?php

/**
 * Powered Up Peptides brand system.
 *
 * Core palette is taken from the logo and hero banner: black base, gold
 * typography, violet/magenta electric accents. Each stack and compound
 * additionally carries its own accent colour, matching the vial artwork.
 */
return [
    'brand' => [
        'gold' => '#FFC000',
        'gold_bright' => '#FFD426',
        'gold_deep' => '#A06000',
        'violet' => '#8B31F5',
        'magenta' => '#D946EF',
    ],

    'accents' => [
        'gold' => ['hex' => '#FFC000', 'glow' => '#A06000'],
        'lime' => ['hex' => '#B4FF2E', 'glow' => '#4F7A0A'],
        'chartreuse' => ['hex' => '#C6FF3D', 'glow' => '#5A7A12'],
        'orange' => ['hex' => '#FF7A1A', 'glow' => '#8A3608'],
        'blue' => ['hex' => '#2F7BFF', 'glow' => '#12336E'],
        'pink' => ['hex' => '#FF2E8A', 'glow' => '#7A0E3E'],
        'violet' => ['hex' => '#A855F7', 'glow' => '#4A1478'],
        'cyan' => ['hex' => '#22D3EE', 'glow' => '#0A5A68'],
    ],

    /*
     * `active` lists the route names that should light the tab up, so a product
     * detail page still highlights the section it belongs to.
     */
    'nav' => [
        ['label' => 'Shop', 'text_key' => 'global.nav_shop', 'route' => 'shop', 'active' => ['shop', 'compound']],
        ['label' => 'Stacks', 'text_key' => 'global.nav_collections', 'route' => 'stacks', 'active' => ['stacks', 'stack']],
        ['label' => 'Lab Reports', 'text_key' => 'global.nav_lab_reports', 'route' => 'lab-reports', 'active' => ['lab-reports']],
        ['label' => 'About Us', 'text_key' => 'global.nav_about', 'route' => 'about', 'active' => ['about']],
        ['label' => 'Contact', 'text_key' => 'global.nav_contact', 'route' => 'contact', 'active' => ['contact', 'contact.store']],
    ],
];
