# Thiết kế hệ thống — Giày dép Hồng An (E-commerce giày da nam)

**Ngày:** 2026-07-11
**Trạng thái:** Đã duyệt bởi user, chờ viết implementation plan
**Phạm vi:** Full e-commerce (giỏ hàng, checkout COD, tracking đơn) cho ngành hàng giày da nam, admin panel đầy đủ, storefront chuẩn SEO.

> Tài liệu này được viết để một phiên Claude Code khác (không có ngữ cảnh hội thoại này) có thể đọc và implement đúng mà không cần hỏi lại các quyết định đã chốt. Mọi quyết định kiến trúc đều ghi kèm lý do. Nếu một mục nào mâu thuẫn với code hiện tại trong repo, code hiện tại là nguồn sự thật — cập nhật tài liệu này theo code, không làm ngược lại.

## 1. Bối cảnh & ràng buộc đã chốt

| Quyết định | Lựa chọn | Lý do |
|---|---|---|
| Mô hình bán hàng | Full e-commerce (giỏ hàng, checkout online, tracking đơn) | Không chỉ catalog+liên hệ |
| Biến thể sản phẩm | Đa biến thể (size × màu), 1 kho duy nhất | Chưa cần multi-warehouse cho MVP |
| Thanh toán | Chỉ COD giai đoạn đầu | Đơn giản hóa, không tích hợp cổng thanh toán online ngay |
| Team | 1 developer, cần MVP nhanh (vài tuần) | Ưu tiên kiến trúc đơn giản, dễ bảo trì một mình |
| Hosting | Shared hosting VN giá rẻ (nhà cung cấp cụ thể **chưa xác định**) | Không được giả định có Node.js chạy nền liên tục trên server production |
| Ngành hàng | Giày dép **da nam** (không phải sneaker/thể thao) | Ảnh hưởng taxonomy, content, target audience (nam 28–50 tuổi, dân văn phòng/chủ DN) |

### Tech stack hiện có trong repo (không thay đổi, chỉ mở rộng)

- Backend: Laravel 12 (`composer.json` khai `laravel/framework ^10.10` — cần kiểm tra/đồng bộ version thật khi implement), Sanctum token auth (không dùng cookie-based SPA auth).
- Frontend: Nuxt 4 + TypeScript, `@nuxtjs/i18n` (locales `vi`/`en` đã có sẵn), SCSS (không dùng Tailwind — xem `SCSS-INTEGRATION-SUMMARY.md`), cấu trúc `assets/scss/{base,components,layouts,pages}`.
- Auth đã implement: `app/Http/Controllers/Api/AuthController.php` (register/login/me/logout/updateProfile), `User` model có `role` enum (`admin`/`staff`/`customer`) và `status` enum (`active`/`inactive`/`suspended`) từ migration `2026_03_16_200100_add_details_to_users_table.php`. **Giữ nguyên pattern này**, không đổi sang package phân quyền (spatie/laravel-permission) trừ khi nhu cầu phân quyền chi tiết hơn phát sinh sau MVP.
- Trang đã scaffold: `frontend/pages/admin/{index,users}.vue`, `frontend/pages/client/{index,profile,services}.vue`, `frontend/pages/login.vue`.

## 2. Kiến trúc & chiến lược rendering (quyết định quan trọng nhất)

**Vấn đề:** Nuxt SSR mặc định cần một Node.js process sống để render mỗi request. Shared hosting giá rẻ VN không đảm bảo hỗ trợ chạy Node app bền vững. Storefront công khai cần SEO tốt (index được trên Google).

**Quyết định:** Tách rendering theo 2 vùng của ứng dụng:

