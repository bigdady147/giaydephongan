<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_reports(): void
    {
        $this->getJson('/api/admin/reports/revenue')->assertStatus(401);
    }

    public function test_staff_cannot_view_reports(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());

        $this->getJson('/api/admin/reports/revenue')->assertStatus(403);
    }

    public function test_admin_can_view_revenue_grouped_by_day_excluding_cancelled(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $today = now()->toDateString();
        Order::factory()->create(['status' => 'delivered', 'total' => 100000])
            ->forceFill(['created_at' => $today])->save();
        Order::factory()->create(['status' => 'confirmed', 'total' => 200000])
            ->forceFill(['created_at' => $today])->save();
        Order::factory()->create(['status' => 'cancelled', 'total' => 999999])
            ->forceFill(['created_at' => $today])->save();

        $response = $this->getJson('/api/admin/reports/revenue');

        $response->assertStatus(200)
            ->assertJsonPath('total_revenue', 300000.0)
            ->assertJsonPath('total_orders', 2)
            ->assertJsonPath('daily.0.date', $today)
            ->assertJsonPath('daily.0.revenue', 300000.0);
    }

    public function test_admin_can_view_top_products_ranked_by_quantity(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $order = Order::factory()->create(['status' => 'delivered']);
        $order->items()->create([
            'product_name_snapshot' => 'Giày Oxford Nâu',
            'variant_snapshot' => 'Size 41 - Nâu',
            'price' => 1000000,
            'quantity' => 5,
            'subtotal' => 5000000,
        ]);
        $order->items()->create([
            'product_name_snapshot' => 'Giày Derby Đen',
            'variant_snapshot' => 'Size 40 - Đen',
            'price' => 900000,
            'quantity' => 2,
            'subtotal' => 1800000,
        ]);

        $cancelledOrder = Order::factory()->create(['status' => 'cancelled']);
        $cancelledOrder->items()->create([
            'product_name_snapshot' => 'Giày Loafer Xám',
            'variant_snapshot' => 'Size 42 - Xám',
            'price' => 500000,
            'quantity' => 100,
            'subtotal' => 50000000,
        ]);

        $response = $this->getJson('/api/admin/reports/top-products');

        $response->assertStatus(200)->assertJsonPath('products.0.name', 'Giày Oxford Nâu');
        $this->assertCount(2, $response->json('products'));
    }
}
