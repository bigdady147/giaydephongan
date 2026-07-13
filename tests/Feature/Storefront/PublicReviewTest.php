<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_approved_reviews(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        Review::factory()->for($product)->approved()->create(['comment' => 'Rất tốt']);
        Review::factory()->for($product)->create(['comment' => 'Chờ duyệt']);
        Review::factory()->for($product)->rejected()->create(['comment' => 'Bị từ chối']);

        $response = $this->getJson("/api/products/{$product->slug}/reviews");

        $response->assertStatus(200)->assertJsonCount(1, 'data');
        $this->assertEquals('Rất tốt', $response->json('data.0.comment'));
    }

    public function test_guest_cannot_submit_review(): void
    {
        $product = Product::factory()->create(['status' => 'published']);

        $this->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])->assertStatus(401);
    }

    public function test_customer_can_submit_review(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson("/api/products/{$product->slug}/reviews", [
            'rating' => 4,
            'comment' => 'Giày đẹp, đóng gói chắc chắn',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'rating' => 4,
            'status' => 'pending',
        ]);
    }

    public function test_customer_cannot_submit_review_twice_for_same_product(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        $user = User::factory()->create();
        Review::factory()->for($product)->for($user)->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/products/{$product->slug}/reviews", ['rating' => 3])
            ->assertStatus(422);
    }

    public function test_store_requires_rating_between_1_and_5(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/products/{$product->slug}/reviews", ['rating' => 6])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['rating']]);
    }
}
