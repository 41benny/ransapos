<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
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
            ['code' => 'regular',       'name' => 'Reguler',        'sort_order' => 0,  'channel_type' => 'offline'],
            ['code' => 'compliment',    'name' => 'Compliment',     'sort_order' => 1,  'channel_type' => 'offline'],
            ['code' => 'family',        'name' => 'Family',         'sort_order' => 2,  'channel_type' => 'offline'],
            ['code' => 'franchise',     'name' => 'Franchise',      'sort_order' => 3,  'channel_type' => 'offline'],
            ['code' => 'gofood',        'name' => 'GoFood',         'sort_order' => 4,  'channel_type' => 'online'],
            ['code' => 'grabfood',      'name' => 'GrabFood',       'sort_order' => 5,  'channel_type' => 'online'],
            ['code' => 'hpp',           'name' => 'HPP',            'sort_order' => 6,  'channel_type' => 'offline'],
            ['code' => 'meal_karyawan', 'name' => 'Meal Karyawan',  'sort_order' => 7,  'channel_type' => 'offline'],
            ['code' => 'member',        'name' => 'Member',         'sort_order' => 8,  'channel_type' => 'offline'],
            ['code' => 'reseller',      'name' => 'Reseller',       'sort_order' => 9,  'channel_type' => 'offline'],
            ['code' => 'shopeefood',    'name' => 'ShopeeFood',     'sort_order' => 10, 'channel_type' => 'online'],
        ];

        // Pemetaan default metode bayar per sales_type -> KODE metode bayar (cocok persis).
        // Produksi sudah punya metode bayar khusus: GO_FOOD, GRAB_FOOD, SHOPEE_FOOD.
        // Cocokkan via kode agar tidak salah ambil (mis. SHOPEE_FOOD vs SHOPEE_PAY).
        $salesTypeToPaymentCode = [
            'gofood'     => 'GO_FOOD',
            'grabfood'   => 'GRAB_FOOD',
            'shopeefood' => 'SHOPEE_FOOD',
        ];

        $paymentIdByCode = PaymentMethod::query()
            ->whereIn('code', array_values($salesTypeToPaymentCode))
            ->pluck('id', 'code')
            ->all();

        $defaultPaymentByCode = [];
        foreach ($salesTypeToPaymentCode as $salesTypeCode => $paymentCode) {
            if (!empty($paymentIdByCode[$paymentCode])) {
                $defaultPaymentByCode[$salesTypeCode] = $paymentIdByCode[$paymentCode];
            }
        }

        foreach ($types as $type) {
            $attributes = [
                'name'         => $type['name'],
                'channel_type' => $type['channel_type'],
                'is_active'    => true,
                'sort_order'   => $type['sort_order'],
            ];

            // Hanya set default payment jika ditemukan, jangan menimpa konfigurasi admin dengan null.
            if (!empty($defaultPaymentByCode[$type['code']])) {
                $attributes['default_payment_method_id'] = $defaultPaymentByCode[$type['code']];
            }

            SalesType::updateOrCreate(
                ['code' => $type['code']],
                $attributes
            );
        }

        $this->command->info('Sales types seeded: ' . count($types) . ' records.');
    }
}
