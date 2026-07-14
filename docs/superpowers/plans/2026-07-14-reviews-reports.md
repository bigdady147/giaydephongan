# Reviews + Admin Reports Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Add product reviews (customer-submitted, admin-moderated, feeding `products.avg_rating`/`reviews_count`) and an admin revenue/top-products reporting dashboard — the two remaining gaps between the shipped Phase 4-6 work and the original spec (`docs/superpowers/specs/2026-07-11-shop-system-design.md` §3.4, §4, §5, §8 "Phase 6").

**Architecture:** Reviews follow the existing snapshot/moderation patterns already in the repo (blog `draft/published`, discount codes admin CRUD): a `reviews` table with `pending/approved/rejected` status, a public storefront endpoint to list/submit, and an admin endpoint to moderate. Approving/rejecting/deleting a review recomputes the parent product's cached `avg_rating`/`reviews_count` (same "cached aggregate" pattern already used for those two columns). Reports reuse the existing `orders`/`order_items` tables with plain SQL aggregation — no new tables. Both features are gated the way the spec's §5 role table says: reviews moderation is `staff-only` (same tier as blog/discount-codes), reports are `admin-only` (spec §5 explicitly restricts "Báo cáo & thống kê" to admin).

**Tech Stack:** Laravel 12 (Sanctum, Eloquent, SQLite in tests), Nuxt 4 + TypeScript + Element Plus (admin only) + scoped SCSS (storefront, no Element Plus).

## Global Constraints

- VND prices are integers with no decimals — `round(..., 1)` is only used for `avg_rating` (a 1-decimal rating, not a price).
- Storefront pages (`frontend/pages/san-pham/**`) use scoped SCSS components only — no Element Plus.
- Admin pages (`frontend/pages/admin/**`) use Element Plus (`el-*`) components, matching `frontend/pages/admin/discount-codes/index.vue` and `frontend/pages/admin/orders/index.vue`.
- No Pinia — state lives in composables (`useState`) or is fetched on demand.
- Run `php artisan test` and `cd frontend && npm run test` after every task.
- Role gates already registered in `app/Providers/AuthServiceProvider.php`: `admin-only` (role === admin), `staff-only` (role in [admin, staff]), `active-only` (status === active). Reuse these — do not add new Gates.
- Follow existing controller conventions exactly: `Validator::make(...)->fails()` → `422` with `{message, errors}`; success responses are `{message, ...}` JSON; admin list endpoints return Eloquent's raw `paginate()` payload (`{data, total, current_page, ...}`).

---

### Task 1: Reviews data layer + storefront API

**Files:**
- Create: `database/migrations/2026_07_14_090000_create_reviews_table.php`
- Create: `app/Models/Review.php`
- Create: `database/factories/ReviewFactory.php`
- Modify: `app/Models/Product.php`
- Create: `app/Http/Controllers/Api/Storefront/ReviewController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Storefront/PublicReviewTest.php`

**Interfaces:**
- Produces: `Review` model with `product()`/`user()` `BelongsTo` relations, fillable `['product_id','user_id','rating','comment','status']`, unique `(product_id, user_id)`. `Product::reviews()` `HasMany`. `GET /api/products/{slug}/reviews` (public, paginated, approved-only). `POST /api/products/{slug}/reviews` (auth:sanctum + active-only, body `{rating, comment?}`).
- Consumes: `Product` model (`app/Models/Product.php`), existing `can:active-only` route group pattern in `routes/api.php`.

