<?php

namespace Tests\Feature\Admin;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BlogControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_guest_cannot_manage_blog(): void
    {
        $this->getJson('/api/admin/blog')->assertStatus(401);
    }

    public function test_customer_cannot_manage_blog(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'customer']));

        $this->getJson('/api/admin/blog')->assertStatus(403);
    }

    public function test_staff_can_create_post_with_generated_slug(): void
    {
        $this->actingAsStaff();

        $response = $this->postJson('/api/admin/blog', [
            'title' => 'Cách chọn size giày chuẩn',
            'content' => '<p>Nội dung hướng dẫn.</p>',
            'status' => 'draft',
        ]);

        $response->assertStatus(201)->assertJsonPath('blog_post.slug', 'cach-chon-size-giay-chuan');
    }

    public function test_slug_collisions_get_suffixed(): void
    {
        $this->actingAsStaff();
        BlogPost::factory()->create(['slug' => 'bao-quan-giay-da']);

        $response = $this->postJson('/api/admin/blog', [
            'title' => 'Bảo quản giày da',
            'content' => '<p>x</p>',
            'status' => 'draft',
        ]);

        $response->assertStatus(201)->assertJsonPath('blog_post.slug', 'bao-quan-giay-da-2');
    }

    public function test_store_requires_title_content_and_status(): void
    {
        $this->actingAsStaff();

        $this->postJson('/api/admin/blog', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['title', 'content', 'status']]);
    }

    public function test_store_rejects_invalid_pillar(): void
    {
        $this->actingAsStaff();

        $this->postJson('/api/admin/blog', [
            'title' => 'Test',
            'content' => '<p>x</p>',
            'status' => 'draft',
            'pillar' => 'khong-hop-le',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['pillar']]);
    }

    public function test_publishing_without_explicit_date_sets_published_at_now(): void
    {
        $this->actingAsStaff();

        $response = $this->postJson('/api/admin/blog', [
            'title' => 'Đăng ngay',
            'content' => '<p>x</p>',
            'status' => 'published',
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('blog_post.published_at'));
    }

    public function test_staff_can_upload_thumbnail_on_create(): void
    {
        Storage::fake('public');
        $this->actingAsStaff();

        $response = $this->post('/api/admin/blog', [
            'title' => 'Có ảnh bìa',
            'content' => '<p>x</p>',
            'status' => 'draft',
            'thumbnail' => UploadedFile::fake()->image('cover.jpg'),
        ]);

        $response->assertStatus(201);
        $this->assertStringContainsString('/storage/blog/', $response->json('blog_post.thumbnail'));
    }

    public function test_staff_can_update_and_delete_post(): void
    {
        $this->actingAsStaff();
        $post = BlogPost::factory()->create();

        $this->putJson("/api/admin/blog/{$post->id}", [
            'title' => $post->title,
            'content' => $post->content,
            'status' => 'draft',
        ])->assertStatus(200)->assertJsonPath('blog_post.status', 'draft');

        $this->deleteJson("/api/admin/blog/{$post->id}")->assertStatus(200);
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }

    public function test_update_without_new_thumbnail_keeps_existing_one(): void
    {
        $this->actingAsStaff();
        $post = BlogPost::factory()->create(['thumbnail' => 'http://localhost:8000/storage/blog/existing.jpg']);

        $response = $this->putJson("/api/admin/blog/{$post->id}", [
            'title' => $post->title,
            'content' => $post->content,
            'status' => $post->status,
        ]);

        $response->assertStatus(200)->assertJsonPath('blog_post.thumbnail', $post->thumbnail);
    }
}
