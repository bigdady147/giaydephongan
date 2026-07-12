<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_pages(): void
    {
        $this->getJson('/api/admin/pages')->assertStatus(401);
    }

    public function test_staff_can_create_page_with_generated_slug(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());

        $response = $this->postJson('/api/admin/pages', [
            'title' => 'Chính sách đổi trả',
            'content' => '<p>Đổi trong 7 ngày.</p>',
        ]);

        $response->assertStatus(201)->assertJsonPath('page.slug', 'chinh-sach-doi-tra');
    }

    public function test_slug_collisions_get_suffixed(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
        Page::factory()->create(['slug' => 'gioi-thieu']);

        $response = $this->postJson('/api/admin/pages', [
            'title' => 'Giới thiệu',
            'content' => '<p>x</p>',
        ]);

        $response->assertStatus(201)->assertJsonPath('page.slug', 'gioi-thieu-2');
    }

    public function test_staff_can_update_and_delete_page(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
        $page = Page::factory()->create();

        $this->putJson("/api/admin/pages/{$page->id}", ['title' => 'Mới', 'is_active' => false])
            ->assertStatus(200)->assertJsonPath('page.is_active', false);

        $this->deleteJson("/api/admin/pages/{$page->id}")->assertStatus(200);
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_store_requires_title_and_content(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());

        $this->postJson('/api/admin/pages', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['title', 'content']]);
    }
}
