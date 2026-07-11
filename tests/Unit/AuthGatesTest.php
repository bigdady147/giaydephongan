<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthGatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_gate_allows_admin_and_staff_but_not_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $customer = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('staff-only'));
        $this->assertTrue(Gate::forUser($staff)->allows('staff-only'));
        $this->assertFalse(Gate::forUser($customer)->allows('staff-only'));
    }

    public function test_active_only_gate_allows_active_and_denies_inactive(): void
    {
        $active = User::factory()->create(['status' => 'active']);
        $inactive = User::factory()->inactive()->create();

        $this->assertTrue(Gate::forUser($active)->allows('active-only'));
        $this->assertFalse(Gate::forUser($inactive)->allows('active-only'));
    }
}