1. **Storefront công khai** (trang chủ, danh mục, chi tiết sản phẩm, blog, trang tĩnh) → **Nuxt static generation** (`nuxi generate` / `nuxt generate`). Output là HTML tĩnh có đầy đủ meta tag + JSON-LD tại thời điểm build. **Không cần Node.js chạy trên server production** — server chỉ cần serve static files (Apache/Nginx/PHP hosting bất kỳ).
2. **Giỏ hàng** → hoàn toàn client-side (Pinia store + `localStorage`), không có bảng `carts`/`cart_items` trong DB cho MVP. Khi checkout, giỏ hàng client được gửi thẳng thành 1 `order` qua API.
3. **Checkout, trang tài khoản, admin panel** → Nuxt route rules với `ssr: false` (SPA/CSR thuần), không cần SEO nên không cần render tĩnh. Vẫn build ra static assets (JS/CSS) được serve từ cùng hosting, chỉ là không có nội dung HTML render sẵn — hydrate hoàn toàn phía client sau khi tải JS.

### Build & deploy pipeline

- CI (GitHub Actions) chạy `nuxi generate` — Node.js **chỉ chạy trong CI**, không chạy trên hosting production.
- Deploy artifact (thư mục `.output/public`) lên hosting qua FTP/SFTP.
- Laravel API deploy như một Laravel app PHP thông thường (composer install, `php artisan migrate`), phục vụ dưới subdomain riêng (khuyến nghị: `api.<domain>`) hoặc subpath `/api` cùng domain với storefront tùy khả năng cấu hình của hosting đã chọn.
- **Trigger rebuild**: khi admin publish/sửa sản phẩm hoặc bài blog, Laravel gọi một GitHub Actions `repository_dispatch` webhook để trigger rebuild+deploy storefront. Có thêm 1 rebuild định kỳ (cron, vd 1 lần/đêm) làm lưới an toàn nếu webhook lỗi.
- **Giảm rủi ro dữ liệu cũ (staleness)**: giá và tồn kho hiển thị trên PDP/PLP tĩnh được **client tự fetch lại từ API khi trang load** (gọi `GET /api/products/{slug}/availability` hoặc tương tự) để override phần giá/tồn kho nếu đã thay đổi từ lúc build gần nhất, tránh khách đặt hàng với giá/tồn kho sai.

### Ghi chú rủi ro / việc cần làm khi có hosting cụ thể

- Khi đã chọn nhà cung cấp hosting, xác nhận: (a) có PHP 8.1+/MySQL, (b) có thể cấu hình rewrite rule phục vụ Nuxt static build cho mọi route trừ `/api/*`, (c) hỗ trợ cron job (cho `php artisan schedule:run` mỗi phút — cần cho các job như tính điểm loyalty, rebuild định kỳ).
- Nếu sau này hosting nâng cấp lên VPS/hỗ trợ Node.js bền vững, có thể chuyển sang Nuxt SSR đầy đủ mà không cần đổi cấu trúc code page/component — chỉ đổi `nitro.preset` và bỏ bước static rebuild pipeline.

## 3. Data model

Quy ước: tất cả bảng có `id` (bigint, PK), `created_at`, `updated_at` trừ khi ghi chú khác. FK dùng `on delete` phù hợp ngữ cảnh (ghi chú riêng khi không phải `cascade`).

### 3.1 Catalog

**`categories`** — điều hướng theo kiểu dáng giày (Oxford, Derby, Loafer, Monk Strap, Chelsea Boot, Boot da, Sandal da...)
| Cột | Kiểu | Ghi chú |
|---|---|---|
| name | string | |
| slug | string, unique | vd `giay-oxford-nam` |
| parent_id | FK categories, nullable | cho phép phân cấp sau này (vd Phụ kiện > Thắt lưng da) |
| description | text, nullable | 300–500 từ, hiển thị trên PLP để tránh thin content |
| seo_title | string, nullable | |
| seo_description | string, nullable | |
| thumbnail | string, nullable | |
| is_active | boolean, default true | |
| sort_order | integer, default 0 | |

**`brands`**: `name`, `slug` unique, `logo` nullable, `description` nullable.

