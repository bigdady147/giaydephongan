<?php

namespace Tests\Feature\Storefront;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_posts_ordered_newest_first(): void
    {
        $old = BlogPost::factory()->create(['status' => 'published', 'published_at' => now()->subDays(5)]);
        $new = BlogPost::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
        BlogPost::factory()->create(['status' => 'draft']);

        $response = $this->getJson('/api/blog');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
        $this->assertSame($new->id, $response->json('data.0.id'));
        $this->assertSame($old->id, $response->json('data.1.id'));
    }

    public function test_index_hides_posts_scheduled_in_the_future(): void
    {
        BlogPost::factory()->create(['status' => 'published', 'published_at' => now()->addDay()]);

        $this->getJson('/api/blog')->assertJsonCount(0, 'data');
    }

    public function test_index_filters_by_pillar(): void
    {
        $match = BlogPost::factory()->create(['status' => 'published', 'pillar' => 'bao-quan-giay-da']);
        BlogPost::factory()->create(['status' => 'published', 'pillar' => 'cam-nang-chon-giay']);

        $response = $this->getJson('/api/blog?pillar=bao-quan-giay-da');

        $response->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $match->id);
    }

    public function test_index_rejects_invalid_pillar(): void
    {
        $this->getJson('/api/blog?pillar=khong-hop-le')->assertStatus(422);
    }

    public function test_show_returns_published_post_with_related_same_pillar(): void
    {
        $post = BlogPost::factory()->create(['status' => 'published', 'pillar' => 'giay-theo-dip']);
        $sibling = BlogPost::factory()->create(['status' => 'published', 'pillar' => 'giay-theo-dip']);
        BlogPost::factory()->create(['status' => 'published', 'pillar' => 'cam-nang-chon-giay']);

        $response = $this->getJson("/api/blog/{$post->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $post->id)
            ->assertJsonCount(1, 'related')
            ->assertJsonPath('related.0.id', $sibling->id);
    }

    public function test_show_404s_for_draft_post(): void
    {
        $draft = BlogPost::factory()->create(['status' => 'draft']);

        $this->getJson("/api/blog/{$draft->slug}")->assertStatus(404);
    }

    public function test_show_404s_for_future_scheduled_post(): void
    {
        $scheduled = BlogPost::factory()->create(['status' => 'published', 'published_at' => now()->addDay()]);

        $this->getJson("/api/blog/{$scheduled->slug}")->assertStatus(404);
    }
}
