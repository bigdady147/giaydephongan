<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_guest_cannot_list_categories(): void
    {
        $this->getJson('/api/admin/categories')->assertStatus(401);
    }

    public function test_customer_cannot_list_categories(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/categories')->assertStatus(403);
    }

    public function test_staff_can_list_categories(): void
    {
        Category::factory()->count(3)->create();
        $this->actingAsStaff();

        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_staff_can_create_a_category_with_auto_generated_slug(): void
    {
        $this->actingAsStaff();

        $response = $this->postJson('/api/admin/categories', ['name' => 'Giày Oxford Nam']);

        $response->assertStatus(201)->assertJsonPath('category.slug', 'giay-oxford-nam');
        $this->assertDatabaseHas('categories', ['name' => 'Giày Oxford Nam', 'slug' => 'giay-oxford-nam']);
    }

    public function test_creating_a_category_requires_a_name(): void
    {
        $this->actingAsStaff();

        $this->postJson('/api/admin/categories', [])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_staff_can_update_a_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old name']);
        $this->actingAsStaff();

        $response = $this->putJson("/api/admin/categories/{$category->id}", ['name' => 'New name']);

        $response->assertStatus(200)->assertJsonPath('category.name', 'New name');
    }

    public function test_staff_can_delete_a_category(): void
    {
        $category = Category::factory()->create();
        $this->actingAsStaff();

        $this->deleteJson("/api/admin/categories/{$category->id}")->assertStatus(200);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_creating_two_categories_with_same_name_generates_unique_slugs(): void
    {
        $this->actingAsStaff();

        $first = $this->postJson('/api/admin/categories', ['name' => 'Giày Oxford Nam']);
        $second = $this->postJson('/api/admin/categories', ['name' => 'Giày Oxford Nam']);

        $first->assertStatus(201)->assertJsonPath('category.slug', 'giay-oxford-nam');
        $second->assertStatus(201)->assertJsonPath('category.slug', 'giay-oxford-nam-2');
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $category = Category::factory()->create();
        $this->actingAsStaff();

        $this->putJson("/api/admin/categories/{$category->id}", ['parent_id' => $category->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }
}
