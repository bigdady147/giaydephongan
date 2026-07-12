<?php

namespace Database\Factories;

use App\Models\DiscountCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountCodeFactory extends Factory
{
    protected $model = DiscountCode::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('?????')),
            'type' => $this->faker->randomElement(['percent', 'fixed']),
            'value' => $this->faker->randomElement([10, 20, 50000, 100000]),
            'min_order_value' => $this->faker->randomElement([0, 100000, 200000]),
            'usage_limit' => $this->faker->optional()->numberBetween(10, 100),
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ];
    }
}
