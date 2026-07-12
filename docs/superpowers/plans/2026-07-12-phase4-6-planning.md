# Phase 4–6 — Client-Side E-commerce Planning

**Ngày tạo:** 2026-07-12  
**Trạng thái:** Đã phân tích, sẵn sàng implement từng phase  
**Mục tiêu:** Tài liệu này tự chứa đủ context để bất kỳ session Claude nào cũng có thể đọc và implement đúng mà không cần hỏi lại.

---

## 0. Ngữ cảnh nhanh (cần đọc trước)

| Mục | Thông tin |
|---|---|
| **Repo** | `d:\Coding\giaydephongan` (monorepo) |
| **Backend** | Laravel 12, SQLite local, `php artisan serve` → `localhost:8000` |
| **Frontend** | Nuxt 4 + TypeScript, SCSS thuần (không TailwindCSS), `npm run dev` → `localhost:3000` |
| **Auth** | Sanctum token, lưu ở `localStorage` key `auth_token` qua `useApiClient.ts` + `useAuth.ts` |
| **Kiểu sản phẩm** | Giày da nam (Oxford, Derby, Loafer, Monk Strap, Chelsea Boot, Boot da, Sandal da) |
| **Design token** | `$sf-color-accent: #8b5e34`, `$sf-color-accent-dark: #6f4a28`, Inter font, scoped SCSS trong SFC |
| **Phase đã xong** | Phase 0–3: Auth, Admin CRUD, Public Storefront Catalog (homepage, PLP, PDP, CMS pages, static gen) |
| **Spec gốc** | `docs/superpowers/specs/2026-07-11-shop-system-design.md` |

### Luôn nhớ khi implement frontend
- Dùng **scoped SCSS** trong từng SFC, không dùng utility class
- **Không dùng Pinia** — dùng `composables/` với `useState()` của Nuxt hoặc `localStorage` reactify
- Trang admin dùng **Element Plus** (`el-*` components)
- Trang storefront dùng **custom SCSS components** (không dùng Element Plus)
- Sử dụng `useApiClient()` composable (đã có) cho mọi API call cần auth header
- Kiểm tra test sau mỗi task: `cd frontend && npm run test` và `php artisan test`

---

## Phase 4 — Giỏ hàng + Checkout COD + Quản lý đơn (Admin)

> **Mục tiêu:** MVP bán được hàng thật — khách xem sản phẩm → thêm giỏ → checkout COD → nhận xác nhận đơn → admin xử lý đơn.

### Task 4.1 — Database & Models

**Migrations cần tạo** (chạy theo thứ tự):

**1. `2026_07_13_100000_create_orders_table.php`**
```php
$table->id();
$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
$table->string('order_code')->unique();
$table->string('guest_name')->nullable();
$table->string('guest_phone')->nullable();
$table->string('guest_email')->nullable();
$table->text('shipping_address');
$table->enum('status', ['pending','confirmed','shipping','delivered','cancelled'])->default('pending');
$table->enum('payment_method', ['cod'])->default('cod');
$table->decimal('subtotal', 12, 0)->default(0);
$table->decimal('shipping_fee', 12, 0)->default(0);
$table->decimal('discount_amount', 12, 0)->default(0);
$table->decimal('total', 12, 0)->default(0);
$table->foreignId('discount_code_id')->nullable()->constrained()->nullOnDelete();
$table->text('note')->nullable();
$table->timestamps();
```

**2. `2026_07_13_100100_create_order_items_table.php`**
```php
$table->id();
$table->foreignId('order_id')->constrained()->cascadeOnDelete();
$table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
$table->string('product_name_snapshot');
$table->string('variant_snapshot'); // vd "Size 41 - Nâu"
$table->decimal('price', 12, 0);
$table->integer('quantity');
$table->decimal('subtotal', 12, 0);
$table->timestamps();
```

