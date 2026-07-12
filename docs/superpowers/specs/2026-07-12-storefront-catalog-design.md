# Thiết kế — Storefront Catalog (trang client công khai)

**Ngày:** 2026-07-12
**Trạng thái:** Đã duyệt bởi user, chờ viết implementation plan
**Phạm vi:** Storefront catalog công khai (trang chủ, danh mục, chi tiết sản phẩm, tìm kiếm, trang nội dung) + API public + nội dung động từ DB (settings/pages/banners) + admin UI quản trị các nội dung đó. **Chưa gồm** giỏ hàng/checkout (phase kế tiếp), blog, reviews, loyalty.

> Tài liệu này kế thừa và chi tiết hóa spec tổng `2026-07-11-shop-system-design.md` (mục 2, 3.4, 4, 6, 7). Nếu mâu thuẫn với code hiện tại trong repo, code là nguồn sự thật. Người implement không cần đọc lại hội thoại brainstorm — mọi quyết định đã chốt đều ghi ở đây kèm lý do.

## 1. Quyết định đã chốt trong brainstorm

| Quyết định | Lựa chọn | Lý do |
|---|---|---|
| Phạm vi phase | Chỉ storefront catalog; giỏ hàng/checkout COD là plan riêng ngay sau | Mỗi plan vừa sức review; đúng lộ trình spec tổng |
| Chiến lược render | **Giữ static generation** (`nuxi generate`) như spec tổng §2 | Hosting shared VN chưa chốt, không giả định có Node.js production; SEO tốt |
| "Dữ liệu động" | Mọi nội dung hiển thị đều đọc từ DB qua API **tại thời điểm build**; giá/tồn kho được client fetch lại lúc runtime qua endpoint `availability` | Thỏa yêu cầu "tất cả dữ liệu động, lưu DB" trong ràng buộc hosting tĩnh |
| Thanh toán | Không tích hợp cổng thanh toán (COD-only toàn dự án) | Chốt từ spec tổng |
| Data-fetching frontend | **Phương án A**: `useAsyncData`/`$fetch` per-page, không Pinia store cho catalog | SSG đã nướng data vào payload từng trang; store là thừa (YAGNI). Pinia thêm ở phase giỏ hàng |
| Thẩm mỹ | **Sáng sạch hiện đại**: nền trắng, nhiều khoảng thở, sans-serif (Inter đã có), một màu nhấn nâu da thuộc trầm dùng tiết chế | User chọn; màu nhấn nâu giữ nhận diện ngành đồ da |
| Pipeline deploy | **Ngoài phạm vi.** Phase này chỉ đảm bảo `nuxi generate` chạy đúng + SEO artifacts, verify local. CI/FTP + webhook rebuild làm khi chốt hosting | Spec tổng §9.1 yêu cầu chốt hosting trước phần deploy |
| UI framework | Storefront dùng **SCSS thuần** — Element Plus vẫn chỉ dành cho `/admin/**` | Ràng buộc từ Phase 2 admin |
| i18n | UI chrome qua `$t()` (vi/en); **nội dung DB chỉ tiếng Việt, không dịch** | Không mở rộng schema đa ngôn ngữ trong phase này |

### Phân tích ngành đã thực hiện (nguồn quyết định các tính năng bổ sung)

Khảo sát Laforce.vn (giày da nam cùng phân khúc — trang chủ, trang danh mục, cụm trang size guide), G-Lab.vn, cộng các pattern đã ghi trong spec tổng (tiemgiayco98, glab, PNJ, Haravan). Kết quả: các tính năng ngành có mà spec tổng thiếu, nay đưa vào phase này — **hướng dẫn chọn size** (lý do hoàn hàng số 1 của ngành giày), **sản phẩm liên quan**, **badge sale/mới trên card**, **tìm kiếm theo tên** (spec chỉ có filter), **widget liên hệ nổi Zalo/Messenger**, **đã xem gần đây**, **sắp xếp bán chạy** (`sold_count`), **breadcrumb UI**. Chủ động bỏ qua (YAGNI): wishlist, so sánh sản phẩm, newsletter, store locator đa chi nhánh, live chat tự host, review kèm ảnh (để phase reviews).

## 2. Data model bổ sung

Quy ước chung như spec tổng: `id` bigint PK + `created_at`/`updated_at`.

### 2.1 `settings` — key-value cho nội dung header/footer/liên hệ