**`products`**
| Cột | Kiểu | Ghi chú |
|---|---|---|
| category_id | FK categories | |
| brand_id | FK brands, nullable | |
| name | string | |
| slug | string, unique | |
| sku | string, nullable | mã gốc, biến thể có SKU riêng |
| description | longtext | |
| material | enum: `full_grain_leather`,`suede`,`pu_leather`,`other` | thuộc tính lọc quan trọng nhất với khách VN (da thật vs da PU) |
| base_price | decimal(12,0) | VND, không cần phần thập phân |
| sale_price | decimal(12,0), nullable | |
| status | enum: `draft`,`published`,`archived` | |
| thumbnail | string | |
| seo_title | string, nullable | |
| seo_description | string, nullable | **bắt buộc unique per-product khi implement** — không copy 1 đoạn boilerplate cho mọi sản phẩm (lỗi quan sát được ở tiemgiayco98.com) |
| avg_rating | decimal(2,1), default 0 | cached từ `reviews` |
| reviews_count | integer, default 0 | cached |

**`product_images`**: `product_id` FK cascade, `url`, `sort_order`.

**`product_variants`**
| Cột | Kiểu | Ghi chú |
|---|---|---|
| product_id | FK products cascade | |
| size | string | vd "40", "41" — lưu string để hỗ trợ cả size chữ nếu cần sau này |
| color | string | |
| sku | string, unique | |
| stock_quantity | integer, default 0 | |
| price_override | decimal(12,0), nullable | null = dùng giá của `products` |

### 3.2 Order

**`orders`**
| Cột | Kiểu | Ghi chú |
|---|---|---|
| user_id | FK users, nullable | null = khách vãng lai |
| order_code | string, unique | vd `HA20260711-0001`, sinh tại thời điểm tạo đơn |
| guest_name / guest_phone / guest_email | string, nullable | bắt buộc nếu `user_id` null |
| shipping_address | text | MVP: lưu địa chỉ dạng text tự do (không tách tỉnh/huyện/xã) — đơn giản hóa, có thể nâng cấp lên bảng địa chỉ hành chính sau nếu cần |
| status | enum: `pending`,`confirmed`,`shipping`,`delivered`,`cancelled` | |
| payment_method | enum: `cod` | chỉ 1 giá trị hiện tại, thiết kế enum để mở rộng (vnpay/momo) không cần đổi schema |
| subtotal / shipping_fee / discount_amount / total | decimal(12,0) | |
| discount_code_id | FK discount_codes, nullable | |
| note | text, nullable | |

**`order_items`**: `order_id` FK cascade, `product_variant_id` FK (nullable — giữ lại nếu variant bị xóa sau này), `product_name_snapshot`, `variant_snapshot` (vd "Size 41 - Nâu"), `price`, `quantity`, `subtotal`. **Snapshot dữ liệu tại thời điểm mua** — không JOIN sang `products`/`product_variants` để hiển thị lịch sử đơn hàng (giá/tên có thể đổi sau).

**`discount_codes`**: `code` unique, `type` enum(`percent`,`fixed`), `value`, `min_order_value` nullable, `usage_limit` nullable, `used_count` default 0, `starts_at`/`expires_at` nullable, `is_active`.

### 3.3 CRM / Loyalty (đơn giản hóa từ mô hình PNJ)

**`membership_tiers`**: `name`, `min_points` (integer), `discount_percent`, `sort_order`. Seed tối thiểu 3–4 hạng.

**`loyalty_ledger`**: `user_id` FK, `order_id` FK nullable, `points` (integer, có thể âm khi redeem), `type` enum(`earn`,`redeem`,`adjust`), `note` nullable. Điểm **không hết hạn** (theo mô hình PNJ), tích lũy trọn đời để xác định hạng thành viên — không cần trường `expires_at`.

**`users`** (mở rộng ở Phase 4, migration mới — không sửa migration cũ): thêm `points` (integer, default 0, denormalized cache của tổng `loyalty_ledger`), `membership_tier_id` (FK nullable).

### 3.4 Content / Marketing

