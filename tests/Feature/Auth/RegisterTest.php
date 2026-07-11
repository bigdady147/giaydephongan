<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Nguyen Van A',
            'username' => 'nguyenvana',
            'email' => 'a@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'access_token', 'token_type', 'user' => ['id', 'email', 'role']]);

        $this->assertDatabaseHas('users', [
            'email' => 'a@example.com',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $user = User::where('email', 'a@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_registration_requires_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'username', 'email', 'password']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Someone',
            'username' => 'someoneelse',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_when_password_confirmation_does_not_match(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Someone',
            'username' => 'someoneelse2',
            'email' => 'someoneelse2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }
}
