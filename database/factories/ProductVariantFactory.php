<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size' => fake()->randomElement(['38', '39', '40', '41', '42', '43', '44', '45']),
            'color' => fake()->randomElement(['Black', 'Brown', 'Tan', 'White', 'Navy']),
            'sku' => fake()->unique()->bothify('SKU-####-##'),
            'stock_quantity' => fake()->numberBetween(0, 100),
        ];
    }
}
