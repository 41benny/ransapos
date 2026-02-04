<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Operasional', 'code' => 'OPS', 'description' => 'Biaya operasional harian'],
            ['name' => 'Listrik & Air', 'code' => 'UTL', 'description' => 'Biaya utilitas'],
            ['name' => 'Transportasi', 'code' => 'TRS', 'description' => 'Biaya transportasi dan perjalanan'],
            ['name' => 'ATK & Perlengkapan', 'code' => 'ATK', 'description' => 'Alat tulis kantor dan perlengkapan'],
            ['name' => 'Maintenance', 'code' => 'MNT', 'description' => 'Perawatan dan perbaikan'],
            ['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Biaya pemasaran dan promosi'],
            ['name' => 'Gaji & Upah', 'code' => 'SAL', 'description' => 'Gaji karyawan'],
            ['name' => 'Konsumsi', 'code' => 'KNS', 'description' => 'Makan minum dan jamuan'],
            ['name' => 'Pajak & Retribusi', 'code' => 'TAX', 'description' => 'Pajak dan retribusi'],
            ['name' => 'Lain-lain', 'code' => 'OTH', 'description' => 'Pengeluaran lainnya'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::firstOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
