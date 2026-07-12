<?php

namespace Database\Factories;

use App\Models\LoyaltyLedger;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyLedgerFactory extends Factory
{
    protected $model = LoyaltyLedger::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => null,
            'points' => $this->faker->numberBetween(10, 100),
            'type' => 'earn',
            'note' => $this->faker->sentence(),
        ];
    }
}