| Cột | Kiểu | Ghi chú |
|---|---|---|
| key | string, unique | |
| value | text, nullable | |
| group | string | `contact` / `social` / `footer` / `usp` — admin UI nhóm form theo cột này |

Seeder bắt buộc (giá trị mẫu thật, không lorem):
- `contact`: `hotline`, `email`, `address`, `open_hours`
- `social`: `facebook_url`, `zalo_url`, `messenger_url`, `instagram_url`
- `footer`: `business_name`, `business_registration`, `copyright`
- `usp`: `usp_1`, `usp_2`, `usp_3` (vd "Da bò thật 100%", "Bảo hành 12 tháng", "Freeship đơn từ 500K")

Widget nổi Zalo/Messenger chỉ hiển thị khi key tương ứng có giá trị.

### 2.2 `pages` — trang nội dung CMS-lite

| Cột | Kiểu | Ghi chú |
|---|---|---|
| title | string | |
| slug | string, unique | sinh bằng helper `UniqueSlug` đã có |
| content | longtext | HTML; admin soạn qua textarea (không rich editor trong phase này). Chỉ admin/staff soạn nên rủi ro XSS chấp nhận được; storefront vẫn sanitize khi render (xem §5.2) |
| seo_title | string, nullable | |
| seo_description | string, nullable | |
| is_active | boolean, default true | |

Seeder: `gioi-thieu`, `chinh-sach-doi-tra`, `chinh-sach-bao-hanh`, `chinh-sach-giao-hang`, `huong-dan-chon-size` (nội dung size guide này dùng chung cho modal trên PDP).

### 2.3 `banners` — đúng spec tổng §3.4

| Cột | Kiểu | Ghi chú |
|---|---|---|
| image | string | upload theo pattern `ProductImageController`: disk `public`, URL qua `Storage::disk('public')->url()` |
| link | string, nullable | |
| position | enum: `homepage_hero`, `homepage_promo` | |
| sort_order | integer, default 0 | |
| starts_at / ends_at | timestamp, nullable | null = không giới hạn |
| is_active | boolean, default true | |

### 2.4 `products` — migration mới (không sửa migration cũ)

- `is_featured` boolean, default false — admin chọn sản phẩm hiển thị section "Sản phẩm nổi bật" trang chủ.
- `sold_count` integer, default 0 — phục vụ sort `best_selling`. **Logic cộng dồn làm ở phase checkout** (khi đơn `delivered`); phase này chỉ tạo cột + sort.

## 3. API public — controller namespace `App\Http\Controllers\Api\Storefront\*`

Không auth. Giữ convention backend: inline `Validator::make()` khi cần validate query, envelope JSON hiện hành, test-driven từng controller.

| Endpoint | Hành vi |
|---|---|
| `GET /api/categories` | Chỉ `is_active`, sort `sort_order`, kèm `products_count` (chỉ đếm `published`). Nuôi menu header + section danh mục trang chủ |
| `GET /api/categories/{slug}` | Chi tiết 1 danh mục active (name, description, seo_*). 404 nếu inactive/không tồn tại |
| `GET /api/brands` | Toàn bộ brands |
| `GET /api/products` | Chỉ `published`. Query params: `category` (slug), `brand` (slug), `material`, `size`, `color` (match trên variants), `price_min`, `price_max`, **`q`** (LIKE trên name), `sort` ∈ `newest` (default) / `price_asc` / `price_desc` / `best_selling` / `featured`, `page`. Phân trang Laravel chuẩn, 12/trang. Mỗi item đủ cho ProductCard: `name`, `slug`, `thumbnail`, `base_price`, `sale_price`, `created_at` |
| `GET /api/products/{slug}` | Chi tiết đầy đủ: images, variants, category, brand **+ `related`** (8 sp published cùng category, loại trừ chính nó) nhúng trong cùng response — tiết kiệm round-trip lúc build. 404 nếu không `published` |
| `GET /api/products/{slug}/availability` | Payload nhẹ: `base_price`, `sale_price`, `variants[]` (`id`, `size`, `color`, `stock_quantity`, `price_override`). Client gọi lúc load PDP để làm tươi giá/tồn kho của trang static (spec tổng §2) |
| `GET /api/pages/{slug}` | Trang CMS active. 404 nếu inactive |
| `GET /api/settings` | Toàn bộ settings dạng object `{key: value}` |
| `GET /api/banners` | Banner `is_active`, trong khung thời gian (`starts_at` null hoặc ≤ now, `ends_at` null hoặc ≥ now), sort `sort_order`, nhóm theo `position`: `{homepage_hero: [...], homepage_promo: [...]}` |

