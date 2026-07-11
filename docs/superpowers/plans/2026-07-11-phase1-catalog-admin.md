# Phase 1 — Catalog Data Model & Admin CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the catalog data model (categories, brands, products, variants, images) from the design spec and a full admin CRUD (API + Nuxt admin UI) on top of it, seeded with the leather-shoe taxonomy already agreed (Oxford/Derby/Loafer/Monk Strap/Chelsea Boot/Boot da/Sandal da).

**Architecture:** Follows `AuthController`'s existing conventions exactly — plain Eloquent models, inline `Validator::make()` in controllers (no FormRequest classes, no service layer), JSON responses shaped `{message, <entity>}`. New controllers live under `App\Http\Controllers\Api\Admin\*` and are registered under a new `/api/admin` route group gated by the `staff-only` Gate (already defined in `AuthServiceProvider`, exercised by Phase 0's `RoleAccessTest`/`AuthGatesTest`). Admin UI pages are new Nuxt pages under `frontend/pages/admin/**` using the `admin` layout and `useApiClient` from Phase 0.

**Tech Stack:** Laravel 10.48 (Eloquent, PHPUnit, `Storage` facade for image uploads), Nuxt 4 + TypeScript, Vitest.

**Prerequisite:** Phase 0 (`docs/superpowers/plans/2026-07-11-phase0-auth-foundation.md`) must be merged first — this plan's route group and admin pages depend on `useApiClient`, `useAuth`, and the `staff-only`/`admin-only` Gates.

## Global Constraints

- Follow `AuthController`'s existing validation style: inline `Validator::make()` per controller method, not FormRequest classes.
- `product.material` enum is exactly `full_grain_leather` / `suede` / `pu_leather` / `other` (design spec §3.1).
- `product.status` enum is exactly `draft` / `published` / `archived` (design spec §3.1).
- Money columns (`base_price`, `sale_price`, `price_override`) are unsigned integers in whole VND — no decimals.
- All new admin routes require `auth:sanctum` + the `staff-only` Gate (admin and staff both allowed), matching design spec §5's "admin, staff" access column for Products/Categories.
- All PHPUnit tests run against SQLite in-memory (per Phase 0 Task 1 — already configured, do not revert).
- All admin UI text is Vietnamese.

---

### Task 1: Migrations, models, and factories for the catalog

**Files:**
- Create: `database/migrations/2026_07_11_000001_create_categories_table.php`
- Create: `database/migrations/2026_07_11_000002_create_brands_table.php`
- Create: `database/migrations/2026_07_11_000003_create_products_table.php`
- Create: `database/migrations/2026_07_11_000004_create_product_images_table.php`
- Create: `database/migrations/2026_07_11_000005_create_product_variants_table.php`
- Create: `app/Models/Category.php`
- Create: `app/Models/Brand.php`
- Create: `app/Models/Product.php`
- Create: `app/Models/ProductImage.php`
- Create: `app/Models/ProductVariant.php`
- Create: `database/factories/CategoryFactory.php`
- Create: `database/factories/BrandFactory.php`
- Create: `database/factories/ProductFactory.php`
- Test: `tests/Feature/Catalog/CatalogModelsTest.php`

**Interfaces:**
- Produces: `Category`, `Brand`, `Product`, `ProductImage`, `ProductVariant` Eloquent models with relationships (`Category::children/parent/products`, `Brand::products`, `Product::category/brand/images/variants`, `ProductVariant::product`, `ProductImage::product`); `CategoryFactory`, `BrandFactory`, `ProductFactory` for use by every later task's tests.

- [ ] **Step 1: Write the migrations**

```php
// database/migrations/2026_07_11_000001_create_categories_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

```php
// database/migrations/2026_07_11_000002_create_brands_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
```

```php
// database/migrations/2026_07_11_000003_create_products_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable();
            $table->longText('description')->nullable();
            $table->enum('material', ['full_grain_leather', 'suede', 'pu_leather', 'other'])->default('full_grain_leather');
            $table->unsignedBigInteger('base_price');
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->string('thumbnail')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->decimal('avg_rating', 2, 1)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

```php
// database/migrations/2026_07_11_000004_create_product_images_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('url');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
```

```php
// database/migrations/2026_07_11_000005_create_product_variants_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('size');
            $table->string('color');
            $table->string('sku')->unique();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedBigInteger('price_override')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
```

- [ ] **Step 2: Write the models**

```php
// app/Models/Category.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'seo_title',
        'seo_description', 'thumbnail', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
```

```php
// app/Models/Brand.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'logo', 'description'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
```

```php
// app/Models/Product.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'description',
        'material', 'base_price', 'sale_price', 'status', 'thumbnail',
        'seo_title', 'seo_description', 'avg_rating', 'reviews_count',
    ];

    protected $casts = [
        'base_price' => 'integer',
        'sale_price' => 'integer',
        'avg_rating' => 'float',
        'reviews_count' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
```

```php
// app/Models/ProductImage.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'url', 'sort_order'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
```

```php
// app/Models/ProductVariant.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'size', 'color', 'sku', 'stock_quantity', 'price_override'];

    protected $casts = [
        'stock_quantity' => 'integer',
        'price_override' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
```

- [ ] **Step 3: Write the factories**

```php
// database/factories/CategoryFactory.php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
```

```php
// database/factories/BrandFactory.php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BrandFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
```

```php
// database/factories/ProductFactory.php
<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'material' => 'full_grain_leather',
            'base_price' => fake()->numberBetween(800000, 3500000),
            'status' => 'published',
        ];
    }
}
```

- [ ] **Step 4: Write a smoke test proving relationships work**