- [x] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            $table->unique(['product_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
```

Save as `database/migrations/2026_07_14_090000_create_reviews_table.php`.

- [x] **Step 2: Write the Review model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'user_id', 'rating', 'comment', 'status'];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Save as `app/Models/Review.php`.

- [x] **Step 3: Write the ReviewFactory**

```php
<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence(),
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }
}
```

Save as `database/factories/ReviewFactory.php`.

- [x] **Step 4: Add the `reviews()` relation to Product**

In `app/Models/Product.php`, add this method inside the `Product` class, after `variants()`:

```php
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
```

- [x] **Step 5: Write the failing storefront tests**

```php
<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_approved_reviews(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        Review::factory()->for($product)->approved()->create(['comment' => 'Rất tốt']);
        Review::factory()->for($product)->create(['comment' => 'Chờ duyệt']);
        Review::factory()->for($product)->rejected()->create(['comment' => 'Bị từ chối']);

        $response = $this->getJson("/api/products/{$product->slug}/reviews");

        $response->assertStatus(200)->assertJsonCount(1, 'data');
        $this->assertEquals('Rất tốt', $response->json('data.0.comment'));
    }

    public function test_guest_cannot_submit_review(): void
    {
        $product = Product::factory()->create(['status' => 'published']);

        $this->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])->assertStatus(401);
    }

    public function test_customer_can_submit_review(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson("/api/products/{$product->slug}/reviews", [
            'rating' => 4,
            'comment' => 'Giày đẹp, đóng gói chắc chắn',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'rating' => 4,
            'status' => 'pending',
        ]);
    }

    public function test_customer_cannot_submit_review_twice_for_same_product(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        $user = User::factory()->create();
        Review::factory()->for($product)->for($user)->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/products/{$product->slug}/reviews", ['rating' => 3])
            ->assertStatus(422);
    }

    public function test_store_requires_rating_between_1_and_5(): void
    {
        $product = Product::factory()->create(['status' => 'published']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/products/{$product->slug}/reviews", ['rating' => 6])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['rating']]);
    }
}
```

Save as `tests/Feature/Storefront/PublicReviewTest.php`.

- [x] **Step 6: Run the tests to verify they fail**

Run: `php artisan test --filter=PublicReviewTest`
Expected: FAIL — route `/api/products/{slug}/reviews` does not exist yet (404s), class `App\Http\Controllers\Api\Storefront\ReviewController` not found.

- [x] **Step 7: Write the ReviewController**

```php
<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index(string $slug)
    {
        $product = Product::where('slug', $slug)->where('status', 'published')->firstOrFail();

        $reviews = $product->reviews()
            ->where('status', 'approved')
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($reviews);
    }

    public function store(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)->where('status', 'published')->firstOrFail();

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        if (Review::where('product_id', $product->id)->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Bạn đã đánh giá sản phẩm này rồi.'], 422);
        }

        $data = $validator->validated();

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Cảm ơn bạn đã gửi đánh giá, đánh giá sẽ hiển thị sau khi được duyệt.',
            'review' => $review,
        ], 201);
    }
}
```

Save as `app/Http/Controllers/Api/Storefront/ReviewController.php`.

- [x] **Step 8: Wire the routes**

In `routes/api.php`, add the import near the other Storefront imports (after the `StorefrontMetaController` import):

```php
use App\Http\Controllers\Api\Storefront\ReviewController as StorefrontReviewController;
```

Add the public GET route in the "Public storefront API" block, after the `/products/{slug}/availability` line:

```php
Route::get('/products/{slug}/reviews', [StorefrontReviewController::class, 'index']);
```

Add the authenticated POST route inside the existing `Route::middleware('can:active-only')->group(function () { ... })` block (the one already containing `/user/orders`, `/user/loyalty`), after the `/user/loyalty` line:

```php
        Route::post('/products/{slug}/reviews', [StorefrontReviewController::class, 'store']);
```

- [x] **Step 9: Run the migration and tests**

Run: `php artisan migrate`
Expected: `2026_07_14_090000_create_reviews_table` migrated successfully.

Run: `php artisan test --filter=PublicReviewTest`
Expected: PASS (5 tests).

- [x] **Step 10: Commit**

```bash
git add database/migrations/2026_07_14_090000_create_reviews_table.php app/Models/Review.php app/Models/Product.php database/factories/ReviewFactory.php app/Http/Controllers/Api/Storefront/ReviewController.php routes/api.php tests/Feature/Storefront/PublicReviewTest.php
git commit -m "feat: add product reviews table and public submit/list API"
```

---

### Task 2: Admin reviews moderation API

**Files:**
- Create: `app/Http/Controllers/Api/Admin/ReviewController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Admin/ReviewControllerTest.php`

**Interfaces:**
- Consumes: `Review` model, `Product` model (both from Task 1).
- Produces: `GET /api/admin/reviews?status=` (staff-only, paginated, filterable), `PATCH /api/admin/reviews/{review}/status` (body `{status: approved|rejected}`), `DELETE /api/admin/reviews/{review}`. All three recompute `Product.avg_rating`/`reviews_count` from approved reviews whenever a review's approved-status changes.

- [x] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
    }

    public function test_guest_cannot_manage_reviews(): void
    {
        $this->getJson('/api/admin/reviews')->assertStatus(401);
    }

    public function test_customer_cannot_manage_reviews(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/reviews')->assertStatus(403);
    }

    public function test_staff_can_list_and_filter_reviews_by_status(): void
    {
        $this->actingAsStaff();
        $product = Product::factory()->create();
        Review::factory()->for($product)->approved()->create();
        Review::factory()->for($product)->create();

        $this->getJson('/api/admin/reviews')->assertStatus(200)->assertJsonCount(2, 'data');
        $this->getJson('/api/admin/reviews?status=approved')->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_approving_a_review_recomputes_product_rating(): void
    {
        $this->actingAsStaff();
        $product = Product::factory()->create(['avg_rating' => 0, 'reviews_count' => 0]);
        $review = Review::factory()->for($product)->create(['rating' => 5]);
        Review::factory()->for($product)->approved()->create(['rating' => 3]);

        $this->patchJson("/api/admin/reviews/{$review->id}/status", ['status' => 'approved'])
            ->assertStatus(200);

        $product->refresh();
        $this->assertEquals(2, $product->reviews_count);
        $this->assertEquals(4.0, $product->avg_rating);
    }

    public function test_rejecting_a_review_excludes_it_from_rating(): void
    {
        $this->actingAsStaff();
        $product = Product::factory()->create();
        $review = Review::factory()->for($product)->approved()->create(['rating' => 5]);

        $this->patchJson("/api/admin/reviews/{$review->id}/status", ['status' => 'rejected'])
            ->assertStatus(200);

        $product->refresh();
        $this->assertEquals(0, $product->reviews_count);
        $this->assertEquals(0.0, $product->avg_rating);
    }

    public function test_deleting_an_approved_review_recomputes_product_rating(): void
    {
        $this->actingAsStaff();
        $product = Product::factory()->create();
        $review = Review::factory()->for($product)->approved()->create(['rating' => 5]);

        $this->deleteJson("/api/admin/reviews/{$review->id}")->assertStatus(200);

        $product->refresh();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertEquals(0, $product->reviews_count);
    }
}
```

