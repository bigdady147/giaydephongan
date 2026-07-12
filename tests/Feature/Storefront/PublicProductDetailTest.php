<?php

namespace Tests\Feature\Storefront;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_published_product_with_relations_and_related(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create(['status' => 'published']);
        ProductVariant::factory()->for($product)->create(['sku' => 'D-1']);
        $sibling = Product::factory()->for($category)->create(['status' => 'published']);
        Product::factory()->for($category)->create(['status' => 'draft']);
        Product::factory()->create(['status' => 'published']); // other category

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $product->id)
            ->assertJsonCount(1, 'variants')
            ->assertJsonCount(1, 'related')
            ->assertJsonPath('related.0.id', $sibling->id);
    }

    public function test_show_404s_for_draft_product(): void
    {
        $draft = Product::factory()->create(['status' => 'draft']);

        $this->getJson("/api/products/{$draft->slug}")->assertStatus(404);
    }

    public function test_availability_returns_light_price_and_stock_payload(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'base_price' => 800000, 'sale_price' => 650000]);
        ProductVariant::factory()->for($product)->create(['sku' => 'A-1', 'stock_quantity' => 7]);

        $response = $this->getJson("/api/products/{$product->slug}/availability");

        $response->assertStatus(200)
            ->assertJsonPath('base_price', 800000)
            ->assertJsonPath('sale_price', 650000)
            ->assertJsonPath('variants.0.stock_quantity', 7);
        $this->assertArrayNotHasKey('description', $response->json());
    }

    public function test_pages_index_lists_only_active_titles_and_slugs(): void
    {
        $page = Page::factory()->create(['is_active' => true]);
        Page::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/pages');

        $response->assertStatus(200)->assertJsonCount(1)->assertJsonPath('0.slug', $page->slug);
        $this->assertArrayNotHasKey('content', $response->json()[0]);
    }

    public function test_page_show_returns_active_and_404s_inactive(): void
    {
        $page = Page::factory()->create(['is_active' => true]);
        $hidden = Page::factory()->create(['is_active' => false]);

        $this->getJson("/api/pages/{$page->slug}")->assertStatus(200)->assertJsonPath('slug', $page->slug);
        $this->getJson("/api/pages/{$hidden->slug}")->assertStatus(404);
    }

    public function test_slugs_endpoint_returns_publishable_slugs_only(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        Product::factory()->create(['status' => 'draft']);
        $category = Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);
        $page = Page::factory()->create(['is_active' => true]);
        $post = BlogPost::factory()->create(['status' => 'published']);
        BlogPost::factory()->create(['status' => 'draft']);

        $response = $this->getJson('/api/slugs');

        $response->assertStatus(200);
        $this->assertContains($product->slug, $response->json('products'));
        $this->assertContains($category->slug, $response->json('categories'));
        $this->assertContains($page->slug, $response->json('pages'));
        $this->assertContains($post->slug, $response->json('blog'));
        $this->assertCount(1, $response->json('blog'));
    }
}
