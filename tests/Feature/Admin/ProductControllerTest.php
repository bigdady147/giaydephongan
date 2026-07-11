<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_staff_can_create_a_product_with_variants(): void
    {
        $category = Category::factory()->create();
        $this->actingAsStaff();

        $response = $this->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'Giày Oxford Da Bò Nâu',
            'material' => 'full_grain_leather',
            'base_price' => 1500000,
            'status' => 'published',
            'variants' => [
                ['size' => '40', 'color' => 'Nâu', 'sku' => 'OXF-NAU-40', 'stock_quantity' => 5],
                ['size' => '41', 'color' => 'Nâu', 'sku' => 'OXF-NAU-41', 'stock_quantity' => 3],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('product.slug', 'giay-oxford-da-bo-nau')
            ->assertJsonCount(2, 'product.variants');

        $this->assertDatabaseCount('product_variants', 2);
    }

    public function test_creating_a_product_requires_category_name_and_price(): void
    {
        $this->actingAsStaff();

        $this->postJson('/api/admin/products', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'base_price']);
    }

    public function test_sale_price_must_be_lower_than_base_price(): void
    {
        $category = Category::factory()->create();
        $this->actingAsStaff();

        $this->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'Test',
            'base_price' => 1000000,
            'sale_price' => 1200000,
        ])->assertStatus(422)->assertJsonValidationErrors(['sale_price']);
    }

    public function test_staff_can_list_products_filtered_by_status(): void
    {
        Product::factory()->create(['status' => 'draft']);
        Product::factory()->create(['status' => 'published']);
        $this->actingAsStaff();

        $response = $this->getJson('/api/admin/products?status=published');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_staff_can_update_a_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old']);
        $this->actingAsStaff();

        $this->putJson("/api/admin/products/{$product->id}", ['name' => 'New'])
            ->assertStatus(200)->assertJsonPath('product.name', 'New');
    }

    public function test_staff_can_delete_a_product(): void
    {
        $product = Product::factory()->create();
        $this->actingAsStaff();

        $this->deleteJson("/api/admin/products/{$product->id}")->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_customer_cannot_create_a_product(): void
    {
        $category = Category::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'Test',
            'base_price' => 100000,
        ])->assertStatus(403);
    }

    public function test_creating_two_products_with_same_name_generates_unique_slugs(): void
    {
        $category = Category::factory()->create();
        $this->actingAsStaff();

        $payload = [
            'category_id' => $category->id,
            'name' => 'Giày Derby Da Bò',
            'material' => 'full_grain_leather',
            'base_price' => 1200000,
            'status' => 'published',
        ];

        $first = $this->postJson('/api/admin/products', $payload);
        $second = $this->postJson('/api/admin/products', $payload);

        $first->assertStatus(201)->assertJsonPath('product.slug', 'giay-derby-da-bo');
        $second->assertStatus(201)->assertJsonPath('product.slug', 'giay-derby-da-bo-2');
    }
}
