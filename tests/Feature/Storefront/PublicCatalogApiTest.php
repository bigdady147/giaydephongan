<?php

namespace Tests\Feature\Storefront;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_lists_only_active_with_published_product_counts(): void
    {
        $active = Category::factory()->create(['is_active' => true, 'sort_order' => 1]);
        Category::factory()->create(['is_active' => false]);
        Product::factory()->for($active)->create(['status' => 'published']);
        Product::factory()->for($active)->create(['status' => 'draft']);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)->assertJsonCount(1)
            ->assertJsonPath('0.id', $active->id)
            ->assertJsonPath('0.products_count', 1);
    }

    public function test_category_show_returns_active_and_404s_inactive(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $hidden = Category::factory()->create(['is_active' => false]);

        $this->getJson("/api/categories/{$category->slug}")->assertStatus(200)->assertJsonPath('id', $category->id);
        $this->getJson("/api/categories/{$hidden->slug}")->assertStatus(404);
    }

    public function test_brands_lists_all_ordered_by_name(): void
    {
        Brand::factory()->create(['name' => 'Zeta']);
        Brand::factory()->create(['name' => 'Alpha']);

        $response = $this->getJson('/api/brands');

        $response->assertStatus(200)->assertJsonCount(2)->assertJsonPath('0.name', 'Alpha');
    }

    public function test_settings_returns_flat_key_value_map(): void
    {
        Setting::factory()->create(['key' => 'hotline', 'value' => '0909 000 000']);

        $this->getJson('/api/settings')->assertStatus(200)->assertJsonPath('hotline', '0909 000 000');
    }

    public function test_banners_grouped_by_position_and_time_filtered(): void
    {
        Banner::factory()->create(['position' => 'homepage_hero', 'sort_order' => 2]);
        Banner::factory()->create(['position' => 'homepage_hero', 'sort_order' => 1]);
        Banner::factory()->create(['position' => 'homepage_promo']);
        Banner::factory()->create(['position' => 'homepage_hero', 'is_active' => false]);
        Banner::factory()->create(['position' => 'homepage_hero', 'ends_at' => now()->subDay()]);

        $response = $this->getJson('/api/banners');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'homepage_hero')
            ->assertJsonCount(1, 'homepage_promo');
        $this->assertSame(1, $response->json('homepage_hero.0.sort_order'));
    }

    public function test_banners_keys_present_even_when_empty(): void
    {
        $response = $this->getJson('/api/banners');

        $response->assertStatus(200)->assertExactJson(['homepage_hero' => [], 'homepage_promo' => []]);
    }
}
