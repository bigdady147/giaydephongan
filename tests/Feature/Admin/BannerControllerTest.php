<?php

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BannerControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_guest_cannot_manage_banners(): void
    {
        $this->getJson('/api/admin/banners')->assertStatus(401);
    }

    public function test_customer_cannot_manage_banners(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'customer']));

        $this->getJson('/api/admin/banners')->assertStatus(403);
    }

    public function test_staff_can_create_banner_with_image_upload(): void
    {
        Storage::fake('public');
        $this->actingAsStaff();

        $response = $this->postJson('/api/admin/banners', [
            'image' => UploadedFile::fake()->image('hero.jpg', 1200, 500),
            'position' => 'homepage_hero',
            'link' => '/danh-muc/giay-oxford-nam',
            'sort_order' => 1,
        ]);

        $response->assertStatus(201)->assertJsonPath('banner.position', 'homepage_hero');
        $this->assertStringContainsString('/storage/banners/', $response->json('banner.image'));
    }

    public function test_store_rejects_invalid_position_and_missing_image(): void
    {
        $this->actingAsStaff();

        $this->postJson('/api/admin/banners', ['position' => 'sidebar'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['image', 'position']]);
    }

    public function test_store_rejects_ends_at_before_starts_at(): void
    {
        Storage::fake('public');
        $this->actingAsStaff();

        $this->postJson('/api/admin/banners', [
            'image' => UploadedFile::fake()->image('hero.jpg'),
            'position' => 'homepage_hero',
            'starts_at' => '2026-08-01 00:00:00',
            'ends_at' => '2026-07-01 00:00:00',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['ends_at']]);
    }

    public function test_staff_can_update_banner_without_replacing_image(): void
    {
        $this->actingAsStaff();
        $banner = Banner::factory()->create(['sort_order' => 1]);

        $response = $this->putJson("/api/admin/banners/{$banner->id}", [
            'position' => 'homepage_promo',
            'sort_order' => 5,
        ]);

        $response->assertStatus(200)->assertJsonPath('banner.sort_order', 5);
        $this->assertSame($banner->image, $response->json('banner.image'));
    }

    public function test_destroy_deletes_row_and_disk_file(): void
    {
        Storage::fake('public');
        $this->actingAsStaff();
        $path = UploadedFile::fake()->image('old.jpg')->store('banners', 'public');
        $banner = Banner::factory()->create(['image' => Storage::disk('public')->url($path)]);

        $this->deleteJson("/api/admin/banners/{$banner->id}")->assertStatus(200);

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
