<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'guest_name' => null,
            'guest_phone' => null,
            'guest_email' => null,
            'shipping_address' => $this->faker->address(),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'shipping', 'delivered', 'cancelled']),
            'payment_method' => 'cod',
            'subtotal' => 500000,
            'shipping_fee' => 30000,
            'discount_amount' => 0,
            'total' => 530000,
            'note' => $this->faker->sentence(),
        ];
    }
}
