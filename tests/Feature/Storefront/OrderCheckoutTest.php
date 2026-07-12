<?php

namespace Tests\Feature\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private $variant;
    private $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'base_price' => 500000,
            'sale_price' => 450000,
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'size' => '42',
            'color' => 'Nâu',
            'stock_quantity' => 10,
        ]);
    }

    public function test_guest_can_checkout_successfully()
    {
        $payload = [
            'guest_name' => 'John Doe',
            'guest_phone' => '0909123456',
            'guest_email' => 'john@example.com',
            'shipping_address' => '123 Test Street, District 1, HCM',
            'items' => [
                [
                    'variant_id' => $this->variant->id,
                    'quantity' => 2,
                ]
            ],
            'note' => 'Giao gio hanh chinh'
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['order_code', 'total', 'status', 'message']);

        $orderCode = $response->json('order_code');

        $this->assertDatabaseHas('orders', [
            'order_code' => $orderCode,
            'guest_name' => 'John Doe',
            'subtotal' => 900000, // 450k * 2
            'shipping_fee' => 0, // >= 500k is free ship
            'total' => 900000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name_snapshot' => $this->product->name,
            'variant_snapshot' => 'Size 42 - Màu Nâu',
            'price' => 450000,
            'quantity' => 2,
        ]);

        // Stock decremented
        $this->assertEquals(8, $this->variant->fresh()->stock_quantity);
    }

    public function test_checkout_fails_on_insufficient_stock()
    {
        $payload = [
            'guest_name' => 'John Doe',
            'guest_phone' => '0909123456',
            'shipping_address' => '123 Test Street',
            'items' => [
                [
                    'variant_id' => $this->variant->id,
                    'quantity' => 11, // stock is 10
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('message', "Sản phẩm {$this->product->name} (size 42, màu Nâu) không đủ hàng tồn kho.");
    }

    public function test_checkout_applies_valid_discount_code()
    {
        $discount = DiscountCode::factory()->create([
            'code' => 'SALE50',
            'type' => 'fixed',
            'value' => 50000,
            'min_order_value' => 200000,
        ]);

        $payload = [
            'guest_name' => 'John Doe',
            'guest_phone' => '0909123456',
            'shipping_address' => '123 Test Street',
            'items' => [
                [
                    'variant_id' => $this->variant->id,
                    'quantity' => 1, // 450k
                ]
            ],
            'discount_code' => 'SALE50'
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);
        $orderCode = $response->json('order_code');

        $this->assertDatabaseHas('orders', [
            'order_code' => $orderCode,
            'subtotal' => 450000,
            'shipping_fee' => 30000, // < 500k has 30k ship fee
            'discount_amount' => 50000,
            'total' => 430000, // 450k + 30k - 50k
            'discount_code_id' => $discount->id,
        ]);

        $this->assertEquals(1, $discount->fresh()->used_count);
    }

    public function test_discount_validation_endpoint()
    {
        $discount = DiscountCode::factory()->create([
            'code' => 'SALE10',
            'type' => 'percent',
            'value' => 10,
            'min_order_value' => 100000,
        ]);

        // Valid
        $this->postJson('/api/discount-codes/validate', [
            'code' => 'SALE10',
            'subtotal' => 200000
        ])->assertStatus(200)
          ->assertJson([
              'valid' => true,
              'discount_amount' => 20000
          ]);

        // Invalid (subtotal below min)
        $this->postJson('/api/discount-codes/validate', [
            'code' => 'SALE10',
            'subtotal' => 50000
        ])->assertStatus(422)
          ->assertJson([
              'valid' => false
          ]);
    }

    public function test_order_tracking_access_rules()
    {
        $guestOrder = Order::factory()->create([
            'user_id' => null,
            'guest_name' => 'Guest User',
            'guest_phone' => '0912345678',
        ]);

        $user = User::factory()->create();
        $userOrder = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        // Guest order: tracking without phone fails
        $this->getJson("/api/orders/{$guestOrder->order_code}")
            ->assertStatus(403);

        // Guest order: tracking with wrong phone fails
        $this->getJson("/api/orders/{$guestOrder->order_code}?phone=0900000000")
            ->assertStatus(403);

        // Guest order: tracking with correct phone succeeds
        $this->getJson("/api/orders/{$guestOrder->order_code}?phone=0912-345-678")
            ->assertStatus(200)
            ->assertJsonPath('order_code', $guestOrder->order_code);

        // User order: tracking by another user fails
        $this->actingAs($user)->getJson("/api/orders/{$guestOrder->order_code}")
            ->assertStatus(403);

        // User order: tracking by owner succeeds
        $this->actingAs($user)->getJson("/api/orders/{$userOrder->order_code}")
            ->assertStatus(200)
            ->assertJsonPath('order_code', $userOrder->order_code);
    }
}
