<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_stats(): void
    {
        $this->getJson('/api/admin/dashboard/stats')->assertStatus(401);
    }

    public function test_staff_can_view_stats(): void
    {
        Category::factory()->count(2)->create();
        Brand::factory()->count(3)->create();
        $category = Category::factory()->create();
        $published = Product::factory()->for($category)->create(['status' => 'published']);
        Product::factory()->for($category)->create(['status' => 'draft']);
        ProductVariant::factory()->for($published)->create(['stock_quantity' => 2, 'sku' => 'LOW-1']);
        ProductVariant::factory()->for($published)->create(['stock_quantity' => 20, 'sku' => 'HIGH-1']);
        ProductVariant::factory()->for($published)->create(['stock_quantity' => 5, 'sku' => 'BOUNDARY-1']);

        Sanctum::actingAs(User::factory()->staff()->create());

        $response = $this->getJson('/api/admin/dashboard/stats');

        // stock_quantity=5 must NOT be counted as low stock: the query is strictly `< 5`.
        $response->assertStatus(200)->assertJson([
            'categories_count' => 3,
            'brands_count' => 3,
            'products_count' => 2,
            'products_published_count' => 1,
            'products_draft_count' => 1,
            'products_archived_count' => 0,
            'variants_count' => 3,
            'low_stock_variants_count' => 1,
        ]);
    }
}
