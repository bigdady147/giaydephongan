<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductVariantControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_staff_can_add_a_variant_to_a_product(): void
    {
        $product = Product::factory()->create();
        $this->actingAsStaff();

        $response = $this->postJson("/api/admin/products/{$product->id}/variants", [
            'size' => '42', 'color' => 'Đen', 'sku' => 'SKU-42-DEN', 'stock_quantity' => 10,
        ]);

        $response->assertStatus(201)->assertJsonPath('variant.sku', 'SKU-42-DEN');
        $this->assertDatabaseHas('product_variants', ['product_id' => $product->id, 'sku' => 'SKU-42-DEN']);
    }

    public function test_variant_sku_must_be_unique(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['sku' => 'DUP-SKU']);
        $this->actingAsStaff();

        $this->postJson("/api/admin/products/{$product->id}/variants", [
            'size' => '43', 'color' => 'Đen', 'sku' => 'DUP-SKU', 'stock_quantity' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['sku']);
    }

    public function test_staff_can_update_stock_quantity(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['stock_quantity' => 5, 'sku' => 'STOCK-SKU']);
        $this->actingAsStaff();

        $this->putJson("/api/admin/variants/{$variant->id}", ['stock_quantity' => 20])
            ->assertStatus(200)->assertJsonPath('variant.stock_quantity', 20);
    }

    public function test_staff_can_delete_a_variant(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'DEL-SKU']);
        $this->actingAsStaff();

        $this->deleteJson("/api/admin/variants/{$variant->id}")->assertStatus(200);
        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }
}