Save as `tests/Feature/Admin/ReviewControllerTest.php`.

- [x] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=ReviewControllerTest`
Expected: FAIL — `/api/admin/reviews` routes don't exist yet.

- [x] **Step 3: Write the admin ReviewController**

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['product:id,name,slug', 'user:id,name'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->paginate(15));
    }

    public function updateStatus(Request $request, Review $review)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $review->update(['status' => $validator->validated()['status']]);
        $this->recomputeProductStats($review->product);

        return response()->json(['message' => 'Đã cập nhật trạng thái đánh giá', 'review' => $review]);
    }

    public function destroy(Review $review)
    {
        $product = $review->product;
        $review->delete();
        $this->recomputeProductStats($product);

        return response()->json(['message' => 'Đã xóa đánh giá']);
    }

    private function recomputeProductStats(Product $product): void
    {
        $approved = Review::where('product_id', $product->id)->where('status', 'approved');

        $product->update([
            'reviews_count' => $approved->count(),
            'avg_rating' => round((float) $approved->avg('rating'), 1),
        ]);
    }
}
```

Save as `app/Http/Controllers/Api/Admin/ReviewController.php`.

- [x] **Step 4: Wire the routes**

In `routes/api.php`, add the import after the `DiscountCodeController` import:

```php
use App\Http\Controllers\Api\Admin\ReviewController;
```

Add these lines inside the existing `Route::middleware(['auth:sanctum', 'can:active-only', 'can:staff-only'])->prefix('admin')->group(...)` block, after the `discount-codes` line:

```php
    Route::get('reviews', [ReviewController::class, 'index']);
    Route::patch('reviews/{review}/status', [ReviewController::class, 'updateStatus']);
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);
```

- [x] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=ReviewControllerTest`
Expected: PASS (6 tests).

- [x] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Admin/ReviewController.php routes/api.php tests/Feature/Admin/ReviewControllerTest.php
git commit -m "feat: add admin review moderation API with rating recomputation"
```

---

### Task 3: Admin reports API (revenue + top products)

**Files:**
- Create: `app/Http/Controllers/Api/Admin/ReportController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Admin/ReportControllerTest.php`

**Interfaces:**
- Consumes: `Order` model (`app/Models/Order.php`), `OrderItem` model (`app/Models/OrderItem.php`).
- Produces: `GET /api/admin/reports/revenue?from=&to=` (admin-only) → `{from, to, total_revenue, total_orders, daily: [{date, revenue, orders_count}]}`. `GET /api/admin/reports/top-products?from=&to=&limit=` (admin-only) → `{from, to, products: [{name, quantity_sold, revenue}]}`. Both exclude `status = 'cancelled'` orders and default to the last 30 days.

- [x] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_reports(): void
    {
        $this->getJson('/api/admin/reports/revenue')->assertStatus(401);
    }

    public function test_staff_cannot_view_reports(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());

        $this->getJson('/api/admin/reports/revenue')->assertStatus(403);
    }

    public function test_admin_can_view_revenue_grouped_by_day_excluding_cancelled(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $today = now()->toDateString();
        Order::factory()->create(['status' => 'delivered', 'total' => 100000])
            ->forceFill(['created_at' => $today])->save();
        Order::factory()->create(['status' => 'confirmed', 'total' => 200000])
            ->forceFill(['created_at' => $today])->save();
        Order::factory()->create(['status' => 'cancelled', 'total' => 999999])
            ->forceFill(['created_at' => $today])->save();

        $response = $this->getJson('/api/admin/reports/revenue');

        $response->assertStatus(200)
            ->assertJsonPath('total_revenue', 300000.0)
            ->assertJsonPath('total_orders', 2)
            ->assertJsonPath('daily.0.date', $today)
            ->assertJsonPath('daily.0.revenue', 300000.0);
    }

    public function test_admin_can_view_top_products_ranked_by_quantity(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $order = Order::factory()->create(['status' => 'delivered']);
        $order->items()->create([
            'product_name_snapshot' => 'Giày Oxford Nâu',
            'variant_snapshot' => 'Size 41 - Nâu',
            'price' => 1000000,
            'quantity' => 5,
            'subtotal' => 5000000,
        ]);
        $order->items()->create([
            'product_name_snapshot' => 'Giày Derby Đen',
            'variant_snapshot' => 'Size 40 - Đen',
            'price' => 900000,
            'quantity' => 2,
            'subtotal' => 1800000,
        ]);

        $cancelledOrder = Order::factory()->create(['status' => 'cancelled']);
        $cancelledOrder->items()->create([
            'product_name_snapshot' => 'Giày Loafer Xám',
            'variant_snapshot' => 'Size 42 - Xám',
            'price' => 500000,
            'quantity' => 100,
            'subtotal' => 50000000,
        ]);

        $response = $this->getJson('/api/admin/reports/top-products');

        $response->assertStatus(200)->assertJsonPath('products.0.name', 'Giày Oxford Nâu');
        $this->assertCount(2, $response->json('products'));
    }
}
```

Save as `tests/Feature/Admin/ReportControllerTest.php`.

- [x] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=ReportControllerTest`
Expected: FAIL — `/api/admin/reports/*` routes don't exist yet.