**3. `2026_07_13_100200_create_discount_codes_table.php`**
```php
$table->id();
$table->string('code')->unique();
$table->enum('type', ['percent', 'fixed']);
$table->decimal('value', 10, 2);
$table->decimal('min_order_value', 12, 0)->nullable();
$table->integer('usage_limit')->nullable();
$table->integer('used_count')->default(0);
$table->timestamp('starts_at')->nullable();
$table->timestamp('expires_at')->nullable();
$table->boolean('is_active')->default(true);
$table->timestamps();
```

**Models cần tạo:**
- `Order.php`: `$fillable`, `$casts = ['status' => ...]`, relations `user()`, `items()`, `discountCode()`, boot `generating order_code`
- `OrderItem.php`: `$fillable`, relations `order()`, `variant()`
- `DiscountCode.php`: `$fillable`, scope `active()`

**Order Code Logic** (trong `Order.php` boot):
```php
static::creating(function ($order) {
    $date = now()->format('Ymd');
    $count = static::whereDate('created_at', now())->count() + 1;
    $order->order_code = 'HA' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
});
```

**Tests:** `tests/Feature/Storefront/OrderCheckoutTest.php`

---

### Task 4.2 — Public APIs (Checkout & Tracking)

**File:** `app/Http/Controllers/Api/Storefront/OrderController.php`

**`POST /api/orders`** — Tạo đơn hàng
- Validate: `shipping_address` required, `items[*.variant_id]`, `items[*.quantity]`
- Guest: thêm validate `guest_name` + `guest_phone` required nếu không có auth
- Kiểm tra stock từng variant (422 nếu hết)
- Apply discount code nếu có
- Trừ stock: SQL `UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?`
- Tạo `Order` + `OrderItem[]` trong transaction
- Response: `{ order_code, total, message }`

**`GET /api/orders/{code}`** — Tra cứu đơn
- Auth guard: nếu user đăng nhập → verify `user_id`, nếu guest → verify `?phone=...` khớp `guest_phone`
- Response: `{ order_code, status, items[], total, shipping_address, created_at }`

**`POST /api/discount-codes/validate`** — Kiểm tra mã
- Body: `{ code, subtotal }`
- Response: `{ valid: bool, discount_type, discount_value, discount_amount, message }`

**Routes trong `routes/api.php`:**
```php
Route::post('/orders', [Storefront\OrderController::class, 'store']);
Route::get('/orders/{code}', [Storefront\OrderController::class, 'show']);
Route::post('/discount-codes/validate', [Storefront\OrderController::class, 'validateDiscount']);
```

---

### Task 4.3 — Admin APIs (Quản lý đơn)

**File:** `app/Http/Controllers/Api/Admin/OrderController.php`

```
GET    /api/admin/orders              → Danh sách, filter: status, search (order_code/phone)
GET    /api/admin/orders/{id}         → Chi tiết (kèm items, discount)
PATCH  /api/admin/orders/{id}/status  → Body: { status } — validate transition hợp lệ
```

**Transition hợp lệ:**
- `pending` → `confirmed` hoặc `cancelled`
- `confirmed` → `shipping`
- `shipping` → `delivered`
- `delivered` / `cancelled` → không đổi được

**Tests:** `tests/Feature/Admin/OrderControllerTest.php`

---

### Task 4.4 — Frontend: `useCart.ts` composable

**File:** `frontend/composables/useCart.ts`

```typescript
interface CartItem {
  variantId: number
  productId: number
  name: string
  slug: string
  thumbnail: string | null
  size: string
  color: string
  price: number  // effective price (sale_price ?? base_price)
  quantity: number
}
```

- `useState<CartItem[]>('cart', () => loadFromStorage())`
- Watch items → save to `localStorage` key `'cart'`
- Export: `{ items, count (computed), total (computed), addItem, removeItem, updateQty, clearCart }`
- `addItem`: nếu đã có cùng variantId thì tăng quantity, không thêm duplicate

**Unit tests Vitest:** `frontend/composables/useCart.test.ts`

---

### Task 4.5 — Frontend: Giỏ hàng + Checkout + Xác nhận

