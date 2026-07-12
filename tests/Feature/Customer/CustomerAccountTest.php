<?php

namespace Tests\Feature\Customer;

use App\Models\MembershipTier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
            'points' => 0,
        ]);

        $this->otherUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        // Seed tiers
        MembershipTier::create(['name' => 'Đồng', 'min_points' => 0, 'discount_percent' => 0, 'sort_order' => 1]);
        MembershipTier::create(['name' => 'Bạc', 'min_points' => 10, 'discount_percent' => 2, 'sort_order' => 2]); // threshold = 10 for easier test
        MembershipTier::create(['name' => 'Vàng', 'min_points' => 100, 'discount_percent' => 5, 'sort_order' => 3]);
    }

    public function test_customer_can_list_their_own_orders()
    {
        Order::factory()->count(2)->create([
            'user_id' => $this->user->id,
        ]);
        Order::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/user/orders');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_customer_can_view_their_own_order_by_code()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/user/orders/{$order->order_code}");

        $response->assertStatus(200)
            ->assertJsonPath('order_code', $order->order_code);
    }

    public function test_customer_cannot_view_others_order_by_code()
    {
        $order = Order::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/user/orders/{$order->order_code}")
            ->assertStatus(404);
    }

    public function test_order_delivery_earns_loyalty_points_and_upgrades_membership_tier()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'total' => 150000, // 150,000 VND / 10,000 = 15 points
        ]);

        // Default tier starts as Bronze or null (let's set to null or Bronze first)
        $this->user->membership_tier_id = MembershipTier::where('name', 'Đồng')->first()->id;
        $this->user->save();

        // Update order status to delivered
        $order->status = 'delivered';
        $order->save();

        // 15 points earned, user points should be 15
        $this->assertEquals(15, $this->user->fresh()->points);

        // Tier should upgrade to Silver (Bạc) because 15 >= 10
        $silverTier = MembershipTier::where('name', 'Bạc')->first();
        $this->assertEquals($silverTier->id, $this->user->fresh()->membership_tier_id);

        // Verify ledger log
        $this->assertDatabaseHas('loyalty_ledger', [
            'user_id' => $this->user->id,
            'order_id' => $order->id,
            'points' => 15,
            'type' => 'earn',
        ]);
    }

    public function test_customer_can_retrieve_loyalty_info_and_history()
    {
        // Give some points
        $this->user->points = 5;
        $this->user->save();

        $response = $this->actingAs($this->user)
            ->getJson('/api/user/loyalty');

        $response->assertStatus(200)
            ->assertJsonPath('points', 5)
            ->assertJsonStructure(['points', 'current_tier', 'next_tier', 'history']);
    }
}