**`blog_posts`**: `title`, `slug` unique, `excerpt`, `content` (longtext), `thumbnail`, `pillar` (string nullable — nhóm content pillar: vd `cam-nang-chon-giay`, `bao-quan-giay-da`, `giay-theo-dip`), `seo_title`, `seo_description`, `status` enum(`draft`,`published`), `published_at` nullable.

**`banners`**: `image`, `link` nullable, `position` enum(`homepage_hero`,`homepage_promo`), `sort_order`, `starts_at`/`ends_at` nullable, `is_active`.

**`reviews`**: `product_id` FK cascade, `user_id` FK (bắt buộc đăng nhập mới review — không cho guest review), `rating` (tinyint 1–5), `comment` text nullable, `status` enum(`pending`,`approved`,`rejected`) — admin duyệt trước khi hiển thị.

## 4. API (mở rộng `routes/api.php` hiện tại)

Giữ nguyên các route auth đã có (`/register`, `/login`, `/logout`, `/user`, `/user/profile`). Thêm:

**Public (không cần auth):**
- `GET /api/categories`, `GET /api/categories/{slug}`
- `GET /api/products` — query params: `category`, `brand`, `material`, `size`, `color`, `price_min`, `price_max`, `sort`, `page`
- `GET /api/products/{slug}`
- `GET /api/products/{slug}/availability` — trả giá + tồn kho hiện tại (dùng để "làm tươi" trang static, xem mục 2)
- `GET /api/brands`
- `POST /api/orders` — tạo đơn (guest hoặc auth, dùng `Sanctum` optional-auth)
- `GET /api/orders/{order_code}` — tra cứu đơn (kèm token/phone xác thực với đơn của khách vãng lai)
- `GET /api/blog`, `GET /api/blog/{slug}`
- `POST /api/discount-codes/validate`

**Auth (customer, `auth:sanctum`):**
- `GET /api/user/orders`
- `GET /api/user/loyalty`

**Admin (`auth:sanctum` + middleware role admin|staff, prefix `/api/admin`):**
- CRUD: `products`, `categories`, `brands`, `product-variants`, `orders` (+ `PATCH /orders/{id}/status`), `discount-codes`, `blog`, `banners`, `reviews` (duyệt/từ chối)
- `GET/PATCH /api/admin/users` — quản lý staff/customer (staff **không** được truy cập endpoint này — chỉ admin)
- `GET /api/admin/reports/revenue`, `GET /api/admin/reports/top-products`

Route phân quyền: dùng `Route::middleware('can:admin-only')` pattern đã có sẵn trong `routes/api.php`, mở rộng Gate để phân biệt `admin` vs `staff` theo từng nhóm route ở trên.

## 5. Admin panel — module

| Module | Chức năng chính | Role truy cập |
|---|---|---|
| Sản phẩm & danh mục | CRUD sản phẩm/biến thể/danh mục/thương hiệu, upload ảnh, SEO field per-page | admin, staff |
| Đơn hàng & vận chuyển | Danh sách/lọc theo trạng thái, chi tiết đơn, đổi trạng thái, in hóa đơn | admin, staff |
| Khách hàng & CRM | Danh sách khách, lịch sử mua, hạng thành viên, chỉnh điểm thủ công | admin, staff |
| Báo cáo & thống kê | Doanh thu theo ngày/tháng, top sản phẩm, cảnh báo sắp hết hàng | admin |
| Nội dung/SEO-Marketing | Blog (gắn pillar), banner trang chủ, mã giảm giá | admin, staff |
| Phân quyền nhân viên | Quản lý tài khoản staff, gán role | admin only |

## 6. Storefront — cấu trúc trang & yêu cầu SEO

