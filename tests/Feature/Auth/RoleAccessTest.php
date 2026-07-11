<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_route(): void
    {
        $this->getJson('/api/admin/users')->assertStatus(401);
    }

    public function test_customer_cannot_access_admin_route(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_staff_cannot_access_admin_only_route(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());

        $this->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_admin_can_access_admin_route(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/admin/users')->assertStatus(200);
    }
}
