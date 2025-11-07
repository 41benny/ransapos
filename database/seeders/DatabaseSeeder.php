<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Outlet;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Models\Stock;
use App\Models\CashSession;
use App\Models\CashAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Full access to system',
        ]);

        $kasirRole = Role::create([
            'name' => 'kasir',
            'display_name' => 'Kasir',
            'description' => 'POS operator',
        ]);

        $managerRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Store manager',
        ]);

        // 2. Outlets
        $outlet1 = Outlet::create([
            'code' => 'OUT001',
            'name' => 'Cabang Utama',
            'address' => 'Jl. Sudirman No. 123, Jakarta',
            'phone' => '021-12345678',
            'email' => 'utama@morest.com',
            'is_active' => true,
        ]);

        $outlet2 = Outlet::create([
            'code' => 'OUT002',
            'name' => 'Cabang Plaza',
            'address' => 'Plaza Senayan Lt. 2, Jakarta',
            'phone' => '021-87654321',
            'email' => 'plaza@morest.com',
            'is_active' => true,
        ]);

        // 3. Users
        $admin = User::create([
            'name' => 'Admin System',
            'email' => 'admin@morest.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'outlet_id' => $outlet1->id,
            'is_active' => true,
        ]);

        $kasir = User::create([
            'name' => 'Kasir 1',
            'email' => 'kasir@morest.com',
            'password' => Hash::make('password'),
            'role_id' => $kasirRole->id,
            'outlet_id' => $outlet1->id,
            'is_active' => true,
        ]);

        // 4. Product Categories
        $categoryBeverage = ProductCategory::create([
            'code' => 'BEV',
            'name' => 'Minuman',
            'description' => 'Berbagai jenis minuman',
            'is_active' => true,
        ]);

        $categoryFood = ProductCategory::create([
            'code' => 'FOOD',
            'name' => 'Makanan',
            'description' => 'Berbagai jenis makanan',
            'is_active' => true,
        ]);

        $categorySnack = ProductCategory::create([
            'code' => 'SNACK',
            'name' => 'Snack',
            'description' => 'Camilan dan snack',
            'is_active' => true,
        ]);

        // 5. Suppliers
        $supplier1 = Supplier::create([
            'code' => 'SUP001',
            'name' => 'PT Kopi Nusantara',
            'contact_person' => 'Budi Santoso',
            'phone' => '021-11111111',
            'email' => 'budi@kopinusantara.com',
            'address' => 'Jl. Gatot Subroto No. 45, Jakarta',
            'is_active' => true,
        ]);

        // 6. Products
        $products = [
            [
                'sku' => 'BEV001',
                'name' => 'Espresso',
                'category_id' => $categoryBeverage->id,
                'description' => 'Espresso single shot',
                'unit' => 'cup',
                'purchase_price' => 8000,
                'selling_price' => 15000,
                'min_stock' => 10,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'sku' => 'BEV002',
                'name' => 'Cappuccino',
                'category_id' => $categoryBeverage->id,
                'description' => 'Cappuccino with milk foam',
                'unit' => 'cup',
                'purchase_price' => 10000,
                'selling_price' => 20000,
                'min_stock' => 10,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'sku' => 'BEV003',
                'name' => 'Latte',
                'category_id' => $categoryBeverage->id,
                'description' => 'Caffe latte',
                'unit' => 'cup',
                'purchase_price' => 10000,
                'selling_price' => 22000,
                'min_stock' => 10,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'sku' => 'BEV004',
                'name' => 'Americano',
                'category_id' => $categoryBeverage->id,
                'description' => 'Americano coffee',
                'unit' => 'cup',
                'purchase_price' => 8000,
                'selling_price' => 18000,
                'min_stock' => 10,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'sku' => 'FOOD001',
                'name' => 'Croissant',
                'category_id' => $categoryFood->id,
                'description' => 'French croissant',
                'unit' => 'pcs',
                'purchase_price' => 12000,
                'selling_price' => 25000,
                'min_stock' => 5,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'sku' => 'FOOD002',
                'name' => 'Sandwich',
                'category_id' => $categoryFood->id,
                'description' => 'Club sandwich',
                'unit' => 'pcs',
                'purchase_price' => 15000,
                'selling_price' => 30000,
                'min_stock' => 5,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'sku' => 'SNACK001',
                'name' => 'Cookies',
                'category_id' => $categorySnack->id,
                'description' => 'Chocolate chip cookies',
                'unit' => 'pack',
                'purchase_price' => 8000,
                'selling_price' => 15000,
                'min_stock' => 20,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'sku' => 'SNACK002',
                'name' => 'Brownies',
                'category_id' => $categorySnack->id,
                'description' => 'Chocolate brownies',
                'unit' => 'pcs',
                'purchase_price' => 10000,
                'selling_price' => 20000,
                'min_stock' => 10,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::create($productData);

            // Buat stok awal untuk setiap produk di outlet 1
            Stock::create([
                'product_id' => $product->id,
                'outlet_id' => $outlet1->id,
                'quantity' => 100, // Stok awal
                'last_mutation_at' => now(),
            ]);

            // Buat stok awal untuk setiap produk di outlet 2
            Stock::create([
                'product_id' => $product->id,
                'outlet_id' => $outlet2->id,
                'quantity' => 50, // Stok awal
                'last_mutation_at' => now(),
            ]);
        }

        // 7. Payment Methods
        PaymentMethod::create([
            'code' => 'CASH',
            'name' => 'Cash / Tunai',
            'is_active' => true,
        ]);

        PaymentMethod::create([
            'code' => 'QRIS',
            'name' => 'QRIS',
            'is_active' => true,
        ]);

        PaymentMethod::create([
            'code' => 'TRANSFER',
            'name' => 'Transfer Bank',
            'is_active' => true,
        ]);

        PaymentMethod::create([
            'code' => 'DEBIT',
            'name' => 'Kartu Debit',
            'is_active' => true,
        ]);

        PaymentMethod::create([
            'code' => 'CREDIT',
            'name' => 'Kartu Kredit',
            'is_active' => true,
        ]);

        // 8. Cash Accounts (Akun Kas & Bank)
        CashAccount::create([
            'name' => 'Kas Toko Utama',
            'code' => 'KAS-001',
            'type' => 'cash',
            'is_active' => true,
            'opening_balance' => 5000000,
            'current_balance' => 5000000,
            'notes' => 'Kas toko cabang utama',
            'created_by' => $admin->id,
        ]);

        CashAccount::create([
            'name' => 'Kas Toko Plaza',
            'code' => 'KAS-002',
            'type' => 'cash',
            'is_active' => true,
            'opening_balance' => 3000000,
            'current_balance' => 3000000,
            'notes' => 'Kas toko cabang plaza',
            'created_by' => $admin->id,
        ]);

        CashAccount::create([
            'name' => 'Bank BCA - Rekening Operasional',
            'code' => 'BANK-BCA',
            'type' => 'bank',
            'is_active' => true,
            'opening_balance' => 50000000,
            'current_balance' => 50000000,
            'notes' => 'Rekening BCA untuk operasional perusahaan',
            'created_by' => $admin->id,
        ]);

        CashAccount::create([
            'name' => 'Bank BRI - Rekening Payroll',
            'code' => 'BANK-BRI',
            'type' => 'bank',
            'is_active' => true,
            'opening_balance' => 30000000,
            'current_balance' => 30000000,
            'notes' => 'Rekening BRI untuk payroll karyawan',
            'created_by' => $admin->id,
        ]);

        CashAccount::create([
            'name' => 'Bank Mandiri - Rekening Investasi',
            'code' => 'BANK-MANDIRI',
            'type' => 'bank',
            'is_active' => true,
            'opening_balance' => 100000000,
            'current_balance' => 100000000,
            'notes' => 'Rekening Mandiri untuk dana investasi',
            'created_by' => $admin->id,
        ]);

        // 9. Cash Session (untuk testing)
        CashSession::create([
            'session_number' => 'CS-' . now()->format('Ymd') . '-001',
            'outlet_id' => $outlet1->id,
            'user_id' => $kasir->id,
            'opening_balance' => 500000,
            'expected_balance' => 500000,
            'actual_balance' => null,
            'difference' => 0,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => now(),
            'closed_at' => null,
            'notes' => 'Shift pagi',
            'status' => 'open',
        ]);

        $this->command->info('✅ Data dummy berhasil dibuat!');
        $this->command->info('👤 Admin: admin@morest.com / password');
        $this->command->info('👤 Kasir: kasir@morest.com / password');
    }
}
