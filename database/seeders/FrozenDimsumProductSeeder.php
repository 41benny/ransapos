<?php

namespace Database\Seeders;

use App\Models\BomDetail;
use App\Models\BomHeader;
use App\Models\Product;
use App\Support\ProductSkuGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FrozenDimsumProductSeeder extends Seeder
{
    /**
     * Seed and repair 15 frozen dimsum products.
     *
     * This seeder is intentionally idempotent. Existing frozen products are
     * updated with calculated HPP and both production/bundle BOM records.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Frozen Siawmay Ayam',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000217',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Udang',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000224',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Dumpling',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000138',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Kekian',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000153',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Ekkado',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000140',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Lumpia Udang',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000167',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Monster Wortel',
                'desc' => 'Pak isi 6 pcs',
                'component_sku' => '000222',
                'qty' => 6,
            ],
            [
                'name' => 'Frozen Monster Cheese',
                'desc' => 'Pak isi 6 pcs',
                'component_sku' => '000179',
                'qty' => 6,
            ],
            [
                'name' => 'Frozen Pangsit Shanghai',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000187',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Ayam Udang',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000218',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Beef',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000219',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Kepiting',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000221',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Nori',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000223',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Sweekiaw',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000233',
                'qty' => 30,
            ],
            [
                'name' => 'Frozen Kucai',
                'desc' => 'Pak isi 30 pcs',
                'component_sku' => '000234',
                'qty' => 30,
            ],
        ];

        $gudangOutletId = 1;
        $created = 0;
        $updated = 0;
        $bomsSynced = 0;

        DB::beginTransaction();

        try {
            foreach ($products as $item) {
                $component = Product::where('sku', $item['component_sku'])->firstOrFail();
                $hpp = round((float) $component->purchase_price * (float) $item['qty'], 2);

                $priceLevels = [
                    'regular' => 90000,
                    'franchise' => 80000,
                    'reseller' => 90000,
                    'MEMBER_1' => 75000,
                    'MEMBER_2' => 85000,
                    'family' => 60000,
                    'hpp' => $hpp,
                ];

                $product = Product::where('name', $item['name'])->first();

                if ($product) {
                    $product->update([
                        'product_type' => 'finished_good',
                        'is_sellable' => true,
                        'is_pos_available' => true,
                        'is_online_order_available' => false,
                        'is_available_all_outlets' => false,
                        'is_available_all_users' => true,
                        'pos_outlet_ids' => [$gudangOutletId],
                        'pos_user_ids' => null,
                        'category_id' => 7,
                        'description' => $item['desc'],
                        'unit' => 'pak',
                        'purchase_price' => $hpp,
                        'selling_price' => 90000,
                        'price_levels' => $priceLevels,
                        'min_stock' => 0,
                        'is_active' => true,
                    ]);
                    $updated++;
                } else {
                    $product = Product::create([
                        'sku' => ProductSkuGenerator::generate($item['name'], false),
                        'name' => $item['name'],
                        'product_type' => 'finished_good',
                        'is_sellable' => true,
                        'is_pos_available' => true,
                        'is_online_order_available' => false,
                        'is_available_all_outlets' => false,
                        'is_available_all_users' => true,
                        'pos_outlet_ids' => [$gudangOutletId],
                        'pos_user_ids' => null,
                        'category_id' => 7,
                        'description' => $item['desc'],
                        'unit' => 'pak',
                        'purchase_price' => $hpp,
                        'selling_price' => 90000,
                        'price_levels' => $priceLevels,
                        'min_stock' => 0,
                        'is_active' => true,
                        'created_by' => Auth::id() ?? 1,
                    ]);
                    $created++;
                }

                foreach (['production', 'bundle'] as $sourceType) {
                    $bom = BomHeader::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'source_type' => $sourceType,
                        ],
                        [
                            'name' => 'Resep ' . $product->name,
                            'is_active' => true,
                            'notes' => $item['desc'],
                        ]
                    );

                    $bom->details()->delete();
                    BomDetail::create([
                        'bom_id' => $bom->id,
                        'component_product_id' => $component->id,
                        'quantity' => $item['qty'],
                        'uom' => $component->unit,
                    ]);
                    $bomsSynced++;
                }

                $this->command->info(sprintf(
                    'OK: %s | HPP %s | komponen %s x %s',
                    $product->name,
                    number_format($hpp, 2, '.', ''),
                    $component->name,
                    $item['qty']
                ));
            }

            DB::commit();

            $this->command->newLine();
            $this->command->info('=== Selesai ===');
            $this->command->info("Dibuat: {$created} produk");
            $this->command->info("Diupdate: {$updated} produk");
            $this->command->info("BOM disinkronkan: {$bomsSynced}");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error('GAGAL: ' . $e->getMessage());
            throw $e;
        }
    }
}