```php
<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_belongs_to_category_and_brand(): void
    {
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $product = Product::factory()->for($category)->create(['brand_id' => $brand->id]);

        $this->assertTrue($product->category->is($category));
        $this->assertTrue($product->brand->is($brand));
    }

    public function test_product_has_many_variants_and_images(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['size' => '41', 'color' => 'Nau', 'sku' => 'SKU-41-NAU']);
        ProductImage::create(['product_id' => $product->id, 'url' => '/storage/products/a.jpg', 'sort_order' => 0]);

        $this->assertCount(1, $product->fresh()->variants);
        $this->assertCount(1, $product->fresh()->images);
    }

    public function test_category_can_have_a_parent_and_children(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->first()->is($child));
    }

    public function test_deleting_a_product_cascades_to_variants_and_images(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'SKU-DEL']);

        $product->delete();

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }
}
```

- [ ] **Step 5: Run migrations and the test**

Run: `php artisan test --filter=CatalogModelsTest`
Expected: 4 tests PASS (this also implicitly runs the 5 new migrations against the SQLite in-memory test DB).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_11_* app/Models/Category.php app/Models/Brand.php app/Models/Product.php app/Models/ProductImage.php app/Models/ProductVariant.php database/factories/CategoryFactory.php database/factories/BrandFactory.php database/factories/ProductFactory.php tests/Feature/Catalog/CatalogModelsTest.php
git commit -m "feat: add catalog data model (categories, brands, products, variants, images)"
```

---

### Task 2: Category admin CRUD

**Files:**
- Create: `app/Http/Controllers/Api/Admin/CategoryController.php`
- Modify: `routes/api.php` (add the `/api/admin` route group — this group is extended by every later task in this plan)
- Test: `tests/Feature/Admin/CategoryControllerTest.php`

**Interfaces:**
- Consumes: `Category` model + `CategoryFactory` (Task 1); `staff-only`/`admin-only` Gates and `UserFactory::admin()/staff()` (Phase 0).
- Produces: `GET/POST /api/admin/categories`, `GET/PUT/DELETE /api/admin/categories/{category}`.

- [ ] **Step 1: Write the failing tests**

```php
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
}
```

- [ ] **Step 2: Run and confirm they fail**

Run: `php artisan test --filter=CategoryControllerTest`
Expected: FAIL — route `/api/admin/categories` doesn't exist yet (404).

- [ ] **Step 3: Implement the controller**

```php
// app/Http/Controllers/Api/Admin/CategoryController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\UniqueSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = $data['slug'] ?? UniqueSlug::make($data['name'], 'categories');

        $category = Category::create($data);

        return response()->json(['message' => 'Category created successfully', 'category' => $category], 201);
    }

    public function show(Category $category)
    {
        return response()->json($category);
    }

    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'parent_id' => ['nullable', 'exists:categories,id', Rule::notIn([$category->id])],
            'description' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $category->update($validator->validated());

        return response()->json(['message' => 'Category updated successfully', 'category' => $category]);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
```

- [ ] **Step 4: Register the `/api/admin` route group**

Add to `routes/api.php`, after the existing `Route::middleware('auth:sanctum')->group(...)` block (do not modify that existing block):

```php
use App\Http\Controllers\Api\Admin\CategoryController;

// Admin catalog management — accessible to both admin and staff (see design spec §5)
Route::middleware(['auth:sanctum', 'can:staff-only'])->prefix('admin')->group(function () {
    Route::apiResource('categories', CategoryController::class);
});
```

- [ ] **Step 5: Run and confirm they pass**

Run: `php artisan test --filter=CategoryControllerTest`
Expected: 7 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Admin/CategoryController.php routes/api.php tests/Feature/Admin/CategoryControllerTest.php
git commit -m "feat: add admin category CRUD API"
```

---

### Task 3: Brand admin CRUD

**Files:**
- Create: `app/Http/Controllers/Api/Admin/BrandController.php`
- Modify: `routes/api.php` (extend the `/api/admin` group added in Task 2)
- Test: `tests/Feature/Admin/BrandControllerTest.php`

**Interfaces:**
- Consumes: `Brand` model + `BrandFactory` (Task 1).
- Produces: `GET/POST /api/admin/brands`, `GET/PUT/DELETE /api/admin/brands/{brand}`.

- [ ] **Step 1: Write the failing tests**

```php
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
}
```

- [ ] **Step 2: Run and confirm they fail**

Run: `php artisan test --filter=BrandControllerTest`
Expected: FAIL — route doesn't exist yet.

- [ ] **Step 3: Implement the controller**

```php
// app/Http/Controllers/Api/Admin/BrandController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Support\UniqueSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index()
    {
        return response()->json(Brand::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:brands,slug',
            'logo' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = $data['slug'] ?? UniqueSlug::make($data['name'], 'brands');

        $brand = Brand::create($data);

        return response()->json(['message' => 'Brand created successfully', 'brand' => $brand], 201);
    }

    public function show(Brand $brand)
    {
        return response()->json($brand);
    }

    public function update(Request $request, Brand $brand)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($brand->id)],
            'logo' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $brand->update($validator->validated());

        return response()->json(['message' => 'Brand updated successfully', 'brand' => $brand]);
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return response()->json(['message' => 'Brand deleted successfully']);
    }
}
```

- [ ] **Step 4: Register the route**

Add inside the existing `/admin` group from Task 2 (`routes/api.php`):

```php
use App\Http\Controllers\Api\Admin\BrandController;

Route::middleware(['auth:sanctum', 'can:staff-only'])->prefix('admin')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
});
```

- [ ] **Step 5: Run and confirm they pass**

