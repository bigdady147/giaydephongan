<?php

namespace Database\Factories;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = $this->faker->sentence();
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $this->faker->paragraph(),
            'content' => $this->faker->text(1000),
            'thumbnail' => $this->faker->imageUrl(),
            'pillar' => $this->faker->randomElement(['cam-nang-chon-giay', 'bao-quan-giay-da', 'giay-theo-dip']),
            'seo_title' => $title,
            'seo_description' => $this->faker->text(150),
            'status' => 'published',
            'published_at' => now()->subDay(),
        ];
    }
}
