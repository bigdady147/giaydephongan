<?php

namespace Database\Factories;

use App\Models\MembershipTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipTierFactory extends Factory
{
    protected $model = MembershipTier::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'min_points' => $this->faker->numberBetween(100, 1000),
            'discount_percent' => $this->faker->randomFloat(2, 0, 15),
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