Run: `php artisan test --filter=BrandControllerTest`
Expected: 5 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Admin/BrandController.php routes/api.php tests/Feature/Admin/BrandControllerTest.php
git commit -m "feat: add admin brand CRUD API"
```

---

### Task 4: Product admin CRUD (with nested variant creation)

**Files:**
- Create: `app/Http/Controllers/Api/Admin/ProductController.php`
- Modify: `routes/api.php` (extend the `/api/admin` group)
- Test: `tests/Feature/Admin/ProductControllerTest.php`

**Interfaces:**
- Consumes: `Product`, `Category`, `Brand` models + factories (Task 1).
- Produces: `GET/POST /api/admin/products` (paginated list, filterable by `category_id`/`status`), `GET/PUT/DELETE /api/admin/products/{product}`. `store` accepts an optional `variants` array and creates them atomically with the product.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_staff_can_create_a_product_with_variants(): void
    {
        $category = Category::factory()->create();
        $this->actingAsStaff();

        $response = $this->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'Giày Oxford Da Bò Nâu',
            'material' => 'full_grain_leather',
            'base_price' => 1500000,
            'status' => 'published',
            'variants' => [
                ['size' => '40', 'color' => 'Nâu', 'sku' => 'OXF-NAU-40', 'stock_quantity' => 5],
                ['size' => '41', 'color' => 'Nâu', 'sku' => 'OXF-NAU-41', 'stock_quantity' => 3],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('product.slug', 'giay-oxford-da-bo-nau')
            ->assertJsonCount(2, 'product.variants');

        $this->assertDatabaseCount('product_variants', 2);
    }

    public function test_creating_a_product_requires_category_name_and_price(): void
    {
        $this->actingAsStaff();

        $this->postJson('/api/admin/products', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'base_price']);
    }

    public function test_sale_price_must_be_lower_than_base_price(): void
    {
        $category = Category::factory()->create();
        $this->actingAsStaff();

        $this->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'Test',
            'base_price' => 1000000,
            'sale_price' => 1200000,
        ])->assertStatus(422)->assertJsonValidationErrors(['sale_price']);
    }

    public function test_staff_can_list_products_filtered_by_status(): void
    {
        Product::factory()->create(['status' => 'draft']);
        Product::factory()->create(['status' => 'published']);
        $this->actingAsStaff();

        $response = $this->getJson('/api/admin/products?status=published');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_staff_can_update_a_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old']);
        $this->actingAsStaff();

        $this->putJson("/api/admin/products/{$product->id}", ['name' => 'New'])
            ->assertStatus(200)->assertJsonPath('product.name', 'New');
    }

    public function test_staff_can_delete_a_product(): void
    {
        $product = Product::factory()->create();
        $this->actingAsStaff();

        $this->deleteJson("/api/admin/products/{$product->id}")->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_customer_cannot_create_a_product(): void
    {
        $category = Category::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'Test',
            'base_price' => 100000,
        ])->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run and confirm they fail**

Run: `php artisan test --filter=ProductControllerTest`
Expected: FAIL — route doesn't exist yet.

- [ ] **Step 3: Implement the controller**

```php
// app/Http/Controllers/Api/Admin/ProductController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\UniqueSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'variants'])->orderByDesc('id');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $validator = $this->validatorFor($request);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $variants = $data['variants'] ?? [];
        unset($data['variants']);
        $data['slug'] = $data['slug'] ?? UniqueSlug::make($data['name'], 'products');

        $product = DB::transaction(function () use ($data, $variants) {
            $product = Product::create($data);

            foreach ($variants as $variant) {
                $product->variants()->create($variant);
            }

            return $product;
        });

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('variants', 'category', 'brand'),
        ], 201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load('variants', 'images', 'category', 'brand'));
    }

    public function update(Request $request, Product $product)
    {
        $request->mergeIfMissing(['base_price' => $product->base_price]);

        $validator = $this->validatorFor($request, $product->id, sometimes: true);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        unset($data['variants']);

        $product->update($data);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->load('variants', 'category', 'brand'),
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    private function validatorFor(Request $request, ?int $productId = null, bool $sometimes = false)
    {
        $prefix = $sometimes ? 'sometimes|required' : 'required';

        return Validator::make($request->all(), [
            'category_id' => "{$prefix}|exists:categories,id",
            'brand_id' => 'nullable|exists:brands,id',
            'name' => "{$prefix}|string|max:255",
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'material' => "{$prefix}|in:full_grain_leather,suede,pu_leather,other",
            'base_price' => "{$prefix}|integer|min:0",
            'sale_price' => 'nullable|integer|min:0|lt:base_price',
            'status' => "{$prefix}|in:draft,published,archived",
            'thumbnail' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'variants' => 'array',
            'variants.*.size' => 'required_with:variants|string|max:50',
            'variants.*.color' => 'required_with:variants|string|max:100',
            'variants.*.sku' => ['required_with:variants', 'string', 'max:255', 'distinct', Rule::unique('product_variants', 'sku')],
            'variants.*.stock_quantity' => 'required_with:variants|integer|min:0',
            'variants.*.price_override' => 'nullable|integer|min:0',
        ]);
    }
}
```

Note: the `material` and `status` fields use `"{$prefix}|required"` on `store` — since `material`/`status` have DB defaults, relax this if you want optional-with-default create requests; the tests above always pass `status`/`material` explicitly on create, so the stricter rule is intentional here (an admin explicitly choosing these on every create is the desired UX — no accidental drafts).

- [ ] **Step 4: Register the route**

Add inside the `/admin` group (`routes/api.php`):

```php
use App\Http\Controllers\Api\Admin\ProductController;

Route::middleware(['auth:sanctum', 'can:staff-only'])->prefix('admin')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('products', ProductController::class);
});
```

- [ ] **Step 5: Run and confirm they pass**

Run: `php artisan test --filter=ProductControllerTest`
Expected: 7 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Admin/ProductController.php routes/api.php tests/Feature/Admin/ProductControllerTest.php
git commit -m "feat: add admin product CRUD API with nested variant creation"
```