**Thêm giỏ hàng vào PDP** (`frontend/pages/san-pham/[slug].vue`):
- Nút "Thêm vào giỏ" chỉ bật khi `selectedVariantId !== null`
- Gọi `useCart().addItem(...)` → toast notification ngắn
- Nút "Mua ngay" → addItem + redirect `/thanh-toan`

**Giỏ hàng icon trên header** (`frontend/layouts/default.vue`):
- Thêm link `/gio-hang` với badge `useCart().count`
- Hiển thị số lượng items

**`frontend/pages/gio-hang.vue`:**
```
definePageMeta({ ssr: false })
- Nếu giỏ rỗng: thông báo + NuxtLink về trang chủ
- Bảng items: thumbnail, tên, size/màu, đơn giá, quantity input, nút xóa, thành tiền
- Tổng cộng + nút "Tiến hành đặt hàng" → /thanh-toan
```

**`frontend/pages/thanh-toan.vue`:**
```
definePageMeta({ ssr: false })
Layout 2 cột:
  - Trái: Form giao hàng (họ tên, SĐT, email, địa chỉ, ghi chú)
  - Phải: Tóm tắt đơn, input mã giảm giá, tổng, nút "Đặt hàng ngay"
  
Logic:
  1. Validate form
  2. Validate mã giảm giá (gọi API nếu có nhập)
  3. POST /api/orders
  4. clearCart() + redirect /dat-hang-thanh-cong?code={order_code}
```

**`frontend/pages/dat-hang-thanh-cong.vue`:**
- Hiển thị `order_code` từ query string
- Thông báo thành công, hướng dẫn chờ xác nhận
- Link tra cứu đơn hàng

---

### Task 4.6 — Frontend: Admin Quản lý Đơn Hàng

**Sidebar** (`frontend/layouts/admin.vue`): thêm `List` icon + menu `/admin/orders` "Đơn hàng"

**`frontend/pages/admin/orders/index.vue`:**
- `el-tabs` theo status: Tất cả / Chờ xác nhận / Đang giao / Đã giao / Đã hủy
- `el-table`: order_code, tên khách, SĐT, tổng tiền, trạng thái (el-tag), ngày tạo, action "Xem"
- Search box theo order_code hoặc SĐT

**Chi tiết đơn** (el-drawer từ phải):
- Thông tin khách + địa chỉ giao hàng
- `el-table` items: tên snapshot, size/màu snapshot, đơn giá, số lượng, thành tiền
- Tóm tắt giá trị (subtotal, phí ship, giảm giá, tổng)
- `el-steps` timeline trạng thái
- Nút đổi trạng thái (chỉ hiện nút tương ứng với bước tiếp theo hợp lệ)

---

## Phase 5 — Tài khoản khách hàng + Loyalty

> **Mục tiêu:** Khách đăng nhập có thể xem đơn hàng, tích điểm, xem hạng thành viên.

### Task 5.1 — Database & Models

**Migrations:**

**1. `create_membership_tiers_table.php`**
```php
$table->id();
$table->string('name');
$table->integer('min_points');
$table->decimal('discount_percent', 5, 2)->default(0);
$table->integer('sort_order')->default(0);
$table->timestamps();
```

**Seed 4 hạng:** Đồng (0đ, 0%), Bạc (500đ, 2%), Vàng (2000đ, 5%), Bạch Kim (5000đ, 10%)

**2. `create_loyalty_ledger_table.php`**
```php
$table->id();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
$table->integer('points'); // âm khi redeem
$table->enum('type', ['earn', 'redeem', 'adjust']);
$table->text('note')->nullable();
$table->timestamps();
```

**3. `add_loyalty_fields_to_users_table.php`** (migration mới)
```php
$table->integer('points')->default(0)->after('status');
$table->foreignId('membership_tier_id')->nullable()->constrained('membership_tiers')->nullOnDelete()->after('points');
```

