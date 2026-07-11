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
