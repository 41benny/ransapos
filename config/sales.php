<?php

return [
    // How to behave when a finished_good is sold but has no active BOM:
    // - reduce: reduce the finished_good stock (legacy behavior)
    // - skip: do not mutate stock (useful for made-to-order menus until BOM is configured)
    // - block: reject the sale until a BOM is configured
    'finished_good_without_bom' => env('SALES_FINISHED_GOOD_WITHOUT_BOM', 'reduce'),

    // Level harga untuk channel/type penjualan.
    // Key dipakai sebagai value teknis, label dipakai di UI.
    'price_levels' => [
        'regular' => 'Reguler',
        'compliment' => 'Compliment',
        'family' => 'Family',
        'franchise' => 'Franchise',
        'gofood' => 'GoFood',
        'grabfood' => 'GrabFood',
        'hpp' => 'HPP',
        'meal_karyawan' => 'Meal Karyawan',
        'member' => 'Member',
        'reseller' => 'Reseller',
        'shopeefood' => 'ShopeeFood',
    ],
];