- [x] **Step 3: Write the ReportController**

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function revenue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $from = $request->filled('from') ? $request->input('from') : now()->subDays(29)->toDateString();
        $to = $request->filled('to') ? $request->input('to') : now()->toDateString();

        $baseQuery = fn () => Order::where('status', '!=', 'cancelled')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $daily = $baseQuery()
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'total_revenue' => (float) $baseQuery()->sum('total'),
            'total_orders' => $baseQuery()->count(),
            'daily' => $daily,
        ]);
    }

    public function topProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $from = $request->filled('from') ? $request->input('from') : now()->subDays(29)->toDateString();
        $to = $request->filled('to') ? $request->input('to') : now()->toDateString();
        $limit = (int) ($request->input('limit') ?? 10);

        $products = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereDate('orders.created_at', '>=', $from)
            ->whereDate('orders.created_at', '<=', $to)
            ->selectRaw('order_items.product_name_snapshot as name, SUM(order_items.quantity) as quantity_sold, SUM(order_items.subtotal) as revenue')
            ->groupBy('order_items.product_name_snapshot')
            ->orderByDesc('quantity_sold')
            ->limit($limit)
            ->get();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'products' => $products,
        ]);
    }
}
```

Save as `app/Http/Controllers/Api/Admin/ReportController.php`.

- [x] **Step 4: Wire the routes**

In `routes/api.php`, add a **new** route group after the existing staff-only admin block (do not put this inside the staff-only block — reports are admin-only per spec §5):

```php
// Admin-only reports (see design spec §5 — reports restricted to admin role, not staff)
Route::middleware(['auth:sanctum', 'can:active-only', 'can:admin-only'])->prefix('admin')->group(function () {
    Route::get('reports/revenue', [\App\Http\Controllers\Api\Admin\ReportController::class, 'revenue']);
    Route::get('reports/top-products', [\App\Http\Controllers\Api\Admin\ReportController::class, 'topProducts']);
});
```

- [x] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=ReportControllerTest`
Expected: PASS (4 tests).

- [x] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Admin/ReportController.php routes/api.php tests/Feature/Admin/ReportControllerTest.php
git commit -m "feat: add admin-only revenue and top-products reports API"
```

---

### Task 4: Frontend — PDP reviews section

**Files:**
- Modify: `frontend/types/storefront.ts`
- Modify: `frontend/pages/san-pham/[slug].vue`
- Modify: `frontend/locales/vi.json`
- Modify: `frontend/locales/en.json`

**Interfaces:**
- Consumes: `GET /api/products/{slug}/reviews`, `POST /api/products/{slug}/reviews` (Task 1), `useAuth().isAuthenticated`, `useApiClient()` (both existing composables).
- Produces: `ReviewInfo` type used by the admin reviews page is NOT shared (admin page defines its own inline interface, matching the existing convention in `frontend/pages/admin/orders/index.vue` where `Order` is redefined per-page).

- [x] **Step 1: Add rating fields and ReviewInfo to storefront types**

In `frontend/types/storefront.ts`, update `ProductDetail` (currently `export interface ProductDetail extends CardProduct { ... }`) to add two fields, and add a new `ReviewInfo` interface after it:

```typescript
export interface ProductDetail extends CardProduct {
  description: string
  material: 'full_grain_leather' | 'suede' | 'pu_leather' | 'other'
  seo_title: string | null
  seo_description: string | null
  avg_rating: number
  reviews_count: number
  images: ProductImageInfo[]
  variants: VariantInfo[]
  category: CategoryInfo | null
  brand: BrandInfo | null
  related: CardProduct[]
}

export interface ReviewInfo {
  id: number
  rating: number
  comment: string | null
  created_at: string
  user: { id: number; name: string }
}
```

- [x] **Step 2: Add i18n keys**

In `frontend/locales/vi.json`, after the `"recentlyViewed": "Đã xem gần đây",` line, add:

```json
      "reviews": "Đánh giá",
      "reviewsEmpty": "Chưa có đánh giá nào cho sản phẩm này.",
      "reviewRatingLabel": "Đánh giá của bạn",
      "reviewPlaceholder": "Chia sẻ cảm nhận của bạn về sản phẩm (không bắt buộc)",
      "reviewSubmit": "Gửi đánh giá",
      "reviewSubmitting": "Đang gửi...",
      "reviewThanks": "Cảm ơn bạn đã đánh giá! Đánh giá sẽ hiển thị sau khi được duyệt.",
      "reviewLoginPrompt": "để gửi đánh giá sản phẩm."