## 4. API admin bổ sung — trong group `auth:sanctum + active-only + staff-only` prefix `/admin` hiện có

- `Route::apiResource('banners', ...)` — CRUD; ảnh upload multipart trong store/update (validate `image`, max size như product images).
- `Route::apiResource('pages', ...)` — CRUD; slug sinh `UniqueSlug` từ title.
- `GET /admin/settings` — trả toàn bộ kèm `group`; `PUT /admin/settings` — bulk update `{key: value, ...}`, chỉ nhận key đã tồn tại (không tạo key mới qua API).
- `ProductController` store/update: validate + nhận thêm `is_featured` (boolean). Giữ nguyên hành vi partial-update đã fix ở Phase 1 (không mất field khi không gửi).

## 5. Frontend storefront

### 5.1 Layout & dọn scaffold cũ

**Viết lại `frontend/layouts/default.vue`** thành layout storefront:
- **Header** (sticky): logo → menu danh mục (từ `GET /api/categories`) → ô tìm kiếm (submit điều hướng `/tim-kiem?q=...`) → hotline (settings) → nút tài khoản (link login, hoặc tên user + logout nếu đã đăng nhập qua `useAuth`). Mobile: hamburger + drawer.
- **Footer** 4 cột: thông tin shop (settings group contact+footer), links chính sách (từ bảng `pages` active), danh mục (categories), social icons (settings group social).
- **Widget nổi**: Zalo + Messenger (chỉ hiện khi settings có giá trị), back-to-top.

**Xóa scaffold cũ** (template "quản lý hồ sơ" không liên quan shop): `pages/client/{index,profile,services}.vue`, `layouts/client.vue`, `pages/demo-routing.vue`, SCSS `pages/_client.scss`, `pages/_demo.scss`, `layouts/_client.scss`, và các key i18n mồ côi chỉ chúng dùng. `pages/index.vue` (landing giả 315 dòng) bị thay hoàn toàn bằng trang chủ thật.

### 5.2 Trang

| Trang | Route | Render | Nội dung |
|---|---|---|---|
| Trang chủ | `/` | prerender | Hero carousel (banners `homepage_hero`) → UspBar (3 USP settings) → lưới danh mục nổi bật (categories có thumbnail) → "Sản phẩm nổi bật" (`sort=featured`, 8 sp) → banner promo → "Hàng mới về" (`sort=newest`, 8 sp) |
| Danh mục | `/danh-muc/[slug]` | prerender từng slug | Breadcrumb → tên + mô tả danh mục (SEO text từ DB) → FilterSidebar (brand/material/size/color/khoảng giá) + SortSelect → ProductGrid → AppPagination. **Filter/sort/page ghi vào query string** (crawlable, share được); trang gốc static, thay đổi filter → fetch client-side (hybrid SSG chuẩn) |
| Chi tiết SP | `/san-pham/[slug]` | prerender từng slug | Xem chi tiết dưới bảng |
| Tìm kiếm | `/tim-kiem` | `ssr: false`, noindex | Đọc `?q=`, fetch client-side, tái dùng ProductGrid + AppPagination |
| Trang nội dung | `/[slug]` (catch-all) | prerender các slug active | Render `content` qua v-html **có sanitize** (strip script/event-handler attrs — dùng thư viện nhẹ hoặc regex whitelist đơn giản), seo_* từ DB. Route tĩnh (login, admin, danh-muc...) luôn thắng catch-all nên không xung đột |

**Chi tiết sản phẩm (PDP)** — trang quan trọng nhất:
- ImageGallery: ảnh chính + dải thumbnail, click đổi ảnh.
- Tên, PriceTag (giá sale + giá gốc gạch, badge `-x%`), badge "Mới" nếu `created_at` ≤ 14 ngày.
- **VariantPicker**: hàng nút size + hàng nút màu; tổ hợp hết hàng bị disable/gạch; hiển thị tồn kho của variant đang chọn ("Còn N đôi" / "Hết hàng").
- **Availability refresh**: `onMounted` gọi `GET /api/products/{slug}/availability`, override giá + tồn kho đã nướng trong HTML static — khách không bao giờ thấy giá/tồn cũ.
- Link "📏 Hướng dẫn chọn size" mở SizeGuideModal (nội dung page `huong-dan-chon-size` — fetch lần đầu mở, cache trong ref).
- **CTA phase này** (chưa có giỏ hàng): khối "Đặt hàng nhanh" gồm nút gọi hotline (`tel:`) + nút chat Zalo (settings). Phase checkout sẽ thay khối này bằng "Thêm vào giỏ" — dựng thành component riêng để swap gọn.
- Mô tả sản phẩm (v-html sanitize), "Sản phẩm liên quan" (field `related` từ API, ProductCard grid), "Đã xem gần đây" (`useRecentlyViewed` — localStorage, tối đa 8, client-only, không hiện sp đang xem).

