<?php

namespace Tests\Feature\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_published_products_paginated(): void
    {
        Product::factory()->count(13)->create(['status' => 'published']);
        Product::factory()->create(['status' => 'draft']);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonPath('total', 13)
            ->assertJsonPath('per_page', 12)
            ->assertJsonCount(12, 'data');
    }

    public function test_filters_by_category_and_brand_slug(): void
    {
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $match = Product::factory()->for($category)->for($brand)->create(['status' => 'published']);
        Product::factory()->create(['status' => 'published']);

        $this->getJson("/api/products?category={$category->slug}")
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $match->id);
        $this->getJson("/api/products?brand={$brand->slug}")
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $match->id);
    }

    public function test_filters_by_variant_size_and_color(): void
    {
        $match = Product::factory()->create(['status' => 'published']);
        ProductVariant::factory()->for($match)->create(['size' => '42', 'color' => 'Nâu', 'sku' => 'V-42-N']);
        $other = Product::factory()->create(['status' => 'published']);
        ProductVariant::factory()->for($other)->create(['size' => '39', 'color' => 'Đen', 'sku' => 'V-39-D']);

        $this->getJson('/api/products?size=42')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $match->id);
        $this->getJson('/api/products?color=Nâu')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $match->id);
    }

    public function test_filters_by_effective_price_range(): void
    {
        Product::factory()->create(['status' => 'published', 'base_price' => 900000, 'sale_price' => 500000]);
        $mid = Product::factory()->create(['status' => 'published', 'base_price' => 700000, 'sale_price' => null]);
        Product::factory()->create(['status' => 'published', 'base_price' => 2000000, 'sale_price' => null]);

        $response = $this->getJson('/api/products?price_min=600000&price_max=1000000');

        $response->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $mid->id);
    }

    public function test_searches_by_name(): void
    {
        $match = Product::factory()->create(['status' => 'published', 'name' => 'Giày Oxford da bò']);
        Product::factory()->create(['status' => 'published', 'name' => 'Dép quai ngang']);

        $this->getJson('/api/products?q=Oxford')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $match->id);
    }

    public function test_sorts_by_effective_price(): void
    {
        $cheap = Product::factory()->create(['status' => 'published', 'base_price' => 900000, 'sale_price' => 400000]);
        $mid = Product::factory()->create(['status' => 'published', 'base_price' => 600000, 'sale_price' => null]);

        $this->getJson('/api/products?sort=price_asc')->assertJsonPath('data.0.id', $cheap->id);
        $this->getJson('/api/products?sort=price_desc')->assertJsonPath('data.0.id', $mid->id);
    }

    public function test_sort_best_selling_uses_sold_count(): void
    {
        $slow = Product::factory()->create(['status' => 'published']);
        $hot = Product::factory()->create(['status' => 'published']);
        $hot->forceFill(['sold_count' => 50])->save();

        $this->getJson('/api/products?sort=best_selling')->assertJsonPath('data.0.id', $hot->id);
    }

    public function test_sort_featured_filters_to_featured_only(): void
    {
        Product::factory()->create(['status' => 'published', 'is_featured' => false]);
        $featured = Product::factory()->create(['status' => 'published', 'is_featured' => true]);

        $response = $this->getJson('/api/products?sort=featured');

        $response->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $featured->id);
    }

    public function test_rejects_invalid_sort(): void
    {
        $this->getJson('/api/products?sort=hack')->assertStatus(422);
    }
}
