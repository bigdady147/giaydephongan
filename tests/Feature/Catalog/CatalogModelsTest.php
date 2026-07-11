<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_belongs_to_category_and_brand(): void
    {
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $product = Product::factory()->for($category)->create(['brand_id' => $brand->id]);

        $this->assertTrue($product->category->is($category));
        $this->assertTrue($product->brand->is($brand));
    }

    public function test_product_has_many_variants_and_images(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['size' => '41', 'color' => 'Nau', 'sku' => 'SKU-41-NAU']);
        ProductImage::create(['product_id' => $product->id, 'url' => '/storage/products/a.jpg', 'sort_order' => 0]);

        $this->assertCount(1, $product->fresh()->variants);
        $this->assertCount(1, $product->fresh()->images);
    }

    public function test_category_can_have_a_parent_and_children(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->first()->is($child));
    }

    public function test_deleting_a_product_cascades_to_variants_and_images(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'SKU-DEL']);

        $product->delete();

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }
}