```

In `frontend/locales/en.json`, after the `"recentlyViewed": "Recently viewed",` line, add:

```json
    "reviews": "Reviews",
    "reviewsEmpty": "No reviews yet for this product.",
    "reviewRatingLabel": "Your rating",
    "reviewPlaceholder": "Share your thoughts about this product (optional)",
    "reviewSubmit": "Submit review",
    "reviewSubmitting": "Submitting...",
    "reviewThanks": "Thanks for your review! It will show up once approved.",
    "reviewLoginPrompt": "to submit a review."
```

- [x] **Step 3: Add the reviews section to the PDP template**

In `frontend/pages/san-pham/[slug].vue`, insert this new `<section>` right after the `<section v-if="product.related.length">...</section>` block and before `<ClientOnly>` (the recently-viewed block):

```html
    <section class="pdp-reviews">
      <SectionHeading :title="`${$t('storefront.reviews')} (${product.reviews_count})`" />

      <div class="reviews-summary">
        <span class="reviews-score">★ {{ product.avg_rating.toFixed(1) }}</span>
        <span class="reviews-sub">{{ product.reviews_count }} đánh giá từ khách hàng</span>
      </div>

      <ClientOnly>
        <form v-if="isAuthenticated && !reviewSubmitted" class="review-form" @submit.prevent="submitReview">
          <label class="review-form-label">{{ $t('storefront.reviewRatingLabel') }}</label>
          <select v-model.number="reviewForm.rating" class="review-form-select">
            <option v-for="n in [5, 4, 3, 2, 1]" :key="n" :value="n">{{ n }} sao</option>
          </select>
          <textarea
            v-model="reviewForm.comment"
            class="review-form-textarea"
            rows="3"
            :placeholder="$t('storefront.reviewPlaceholder')"
          />
          <p v-if="reviewError" class="review-form-error">{{ reviewError }}</p>
          <button type="submit" class="review-form-submit" :disabled="reviewSubmitting">
            {{ reviewSubmitting ? $t('storefront.reviewSubmitting') : $t('storefront.reviewSubmit') }}
          </button>
        </form>
        <p v-else-if="reviewSubmitted" class="review-form-thanks">{{ $t('storefront.reviewThanks') }}</p>
        <p v-else class="review-form-login">
          <NuxtLink to="/login">{{ $t('auth.login') }}</NuxtLink> {{ $t('storefront.reviewLoginPrompt') }}
        </p>
      </ClientOnly>

      <div v-if="reviews.length" class="review-list">
        <div v-for="review in reviews" :key="review.id" class="review-item">
          <div class="review-item-head">
            <strong>{{ review.user.name }}</strong>
            <span class="review-item-rating">{{ '★'.repeat(review.rating) }}{{ '☆'.repeat(5 - review.rating) }}</span>
          </div>
          <p v-if="review.comment" class="review-item-comment">{{ review.comment }}</p>
          <span class="review-item-date">{{ formatReviewDate(review.created_at) }}</span>
        </div>
      </div>
      <p v-else class="review-list-empty">{{ $t('storefront.reviewsEmpty') }}</p>
    </section>

```

> Note: check `frontend/locales/vi.json` for the exact key used for the login link label (likely `auth.login`); if that key doesn't exist, replace `{{ $t('auth.login') }}` with the literal string `Đăng nhập`.

- [x] **Step 4: Add reviews state and logic to the script**

In `frontend/pages/san-pham/[slug].vue`, update the import line to include `ReviewInfo`:

```typescript
import type { AvailabilityInfo, ProductDetail, ReviewInfo } from '~/types/storefront'
```

After the line `const { addItem } = useCart()`, add:

```typescript
const { isAuthenticated } = useAuth()
const apiClient = useApiClient()

const reviews = ref<ReviewInfo[]>([])
const reviewForm = reactive({ rating: 5, comment: '' })
const reviewSubmitting = ref(false)
const reviewSubmitted = ref(false)
const reviewError = ref('')

const loadReviews = async () => {
  try {
    const res = await $fetch<{ data: ReviewInfo[] }>(`${api}/products/${slug}/reviews`)
    reviews.value = res.data
  } catch {
    reviews.value = []
  }
}

const submitReview = async () => {
  reviewSubmitting.value = true
  reviewError.value = ''
  try {
    await apiClient.post(`/products/${slug}/reviews`, {
      rating: reviewForm.rating,
      comment: reviewForm.comment || null
    })
    reviewSubmitted.value = true
    await loadReviews()
  } catch (err: any) {
    reviewError.value = err.message ?? 'Gửi đánh giá thất bại.'
  } finally {
    reviewSubmitting.value = false
  }
}

const formatReviewDate = (val: string) => new Date(val).toLocaleDateString('vi-VN')
```

In the existing `onMounted(async () => { ... })` block (the one that fetches availability), add `loadReviews()` to the `Promise` work — insert this line right after the `pushRecent({...})` call and before the `try { const fresh = ... }` block:

```typescript
  loadReviews()