---

### Task 5: Product variants and images sub-resources

**Files:**
- Create: `app/Http/Controllers/Api/Admin/ProductVariantController.php`
- Create: `app/Http/Controllers/Api/Admin/ProductImageController.php`
- Modify: `routes/api.php` (extend the `/api/admin` group)
- Test: `tests/Feature/Admin/ProductVariantControllerTest.php`
- Test: `tests/Feature/Admin/ProductImageControllerTest.php`

**Interfaces:**
- Consumes: `Product`, `ProductVariant`, `ProductImage` models (Task 1).
- Produces: `POST /api/admin/products/{product}/variants`, `PUT/DELETE /api/admin/variants/{variant}`, `POST /api/admin/products/{product}/images`, `DELETE /api/admin/images/{image}`.

**Prerequisite:** run `php artisan storage:link` once locally so `Storage::url()` (public disk) resolves to a real accessible path — this is an environment setup step, not part of the app code.

- [ ] **Step 1: Write the failing tests for variants**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductVariantControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_staff_can_add_a_variant_to_a_product(): void
    {
        $product = Product::factory()->create();
        $this->actingAsStaff();

        $response = $this->postJson("/api/admin/products/{$product->id}/variants", [
            'size' => '42', 'color' => 'Đen', 'sku' => 'SKU-42-DEN', 'stock_quantity' => 10,
        ]);

        $response->assertStatus(201)->assertJsonPath('variant.sku', 'SKU-42-DEN');
        $this->assertDatabaseHas('product_variants', ['product_id' => $product->id, 'sku' => 'SKU-42-DEN']);
    }

    public function test_variant_sku_must_be_unique(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['sku' => 'DUP-SKU']);
        $this->actingAsStaff();

        $this->postJson("/api/admin/products/{$product->id}/variants", [
            'size' => '43', 'color' => 'Đen', 'sku' => 'DUP-SKU', 'stock_quantity' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['sku']);
    }

    public function test_staff_can_update_stock_quantity(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['stock_quantity' => 5, 'sku' => 'STOCK-SKU']);
        $this->actingAsStaff();

        $this->putJson("/api/admin/variants/{$variant->id}", ['stock_quantity' => 20])
            ->assertStatus(200)->assertJsonPath('variant.stock_quantity', 20);
    }

    public function test_staff_can_delete_a_variant(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'DEL-SKU']);
        $this->actingAsStaff();

        $this->deleteJson("/api/admin/variants/{$variant->id}")->assertStatus(200);
        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }
}
```

- [ ] **Step 2: Write the failing tests for images**

```php
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

    public function test_staff_can_delete_a_product_image(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $image = $product->images()->create(['url' => '/storage/products/test.jpg', 'sort_order' => 0]);
        $this->actingAsStaff();

        $this->deleteJson("/api/admin/images/{$image->id}")->assertStatus(200);
        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    }
}
```

- [ ] **Step 3: Run and confirm both files fail**

Run: `php artisan test --filter=ProductVariantControllerTest`
Run: `php artisan test --filter=ProductImageControllerTest`
Expected: both FAIL — routes don't exist yet.

- [ ] **Step 4: Implement `ProductVariantController`**

```php
// app/Http/Controllers/Api/Admin/ProductVariantController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'size' => 'required|string|max:50',
            'color' => 'required|string|max:100',
            'sku' => 'required|string|max:255|unique:product_variants,sku',
            'stock_quantity' => 'required|integer|min:0',
            'price_override' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $variant = $product->variants()->create($validator->validated());

        return response()->json(['message' => 'Variant created successfully', 'variant' => $variant], 201);
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $validator = Validator::make($request->all(), [
            'size' => 'sometimes|required|string|max:50',
            'color' => 'sometimes|required|string|max:100',
            'sku' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($variant->id)],
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'price_override' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $variant->update($validator->validated());

        return response()->json(['message' => 'Variant updated successfully', 'variant' => $variant]);
    }

    public function destroy(ProductVariant $variant)
    {
        $variant->delete();

        return response()->json(['message' => 'Variant deleted successfully']);
    }
}
```

- [ ] **Step 5: Implement `ProductImageController`**

```php
// app/Http/Controllers/Api/Admin/ProductImageController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductImageController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:4096',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $path = $request->file('image')->store('products', 'public');

        $image = $product->images()->create([
            'url' => Storage::url($path),
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        return response()->json(['message' => 'Image uploaded successfully', 'image' => $image], 201);
    }

    public function destroy(ProductImage $image)
    {
        $path = str_replace('/storage/', '', $image->url);
        Storage::disk('public')->delete($path);
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }
}
```

- [ ] **Step 6: Register the routes**

Add inside the `/admin` group (`routes/api.php`):

```php
use App\Http\Controllers\Api\Admin\ProductVariantController;
use App\Http\Controllers\Api\Admin\ProductImageController;

Route::middleware(['auth:sanctum', 'can:staff-only'])->prefix('admin')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('products', ProductController::class);
    Route::post('products/{product}/variants', [ProductVariantController::class, 'store']);
    Route::put('variants/{variant}', [ProductVariantController::class, 'update']);
    Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy']);
    Route::post('products/{product}/images', [ProductImageController::class, 'store']);
    Route::delete('images/{image}', [ProductImageController::class, 'destroy']);
});
```

- [ ] **Step 7: Run and confirm both pass**

Run: `php artisan test --filter=ProductVariantControllerTest` — Expected: 4 tests PASS.
Run: `php artisan test --filter=ProductImageControllerTest` — Expected: 3 tests PASS.

- [ ] **Step 8: Run the full backend suite**

Run: `php artisan test`
Expected: all tests PASS (Phase 0's 21 + this plan's Task 1 (4) + Task 2 (7) + Task 3 (5) + Task 4 (7) + Task 5 (7) = 51 tests).

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Api/Admin/ProductVariantController.php app/Http/Controllers/Api/Admin/ProductImageController.php routes/api.php tests/Feature/Admin/ProductVariantControllerTest.php tests/Feature/Admin/ProductImageControllerTest.php
git commit -m "feat: add admin product variant and image sub-resource APIs"
```

