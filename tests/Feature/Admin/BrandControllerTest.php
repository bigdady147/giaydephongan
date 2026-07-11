<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BrandControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_staff_can_list_brands(): void
    {
        Brand::factory()->count(2)->create();
        $this->actingAsStaff();

        $this->getJson('/api/admin/brands')->assertStatus(200)->assertJsonCount(2);
    }

    public function test_staff_can_create_a_brand(): void
    {
        $this->actingAsStaff();

        $response = $this->postJson('/api/admin/brands', ['name' => 'Clarks']);

        $response->assertStatus(201)->assertJsonPath('brand.slug', 'clarks');
    }

    public function test_creating_a_brand_requires_a_unique_slug(): void
    {
        Brand::factory()->create(['slug' => 'clarks']);
        $this->actingAsStaff();

        $this->postJson('/api/admin/brands', ['name' => 'Other', 'slug' => 'clarks'])
            ->assertStatus(422)->assertJsonValidationErrors(['slug']);
    }

    public function test_staff_can_update_a_brand(): void
    {
        $brand = Brand::factory()->create();
        $this->actingAsStaff();

        $this->putJson("/api/admin/brands/{$brand->id}", ['name' => 'Updated'])
            ->assertStatus(200)->assertJsonPath('brand.name', 'Updated');
    }

    public function test_staff_can_delete_a_brand(): void
    {
        $brand = Brand::factory()->create();
        $this->actingAsStaff();

        $this->deleteJson("/api/admin/brands/{$brand->id}")->assertStatus(200);
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_creating_two_brands_with_same_name_generates_unique_slugs(): void
    {
        $this->actingAsStaff();

        $first = $this->postJson('/api/admin/brands', ['name' => 'Clarks']);
        $second = $this->postJson('/api/admin/brands', ['name' => 'Clarks']);

        $first->assertStatus(201)->assertJsonPath('brand.slug', 'clarks');
        $second->assertStatus(201)->assertJsonPath('brand.slug', 'clarks-2');
    }
}