**OrderObserver** (`app/Observers/OrderObserver.php`):
- Khi `status` đổi thành `delivered` và `user_id` không null:
  - Earn `floor(total / 10000)` điểm
  - Thêm row vào `loyalty_ledger`
  - Cập nhật `users.points` và `users.membership_tier_id` theo bảng hạng

**Đăng ký Observer** trong `AppServiceProvider.php`

---

### Task 5.2 — Customer APIs

**File:** `app/Http/Controllers/Api/Customer/AccountController.php`

```
GET /api/user/orders           → Danh sách đơn (phân trang)
GET /api/user/orders/{code}    → Chi tiết đơn (verify chủ đơn)
GET /api/user/loyalty          → { points, tier: { name, min_points, discount_percent, next_tier }, history[] }
```

**Routes:** Thêm vào `routes/api.php` trong group `auth:sanctum`

**Tests:** `tests/Feature/Customer/AccountTest.php`

---

### Task 5.3 — Frontend: Khu vực tài khoản

**Middleware** `frontend/middleware/auth.client.ts`:
```typescript
// Redirect về /login nếu chưa đăng nhập
export default defineNuxtRouteMiddleware(() => {
  const { user } = useAuth()
  if (!user.value) return navigateTo('/login')
})
```

**Layout** `frontend/layouts/account.vue`:
- Sidebar: Đơn hàng của tôi | Điểm thưởng | Hồ sơ | Đăng xuất
- `definePageMeta({ layout: 'account', middleware: 'auth' })` trong mọi trang con

**`frontend/pages/tai-khoan/don-hang.vue`:**
- `definePageMeta({ ssr: false })`
- Danh sách đơn: order_code, ngày, tổng, trạng thái (màu sắc theo status)
- Click → Drawer chi tiết đơn

**`frontend/pages/tai-khoan/loyalty.vue`:**
- Thẻ hạng hiện tại với màu hạng (Đồng/Bạc/Vàng/Bạch Kim)
- Tổng điểm + thanh tiến trình lên hạng tiếp theo
- Bảng lịch sử giao dịch điểm

**`frontend/pages/tai-khoan/ho-so.vue`:**
- Form sửa tên (`name`) + mật khẩu (old_password, new_password, confirm)
- Gọi `PATCH /api/user/profile` (đã có)

---

## Phase 6 — Blog + Mã giảm giá

> **Mục tiêu:** SEO dài hạn qua nội dung, marketing qua discount codes.

### Task 6.1 — Backend Blog

**Migration `create_blog_posts_table.php`:**
```php
$table->string('title');
$table->string('slug')->unique();
$table->text('excerpt')->nullable();
$table->longText('content');
$table->string('thumbnail')->nullable();
$table->string('pillar')->nullable(); // cam-nang-chon-giay | bao-quan-giay-da | giay-theo-dip
$table->string('seo_title')->nullable();
$table->string('seo_description')->nullable();
$table->enum('status', ['draft', 'published'])->default('draft');
$table->timestamp('published_at')->nullable();
```

**Public APIs:** `GET /api/blog`, `GET /api/blog/{slug}`

**Admin APIs:** CRUD `/api/admin/blog`

**Thêm vào `/api/slugs`:** trả thêm `blog: string[]`

---

### Task 6.2 — Frontend Blog Storefront

**`frontend/pages/cam-nang/index.vue`:**
- Grid bài viết, filter pillar (tabs), phân trang
- `useSeoMeta` cho trang danh sách blog

**`frontend/pages/cam-nang/[slug].vue`:**
- `useSeoMeta` với dữ liệu từ DB
- JSON-LD `Article` + `BreadcrumbList`
- Nội dung: `v-html="sanitizeHtml(post.content)"`
- Sidebar bài cùng pillar

**Navigation:** Thêm "Cẩm nang" vào `default.vue` header + footer

---

### Task 6.3 — Admin Blog + Discount Codes (Frontend)

