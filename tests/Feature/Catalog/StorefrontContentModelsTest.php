<?php

namespace Tests\Feature\Catalog;

use App\Models\Banner;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontContentModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_banner_currently_active_scope_filters_by_flag_and_time_window(): void
    {
        $live = Banner::factory()->create(['is_active' => true, 'starts_at' => null, 'ends_at' => null]);
        Banner::factory()->create(['is_active' => false]);
        Banner::factory()->create(['is_active' => true, 'starts_at' => now()->addDay()]);
        Banner::factory()->create(['is_active' => true, 'ends_at' => now()->subDay()]);
        $window = Banner::factory()->create(['is_active' => true, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);

        $this->assertEqualsCanonicalizing(
            [$live->id, $window->id],
            Banner::currentlyActive()->pluck('id')->all()
        );
    }

    public function test_page_defaults_to_active_with_boolean_cast(): void
    {
        $page = Page::factory()->create();
        $this->assertTrue($page->fresh()->is_active);
    }

    public function test_product_gains_featured_flag_and_sold_count_defaults(): void
    {
        $product = Product::factory()->create();
        $this->assertFalse($product->fresh()->is_featured);
        $this->assertSame(0, $product->fresh()->sold_count);
    }
}
