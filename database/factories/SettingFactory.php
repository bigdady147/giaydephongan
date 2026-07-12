<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => 'key_' . fake()->unique()->word(),
            'value' => fake()->sentence(3),
            'group' => 'contact',
        ];
    }
}
