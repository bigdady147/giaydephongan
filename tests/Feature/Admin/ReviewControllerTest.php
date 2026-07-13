<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_guest_cannot_manage_reviews(): void
    {
        $this->getJson('/api/admin/reviews')->assertStatus(401);
    }

    public function test_customer_cannot_manage_reviews(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/reviews')->assertStatus(403);
    }

    public function test_staff_can_list_and_filter_reviews_by_status(): void
    {
        $this->actingAsStaff();
        $product = Product::factory()->create();
        Review::factory()->for($product)->approved()->create();
        Review::factory()->for($product)->create();

        $this->getJson('/api/admin/reviews')->assertStatus(200)->assertJsonCount(2, 'data');
        $this->getJson('/api/admin/reviews?status=approved')->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_approving_a_review_recomputes_product_rating(): void
    {
        $this->actingAsStaff();
        $product = Product::factory()->create(['avg_rating' => 0, 'reviews_count' => 0]);
        $review = Review::factory()->for($product)->create(['rating' => 5]);
        Review::factory()->for($product)->approved()->create(['rating' => 3]);

        $this->patchJson("/api/admin/reviews/{$review->id}/status", ['status' => 'approved'])
            ->assertStatus(200);

        $product->refresh();
        $this->assertEquals(2, $product->reviews_count);
        $this->assertEquals(4.0, $product->avg_rating);
    }

    public function test_rejecting_a_review_excludes_it_from_rating(): void
    {
        $this->actingAsStaff();
        $product = Product::factory()->create();
        $review = Review::factory()->for($product)->approved()->create(['rating' => 5]);

        $this->patchJson("/api/admin/reviews/{$review->id}/status", ['status' => 'rejected'])
            ->assertStatus(200);

        $product->refresh();
        $this->assertEquals(0, $product->reviews_count);
        $this->assertEquals(0.0, $product->avg_rating);
    }

    public function test_deleting_an_approved_review_recomputes_product_rating(): void
    {
        $this->actingAsStaff();
        $product = Product::factory()->create();
        $review = Review::factory()->for($product)->approved()->create(['rating' => 5]);

        $this->deleteJson("/api/admin/reviews/{$review->id}")->assertStatus(200);

        $product->refresh();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertEquals(0, $product->reviews_count);
    }
}
