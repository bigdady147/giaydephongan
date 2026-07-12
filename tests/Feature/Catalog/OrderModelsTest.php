<?php

namespace Tests\Feature\Catalog;

use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creates_with_unique_order_code()
    {
        $order = Order::factory()->create([
            'user_id' => null,
            'guest_name' => 'John Doe',
            'guest_phone' => '0909000000',
            'shipping_address' => '123 Test St',
        ]);

        $this->assertNotNull($order->order_code);
        $this->assertStringStartsWith('HA', $order->order_code);
    }

    public function test_discount_code_validation_and_calculation()
    {
        $code = DiscountCode::factory()->create([
            'code' => 'TEST10',
            'type' => 'percent',
            'value' => 10,
            'min_order_value' => 100000,
        ]);

        $this->assertTrue($code->isValidFor(150000));
        $this->assertFalse($code->isValidFor(50000));

        $this->assertEquals(15000, $code->calculateDiscount(150000));
    }
}