### 5.3 Component (SCSS thuần)

`ProductCard` (ảnh 4:5, tên, PriceTag, badge sale/mới, hover elevate) · `ProductGrid` (2 cột mobile / 4 desktop) · `AppPagination` · `FilterSidebar` (desktop: cột trái; mobile: bottom-sheet + nút "Áp dụng") · `SortSelect` · `Breadcrumbs` · `BannerCarousel` (tự viết: autoplay, dots, swipe — không thêm dependency) · `VariantPicker` · `ImageGallery` · `SizeGuideModal` · `PriceTag` · `SectionHeading` · `UspBar` · `FloatingContact`.

Helper thuần (có unit test): `formatVnd(1290000) → "1.290.000₫"`, build/parse query string filter PLP.

### 5.4 UX/UI — "sáng sạch hiện đại"

- Nền trắng `#ffffff`, text chính `#1a1a1a`, xám phụ trợ; **một màu nhấn nâu da thuộc trầm** (khoảng `#8b5e34`, chốt chính xác khi implement với frontend-design skill) chỉ dùng cho CTA, badge sale, giá.
- Font Inter (đã preload trong `nuxt.config.ts`). Heading đậm, tracking chặt; body 15–16px.
- Ảnh là nhân vật chính: card 4:5, khoảng thở rộng, không viền thừa.
- Mobile-first; skeleton loading cho mọi vùng client-fetch; empty state có minh họa + CTA về trang chủ.
- Token hóa `_variables.scss` (màu/spacing/radius/shadow); partials mới: `pages/_home.scss` (viết lại), `pages/_plp.scss`, `pages/_pdp.scss`, `components/_product-card.scss`, `components/_storefront-header.scss`... theo cấu trúc `assets/scss/` hiện có.

### 5.5 i18n

- Khối key mới `storefront.*` trong `vi.json` + `en.json` cho toàn bộ UI chrome (nút, label filter, breadcrumb "Trang chủ", trạng thái tồn kho, empty state...).
- Nội dung DB không dịch; locale `en` chỉ đổi UI chrome. Giữ `prefix_except_default`, default `vi`.
- Dọn key mồ côi của scaffold cũ khi xóa (mục 5.1).

### 5.6 Data-fetching & build (SSG)

- Page dùng `useAsyncData` + `$fetch` tới `runtimeConfig.public.apiBase` — data nướng vào payload lúc `nuxi generate`. **Không** dùng `useApiClient` cho trang public (composable đó client-only, đọc localStorage token — dành cho phần authenticated/admin).
- **Route discovery**: hook `prerender:routes` (hoặc `nitro.prerender.routes` động) trong `nuxt.config.ts` gọi API lấy toàn bộ slug: products published, categories active, pages active → generate đủ mọi trang động.
- **Laravel phải chạy trong lúc `nuxi generate`** — ghi rõ thành prerequisite trong plan.
- Route rules bổ sung: `'/tim-kiem': { ssr: false }`. Giữ nguyên rules `/login`, `/register`, `/admin/**` hiện có.

### 5.7 SEO

- `useSeoMeta` mỗi trang: title/description ưu tiên `seo_*` DB, fallback `{name} | Giày dép Hồng An`; og:title/description/image; canonical URL tuyệt đối.
- JSON-LD (`useHead` script type `application/ld+json`): trang chủ `Organization` + `WebSite` (kèm `SearchAction` trỏ `/tim-kiem?q={search_term_string}`); PLP `BreadcrumbList` + `ItemList`; PDP `Product` + `Offer` (`priceCurrency: VND`, availability theo tồn kho) + `BreadcrumbList`.
- `sitemap.xml` + `robots.txt` sinh trong bước generate bằng Nitro hook (cùng data slug với prerender) — artifact tĩnh tự chứa, không cần Laravel command. `robots.txt` khai `Sitemap:` URL đúng, disallow `/admin`, `/tim-kiem`.
- `noindex` meta cho `/tim-kiem`.

## 6. Admin UI mới (Element Plus, pattern các trang admin Phase 2)

