<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Seed the application's payment methods.
     */
    public function run(): void
    {
        $methods = [
            ['code' => 'CASH', 'name' => 'Cash'],
            ['code' => 'CREDIT_CARD', 'name' => 'Credit Card'],
            ['code' => 'DEBIT_CARD', 'name' => 'Debit Card'],
            ['code' => 'DEPOSIT', 'name' => 'Deposit'],
            ['code' => 'GO_FOOD', 'name' => 'Go-Food'],
            ['code' => 'GO_PAY', 'name' => 'Go-Pay'],
            ['code' => 'GRAB_FOOD', 'name' => 'Grab-Food'],
            ['code' => 'OVO', 'name' => 'Ovo'],
            ['code' => 'SHOPEE_FOOD', 'name' => 'Shopee-food'],
            ['code' => 'SHOPEE_PAY', 'name' => 'Shopee-Pay'],
            ['code' => 'TRANSFER_BANK', 'name' => 'Transfer Bank'],
        ];

        // Matikan dulu semua metode lama agar pilihan POS hanya mengikuti master saat ini.
        PaymentMethod::query()->update(['is_active' => false]);

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                [
                    'name' => $method['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