| Trang | URL pattern | Ghi chú SEO |
|---|---|---|
| Trang chủ | `/` | JSON-LD `Organization` + `WebSite` (kèm `SearchAction`) |
| Danh mục | `/danh-muc/{slug}` | JSON-LD `BreadcrumbList` + `ItemList`; filter (brand/material/size/color/price) ghi vào query string (crawlable, giống pattern glab.vn); nội dung giới thiệu 300-500 từ trên/dưới grid |
| Chi tiết sản phẩm | `/san-pham/{slug}` | JSON-LD `Product` + `Offer` + `BreadcrumbList`; `seo_title`/`seo_description` unique bắt buộc; `og:*` tags |
| Blog | `/cam-nang`, `/cam-nang/{slug}` | JSON-LD `Article`/`BlogPosting`; nội dung theo pillar/cluster (mục 7) |
| Giỏ hàng | `/gio-hang` | không cần SEO, `noindex` |
| Checkout | `/thanh-toan` | không cần SEO, `noindex`, SPA thuần |
| Tài khoản | `/tai-khoan/*` | cần đăng nhập, `noindex`, SPA thuần |
| Trang tĩnh | `/gioi-thieu`, `/chinh-sach-*`, `/lien-he` | render tĩnh, SEO cơ bản |

Ngoài ra: `sitemap.xml` sinh tự động (Laravel command chạy trong bước build, chia theo `sitemap_products.xml`/`sitemap_categories.xml`/`sitemap_blog.xml` giống pattern Haravan quan sát được), `robots.txt` khai `Sitemap:` đúng URL (tránh lỗi sitemap 404 như quan sát ở pnj.com.vn), canonical tag trên mọi trang.

## 7. Taxonomy & content strategy (ngành giày da nam)

**Danh mục gốc theo kiểu dáng** (seed data cho `categories`): Giày Oxford, Giày Derby, Giày Loafer (giày lười), Giày Monk Strap, Chelsea Boot, Boot da nam, Sandal/Dép da nam.

**Thuộc tính lọc trên `products`**: `material` (da bò thật / da lộn / da PU), màu (nâu đậm, đen, xanh navy là 3 màu chủ đạo theo xu hướng hiện tại — không cần bảng `colors` riêng, lưu string trên `product_variants.color`).

**Content pillar cho blog** (giá trị hợp lệ cho `blog_posts.pillar`):
1. `cam-nang-chon-giay` — phân biệt Oxford/Derby/Loafer, cách chọn size, cách phối đồ
2. `bao-quan-giay-da` — đánh xi, chống ẩm mốc (khí hậu VN), khử mùi
3. `giay-theo-dip` — giày cưới, công sở, dự tiệc

## 8. Lộ trình triển khai

| Giai đoạn | Nội dung | Kết quả |
|---|---|---|
| Phase 0 | Hoàn thiện auth (đã gần xong), middleware role admin/staff/customer | Auth production-ready |
| Phase 1 | Migration catalog (mục 3.1), Admin CRUD sản phẩm/danh mục/biến thể/ảnh | Có dữ liệu thật để build storefront |
| Phase 2 | Storefront (trang chủ/PLP/PDP), Nuxt static generation + route rules, JSON-LD/sitemap/robots.txt, CI build+deploy pipeline | Test được trên hosting thật — rủi ro kỹ thuật cao nhất, làm sớm |
| Phase 3 | Giỏ hàng client-side, checkout COD, `orders`/`order_items`, admin quản lý đơn | **MVP bán được hàng thật** |
| Phase 4 | Trang tài khoản, `loyalty_ledger`/`membership_tiers` | CRM cơ bản |
| Phase 5 | Blog (pillar/cluster), banner, `discount_codes` | SEO dài hạn + marketing |
| Phase 6 | Dashboard báo cáo, phân quyền staff chi tiết, audit SEO, tối ưu pipeline | Hoàn thiện vận hành |

## 9. Việc cần làm ngay khi bắt đầu implement (writing-plans)

1. Xác nhận nhà cung cấp hosting cụ thể trước Phase 2 (ảnh hưởng cấu hình rewrite rule + cron).
2. Phase 1 và Phase 2 nên có kế hoạch triển khai (implementation plan) riêng — mỗi phase là 1 spec/plan cycle độc lập, không viết 1 plan khổng lồ cho toàn bộ 6 phase.
