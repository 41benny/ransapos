<?php

return [
    // Used to separate "Komponen" vs "Produk Utama" on BOM screens when you manage items by category.
    // If the category is not found, the app will fallback to filtering by product_type.
    'raw_material_category_name' => env('BOM_RAW_MATERIAL_CATEGORY_NAME', 'Bahan Baku'),
];

