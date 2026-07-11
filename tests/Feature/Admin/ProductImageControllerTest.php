<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductImageControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_staff_can_upload_a_product_image(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $this->actingAsStaff();

        $response = $this->postJson("/api/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->image('shoe.jpg'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('product_images', ['product_id' => $product->id]);
    }

    public function test_uploaded_image_url_is_absolute_and_browser_resolvable(): void
    {
        // Storage::fake() normally drops the disk's configured 'url' (it
        // builds a bare fake local disk), which would make this assertion
        // meaningless — explicitly preserve it so the fake behaves like the
        // real 'public' disk for URL generation.
        Storage::fake('public', ['url' => config('filesystems.disks.public.url')]);
        $product = Product::factory()->create();
        $this->actingAsStaff();

        $this->postJson("/api/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->image('shoe.jpg'),
        ])->assertStatus(201);

        $image = $product->images()->firstOrFail();

        // Must be a full absolute URL (scheme + host), not a root-relative
        // path like "/storage/..." — a relative path resolves against
        // whatever origin renders it (e.g. the frontend dev server), not
        // this API's own host, and 404s in the browser.
        $this->assertStringStartsWith(config('app.url'), $image->url);
        $this->assertStringContainsString('/storage/', $image->url);
    }

    public function test_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $this->actingAsStaff();

        $this->postJson("/api/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->create('doc.pdf', 100),
        ])->assertStatus(422)->assertJsonValidationErrors(['image']);
    }

    public function test_staff_can_delete_a_product_image_and_its_file(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $this->actingAsStaff();

        $uploadResponse = $this->postJson("/api/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->image('shoe.jpg'),
        ]);
        $uploadResponse->assertStatus(201);

        $image = $product->images()->firstOrFail();
        $storedPath = ltrim(parse_url($image->url, PHP_URL_PATH) ?? '', '/');
        $storedPath = preg_replace('#^storage/#', '', $storedPath);
        Storage::disk('public')->assertExists($storedPath);

        $this->deleteJson("/api/admin/images/{$image->id}")->assertStatus(200);

        Storage::disk('public')->assertMissing($storedPath);
        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    }
}
