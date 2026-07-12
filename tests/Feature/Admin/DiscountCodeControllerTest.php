<?php

namespace Tests\Feature\Admin;

use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiscountCodeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_guest_cannot_manage_discount_codes(): void
    {
        $this->getJson('/api/admin/discount-codes')->assertStatus(401);
    }

    public function test_customer_cannot_manage_discount_codes(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'customer']));

        $this->getJson('/api/admin/discount-codes')->assertStatus(403);
    }

    public function test_staff_can_list_discount_codes(): void
    {
        $this->actingAsStaff();
        DiscountCode::factory()->count(2)->create();

        $this->getJson('/api/admin/discount-codes')->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_staff_can_create_discount_code_and_code_is_uppercased(): void
    {
        $this->actingAsStaff();

        $response = $this->postJson('/api/admin/discount-codes', [
            'code' => 'sale10',
            'type' => 'percent',
            'value' => 10,
        ]);

        $response->assertStatus(201)->assertJsonPath('discount_code.code', 'SALE10');
    }

    public function test_store_requires_code_type_and_value(): void
    {
        $this->actingAsStaff();

        $this->postJson('/api/admin/discount-codes', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['code', 'type', 'value']]);
    }

    public function test_store_rejects_duplicate_code_case_insensitively(): void
    {
        $this->actingAsStaff();
        DiscountCode::factory()->create(['code' => 'WELCOME']);

        $this->postJson('/api/admin/discount-codes', [
            'code' => 'welcome',
            'type' => 'fixed',
            'value' => 50000,
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['code']]);
    }

    public function test_store_rejects_expires_at_before_starts_at(): void
    {
        $this->actingAsStaff();

        $this->postJson('/api/admin/discount-codes', [
            'code' => 'BADDATE',
            'type' => 'percent',
            'value' => 10,
            'starts_at' => '2026-08-01 00:00:00',
            'expires_at' => '2026-07-01 00:00:00',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['expires_at']]);
    }

    public function test_staff_can_update_and_delete_discount_code(): void
    {
        $this->actingAsStaff();
        $code = DiscountCode::factory()->create(['is_active' => true]);

        $this->putJson("/api/admin/discount-codes/{$code->id}", [
            'code' => $code->code,
            'type' => $code->type,
            'value' => $code->value,
            'is_active' => false,
        ])->assertStatus(200)->assertJsonPath('discount_code.is_active', false);

        $this->deleteJson("/api/admin/discount-codes/{$code->id}")->assertStatus(200);
        $this->assertDatabaseMissing('discount_codes', ['id' => $code->id]);
    }
}
