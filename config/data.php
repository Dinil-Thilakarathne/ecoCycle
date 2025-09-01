<?php

/**
 * Application data constants
 *
 * Central location for static application data/constants used across
 * the dashboard and other parts of the system.
 */

return [
    /* Minimum bid values (Rs) per material/category */
    'minimum_bids' => [
        'plastic' => 1200,
        'paper' => 950,
        'metal' => 1400,
        'glass' => 750,
        'organic' => 750,
    ],

    /* Material color map (hex) */
    'material_colors' => [
        'plastic' => '#0000ff',
        'paper' => '#008000',
        'metal' => '#ffa500',
        'glass' => '#008000',
        'organic' => '#8B5A2B',
    ],
    'wasteCategories' => [
        ['category' => 'Plastic', 'volume' => 2500, 'percentage' => 35],
        ['category' => 'Paper', 'volume' => 1800, 'percentage' => 25],
        ['category' => 'Glass', 'volume' => 1200, 'percentage' => 17],
        ['category' => 'Metal', 'volume' => 900, 'percentage' => 13],
        ['category' => 'Cardboard', 'volume' => 700, 'percentage' => 10],
    ],
];
