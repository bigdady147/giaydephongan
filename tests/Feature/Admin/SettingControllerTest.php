<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_settings_admin(): void
    {
        $this->getJson('/api/admin/settings')->assertStatus(401);
    }

    public function test_staff_can_list_settings_with_groups(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
        Setting::factory()->create(['key' => 'hotline', 'value' => '0909', 'group' => 'contact']);

        $response = $this->getJson('/api/admin/settings');

        $response->assertStatus(200)->assertJsonPath('0.key', 'hotline')->assertJsonPath('0.group', 'contact');
    }

    public function test_bulk_update_changes_existing_keys(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
        Setting::factory()->create(['key' => 'hotline', 'value' => 'old']);
        Setting::factory()->create(['key' => 'email', 'value' => 'old@x.vn']);

        $response = $this->putJson('/api/admin/settings', [
            'settings' => ['hotline' => '0911 222 333', 'email' => 'new@x.vn'],
        ]);

        $response->assertStatus(200)->assertJsonPath('settings.hotline', '0911 222 333');
        $this->assertDatabaseHas('settings', ['key' => 'email', 'value' => 'new@x.vn']);
    }

    public function test_bulk_update_rejects_unknown_keys(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
        Setting::factory()->create(['key' => 'hotline']);

        $this->putJson('/api/admin/settings', ['settings' => ['hotline' => 'x', 'evil_key' => 'y']])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['settings']]);
    }

    public function test_bulk_update_rejects_empty_payload(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());

        $this->putJson('/api/admin/settings', [])->assertStatus(422);
    }
}
