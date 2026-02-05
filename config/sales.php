<?php

return [
    // How to behave when a finished_good is sold but has no active BOM:
    // - reduce: reduce the finished_good stock (legacy behavior)
    // - skip: do not mutate stock (useful for made-to-order menus until BOM is configured)
    // - block: reject the sale until a BOM is configured
    'finished_good_without_bom' => env('SALES_FINISHED_GOOD_WITHOUT_BOM', 'reduce'),
];