---

### Task 6: Seed the leather-shoe taxonomy

**Files:**
- Create: `database/seeders/CatalogSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

**Interfaces:**
- Consumes: `Category`, `Brand` models (Task 1).
- Produces: 7 seeded categories matching design spec §7, a handful of sample brands — used by Task 9's frontend manual verification and by Phase 2's storefront work later.

- [ ] **Step 1: Write the seeder**

```php
// database/seeders/CatalogSeeder.php
<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Giày Oxford',
            'Giày Derby',
            'Giày Loafer',
            'Giày Monk Strap',
            'Chelsea Boot',
            'Boot Da Nam',
            'Sandal Da Nam',
        ];

        foreach ($categories as $index => $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $index]
            );
        }

        $brands = ['Hồng An', 'Pierre Cardin', 'Clarks', 'GEOX'];

        foreach ($brands as $name) {
            Brand::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
```

- [ ] **Step 2: Register it in `DatabaseSeeder`**

Replace `database/seeders/DatabaseSeeder.php` with:

```php
<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
```

- [ ] **Step 3: Run it against the real dev database and verify**

Run: `php artisan db:seed`
Expected: exits 0.
Run: `php artisan tinker --execute="echo App\Models\Category::count();"`
Expected: prints `7` (or more, if run more than once — `firstOrCreate` makes this idempotent).

- [ ] **Step 4: Commit**

```bash
git add database/seeders/CatalogSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat: seed leather-shoe category taxonomy and sample brands"
```

---

### Task 7: Admin UI — Categories page

**Files:**
- Create: `frontend/pages/admin/categories/index.vue`
- Modify: `frontend/layouts/admin.vue:8-31` (sidebar nav — add links to the 3 new catalog pages this plan introduces)

**Interfaces:**
- Consumes: `useApiClient()` (Phase 0 Task 8).

- [ ] **Step 1: Add nav links for the new catalog pages**

Replace the `<nav class="sidebar-nav">...</nav>` block in `frontend/layouts/admin.vue`:

```html
<nav class="sidebar-nav">
  <NuxtLink to="/admin" class="nav-item">
    <span class="nav-icon">📊</span>
    {{ $t('navigation.dashboard') }}
  </NuxtLink>
  <NuxtLink to="/admin/categories" class="nav-item">
    <span class="nav-icon">🗂️</span>
    Danh mục
  </NuxtLink>
  <NuxtLink to="/admin/brands" class="nav-item">
    <span class="nav-icon">🏷️</span>
    Thương hiệu
  </NuxtLink>
  <NuxtLink to="/admin/products" class="nav-item">
    <span class="nav-icon">👞</span>
    Sản phẩm
  </NuxtLink>
  <NuxtLink to="/admin/users" class="nav-item">
    <span class="nav-icon">👥</span>
    {{ $t('navigation.users') }}
  </NuxtLink>
</nav>
```

(This drops the `/admin/records`, `/admin/services`, `/admin/reports`, `/admin/settings` links, which point at pages that were never built and are out of this project's scope — see design spec, none of those concepts exist in it.)

- [ ] **Step 2: Create the categories admin page**

```html
<!-- frontend/pages/admin/categories/index.vue -->
<template>
  <div class="categories-page">
    <div class="header">
      <h1>Danh mục sản phẩm</h1>
      <BaseButton @click="openCreateForm">+ Thêm danh mục</BaseButton>
    </div>

    <div v-if="loadError" class="general-error">{{ loadError }}</div>

    <table class="data-table">
      <thead>
        <tr>
          <th>Tên</th>
          <th>Slug</th>
          <th>Trạng thái</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="category in categories" :key="category.id">
          <td>{{ category.name }}</td>
          <td>{{ category.slug }}</td>
          <td>{{ category.is_active ? 'Hoạt động' : 'Ẩn' }}</td>
          <td class="row-actions">
            <button @click="openEditForm(category)">Sửa</button>
            <button @click="removeCategory(category)">Xóa</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showForm" class="form-panel">
      <h2>{{ editingId ? 'Sửa danh mục' : 'Thêm danh mục' }}</h2>
      <div v-if="formError" class="general-error">{{ formError }}</div>
      <form @submit.prevent="submitForm">
        <BaseInput v-model="form.name" label="Tên danh mục" required :error="formErrors.name" />
        <BaseInput v-model="form.description" label="Mô tả (SEO)" />
        <div class="form-actions">
          <BaseButton type="submit" :loading="saving">Lưu</BaseButton>
          <button type="button" @click="closeForm">Hủy</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

definePageMeta({ layout: 'admin' })

interface Category {
  id: number
  name: string
  slug: string
  description: string | null
  is_active: boolean
}

const api = useApiClient()

const categories = ref<Category[]>([])
const loadError = ref('')
const showForm = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const formError = ref('')
const formErrors = reactive({ name: '' })

const form = reactive({
  name: '',
  description: ''
})

const loadCategories = async () => {
  loadError.value = ''
  try {
    categories.value = await api.get<Category[]>('/admin/categories')
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải danh mục.'
  }
}

const openCreateForm = () => {
  editingId.value = null
  form.name = ''
  form.description = ''
  formErrors.name = ''
  formError.value = ''
  showForm.value = true
}

const openEditForm = (category: Category) => {
  editingId.value = category.id
  form.name = category.name
  form.description = category.description ?? ''
  formErrors.name = ''
  formError.value = ''
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
}

const submitForm = async () => {
  saving.value = true
  formError.value = ''
  formErrors.name = ''

  try {
    if (editingId.value) {
      await api.put(`/admin/categories/${editingId.value}`, { ...form })
    } else {
      await api.post('/admin/categories', { ...form })
    }
    showForm.value = false
    await loadCategories()
  } catch (err: any) {
    formErrors.name = err.errors?.name?.[0] ?? ''
    formError.value = err.message ?? 'Lưu danh mục thất bại.'
  } finally {
    saving.value = false
  }
}

const removeCategory = async (category: Category) => {
  if (!confirm(`Xóa danh mục "${category.name}"?`)) return
  try {
    await api.del(`/admin/categories/${category.id}`)
    await loadCategories()
  } catch (err: any) {
    loadError.value = err.message ?? 'Xóa danh mục thất bại.'
  }
}

onMounted(loadCategories)
</script>

<style scoped lang="scss">
.categories-page {
  max-width: 1000px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;

  th, td {
    text-align: left;
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
  }
}

.row-actions button {
  margin-right: 8px;
  background: none;
  border: none;
  color: #2563eb;
  cursor: pointer;
}

.form-panel {
  margin-top: 24px;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  max-width: 480px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.general-error {
  background-color: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 12px 16px;
  font-size: 14px;
  margin-bottom: 16px;
}
</style>
```

- [ ] **Step 3: Typecheck**

Run: `cd frontend && npx nuxi typecheck`
Expected: exits 0.

- [ ] **Step 4: Commit**

```bash
git add frontend/pages/admin/categories/index.vue frontend/layouts/admin.vue
git commit -m "feat: add admin categories management page"
```

---

### Task 8: Admin UI — Brands page

**Files:**
- Create: `frontend/pages/admin/brands/index.vue`

**Interfaces:**
- Consumes: `useApiClient()` (Phase 0 Task 8). Structurally mirrors Task 7's categories page against the `/admin/brands` endpoints from Task 3.

- [ ] **Step 1: Create the brands admin page**

```html
<!-- frontend/pages/admin/brands/index.vue -->
<template>
  <div class="brands-page">
    <div class="header">
      <h1>Thương hiệu</h1>
      <BaseButton @click="openCreateForm">+ Thêm thương hiệu</BaseButton>
    </div>

    <div v-if="loadError" class="general-error">{{ loadError }}</div>

    <table class="data-table">
      <thead>
        <tr>
          <th>Tên</th>
          <th>Slug</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="brand in brands" :key="brand.id">
          <td>{{ brand.name }}</td>
          <td>{{ brand.slug }}</td>
          <td class="row-actions">
            <button @click="openEditForm(brand)">Sửa</button>
            <button @click="removeBrand(brand)">Xóa</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showForm" class="form-panel">
      <h2>{{ editingId ? 'Sửa thương hiệu' : 'Thêm thương hiệu' }}</h2>
      <div v-if="formError" class="general-error">{{ formError }}</div>
      <form @submit.prevent="submitForm">
        <BaseInput v-model="form.name" label="Tên thương hiệu" required :error="formErrors.name" />
        <BaseInput v-model="form.description" label="Mô tả" />
        <div class="form-actions">
          <BaseButton type="submit" :loading="saving">Lưu</BaseButton>
          <button type="button" @click="closeForm">Hủy</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

definePageMeta({ layout: 'admin' })

interface Brand {
  id: number
  name: string
  slug: string
  description: string | null
}

const api = useApiClient()

const brands = ref<Brand[]>([])
const loadError = ref('')
const showForm = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const formError = ref('')
const formErrors = reactive({ name: '' })

const form = reactive({
  name: '',
  description: ''
})

const loadBrands = async () => {
  loadError.value = ''
  try {
    brands.value = await api.get<Brand[]>('/admin/brands')
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải thương hiệu.'
  }
}

const openCreateForm = () => {
  editingId.value = null
  form.name = ''
  form.description = ''
  formErrors.name = ''
  formError.value = ''
  showForm.value = true
}

const openEditForm = (brand: Brand) => {
  editingId.value = brand.id
  form.name = brand.name
  form.description = brand.description ?? ''
  formErrors.name = ''
  formError.value = ''
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
}

const submitForm = async () => {
  saving.value = true
  formError.value = ''
  formErrors.name = ''

  try {
    if (editingId.value) {
      await api.put(`/admin/brands/${editingId.value}`, { ...form })
    } else {
      await api.post('/admin/brands', { ...form })
    }
    showForm.value = false
    await loadBrands()
  } catch (err: any) {
    formErrors.name = err.errors?.name?.[0] ?? ''
    formError.value = err.message ?? 'Lưu thương hiệu thất bại.'
  } finally {
    saving.value = false
  }
}

const removeBrand = async (brand: Brand) => {
  if (!confirm(`Xóa thương hiệu "${brand.name}"?`)) return
  try {
    await api.del(`/admin/brands/${brand.id}`)
    await loadBrands()
  } catch (err: any) {
    loadError.value = err.message ?? 'Xóa thương hiệu thất bại.'
  }
}

onMounted(loadBrands)
</script>

<style scoped lang="scss">
.brands-page {
  max-width: 1000px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;

  th, td {
    text-align: left;
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
  }
}

.row-actions button {
  margin-right: 8px;
  background: none;
  border: none;
  color: #2563eb;
  cursor: pointer;
}

.form-panel {
  margin-top: 24px;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  max-width: 480px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.general-error {
  background-color: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 12px 16px;
  font-size: 14px;
  margin-bottom: 16px;
}
</style>
```

- [ ] **Step 2: Typecheck**

Run: `cd frontend && npx nuxi typecheck`
Expected: exits 0.

- [ ] **Step 3: Commit**

```bash
git add frontend/pages/admin/brands/index.vue
git commit -m "feat: add admin brands management page"
```

---

### Task 9: Admin UI — Products page (list + form with variants)

**Files:**
- Create: `frontend/pages/admin/products/index.vue`

**Interfaces:**
- Consumes: `useApiClient()` (Phase 0); `/admin/products`, `/admin/categories`, `/admin/brands` endpoints (Tasks 2-4).

**Scope note:** variant rows are only editable at product-creation time in this page (they're sent as part of `POST /admin/products`, matching `ProductController::store`). Editing an existing product's variants (stock/size/color changes after creation) requires calling the dedicated `PUT /admin/variants/{variant}` / `POST /admin/products/{product}/variants` endpoints from Task 5 — wiring those into the edit form (e.g. a per-row "add/edit variant" UI on the edit screen) is deliberately deferred to keep this task bounded; add it as a follow-up task before Phase 1 is considered done for real inventory operations.

- [ ] **Step 1: Create the products admin page**

```html
<!-- frontend/pages/admin/products/index.vue -->
<template>
  <div class="products-page">
    <div class="header">
      <h1>Sản phẩm</h1>
      <BaseButton @click="openCreateForm">+ Thêm sản phẩm</BaseButton>
    </div>

    <div v-if="loadError" class="general-error">{{ loadError }}</div>

    <table class="data-table">
      <thead>
        <tr>
          <th>Tên</th>
          <th>Danh mục</th>
          <th>Giá</th>
          <th>Trạng thái</th>
          <th>Biến thể</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="product in products" :key="product.id">
          <td>{{ product.name }}</td>
          <td>{{ product.category?.name }}</td>
          <td>{{ product.base_price.toLocaleString('vi-VN') }}đ</td>
          <td>{{ statusLabel(product.status) }}</td>
          <td>{{ product.variants.length }}</td>
          <td class="row-actions">
            <button @click="openEditForm(product)">Sửa</button>
            <button @click="removeProduct(product)">Xóa</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showForm" class="form-panel">
      <h2>{{ editingId ? 'Sửa sản phẩm' : 'Thêm sản phẩm' }}</h2>
      <div v-if="formError" class="general-error">{{ formError }}</div>
      <form @submit.prevent="submitForm">
        <BaseInput v-model="form.name" label="Tên sản phẩm" required :error="formErrors.name" />

        <label class="field-label">Danh mục</label>
        <select v-model.number="form.category_id" required class="native-select">
          <option value="" disabled>Chọn danh mục</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </select>
        <span v-if="formErrors.category_id" class="error-message">{{ formErrors.category_id }}</span>

        <label class="field-label">Thương hiệu (không bắt buộc)</label>
        <select v-model.number="form.brand_id" class="native-select">
          <option :value="null">Không chọn</option>
          <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
        </select>

        <label class="field-label">Chất liệu</label>
        <select v-model="form.material" class="native-select">
          <option value="full_grain_leather">Da bò thật (full-grain)</option>
          <option value="suede">Da lộn</option>
          <option value="pu_leather">Da PU</option>
          <option value="other">Khác</option>
        </select>

        <BaseInput v-model.number="form.base_price" label="Giá gốc (VNĐ)" type="number" required :error="formErrors.base_price" />
        <BaseInput v-model.number="form.sale_price" label="Giá khuyến mãi (không bắt buộc)" type="number" :error="formErrors.sale_price" />

        <label class="field-label">Trạng thái</label>
        <select v-model="form.status" class="native-select">
          <option value="draft">Nháp</option>
          <option value="published">Đã đăng</option>
          <option value="archived">Lưu trữ</option>
        </select>

        <fieldset class="variants-fieldset">
          <legend>Biến thể (size / màu)</legend>
          <div v-for="(variant, index) in form.variants" :key="index" class="variant-row">
            <input v-model="variant.size" placeholder="Size (vd: 41)" required>
            <input v-model="variant.color" placeholder="Màu (vd: Nâu)" required>
            <input v-model="variant.sku" placeholder="SKU" required>
            <input v-model.number="variant.stock_quantity" type="number" placeholder="Tồn kho" required>
            <button type="button" @click="removeVariantRow(index)">Xóa</button>
          </div>
          <button type="button" @click="addVariantRow">+ Thêm biến thể</button>
        </fieldset>

        <div class="form-actions">
          <BaseButton type="submit" :loading="saving">Lưu</BaseButton>
          <button type="button" @click="closeForm">Hủy</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

definePageMeta({ layout: 'admin' })

interface Category { id: number; name: string }
interface Brand { id: number; name: string }
interface Variant { id?: number; size: string; color: string; sku: string; stock_quantity: number }
interface Product {
  id: number
  name: string
  base_price: number
  status: 'draft' | 'published' | 'archived'
  category?: Category
  variants: Variant[]
}

const api = useApiClient()

const products = ref<Product[]>([])
const categories = ref<Category[]>([])
const brands = ref<Brand[]>([])
const loadError = ref('')
const showForm = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const formError = ref('')
const formErrors = reactive({ name: '', category_id: '', base_price: '', sale_price: '' })

const emptyForm = () => ({
  name: '',
  category_id: '' as number | '',
  brand_id: null as number | null,
  material: 'full_grain_leather',
  base_price: 0,
  sale_price: null as number | null,
  status: 'draft' as 'draft' | 'published' | 'archived',
  variants: [] as Variant[]
})

const form = reactive(emptyForm())

const statusLabel = (status: Product['status']) => ({
  draft: 'Nháp',
  published: 'Đã đăng',
  archived: 'Lưu trữ'
})[status]

const loadAll = async () => {
  loadError.value = ''
  try {
    const [productList, categoryList, brandList] = await Promise.all([
      api.get<{ data: Product[] }>('/admin/products'),
      api.get<Category[]>('/admin/categories'),
      api.get<Brand[]>('/admin/brands')
    ])
    products.value = productList.data
    categories.value = categoryList
    brands.value = brandList
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải sản phẩm.'
  }
}

const openCreateForm = () => {
  editingId.value = null
  Object.assign(form, emptyForm())
  showForm.value = true
}

const openEditForm = (product: Product) => {
  editingId.value = product.id
  Object.assign(form, {
    name: product.name,
    category_id: product.category?.id ?? '',
    brand_id: null,
    material: 'full_grain_leather',
    base_price: product.base_price,
    sale_price: null,
    status: product.status,
    variants: []
  })
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
}

const addVariantRow = () => {
  form.variants.push({ size: '', color: '', sku: '', stock_quantity: 0 })
}

const removeVariantRow = (index: number) => {
  form.variants.splice(index, 1)
}

const submitForm = async () => {
  saving.value = true
  formError.value = ''
  formErrors.name = ''
  formErrors.category_id = ''
  formErrors.base_price = ''
  formErrors.sale_price = ''

  try {
    const payload = { ...form, sale_price: form.sale_price || null }
    if (editingId.value) {
      await api.put(`/admin/products/${editingId.value}`, payload)
    } else {
      await api.post('/admin/products', payload)
    }
    showForm.value = false
    await loadAll()
  } catch (err: any) {
    formErrors.name = err.errors?.name?.[0] ?? ''
    formErrors.category_id = err.errors?.category_id?.[0] ?? ''
    formErrors.base_price = err.errors?.base_price?.[0] ?? ''
    formErrors.sale_price = err.errors?.sale_price?.[0] ?? ''
    formError.value = err.message ?? 'Lưu sản phẩm thất bại.'
  } finally {
    saving.value = false
  }
}

const removeProduct = async (product: Product) => {
  if (!confirm(`Xóa sản phẩm "${product.name}"?`)) return
  try {
    await api.del(`/admin/products/${product.id}`)
    await loadAll()
  } catch (err: any) {
    loadError.value = err.message ?? 'Xóa sản phẩm thất bại.'
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.products-page {
  max-width: 1100px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;

  th, td {
    text-align: left;
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
  }
}

.row-actions button {
  margin-right: 8px;
  background: none;
  border: none;
  color: #2563eb;
  cursor: pointer;
}

.form-panel {
  margin-top: 24px;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  max-width: 560px;
}

.field-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  margin: 12px 0 4px;
}

.native-select {
  width: 100%;
  padding: 8px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
}

.variants-fieldset {
  margin-top: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
}

.variant-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr auto;
  gap: 8px;
  margin-bottom: 8px;

  input {
    padding: 6px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
  }
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.general-error {
  background-color: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 12px 16px;
  font-size: 14px;
  margin-bottom: 16px;
}

.error-message {
  color: #dc2626;
  font-size: 12px;
}
</style>
```

- [ ] **Step 2: Typecheck**

Run: `cd frontend && npx nuxi typecheck`
Expected: exits 0.

- [ ] **Step 3: Commit**

```bash
git add frontend/pages/admin/products/index.vue
git commit -m "feat: add admin products management page with variant rows"
```

---

### Task 10: End-to-end verification

**Files:** none (verification only).

**Interfaces:** exercises the full stack built in Tasks 1-9, on top of Phase 0.

- [ ] **Step 1: Run the full backend suite**

Run: `php artisan test`
Expected: all tests PASS (Phase 0's 21 + this plan's 30 = 51 tests).

- [ ] **Step 2: Seed the database**

Run: `php artisan migrate:fresh --seed`
Expected: exits 0; re-creates all tables (auth + catalog) and seeds the 7 categories + 4 brands.

- [ ] **Step 3: Re-create the admin test user (wiped by `migrate:fresh`)**

Run:
```bash
php artisan tinker --execute="App\Models\User::create(['name'=>'Admin','username'=>'admin','email'=>'admin@hongan.vn','password'=>bcrypt('password123'),'role'=>'admin','status'=>'active']);"
```

- [ ] **Step 4: Start both dev servers**

Run (background): `php artisan serve`
Run (background): `cd frontend && npm run dev`

- [ ] **Step 5: Browser walkthrough (use the Playwright browser tools)**

1. Log in at `http://localhost:3000/login` as `admin@hongan.vn` / `password123`.
2. Navigate to `http://localhost:3000/admin/categories`. Confirm the 7 seeded categories (Giày Oxford, Giày Derby, ...) are listed.
3. Create a new category via the form, confirm it appears in the list without a page reload.
4. Navigate to `http://localhost:3000/admin/brands`. Confirm the 4 seeded brands are listed. Create one, confirm it appears.
5. Navigate to `http://localhost:3000/admin/products`. Create a product: pick a category, fill in name/price, add 2 variant rows (different size/color/SKU), submit. Confirm the new product appears in the table with "2" under Biến thể.
6. Edit the product's name, confirm the table updates.
7. Delete the product, confirm it disappears from the table.

- [ ] **Step 6: Report results**

If any step fails, that's a bug in Tasks 1-9 (not a plan gap) — fix it, re-run that task's automated tests, then repeat this task from Step 5.

No commit for this task — it's verification only.
