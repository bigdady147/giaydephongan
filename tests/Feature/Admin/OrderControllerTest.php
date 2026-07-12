<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $staff;
    private $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'active',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);
    }

    public function test_guest_cannot_access_orders_admin()
    {
        $this->getJson('/api/admin/orders')->assertStatus(401);
    }

    public function test_customer_cannot_access_orders_admin()
    {
        $this->actingAs($this->customer)
            ->getJson('/api/admin/orders')
            ->assertStatus(403);
    }

    public function test_staff_can_list_orders()
    {
        Order::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'status' => 'pending'
        ]);
        Order::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => 'confirmed'
        ]);

        // General list
        $response = $this->actingAs($this->staff)
            ->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');

        // Status filter
        $responseFiltered = $this->actingAs($this->staff)
            ->getJson('/api/admin/orders?status=confirmed');

        $responseFiltered->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_staff_can_view_order_details()
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson("/api/admin/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('order_code', $order->order_code);
    }

    public function test_staff_can_update_order_status()
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'user_id' => $this->customer->id
        ]);

        // Valid transition: pending -> confirmed
        $response = $this->actingAs($this->staff)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'confirmed'
            ]);

        $response->assertStatus(200);
        $this->assertEquals('confirmed', $order->fresh()->status);

        // Valid transition: confirmed -> shipping
        $response2 = $this->actingAs($this->staff)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'shipping'
            ]);

        $response2->assertStatus(200);
        $this->assertEquals('shipping', $order->fresh()->status);
    }

    public function test_staff_cannot_make_invalid_status_transitions()
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'user_id' => $this->customer->id
        ]);

        // Invalid: pending -> shipping
        $response = $this->actingAs($this->staff)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'shipping'
            ]);

        $response->assertStatus(422);

        // Invalid: pending -> delivered
        $response2 = $this->actingAs($this->staff)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'delivered'
            ]);

        $response2->assertStatus(422);
    }

    public function test_staff_cannot_change_status_of_finalized_orders()
    {
        $order = Order::factory()->create([
            'status' => 'delivered',
            'user_id' => $this->customer->id
        ]);

        // Invalid: delivered -> pending
        $response = $this->actingAs($this->staff)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'pending'
            ]);

        $response->assertStatus(422);
    }
}