Sidebar `layouts/admin.vue` thêm 3 mục dưới "Sản phẩm": **Banner**, **Trang nội dung**, **Cài đặt website**.

| Trang | Pattern | Nội dung |
|---|---|---|
| `/admin/banners` | el-table + el-dialog (như Categories) | Bảng: thumbnail, vị trí (tag Hero/Promo), link, sort_order, khung thời gian, trạng thái, Sửa/Xóa. Dialog: el-upload ảnh (custom `http-request` qua `useApiClient` như `ProductImageManager`), select vị trí, input link, input-number sort, date-range picker, switch active |
| `/admin/pages` | el-table + el-dialog | Bảng: title, slug, trạng thái. Dialog: title, textarea content (`:rows="15"`), seo_title, seo_description, switch active |
| `/admin/settings` | **el-tabs form** (không phải bảng) | 4 tab theo `group`: Liên hệ / Mạng xã hội / Footer / USP. Mỗi tab render el-input theo key; một nút "Lưu tất cả" → `PUT /admin/settings` bulk |
| `/admin/products` (sửa) | — | Thêm switch "Nổi bật" trong tab Thông tin + cột el-tag "Nổi bật" trong bảng |

Toàn bộ text admin tiếng Việt, error handling + toast theo pattern hiện có.

## 7. Testing & verification

**Backend — TDD từng controller (convention hiện hành):**
- Storefront: categories (chỉ active, products_count chỉ đếm published), products (từng filter param, `q`, từng sort, phân trang, chỉ published), product detail (related đúng + loại trừ chính nó, 404 draft), availability, pages (404 inactive), settings, banners (lọc khung thời gian: đang chạy/chưa tới/đã hết hạn).
- Admin: banners CRUD + upload ảnh, pages CRUD + slug unique, settings get/bulk-put (từ chối key lạ), products `is_featured`. Guest → 401, customer → 403 đúng Gate.
- Ước tính: 62 hiện có + ~30–35 mới.

**Frontend:** không unit test page/component Vue (convention); unit test cho helper thuần (`formatVnd`, filter query builder). `npx nuxi typecheck` + `npm run test` sau mỗi task frontend.

**E2E cuối phase (browser):**
1. Seed demo: banners 2 vị trí, 5 pages, settings đầy đủ, ≥2 sp featured.
2. Admin: CRUD banner (kèm upload), CRUD page, sửa settings, bật "Nổi bật".
3. Storefront (dev mode): trang chủ đúng dữ liệu DB; PLP lọc/sort/phân trang + URL share được; PDP đủ tính năng (variant, size guide modal, related, recently viewed); tìm kiếm; trang nội dung; đổi locale `en` → UI chrome đổi.
4. **`nuxi generate` thật** (Laravel chạy): kiểm tra HTML tĩnh có meta/JSON-LD/nội dung render sẵn; `sitemap.xml`/`robots.txt` đúng; serve output tĩnh và xác nhận availability-refresh hoạt động trên PDP.

## 8. Thứ tự triển khai dự kiến (~14 task)

1. Migrations + models + seeders (settings, pages, banners, cột products)
2. API Storefront: categories + brands + settings + banners
3. API Storefront: products index (filter/sort/search/phân trang)
4. API Storefront: product detail + availability + pages
5. API Admin: banners CRUD + upload
6. API Admin: pages CRUD + settings + `is_featured`
7. Layout storefront + dọn scaffold cũ + i18n keys + SCSS tokens
8. Component nền: ProductCard, ProductGrid, AppPagination, PriceTag (+unit test helper), Breadcrumbs, SectionHeading
9. Trang chủ
10. PLP + trang tìm kiếm
11. PDP + trang nội dung catch-all
12. Admin UI: Banners + Pages + Settings + switch Nổi bật
13. Build/SEO: prerender:routes hook, JSON-LD, sitemap/robots, route rules, verify `nuxi generate`
14. E2E walkthrough (mục 7)

## 9. Ngoài phạm vi (đã chốt, không làm trong phase này)

- Giỏ hàng, checkout COD, orders API — plan kế tiếp ngay sau.
- CI/CD deploy FTP, webhook trigger rebuild — chờ chốt hosting (spec tổng §9.1).
- Blog, reviews, loyalty, discount codes — các phase sau theo lộ trình spec tổng §8.
- Dịch nội dung DB sang tiếng Anh; rich text editor cho admin; wishlist/so sánh/newsletter/store locator/live chat tự host; cộng dồn `sold_count` (làm ở phase checkout).
