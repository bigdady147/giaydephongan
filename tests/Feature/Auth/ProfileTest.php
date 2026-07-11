<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)->assertJsonPath('id', $user->id);
    }

    public function test_guest_cannot_fetch_profile(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/profile', [
            'name' => 'Updated Name',
            'phone' => '0900000000',
        ]);

        $response->assertStatus(200)->assertJsonPath('user.name', 'Updated Name');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name', 'phone' => '0900000000']);
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');
        $logoutResponse->assertStatus(200);

        // Laravel's auth guard caches the resolved user on the guard instance,
        // which persists across sequential simulated HTTP calls within one
        // test method (unlike real requests, which each get a fresh guard).
        // Without this, the follow-up call below sees the pre-logout cached
        // user and wrongly returns 200 even though the token row is deleted.
        Auth::forgetGuards();

        $followUpResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');
        $followUpResponse->assertStatus(401);
    }
}
