<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SteakMenuDummySeeder extends Seeder
{
    public function run(): void
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('Ekstensi PHP GD dibutuhkan untuk membuat thumbnail menu dummy.');
        }

        $creatorId = User::query()->orderBy('id')->value('id');
        $categories = $this->categories();
        $menus = $this->menus();
        $outletIds = Outlet::query()->where('is_active', true)->pluck('id');

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($categories, $menus, $outletIds, $creatorId, &$created, &$updated) {
            $categoryIds = [];

            foreach ($categories as $category) {
                $model = ProductCategory::query()->updateOrCreate(
                    ['code' => $category['code']],
                    [
                        'name' => $category['name'],
                        'description' => $category['description'],
                        'is_active' => true,
                    ]
                );

                $categoryIds[$category['code']] = $model->id;
            }

            foreach ($menus as $menu) {
                [$imagePath, $thumbnailPath] = $this->ensureMenuImages($menu);
                $priceLevels = $this->priceLevels((int) $menu['price'], (int) $menu['hpp']);

                $product = Product::query()->where('sku', $menu['sku'])->first();
                $payload = [
                    'name' => $menu['name'],
                    'category_id' => $categoryIds[$menu['category_code']],
                    'description' => $menu['description'],
                    'image_path' => $imagePath,
                    'thumbnail_path' => $thumbnailPath,
                    'unit' => 'porsi',
                    'product_type' => 'finished_good',
                    'is_sellable' => true,
                    'is_pos_available' => true,
                    'is_online_order_available' => true,
                    'is_available_all_outlets' => true,
                    'is_available_all_users' => true,
                    'pos_outlet_ids' => null,
                    'pos_user_ids' => null,
                    'purchase_price' => $menu['hpp'],
                    'selling_price' => $menu['price'],
                    'price_levels' => $priceLevels,
                    'min_stock' => 5,
                    'is_active' => true,
                ];

                if ($product) {
                    $product->update($payload);
                    $updated++;
                } else {
                    $product = Product::query()->create($payload + [
                        'sku' => $menu['sku'],
                        'created_by' => $creatorId,
                    ]);
                    $created++;
                }

                foreach ($outletIds as $outletId) {
                    Stock::query()->updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'outlet_id' => $outletId,
                        ],
                        [
                            'quantity' => $menu['stock'],
                            'last_mutation_at' => now(),
                        ]
                    );
                }
            }
        });

        $this->command?->info("Steak dummy menu selesai. Dibuat: {$created}, diperbarui: {$updated}.");
    }

    private function categories(): array
    {
        return [
            [
                'code' => 'STEAK',
                'name' => 'Steak',
                'description' => 'Menu utama steak dan grill khas Dom Steak.',
            ],
            [
                'code' => 'GRILL',
                'name' => 'Chicken & Grill',
                'description' => 'Pilihan ayam, sosis, dan menu grill non-steak.',
            ],
            [
                'code' => 'RICEPASTA',
                'name' => 'Rice & Pasta',
                'description' => 'Menu nasi dan pasta untuk pendamping steak.',
            ],
            [
                'code' => 'SIDES',
                'name' => 'Sides',
                'description' => 'Kentang, salad, dan camilan pendamping.',
            ],
            [
                'code' => 'DRINKS',
                'name' => 'Minuman',
                'description' => 'Minuman dingin dan hangat.',
            ],
        ];
    }

    private function menus(): array
    {
        return [
            ['sku' => 'DS-STK-001', 'name' => 'Sirloin Steak Original', 'category_code' => 'STEAK', 'description' => 'Sirloin grill dengan brown sauce, potato wedges, dan sayuran.', 'price' => 78000, 'hpp' => 42000, 'stock' => 80, 'theme' => 'beef'],
            ['sku' => 'DS-STK-002', 'name' => 'Tenderloin Steak', 'category_code' => 'STEAK', 'description' => 'Tenderloin lembut dengan mushroom sauce dan mashed potato.', 'price' => 98000, 'hpp' => 56000, 'stock' => 60, 'theme' => 'beef'],
            ['sku' => 'DS-STK-003', 'name' => 'Rib Eye Steak', 'category_code' => 'STEAK', 'description' => 'Rib eye juicy dengan blackpepper sauce dan butter corn.', 'price' => 118000, 'hpp' => 70000, 'stock' => 45, 'theme' => 'beef'],
            ['sku' => 'DS-STK-004', 'name' => 'Wagyu Meltique Steak', 'category_code' => 'STEAK', 'description' => 'Wagyu meltique grill medium dengan garlic butter.', 'price' => 135000, 'hpp' => 82000, 'stock' => 35, 'theme' => 'beef'],
            ['sku' => 'DS-STK-005', 'name' => 'Cheese Steak Platter', 'category_code' => 'STEAK', 'description' => 'Steak sapi dengan saus keju creamy dan french fries.', 'price' => 89000, 'hpp' => 50000, 'stock' => 55, 'theme' => 'cheese'],
            ['sku' => 'DS-STK-006', 'name' => 'Blackpepper Beef Steak', 'category_code' => 'STEAK', 'description' => 'Steak sapi saus lada hitam dengan coleslaw.', 'price' => 86000, 'hpp' => 47000, 'stock' => 65, 'theme' => 'pepper'],
            ['sku' => 'DS-STK-007', 'name' => 'Double Beef Steak', 'category_code' => 'STEAK', 'description' => 'Dua potong steak sapi untuk porsi besar.', 'price' => 125000, 'hpp' => 76000, 'stock' => 40, 'theme' => 'beef'],
            ['sku' => 'DS-STK-008', 'name' => 'Kids Mini Steak', 'category_code' => 'STEAK', 'description' => 'Steak mini dengan fries dan saus ringan untuk anak.', 'price' => 52000, 'hpp' => 28000, 'stock' => 75, 'theme' => 'beef'],
            ['sku' => 'DS-GRL-001', 'name' => 'Chicken Steak Crispy', 'category_code' => 'GRILL', 'description' => 'Ayam crispy dengan brown sauce, fries, dan sayuran.', 'price' => 48000, 'hpp' => 24000, 'stock' => 100, 'theme' => 'chicken'],
            ['sku' => 'DS-GRL-002', 'name' => 'Chicken Steak Grill', 'category_code' => 'GRILL', 'description' => 'Dada ayam grill dengan barbeque sauce.', 'price' => 54000, 'hpp' => 29000, 'stock' => 90, 'theme' => 'chicken'],
            ['sku' => 'DS-GRL-003', 'name' => 'Fish & Chips Steak Sauce', 'category_code' => 'GRILL', 'description' => 'Fillet ikan crispy dengan tartar dan saus steak.', 'price' => 57000, 'hpp' => 31000, 'stock' => 70, 'theme' => 'fish'],
            ['sku' => 'DS-GRL-004', 'name' => 'Sausage Grill Platter', 'category_code' => 'GRILL', 'description' => 'Sosis grill, fries, corn, dan barbeque sauce.', 'price' => 45000, 'hpp' => 22000, 'stock' => 85, 'theme' => 'sausage'],
            ['sku' => 'DS-RP-001', 'name' => 'Beef Steak Rice Bowl', 'category_code' => 'RICEPASTA', 'description' => 'Nasi dengan irisan beef steak, egg, dan sauce.', 'price' => 52000, 'hpp' => 27000, 'stock' => 85, 'theme' => 'rice'],
            ['sku' => 'DS-RP-002', 'name' => 'Chicken Mushroom Rice', 'category_code' => 'RICEPASTA', 'description' => 'Nasi ayam mushroom sauce dengan sayuran.', 'price' => 43000, 'hpp' => 21000, 'stock' => 90, 'theme' => 'rice'],
            ['sku' => 'DS-RP-003', 'name' => 'Spaghetti Bolognese', 'category_code' => 'RICEPASTA', 'description' => 'Spaghetti saus daging tomat dan parmesan.', 'price' => 46000, 'hpp' => 23000, 'stock' => 80, 'theme' => 'pasta'],
            ['sku' => 'DS-RP-004', 'name' => 'Creamy Carbonara', 'category_code' => 'RICEPASTA', 'description' => 'Pasta creamy dengan smoked beef dan parmesan.', 'price' => 49000, 'hpp' => 25000, 'stock' => 75, 'theme' => 'pasta'],
            ['sku' => 'DS-SID-001', 'name' => 'French Fries', 'category_code' => 'SIDES', 'description' => 'Kentang goreng renyah dengan saus pilihan.', 'price' => 25000, 'hpp' => 10000, 'stock' => 150, 'theme' => 'fries'],
            ['sku' => 'DS-SID-002', 'name' => 'Mashed Potato', 'category_code' => 'SIDES', 'description' => 'Mashed potato lembut dengan gravy.', 'price' => 22000, 'hpp' => 9000, 'stock' => 120, 'theme' => 'potato'],
            ['sku' => 'DS-SID-003', 'name' => 'Garden Salad', 'category_code' => 'SIDES', 'description' => 'Salad segar dengan dressing wijen.', 'price' => 28000, 'hpp' => 12000, 'stock' => 90, 'theme' => 'salad'],
            ['sku' => 'DS-SID-004', 'name' => 'Garlic Bread', 'category_code' => 'SIDES', 'description' => 'Roti panggang garlic butter.', 'price' => 20000, 'hpp' => 8000, 'stock' => 120, 'theme' => 'bread'],
            ['sku' => 'DS-DRK-001', 'name' => 'Iced Lemon Tea', 'category_code' => 'DRINKS', 'description' => 'Teh lemon dingin segar.', 'price' => 18000, 'hpp' => 6000, 'stock' => 150, 'theme' => 'tea'],
            ['sku' => 'DS-DRK-002', 'name' => 'Lychee Tea', 'category_code' => 'DRINKS', 'description' => 'Teh leci dingin dengan buah leci.', 'price' => 22000, 'hpp' => 8000, 'stock' => 130, 'theme' => 'tea'],
            ['sku' => 'DS-DRK-003', 'name' => 'Chocolate Milkshake', 'category_code' => 'DRINKS', 'description' => 'Milkshake cokelat creamy.', 'price' => 30000, 'hpp' => 13000, 'stock' => 100, 'theme' => 'shake'],
            ['sku' => 'DS-DRK-004', 'name' => 'Mineral Water', 'category_code' => 'DRINKS', 'description' => 'Air mineral botol.', 'price' => 8000, 'hpp' => 3000, 'stock' => 200, 'theme' => 'water'],
        ];
    }

    private function priceLevels(int $regular, int $hpp): array
    {
        return [
            'regular' => ['default' => $regular, 'outlets' => []],
            'compliment' => ['default' => 0, 'outlets' => []],
            'family' => ['default' => (int) round($regular * 0.9), 'outlets' => []],
            'franchise' => ['default' => (int) round($regular * 0.85), 'outlets' => []],
            'gofood' => ['default' => (int) round($regular * 1.15), 'outlets' => []],
            'grabfood' => ['default' => (int) round($regular * 1.15), 'outlets' => []],
            'hpp' => ['default' => $hpp, 'outlets' => []],
            'meal_karyawan' => ['default' => (int) round($regular * 0.5), 'outlets' => []],
            'member' => ['default' => (int) round($regular * 0.95), 'outlets' => []],
            'reseller' => ['default' => (int) round($regular * 0.8), 'outlets' => []],
            'shopeefood' => ['default' => (int) round($regular * 1.15), 'outlets' => []],
        ];
    }

    private function ensureMenuImages(array $menu): array
    {
        $slug = Str::slug($menu['sku'] . '-' . $menu['name']);
        $imagePath = "products/dummy-steak/{$slug}.jpg";
        $thumbnailPath = "products/thumbnails/dummy-steak/{$slug}_thumb.jpg";

        $disk = Storage::disk('public');
        $this->makeImage($disk->path($imagePath), 640, $menu);
        $this->makeImage($disk->path($thumbnailPath), 320, $menu);

        return [$imagePath, $thumbnailPath];
    }

    private function makeImage(string $path, int $size, array $menu): void
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $img = imagecreatetruecolor($size, $size);
        imageantialias($img, true);

        $bg = $this->allocate($img, [250, 247, 241]);
        $plate = $this->allocate($img, [255, 255, 255]);
        $plateShadow = $this->allocate($img, [214, 206, 194]);
        $text = $this->allocate($img, [72, 49, 38]);
        $muted = $this->allocate($img, [128, 104, 88]);
        $accent = $this->themeColor($img, $menu['theme']);

        imagefill($img, 0, 0, $bg);
        $this->drawPattern($img, $size);

        imagefilledellipse($img, (int) ($size * 0.5), (int) ($size * 0.48), (int) ($size * 0.78), (int) ($size * 0.54), $plateShadow);
        imagefilledellipse($img, (int) ($size * 0.5), (int) ($size * 0.45), (int) ($size * 0.78), (int) ($size * 0.54), $plate);
        imageellipse($img, (int) ($size * 0.5), (int) ($size * 0.45), (int) ($size * 0.72), (int) ($size * 0.48), $this->allocate($img, [232, 226, 217]));

        $this->drawFood($img, $size, $menu['theme'], $accent);
        $this->drawLabel($img, $size, $menu['name'], $menu['price'], $text, $muted);

        imagejpeg($img, $path, 88);
        imagedestroy($img);
    }

    private function drawPattern(\GdImage $img, int $size): void
    {
        $line = $this->allocate($img, [239, 231, 219]);

        for ($i = -$size; $i < $size * 2; $i += 48) {
            imageline($img, $i, 0, $i + $size, $size, $line);
        }
    }

    private function drawFood(\GdImage $img, int $size, string $theme, int $accent): void
    {
        $brown = $this->allocate($img, [95, 47, 31]);
        $darkBrown = $this->allocate($img, [56, 32, 24]);
        $gold = $this->allocate($img, [239, 181, 74]);
        $green = $this->allocate($img, [73, 144, 89]);
        $cream = $this->allocate($img, [245, 226, 174]);
        $red = $this->allocate($img, [199, 59, 43]);

        if (in_array($theme, ['tea', 'shake', 'water'], true)) {
            $this->drawDrink($img, $size, $theme, $accent);
            return;
        }

        if (in_array($theme, ['pasta', 'rice'], true)) {
            $this->drawBowl($img, $size, $theme, $accent);
            return;
        }

        if (in_array($theme, ['fries', 'potato', 'salad', 'bread'], true)) {
            $this->drawSide($img, $size, $theme, $accent);
            return;
        }

        imagefilledellipse($img, (int) ($size * 0.42), (int) ($size * 0.43), (int) ($size * 0.33), (int) ($size * 0.20), $brown);
        imagefilledellipse($img, (int) ($size * 0.42), (int) ($size * 0.40), (int) ($size * 0.30), (int) ($size * 0.16), $this->allocate($img, [130, 62, 40]));

        for ($i = 0; $i < 4; $i++) {
            $x = (int) ($size * (0.30 + ($i * 0.07)));
            imageline($img, $x, (int) ($size * 0.34), $x + (int) ($size * 0.08), (int) ($size * 0.48), $darkBrown);
        }

        imagefilledellipse($img, (int) ($size * 0.61), (int) ($size * 0.42), (int) ($size * 0.16), (int) ($size * 0.13), $accent);
        imagefilledrectangle($img, (int) ($size * 0.58), (int) ($size * 0.47), (int) ($size * 0.72), (int) ($size * 0.50), $gold);
        imagefilledellipse($img, (int) ($size * 0.66), (int) ($size * 0.35), (int) ($size * 0.08), (int) ($size * 0.08), $red);

        foreach ([0.58, 0.64, 0.70] as $x) {
            imagefilledellipse($img, (int) ($size * $x), (int) ($size * 0.51), (int) ($size * 0.09), (int) ($size * 0.04), $green);
        }

        if ($theme === 'cheese') {
            imagefilledellipse($img, (int) ($size * 0.42), (int) ($size * 0.37), (int) ($size * 0.22), (int) ($size * 0.07), $cream);
        }
    }

    private function drawDrink(\GdImage $img, int $size, string $theme, int $accent): void
    {
        $glass = $this->allocate($img, [234, 246, 250]);
        $outline = $this->allocate($img, [96, 119, 130]);
        $liquid = $theme === 'water' ? $this->allocate($img, [111, 183, 224]) : $accent;

        imagefilledrectangle($img, (int) ($size * 0.39), (int) ($size * 0.25), (int) ($size * 0.61), (int) ($size * 0.61), $glass);
        imagefilledrectangle($img, (int) ($size * 0.41), (int) ($size * 0.36), (int) ($size * 0.59), (int) ($size * 0.59), $liquid);
        imagerectangle($img, (int) ($size * 0.39), (int) ($size * 0.25), (int) ($size * 0.61), (int) ($size * 0.61), $outline);
        imagefilledellipse($img, (int) ($size * 0.50), (int) ($size * 0.31), (int) ($size * 0.16), (int) ($size * 0.05), $this->allocate($img, [255, 255, 255]));
        imageline($img, (int) ($size * 0.58), (int) ($size * 0.20), (int) ($size * 0.52), (int) ($size * 0.47), $outline);
    }

    private function drawBowl(\GdImage $img, int $size, string $theme, int $accent): void
    {
        $bowl = $this->allocate($img, [237, 239, 241]);
        $outline = $this->allocate($img, [109, 101, 94]);
        $food = $theme === 'pasta' ? $this->allocate($img, [236, 196, 100]) : $this->allocate($img, [250, 245, 225]);

        imagefilledellipse($img, (int) ($size * 0.50), (int) ($size * 0.45), (int) ($size * 0.43), (int) ($size * 0.20), $food);
        imagefilledarc($img, (int) ($size * 0.50), (int) ($size * 0.47), (int) ($size * 0.50), (int) ($size * 0.26), 0, 180, $bowl, IMG_ARC_PIE);
        imagearc($img, (int) ($size * 0.50), (int) ($size * 0.44), (int) ($size * 0.50), (int) ($size * 0.18), 0, 360, $outline);
        imagefilledellipse($img, (int) ($size * 0.45), (int) ($size * 0.39), (int) ($size * 0.08), (int) ($size * 0.05), $accent);
        imagefilledellipse($img, (int) ($size * 0.57), (int) ($size * 0.39), (int) ($size * 0.08), (int) ($size * 0.05), $this->allocate($img, [74, 135, 74]));
    }

    private function drawSide(\GdImage $img, int $size, string $theme, int $accent): void
    {
        $green = $this->allocate($img, [75, 151, 92]);
        $yellow = $this->allocate($img, [242, 192, 76]);
        $bread = $this->allocate($img, [200, 136, 74]);
        $cream = $this->allocate($img, [242, 229, 198]);

        if ($theme === 'salad') {
            for ($i = 0; $i < 8; $i++) {
                imagefilledellipse($img, (int) ($size * (0.34 + ($i % 4) * 0.10)), (int) ($size * (0.35 + intdiv($i, 4) * 0.10)), (int) ($size * 0.13), (int) ($size * 0.08), $i % 2 ? $green : $accent);
            }
            return;
        }

        if ($theme === 'potato') {
            imagefilledellipse($img, (int) ($size * 0.50), (int) ($size * 0.42), (int) ($size * 0.34), (int) ($size * 0.20), $cream);
            imagefilledellipse($img, (int) ($size * 0.56), (int) ($size * 0.37), (int) ($size * 0.07), (int) ($size * 0.04), $yellow);
            return;
        }

        for ($i = 0; $i < 6; $i++) {
            $x = (int) ($size * (0.34 + $i * 0.055));
            imagefilledrectangle($img, $x, (int) ($size * 0.29), $x + (int) ($size * 0.035), (int) ($size * 0.55), $theme === 'bread' ? $bread : $yellow);
        }
    }

    private function drawLabel(\GdImage $img, int $size, string $name, int $price, int $text, int $muted): void
    {
        $font = 5;
        $lines = $this->wrapText($name, 22);
        $y = (int) ($size * 0.73);

        foreach (array_slice($lines, 0, 2) as $line) {
            $x = (int) (($size - imagefontwidth($font) * strlen($line)) / 2);
            imagestring($img, $font, max(12, $x), $y, $line, $text);
            $y += imagefontheight($font) + 6;
        }

        $priceText = 'Rp ' . number_format($price, 0, ',', '.');
        $x = (int) (($size - imagefontwidth(4) * strlen($priceText)) / 2);
        imagestring($img, 4, max(12, $x), $y + 2, $priceText, $muted);
    }

    private function wrapText(string $text, int $length): array
    {
        return explode("\n", wordwrap($text, $length, "\n", true));
    }

    private function themeColor(\GdImage $img, string $theme): int
    {
        $palette = [
            'beef' => [164, 79, 49],
            'cheese' => [245, 194, 72],
            'pepper' => [64, 57, 52],
            'chicken' => [222, 143, 76],
            'fish' => [91, 146, 179],
            'sausage' => [185, 69, 52],
            'rice' => [197, 103, 61],
            'pasta' => [219, 85, 50],
            'fries' => [239, 184, 71],
            'potato' => [207, 169, 106],
            'salad' => [96, 161, 82],
            'bread' => [181, 112, 58],
            'tea' => [204, 126, 51],
            'shake' => [117, 72, 49],
            'water' => [86, 163, 211],
        ];

        return $this->allocate($img, $palette[$theme] ?? [164, 79, 49]);
    }

    private function allocate(\GdImage $img, array $rgb): int
    {
        return imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
    }
}