**`frontend/pages/admin/blog/index.vue`:**
- `el-table` danh sách, lọc status/pillar
- Dialog tạo/sửa: title, slug, pillar select, thumbnail upload, content textarea HTML, SEO fields, status + published_at

**`frontend/pages/admin/discount-codes/index.vue`:**
- `el-table`: code, type, value, min_order, usage/limit, expires_at, is_active switch
- Dialog tạo/sửa: code, type (percent/fixed), value, min_order_value, dates, limit, toggle active

**Sidebar admin:** thêm "Blog" (Document icon) và "Mã giảm giá" (Discount icon)

---

## Checklist thứ tự implementation

### Phase 4 — MVP Bán hàng
- [x] **Task 4.1** Migrations orders + order_items + discount_codes + Models + Tests
- [x] **Task 4.2** Public APIs: POST /api/orders, GET /api/orders/{code}, validate discount
- [x] **Task 4.3** Admin APIs: GET/PATCH /api/admin/orders
- [x] **Task 4.4** `useCart.ts` composable + Vitest tests
- [x] **Task 4.5** `/gio-hang` + `/thanh-toan` + `/dat-hang-thanh-cong` + nút "Thêm giỏ" trong PDP
- [x] **Task 4.6** `/admin/orders` danh sách + chi tiết + đổi trạng thái

### Phase 5 — Tài khoản
- [x] **Task 5.1** Migrations + OrderObserver tích điểm tự động
- [x] **Task 5.2** Customer APIs + Tests
- [x] **Task 5.3** Frontend account layout + `/tai-khoan/{don-hang,loyalty,ho-so}`

### Phase 6 — Blog + Discount
- [x] **Task 6.1** Migration blog_posts + Admin/Public APIs + Tests (đã bổ sung: CRUD mã giảm giá admin — `Api\Admin\DiscountCodeController` — không có trong bản nháp gốc nhưng cần thiết cho Task 6.3 frontend)
- [x] **Task 6.2** Storefront `/cam-nang` + `/cam-nang/[slug]`
- [x] **Task 6.3** Admin Blog + Discount Codes CRUD frontend
- [x] **Task 6.4** Cập nhật sitemap, navigation, verify generate (phát hiện + sửa 2 bug chặn build có sẵn từ Phase 4/5: `formatCurrency` không tồn tại trong `utils/format.ts`, và `/gio-hang` `/thanh-toan` `/tai-khoan/**` thiếu `ssr:false` khiến `nuxi generate` lỗi 500 — đã verify `npm run generate` chạy sạch, 78 route, sitemap/robots đúng)

---

## Cách dùng lại tài liệu này với Claude

Khi bắt đầu implement một phase mới, dùng prompt sau:

```
Tôi đang implement dự án giày dép Hồng An.

Đọc các file sau để nắm context:
- d:\Coding\giaydephongan\docs\superpowers\plans\2026-07-12-phase4-6-planning.md (file này)
- d:\Coding\giaydephongan\docs\superpowers\specs\2026-07-11-shop-system-design.md

Implement giúp tôi Phase [4/5/6] theo đúng thứ tự Tasks trong planning.
```

---

## Ghi chú kỹ thuật quan trọng

| Vấn đề | Giải pháp |
|---|---|
| Race condition khi checkout | `UPDATE stock WHERE stock_quantity >= quantity`, trả 422 nếu 0 rows affected |
| Guest checkout | `user_id = null`, bắt buộc `guest_name` + `guest_phone` |
| Giỏ hàng mất khi reload | `localStorage` + `watchEffect` lưu lại trong composable |
| Trang account cần auth | Nuxt `middleware/auth.client.ts` redirect về `/login` |
| Cart badge header | `useCart().count` là computed, cập nhật reactive |
| SQLite số học | Cast price sang `int` trong PHP trước khi tính toán |
| Admin Order transition | Validate state machine trong Controller, reject transition không hợp lệ với 422 |
| Blog HTML an toàn | Reuse `sanitizeHtml.ts` đã có (lọc script + event handlers) |