```

(Fire-and-forget is fine here — reviews are supplementary content, not blocking price/stock refresh.)

- [x] **Step 5: Add styles**

In `frontend/pages/san-pham/[slug].vue`, inside the `<style scoped lang="scss">` block, add after `.pdp-toast { ... }`:

```scss
.pdp-reviews { margin-top: 40px; }
.reviews-summary {
  display: flex; align-items: baseline; gap: 10px; margin-bottom: 16px;
  .reviews-score { font-size: 22px; font-weight: 700; color: $sf-color-accent; }
  .reviews-sub { font-size: 13px; color: $sf-color-muted; }
}
.review-form {
  display: flex; flex-direction: column; gap: 10px; max-width: 480px;
  border: 1px solid $sf-color-border; border-radius: $sf-radius; padding: 16px; margin-bottom: 24px;
  .review-form-label { font-weight: 600; font-size: 14px; }
  .review-form-select, .review-form-textarea {
    border: 1px solid $sf-color-border; border-radius: 6px; padding: 8px 10px; font-size: 14px; font-family: inherit;
  }
  .review-form-error { color: #dc2626; font-size: 13px; margin: 0; }
  .review-form-submit {
    align-self: flex-start; border: 0; border-radius: 8px; padding: 10px 20px; font-weight: 700;
    background: $sf-color-accent; color: #fff; cursor: pointer;
    &:disabled { opacity: 0.6; cursor: not-allowed; }
    &:hover:not(:disabled) { background: $sf-color-accent-dark; }
  }
}
.review-form-thanks { color: #16a34a; font-weight: 600; margin-bottom: 24px; }
.review-form-login { font-size: 14px; margin-bottom: 24px; a { color: $sf-color-accent; font-weight: 600; } }
.review-list { display: flex; flex-direction: column; gap: 16px; }
.review-item {
  border-bottom: 1px solid $sf-color-border; padding-bottom: 16px;
  .review-item-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
  .review-item-rating { color: $sf-color-accent; letter-spacing: 1px; }
  .review-item-comment { font-size: 14px; color: #333; margin-bottom: 6px; }
  .review-item-date { font-size: 12px; color: $sf-color-muted; }
}
.review-list-empty { color: $sf-color-muted; font-size: 14px; }
```

- [x] **Step 6: Run frontend tests and start the dev server to manually verify**

Run: `cd frontend && npm run test -- --run`
Expected: PASS (still 23 tests — this page has no dedicated unit tests, matching the existing convention that `.vue` pages aren't unit-tested).

Run: `cd frontend && npm run dev` (in background), then open a product detail page (`/san-pham/{any-published-slug}`) in a browser and confirm:
- The reviews section renders with "0.0" rating and the empty-state message when there are no reviews.
- Logging in as a customer shows the review form; submitting it shows the thank-you message.

- [x] **Step 7: Commit**

```bash
git add frontend/types/storefront.ts frontend/pages/san-pham/[slug].vue frontend/locales/vi.json frontend/locales/en.json
git commit -m "feat: add product reviews display and submission to PDP"
```

---

### Task 5: Frontend — admin reviews moderation page

**Files:**
- Create: `frontend/pages/admin/reviews/index.vue`
- Modify: `frontend/layouts/admin.vue`

**Interfaces:**
- Consumes: `GET /admin/reviews`, `PATCH /admin/reviews/{id}/status`, `DELETE /admin/reviews/{id}` (Task 2), `useApiClient()`.

- [x] **Step 1: Create the admin reviews page**

```vue
<template>
  <div class="reviews-admin-page">
    <div class="page-header">
      <h1>Duyệt đánh giá</h1>
    </div>

    <el-alert v-if="error" :title="error" type="error" show-icon class="page-alert" />

    <el-radio-group v-model="filterStatus" @change="loadAll">
      <el-radio-button label="">Tất cả</el-radio-button>
      <el-radio-button label="pending">Chờ duyệt</el-radio-button>
      <el-radio-button label="approved">Đã duyệt</el-radio-button>
      <el-radio-button label="rejected">Đã từ chối</el-radio-button>
    </el-radio-group>

    <el-table :data="reviews" v-loading="loading" stripe style="width: 100%; margin-top: 16px;">
      <el-table-column label="Sản phẩm" width="220">
        <template #default="{ row }">{{ (row as Review).product.name }}</template>
      </el-table-column>
      <el-table-column label="Khách hàng" width="160">
        <template #default="{ row }">{{ (row as Review).user.name }}</template>
      </el-table-column>
      <el-table-column label="Đánh giá" width="110">
        <template #default="{ row }">{{ '★'.repeat((row as Review).rating) }}</template>
      </el-table-column>
      <el-table-column prop="comment" label="Nhận xét" />
      <el-table-column label="Trạng thái" width="120" align="center">
        <template #default="{ row }">
          <el-tag :type="statusTagType((row as Review).status)">{{ statusLabel((row as Review).status) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="" width="220" align="center">
        <template #default="{ row }">
          <el-button v-if="(row as Review).status !== 'approved'" link type="success" @click="setStatus(row as Review, 'approved')">Duyệt</el-button>
          <el-button v-if="(row as Review).status !== 'rejected'" link type="warning" @click="setStatus(row as Review, 'rejected')">Từ chối</el-button>
          <el-button link type="danger" @click="removeReview(row as Review)">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="pagination-container" style="margin-top: 16px; display: flex; justify-content: flex-end;">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        layout="prev, pager, next"
        :total="totalRows"
        @current-change="loadAll"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

definePageMeta({ layout: 'admin' })

interface Review {
  id: number
  rating: number
  comment: string | null
  status: 'pending' | 'approved' | 'rejected'
  created_at: string
  product: { id: number; name: string; slug: string }
  user: { id: number; name: string }
}

const api = useApiClient()

const reviews = ref<Review[]>([])
const loading = ref(true)
const error = ref('')
const filterStatus = ref('')
const currentPage = ref(1)
const pageSize = ref(15)
const totalRows = ref(0)

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    let url = `/admin/reviews?page=${currentPage.value}`
    if (filterStatus.value) {
      url += `&status=${filterStatus.value}`
    }
    const res = await api.get<{ data: Review[]; total: number; current_page: number }>(url)
    reviews.value = res.data
    totalRows.value = res.total
    currentPage.value = res.current_page
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải danh sách đánh giá.'
  } finally {
    loading.value = false
  }
}

const statusLabel = (status: Review['status']) => ({
  pending: 'Chờ duyệt',
  approved: 'Đã duyệt',
  rejected: 'Đã từ chối'
})[status]

const statusTagType = (status: Review['status']) => ({
  pending: 'info',
  approved: 'success',
  rejected: 'danger'
})[status] as 'info' | 'success' | 'danger'

const setStatus = async (review: Review, status: 'approved' | 'rejected') => {
  try {
    await api.patch(`/admin/reviews/${review.id}/status`, { status })
    ElMessage.success('Đã cập nhật trạng thái đánh giá')
    await loadAll()
  } catch (err: any) {
    ElMessage.error(err.message ?? 'Cập nhật thất bại.')
  }
}

const removeReview = async (review: Review) => {
  try {
    await ElMessageBox.confirm('Xóa đánh giá này?', 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/reviews/${review.id}`)
    ElMessage.success('Đã xóa đánh giá')
    await loadAll()
  } catch (err: any) {
    ElMessage.error(err.message ?? 'Xóa thất bại.')
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.reviews-admin-page { max-width: 1200px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-alert { margin-bottom: 16px; }
</style>
```

Save as `frontend/pages/admin/reviews/index.vue`.

- [x] **Step 2: Add the sidebar link**

In `frontend/layouts/admin.vue`, add `ChatDotSquare` to the icon import line:

```typescript
import { Odometer, Collection, PriceTag, Goods, User, ArrowDown, Picture, Document, Setting, List, Notebook, Discount, ChatDotSquare, TrendCharts } from '@element-plus/icons-vue'
```

(This also imports `TrendCharts`, used by Task 6.)

Add a new `<el-menu-item>` after the `/admin/discount-codes` item and before `/admin/settings`:

```html
        <el-menu-item index="/admin/reviews">
          <el-icon><ChatDotSquare /></el-icon>
          <span>Đánh giá</span>
        </el-menu-item>
```

- [x] **Step 3: Manually verify**

Run: `cd frontend && npm run dev` (in background, if not already running).
Open `/admin/reviews` logged in as staff or admin, confirm the table loads (empty state is fine with no seeded reviews) and the tab filters work.

- [x] **Step 4: Commit**

```bash
git add frontend/pages/admin/reviews/index.vue frontend/layouts/admin.vue
git commit -m "feat: add admin review moderation page"
```

---

### Task 6: Frontend — admin reports dashboard page

**Files:**
- Create: `frontend/pages/admin/reports/index.vue`
- Modify: `frontend/layouts/admin.vue`

**Interfaces:**
- Consumes: `GET /admin/reports/revenue`, `GET /admin/reports/top-products` (Task 3), `useApiClient()`, `formatVnd` (`frontend/utils/format.ts`).

- [x] **Step 1: Create the admin reports page**

```vue
<template>
  <div class="reports-page">
    <div class="page-header">
      <h1>Báo cáo doanh thu</h1>
      <el-date-picker
        v-model="dateRange"
        type="daterange"
        start-placeholder="Từ ngày"
        end-placeholder="Đến ngày"
        value-format="YYYY-MM-DD"
        @change="loadAll"
      />
    </div>

    <el-alert v-if="error" :title="error" type="error" show-icon class="page-alert" />

    <el-row :gutter="20" v-loading="loading">
      <el-col :span="8">
        <el-card shadow="hover">
          <el-statistic title="Tổng doanh thu (VNĐ)" :value="revenue.total_revenue" />
          <p class="stat-sub">{{ formatVnd(revenue.total_revenue) }}</p>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <el-statistic title="Tổng số đơn (không tính đơn hủy)" :value="revenue.total_orders" />
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="reports-tables">
      <el-col :span="12">
        <el-card shadow="never">
          <template #header>Doanh thu theo ngày</template>
          <el-table :data="revenue.daily" size="small" style="width: 100%">
            <el-table-column prop="date" label="Ngày" />
            <el-table-column label="Doanh thu">
              <template #default="{ row }">{{ formatVnd((row as DailyRevenue).revenue) }}</template>
            </el-table-column>
            <el-table-column prop="orders_count" label="Số đơn" />
          </el-table>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card shadow="never">
          <template #header>Top sản phẩm bán chạy</template>
          <el-table :data="topProducts.products" size="small" style="width: 100%">
            <el-table-column prop="name" label="Sản phẩm" />
            <el-table-column prop="quantity_sold" label="SL bán" width="90" />
            <el-table-column label="Doanh thu">
              <template #default="{ row }">{{ formatVnd((row as TopProduct).revenue) }}</template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { formatVnd } from '~/utils/format'

definePageMeta({ layout: 'admin' })

interface DailyRevenue { date: string; revenue: number; orders_count: number }
interface TopProduct { name: string; quantity_sold: number; revenue: number }

const api = useApiClient()

const loading = ref(true)
const error = ref('')
const dateRange = ref<[string, string] | null>(null)

const revenue = reactive<{ total_revenue: number; total_orders: number; daily: DailyRevenue[] }>({
  total_revenue: 0,
  total_orders: 0,
  daily: []
})
const topProducts = reactive<{ products: TopProduct[] }>({ products: [] })

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    const query = dateRange.value ? `?from=${dateRange.value[0]}&to=${dateRange.value[1]}` : ''
    const [revenueRes, topRes] = await Promise.all([
      api.get<{ total_revenue: number; total_orders: number; daily: DailyRevenue[] }>(`/admin/reports/revenue${query}`),
      api.get<{ products: TopProduct[] }>(`/admin/reports/top-products${query}`)
    ])
    Object.assign(revenue, revenueRes)
    Object.assign(topProducts, topRes)
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải báo cáo.'
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.reports-page { max-width: 1300px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-alert { margin-bottom: 16px; }
.stat-sub { margin-top: 8px; font-size: 12px; color: #6b7280; }
.reports-tables { margin-top: 20px; }
</style>
```

Save as `frontend/pages/admin/reports/index.vue`.

- [x] **Step 2: Add the admin-only sidebar link**

In `frontend/layouts/admin.vue`, add a new `<el-menu-item>` gated by `v-if="isAdmin"` (matching the existing `/admin/users` item), placed right before it:

```html
        <el-menu-item v-if="isAdmin" index="/admin/reports">
          <el-icon><TrendCharts /></el-icon>
          <span>Báo cáo</span>
        </el-menu-item>
```

- [x] **Step 3: Manually verify**

Open `/admin/reports` logged in as admin — confirm stat cards and both tables render (zero values are fine with no seeded orders). Log in as staff and confirm the sidebar link is hidden and navigating to `/admin/reports` directly still succeeds in loading the page shell (the API call will 403 — that's expected and acceptable since the frontend has no route-level guard on this page, matching how `/admin/users` behaves today for staff).

- [x] **Step 4: Commit**

```bash
git add frontend/pages/admin/reports/index.vue frontend/layouts/admin.vue
git commit -m "feat: add admin revenue and top-products reports dashboard"
```

---

### Task 7: Final verification

**Files:** none (verification only)

- [x] **Step 1: Run the full backend suite**

Run: `php artisan test`
Expected: All tests pass (previous 148 + 15 new = 163).

- [x] **Step 2: Run the full frontend suite**

Run: `cd frontend && npm run test -- --run`
Expected: All 23 tests still pass.

- [x] **Step 3: Verify static generation still succeeds**

Run: `cd frontend && npm run generate`
Expected: Completes without error (the PDP's reviews section only calls client-side `$fetch`/composables inside `onMounted`/`ClientOnly`, so it must not break the SSG build the way the pre-existing `formatCurrency`/`ssr:false` bugs did in Phase 6).

- [x] **Step 4: Update the phase plan doc**

In `docs/superpowers/plans/2026-07-12-phase4-6-planning.md`, this new plan is a separate increment covering the spec's original Phase 6 gaps — no edit needed there. Optionally note in a commit message that reviews + reports close out the remaining spec gaps identified in `docs/superpowers/specs/2026-07-11-shop-system-design.md` §3.4/§4/§5/§8.

---

## Self-Review

**Spec coverage:**
- §3.4 `reviews` table (product_id, user_id, rating, comment, status pending/approved/rejected) → Task 1.
- §4 `POST/GET` reviews endpoints, `GET /api/admin/reports/revenue`, `GET /api/admin/reports/top-products` → Tasks 1, 2, 3.
- §5 role table ("Báo cáo & thống kê" = admin only) → Task 3 uses a separate `admin-only` route group, not the shared `staff-only` admin block.
- §8 Phase 6 "Dashboard báo cáo" → Tasks 3, 6. "Phân quyền staff chi tiết" is already satisfied by the existing `/admin/users` (`admin-only`) vs. catalog (`staff-only`) split — verified in `tests/Feature/Auth/RoleAccessTest.php`, no task needed. CI/CD rebuild pipeline and broader SEO audit are explicitly out of scope per the user's selection this session.

**Placeholder scan:** no TBD/TODO markers; every step has runnable code.

**Type consistency:** `ReviewInfo` (storefront) and the inline `Review` interface (admin page) intentionally differ in shape (storefront never sees `status`; admin never needs it hidden) — this matches the existing repo convention of per-page interface redefinition rather than a shared package.
