<?php

namespace Database\Seeders;

use App\Models\SalesType;
use Illuminate\Database\Seeder;

class SalesTypeSeeder extends Seeder
{
    /**
     * Seed sales_types table dari data config lama.
     * Menggunakan upsert agar aman dijalankan berulang.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'regular',       'name' => 'Reguler',        'sort_order' => 0],
            ['code' => 'compliment',    'name' => 'Compliment',     'sort_order' => 1],
            ['code' => 'family',        'name' => 'Family',         'sort_order' => 2],
            ['code' => 'franchise',     'name' => 'Franchise',      'sort_order' => 3],
            ['code' => 'gofood',        'name' => 'GoFood',         'sort_order' => 4],
            ['code' => 'grabfood',      'name' => 'GrabFood',       'sort_order' => 5],
            ['code' => 'hpp',           'name' => 'HPP',            'sort_order' => 6],
            ['code' => 'meal_karyawan', 'name' => 'Meal Karyawan',  'sort_order' => 7],
            ['code' => 'member',        'name' => 'Member',         'sort_order' => 8],
            ['code' => 'reseller',      'name' => 'Reseller',       'sort_order' => 9],
            ['code' => 'shopeefood',    'name' => 'ShopeeFood',     'sort_order' => 10],
        ];

        foreach ($types as $type) {
            SalesType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name'       => $type['name'],
                    'is_active'  => true,
                    'sort_order' => $type['sort_order'],
                ]
            );
        }

        $this->command->info('Sales types seeded: ' . count($types) . ' records.');
    }
}
