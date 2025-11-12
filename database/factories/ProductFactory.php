<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 10000, 100000);
        $sellingPrice = $purchasePrice * fake()->randomFloat(2, 1.2, 2.5);

        return [
            'sku' => 'PRD'.fake()->unique()->numberBetween(10000, 99999),
            'name' => fake()->words(3, true),
            'category_id' => \App\Models\ProductCategory::factory(),
            'description' => fake()->sentence(),
            'unit' => fake()->randomElement(['pcs', 'kg', 'liter', 'box']),
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'min_stock' => fake()->numberBetween(5, 20),
            'is_active' => true,
            'created_by' => 1,
        ];
    }
}
