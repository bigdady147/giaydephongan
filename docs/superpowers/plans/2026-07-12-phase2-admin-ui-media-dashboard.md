# Phase 2 — Admin UI Overhaul (Element Plus) + Image/Variant Management + Real Dashboard

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the three gaps found after Phase 0/1 shipped: (1) the admin dashboard is 100% fake static data disconnected from the real system, (2) there is no UI to upload/manage product images despite a working backend API for it, (3) there is no UI to edit a product's variants after creation. Fix all three while replacing the hand-rolled, duplicated table/form HTML across the admin pages with a real component library so the admin panel looks and behaves like a proper dashboard instead of a test scaffold.

**Architecture:** Adopt **Element Plus** (`element-plus` + `@element-plus/nuxt`) as the UI component library for the admin panel. It's Vue 3-native (unlike AdminLTE, which is jQuery/Bootstrap and would fight Nuxt's reactivity model), ships its own component-scoped CSS rather than a utility framework, and is what most modern Vue admin dashboards use for exactly the sidebar/table/card look being asked for. It is additive to the existing SCSS setup, not a replacement — `assets/scss/*` keeps applying to custom markup; Element Plus's own components bring their own styles via the Nuxt module. New reusable pieces: a `ProductImageManager` component (upload/list/delete, wraps the Phase 1 Task 5 image endpoints) and a `ProductVariantManager` component (list/add/edit/delete, wraps the Phase 1 Task 5 variant endpoints) — both embedded in the rebuilt Products admin page via tabs, both usable standalone if a future page needs them.

**Tech Stack:** Laravel 10.48 (unchanged conventions: inline `Validator::make()`, no FormRequest), Nuxt 4 + TypeScript, **Element Plus 2.x** (new), `@element-plus/icons-vue` (new).

## Global Constraints

- Element Plus is scoped to the **admin panel only** (`/admin/**`). It supersedes the prior "hand-rolled table/form HTML" pattern for admin pages specifically. This does not relax "SCSS only, no Tailwind" for the rest of the app (the future public storefront) — Element Plus ships its own CSS, it is not a utility-class framework and does not conflict with that constraint.
- `php artisan storage:link` must exist in any environment serving product images (already noted as a Phase 1 prerequisite; this plan actually runs it and verifies it works end-to-end for the first time).
- Keep existing backend conventions: inline `Validator::make()`, `{message, <entity>}` JSON envelope, `staff-only` Gate on all admin catalog routes.
- All user-facing text stays Vietnamese.
- UI tasks in this plan (layout/pages/components) follow the Phase 1 Tasks 7-9 precedent: no dedicated frontend unit tests for Vue pages (not the established pattern in this codebase), verified via `npx nuxi typecheck` + the existing `npm run test` regression suite + a final browser E2E task. The one new backend task (dashboard stats) IS test-driven, matching every other backend task in this project.

---

### Task 1: Add Element Plus to the Nuxt app

**Files:**
- Modify: `frontend/package.json`
- Modify: `frontend/nuxt.config.ts`

**Interfaces:**
- Produces: every Element Plus component (`ElButton`, `ElTable`, `ElDialog`, `ElForm`, `ElUpload`, etc.) and `@element-plus/icons-vue` icon components auto-imported and usable in any page/component from Task 2 onward, with no manual `import` needed for the components themselves (icons still need explicit import — see Task 2).

- [ ] **Step 1: Add dependencies**

Edit `frontend/package.json` — add to `dependencies`:

```json
    "element-plus": "^2.8.0",
    "@element-plus/icons-vue": "^2.3.1"
```

and to `devDependencies`:

```json
    "@element-plus/nuxt": "^1.1.1"
```

If any of these exact versions fail to resolve against the installed Nuxt/Vue versions, use the latest compatible version within the same major and note the change in your report (same escape hatch as Phase 0 Task 6's `@vitejs/plugin-vue` bump).

- [ ] **Step 2: Install**

Run: `cd frontend && npm install`
Expected: exits 0.

- [ ] **Step 3: Register the Nuxt module**

Edit `frontend/nuxt.config.ts` — change:

```typescript
  modules: ['@nuxtjs/i18n'],
```

to:

```typescript
  modules: ['@nuxtjs/i18n', '@element-plus/nuxt'],
```

Leave every other key in the file untouched.

- [ ] **Step 4: Verify the module loads**

Run: `cd frontend && npx nuxi prepare`
Expected: exits 0, no "module not found" or resolution errors.

- [ ] **Step 5: Commit**

```bash
git add frontend/package.json frontend/package-lock.json frontend/nuxt.config.ts
git commit -m "chore: add Element Plus component library to the admin frontend"
```

---

### Task 2: Rebuild the admin layout with Element Plus

**Files:**
- Modify: `frontend/layouts/admin.vue` (full rewrite)

**Interfaces:**
- Consumes: `useAuth()` (Phase 0) — destructure `{ user, isAdmin, logout }`, not `auth.user`/`auth.isAdmin` (Vue's template ref-auto-unwrap only applies to top-level `<script setup>` bindings, not nested property access on a plain returned object).
- Produces: sidebar nav items for Dashboard/Danh mục/Thương hiệu/Sản phẩm/Users (Users hidden unless `isAdmin`, fixing the pre-existing bug where staff saw a link to an admin-only 403 page).

- [ ] **Step 1: Replace the whole file**

```html
<template>
  <el-container class="admin-layout">
    <el-aside width="220px" class="admin-sidebar">
      <div class="sidebar-brand">
        <span class="brand-icon">👞</span>
        <span class="brand-name">Hồng An Admin</span>
      </div>
      <el-menu :default-active="route.path" router class="sidebar-menu" background-color="#1f2937" text-color="#d1d5db" active-text-color="#ffffff">
        <el-menu-item index="/admin">
          <el-icon><Odometer /></el-icon>
          <span>{{ $t('navigation.dashboard') }}</span>
        </el-menu-item>
        <el-menu-item index="/admin/categories">
          <el-icon><Collection /></el-icon>
          <span>Danh mục</span>
        </el-menu-item>
        <el-menu-item index="/admin/brands">
          <el-icon><PriceTag /></el-icon>
          <span>Thương hiệu</span>
        </el-menu-item>
        <el-menu-item index="/admin/products">
          <el-icon><Goods /></el-icon>
          <span>Sản phẩm</span>
        </el-menu-item>
        <el-menu-item v-if="isAdmin" index="/admin/users">
          <el-icon><User /></el-icon>
          <span>{{ $t('navigation.users') }}</span>
        </el-menu-item>
      </el-menu>
    </el-aside>

    <el-container>
      <el-header class="admin-header">
        <span class="header-title">Giày dép Hồng An</span>
        <el-dropdown @command="handleCommand">
          <span class="header-user">
            <el-avatar :size="32">{{ userInitial }}</el-avatar>
            <span class="header-username">{{ user?.name ?? 'Admin' }}</span>
            <el-icon><ArrowDown /></el-icon>
          </span>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="logout">Đăng xuất</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </el-header>

      <el-main class="admin-main">
        <slot />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Odometer, Collection, PriceTag, Goods, User, ArrowDown } from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const { user, isAdmin, logout } = useAuth()

const userInitial = computed(() => (user.value?.name ?? 'A').charAt(0).toUpperCase())

const handleCommand = async (command: string) => {
  if (command !== 'logout') return

  try {
    await ElMessageBox.confirm(t('auth.logoutConfirm'), 'Xác nhận', {
      confirmButtonText: 'Đăng xuất',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await logout()
  } catch {
    // Local session is already cleared by logout()'s finally block.
  }
  router.push('/login')
}
</script>

<style scoped lang="scss">
.admin-layout {
  min-height: 100vh;
}

.admin-sidebar {
  background-color: #1f2937;
  display: flex;
  flex-direction: column;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 20px 16px;
  color: white;
  font-weight: 700;
  font-size: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar-menu {
  border-right: none;
  flex: 1;
}

.admin-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #e5e7eb;
  background-color: white;
}

.header-title {
  font-weight: 600;
  font-size: 16px;
}

.header-user {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.header-username {
  font-size: 14px;
  color: #374151;
}

.admin-main {
  background-color: #f3f4f6;
  padding: 24px;
}
</style>
```

- [ ] **Step 2: Typecheck**

Run: `cd frontend && npx nuxi typecheck`
Expected: no errors in `layouts/admin.vue` (pre-existing errors in untouched files are out of scope).

- [ ] **Step 3: Run the regression suite**

Run: `cd frontend && npm run test`
Expected: all 12 existing tests still pass.

- [ ] **Step 4: Commit**

```bash
git add frontend/layouts/admin.vue
git commit -m "feat: rebuild admin layout with Element Plus sidebar/header"
```

---

### Task 3: Backend dashboard stats endpoint

**Files:**
- Create: `app/Http/Controllers/Api/Admin/DashboardController.php`
- Modify: `routes/api.php` (one line inside the existing admin group)
- Test: `tests/Feature/Admin/DashboardControllerTest.php`

**Interfaces:**
- Produces: `GET /api/admin/dashboard/stats` → `{categories_count, brands_count, products_count, products_published_count, products_draft_count, products_archived_count, variants_count, low_stock_variants_count}`. "Low stock" = `stock_quantity < 5`.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_stats(): void
    {
        $this->getJson('/api/admin/dashboard/stats')->assertStatus(401);
    }

    public function test_staff_can_view_stats(): void
    {
        Category::factory()->count(2)->create();
        Brand::factory()->count(3)->create();
        $published = Product::factory()->create(['status' => 'published']);
        Product::factory()->create(['status' => 'draft']);
        ProductVariant::factory()->for($published)->create(['stock_quantity' => 2, 'sku' => 'LOW-1']);
        ProductVariant::factory()->for($published)->create(['stock_quantity' => 20, 'sku' => 'HIGH-1']);

        Sanctum::actingAs(User::factory()->staff()->create());

        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(200)->assertJson([
            'categories_count' => 2,
            'brands_count' => 3,
            'products_count' => 2,
            'products_published_count' => 1,
            'products_draft_count' => 1,
            'products_archived_count' => 0,
            'variants_count' => 2,
            'low_stock_variants_count' => 1,
        ]);
    }
}
```

- [ ] **Step 2: Run and confirm they fail**

Run: `php artisan test --filter=DashboardControllerTest`
Expected: FAIL — route doesn't exist yet.

- [ ] **Step 3: Implement the controller**

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'categories_count' => Category::count(),
            'brands_count' => Brand::count(),
            'products_count' => Product::count(),
            'products_published_count' => Product::where('status', 'published')->count(),
            'products_draft_count' => Product::where('status', 'draft')->count(),
            'products_archived_count' => Product::where('status', 'archived')->count(),
            'variants_count' => ProductVariant::count(),
            'low_stock_variants_count' => ProductVariant::where('stock_quantity', '<', 5)->count(),
        ]);
    }
}
```

- [ ] **Step 4: Register the route**

Add inside the existing `/admin` group in `routes/api.php` (any line inside the block is fine, e.g. right after the `use` imports are extended with `use App\Http\Controllers\Api\Admin\DashboardController;` at the top):

```php
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
```

- [ ] **Step 5: Run and confirm they pass**

Run: `php artisan test --filter=DashboardControllerTest`
Expected: 2 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Admin/DashboardController.php routes/api.php tests/Feature/Admin/DashboardControllerTest.php
git commit -m "feat: add admin dashboard stats API"
```

---

### Task 4: Real dashboard page

**Files:**
- Modify: `frontend/pages/admin/index.vue` (full rewrite — currently 100% static fake data: "Tổng số hồ sơ: 1.234" etc., unrelated to the real system)

**Interfaces:**
- Consumes: `useApiClient()` (Phase 0); `GET /admin/dashboard/stats` (Task 3).

- [ ] **Step 1: Replace the whole file**

```html
<template>
  <div class="dashboard-page">
    <h1 class="page-title">Tổng quan</h1>

    <el-alert v-if="loadError" :title="loadError" type="error" show-icon class="dashboard-alert" />

    <el-row :gutter="20" v-loading="loading">
      <el-col :span="6">
        <el-card shadow="hover">
          <el-statistic title="Sản phẩm" :value="stats.products_count" />
          <p class="stat-sub">{{ stats.products_published_count }} đã đăng · {{ stats.products_draft_count }} nháp</p>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <el-statistic title="Danh mục" :value="stats.categories_count" />
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <el-statistic title="Thương hiệu" :value="stats.brands_count" />
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <el-statistic title="Biến thể sắp hết hàng" :value="stats.low_stock_variants_count" />
          <p class="stat-sub">Trong tổng {{ stats.variants_count }} biến thể (dưới 5 đôi)</p>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="quick-links">
      <el-col :span="8">
        <el-card shadow="never">
          <template #header>Thao tác nhanh</template>
          <div class="quick-actions">
            <el-button type="primary" @click="router.push('/admin/products')">+ Thêm sản phẩm</el-button>
            <el-button @click="router.push('/admin/categories')">Quản lý danh mục</el-button>
            <el-button @click="router.push('/admin/brands')">Quản lý thương hiệu</el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

definePageMeta({ layout: 'admin' })

interface DashboardStats {
  categories_count: number
  brands_count: number
  products_count: number
  products_published_count: number
  products_draft_count: number
  products_archived_count: number
  variants_count: number
  low_stock_variants_count: number
}

const router = useRouter()
const api = useApiClient()

const loading = ref(true)
const loadError = ref('')
const stats = reactive<DashboardStats>({
  categories_count: 0,
  brands_count: 0,
  products_count: 0,
  products_published_count: 0,
  products_draft_count: 0,
  products_archived_count: 0,
  variants_count: 0,
  low_stock_variants_count: 0
})

const loadStats = async () => {
  loading.value = true
  loadError.value = ''
  try {
    Object.assign(stats, await api.get<DashboardStats>('/admin/dashboard/stats'))
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải thống kê.'
  } finally {
    loading.value = false
  }
}

onMounted(loadStats)
</script>

<style scoped lang="scss">
.dashboard-page {
  max-width: 1200px;
}

.page-title {
  margin-bottom: 20px;
}

.stat-sub {
  margin-top: 8px;
  font-size: 12px;
  color: #6b7280;
}

.quick-links {
  margin-top: 20px;
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.dashboard-alert {
  margin-bottom: 16px;
}
</style>
```

- [ ] **Step 2: Typecheck + regression**

Run: `cd frontend && npx nuxi typecheck` — no errors in this file.
Run: `cd frontend && npm run test` — 12/12 still pass.

- [ ] **Step 3: Commit**

```bash
git add frontend/pages/admin/index.vue
git commit -m "feat: replace fake dashboard with real stats from the catalog API"
```

---

### Task 5: Rebuild the Categories admin page with Element Plus

**Files:**
- Modify: `frontend/pages/admin/categories/index.vue` (full rewrite — replaces the hand-rolled HTML table/inline panel from Phase 1 Task 7 with `el-table`/`el-dialog`)

**Interfaces:**
- Consumes: `useApiClient()`; `/admin/categories` endpoints (unchanged, Phase 1 Task 2).

- [ ] **Step 1: Replace the whole file**

```html
<template>
  <div class="categories-page">
    <div class="page-header">
      <h1>Danh mục sản phẩm</h1>
      <el-button type="primary" @click="openCreateForm">+ Thêm danh mục</el-button>
    </div>

    <el-alert v-if="loadError" :title="loadError" type="error" show-icon class="page-alert" />

    <el-table :data="categories" v-loading="loading" stripe style="width: 100%">
      <el-table-column prop="name" label="Tên" />
      <el-table-column prop="slug" label="Slug" />
      <el-table-column label="Trạng thái" width="120">
        <template #default="{ row }">
          <el-tag :type="row.is_active ? 'success' : 'info'">{{ row.is_active ? 'Hoạt động' : 'Ẩn' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="" width="160">
        <template #default="{ row }">
          <el-button link type="primary" @click="openEditForm(row)">Sửa</el-button>
          <el-button link type="danger" @click="removeCategory(row)">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="showForm" :title="editingId ? 'Sửa danh mục' : 'Thêm danh mục'" width="480px">
      <el-alert v-if="formError" :title="formError" type="error" show-icon class="page-alert" />
      <el-form label-position="top">
        <el-form-item label="Tên danh mục" :error="formErrors.name" required>
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="Mô tả (SEO)">
          <el-input v-model="form.description" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeForm">Hủy</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">Lưu</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

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
const loading = ref(true)
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
  loading.value = true
  loadError.value = ''
  try {
    categories.value = await api.get<Category[]>('/admin/categories')
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải danh mục.'
  } finally {
    loading.value = false
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
    ElMessage.success('Đã lưu danh mục')
    await loadCategories()
  } catch (err: any) {
    formErrors.name = err.errors?.name?.[0] ?? ''
    formError.value = err.message ?? 'Lưu danh mục thất bại.'
  } finally {
    saving.value = false
  }
}

const removeCategory = async (category: Category) => {
  try {
    await ElMessageBox.confirm(`Xóa danh mục "${category.name}"?`, 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/categories/${category.id}`)
    ElMessage.success('Đã xóa danh mục')
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

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-alert {
  margin-bottom: 16px;
}
</style>
```

Note: `removeCategory`'s error path now correctly surfaces `err.message` — the backend fix from the earlier final review (`CategoryController::destroy` returns 409 with a Vietnamese message when the category still has products) will render directly in `loadError` here.

- [ ] **Step 2: Typecheck + regression**

Run: `cd frontend && npx nuxi typecheck` — no errors in this file.
Run: `cd frontend && npm run test` — 12/12 still pass.

- [ ] **Step 3: Commit**

```bash
git add frontend/pages/admin/categories/index.vue
git commit -m "feat: rebuild categories admin page with Element Plus table/dialog"
```

---

### Task 6: Rebuild the Brands admin page with Element Plus

**Files:**
- Modify: `frontend/pages/admin/brands/index.vue` (full rewrite, mirrors Task 5's pattern against `/admin/brands`)

**Interfaces:**
- Consumes: `useApiClient()`; `/admin/brands` endpoints (unchanged, Phase 1 Task 3).

- [ ] **Step 1: Replace the whole file**

```html
<template>
  <div class="brands-page">
    <div class="page-header">
      <h1>Thương hiệu</h1>
      <el-button type="primary" @click="openCreateForm">+ Thêm thương hiệu</el-button>
    </div>

    <el-alert v-if="loadError" :title="loadError" type="error" show-icon class="page-alert" />

    <el-table :data="brands" v-loading="loading" stripe style="width: 100%">
      <el-table-column prop="name" label="Tên" />
      <el-table-column prop="slug" label="Slug" />
      <el-table-column label="" width="160">
        <template #default="{ row }">
          <el-button link type="primary" @click="openEditForm(row)">Sửa</el-button>
          <el-button link type="danger" @click="removeBrand(row)">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="showForm" :title="editingId ? 'Sửa thương hiệu' : 'Thêm thương hiệu'" width="480px">
      <el-alert v-if="formError" :title="formError" type="error" show-icon class="page-alert" />
      <el-form label-position="top">
        <el-form-item label="Tên thương hiệu" :error="formErrors.name" required>
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="Mô tả">
          <el-input v-model="form.description" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeForm">Hủy</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">Lưu</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

definePageMeta({ layout: 'admin' })

interface Brand {
  id: number
  name: string
  slug: string
  description: string | null
}

const api = useApiClient()

const brands = ref<Brand[]>([])
const loading = ref(true)
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
  loading.value = true
  loadError.value = ''
  try {
    brands.value = await api.get<Brand[]>('/admin/brands')
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải thương hiệu.'
  } finally {
    loading.value = false
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
    ElMessage.success('Đã lưu thương hiệu')
    await loadBrands()
  } catch (err: any) {
    formErrors.name = err.errors?.name?.[0] ?? ''
    formError.value = err.message ?? 'Lưu thương hiệu thất bại.'
  } finally {
    saving.value = false
  }
}

const removeBrand = async (brand: Brand) => {
  try {
    await ElMessageBox.confirm(`Xóa thương hiệu "${brand.name}"?`, 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/brands/${brand.id}`)
    ElMessage.success('Đã xóa thương hiệu')
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

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-alert {
  margin-bottom: 16px;
}
</style>
```

- [ ] **Step 2: Typecheck + regression**

Run: `cd frontend && npx nuxi typecheck` — no errors in this file.
Run: `cd frontend && npm run test` — 12/12 still pass.

- [ ] **Step 3: Commit**

```bash
git add frontend/pages/admin/brands/index.vue
git commit -m "feat: rebuild brands admin page with Element Plus table/dialog"
```

---

### Task 7: `storage:link` + `ProductImageManager` component

**Files:**
- Create: `frontend/components/ProductImageManager.vue`

**Interfaces:**
- Consumes: `useApiClient()`; `POST /admin/products/{product}/images`, `DELETE /admin/images/{image}` (Phase 1 Task 5); `GET /admin/products/{product}` (Phase 1 Task 4, already eager-loads `images`).
- Produces: a `<ProductImageManager :product-id="..." />` component (auto-imported from `frontend/components/`, no manual import needed) — Task 9 embeds it in the Products page. `productId: number | null` — pass `null` while a product is still being created (no images can be attached until the product row exists); the component shows a note instead of the upload control in that case.

**Prerequisite (environment, not app code):** run `php artisan storage:link` once in this environment so `public/storage` → `storage/app/public` exists. Without it, uploaded images 404 in the browser even though the upload API call succeeds.

- [ ] **Step 1: Run the storage link setup**

Run: `php artisan storage:link`
Expected: exits 0, creates `public/storage` as a symlink to `storage/app/public`.
Verify: `ls public/storage` (or equivalent) shows it resolves without error.

- [ ] **Step 2: Create the component**

```html
<!-- frontend/components/ProductImageManager.vue -->
<template>
  <div class="image-manager">
    <p v-if="!productId" class="image-manager-note">Lưu sản phẩm trước khi thêm ảnh.</p>
    <template v-else>
      <el-alert v-if="error" :title="error" type="error" show-icon class="image-manager-alert" />

      <div class="image-grid">
        <div v-for="image in images" :key="image.id" class="image-tile">
          <img :src="image.url" :alt="'Ảnh sản phẩm ' + image.id">
          <el-button link type="danger" size="small" class="image-remove" @click="removeImage(image)">Xóa</el-button>
        </div>
      </div>

      <el-upload :show-file-list="false" :auto-upload="true" accept="image/*" :http-request="uploadImage">
        <el-button :loading="uploading">+ Tải ảnh lên</el-button>
      </el-upload>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { ElMessageBox } from 'element-plus'

interface ProductImage {
  id: number
  url: string
  sort_order: number
}

const props = defineProps<{ productId: number | null }>()

const api = useApiClient()

const images = ref<ProductImage[]>([])
const error = ref('')
const uploading = ref(false)

const loadImages = async () => {
  const requestedId = props.productId
  if (!requestedId) return
  error.value = ''
  try {
    const product = await api.get<{ images: ProductImage[] }>(`/admin/products/${requestedId}`)
    if (props.productId !== requestedId) return // productId changed while this request was in flight
    images.value = product.images
  } catch (err: any) {
    if (props.productId !== requestedId) return
    error.value = err.message ?? 'Không thể tải ảnh sản phẩm.'
  }
}

const uploadImage = async (options: { file: File }) => {
  if (!props.productId) return
  uploading.value = true
  error.value = ''

  try {
    const body = new FormData()
    body.append('image', options.file)
    await api.post(`/admin/products/${props.productId}/images`, body)
    await loadImages()
  } catch (err: any) {
    error.value = err.message ?? 'Tải ảnh lên thất bại.'
  } finally {
    uploading.value = false
  }
}

const removeImage = async (image: ProductImage) => {
  try {
    await ElMessageBox.confirm('Xóa ảnh này?', 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  error.value = ''
  try {
    await api.del(`/admin/images/${image.id}`)
    await loadImages()
  } catch (err: any) {
    error.value = err.message ?? 'Xóa ảnh thất bại.'
  }
}

watch(() => props.productId, () => {
  images.value = []
  loadImages()
})
onMounted(loadImages)
</script>

<style scoped lang="scss">
.image-manager-note {
  color: #6b7280;
  font-size: 13px;
}

.image-manager-alert {
  margin-bottom: 12px;
}

.image-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.image-tile {
  position: relative;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;

  img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    display: block;
  }

  .image-remove {
    position: absolute;
    bottom: 2px;
    right: 2px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 4px;
  }
}
</style>
```

Note on `uploadImage`: `useApiClient().post()` passes `body` straight through to `$fetch`/ofetch without setting `Content-Type` — ofetch auto-detects a `FormData` body and lets the browser set the correct multipart boundary itself, so no special-casing is needed versus a JSON post.

- [ ] **Step 3: Typecheck**

Run: `cd frontend && npx nuxi typecheck` — no errors in this file.

- [ ] **Step 4: Commit**

```bash
git add frontend/components/ProductImageManager.vue
git commit -m "feat: add ProductImageManager component for admin image upload/delete"
```

---

### Task 8: `ProductVariantManager` component

**Files:**
- Create: `frontend/components/ProductVariantManager.vue`

**Interfaces:**
- Consumes: `useApiClient()`; `POST /admin/products/{product}/variants`, `PUT/DELETE /admin/variants/{variant}` (Phase 1 Task 5); `GET /admin/products/{product}` (already eager-loads `variants`).
- Produces: a `<ProductVariantManager :product-id="..." />` component (auto-imported). Same `productId: number | null` contract as Task 7's component.

- [ ] **Step 1: Create the component**

```html
<!-- frontend/components/ProductVariantManager.vue -->
<template>
  <div class="variant-manager">
    <p v-if="!productId" class="variant-manager-note">Lưu sản phẩm trước khi quản lý biến thể.</p>
    <template v-else>
      <el-alert v-if="error" :title="error" type="error" show-icon class="variant-manager-alert" />

      <el-table :data="variants" v-loading="loading" size="small" stripe>
        <el-table-column label="Size" width="90">
          <template #default="{ row }">
            <el-input v-model="row.size" size="small" @blur="saveVariant(row)" />
          </template>
        </el-table-column>
        <el-table-column label="Màu" width="110">
          <template #default="{ row }">
            <el-input v-model="row.color" size="small" @blur="saveVariant(row)" />
          </template>
        </el-table-column>
        <el-table-column label="SKU">
          <template #default="{ row }">
            <el-input v-model="row.sku" size="small" @blur="saveVariant(row)" />
          </template>
        </el-table-column>
        <el-table-column label="Tồn kho" width="130">
          <template #default="{ row }">
            <el-input-number v-model="row.stock_quantity" :min="0" size="small" @change="saveVariant(row)" />
          </template>
        </el-table-column>
        <el-table-column label="" width="70">
          <template #default="{ row }">
            <el-button link type="danger" size="small" @click="removeVariant(row)">Xóa</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="add-variant-row">
        <el-input v-model="newVariant.size" placeholder="Size" size="small" />
        <el-input v-model="newVariant.color" placeholder="Màu" size="small" />
        <el-input v-model="newVariant.sku" placeholder="SKU" size="small" />
        <el-input-number v-model="newVariant.stock_quantity" :min="0" placeholder="Tồn kho" size="small" />
        <el-button type="primary" size="small" :loading="adding" @click="addVariant">+ Thêm</el-button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { ElMessageBox } from 'element-plus'

interface Variant {
  id: number
  size: string
  color: string
  sku: string
  stock_quantity: number
}

const props = defineProps<{ productId: number | null }>()

const api = useApiClient()

const variants = ref<Variant[]>([])
const loading = ref(false)
const error = ref('')
const adding = ref(false)

const newVariant = reactive({ size: '', color: '', sku: '', stock_quantity: 0 })

const loadVariants = async () => {
  if (!props.productId) return
  loading.value = true
  error.value = ''
  try {
    const product = await api.get<{ variants: Variant[] }>(`/admin/products/${props.productId}`)
    variants.value = product.variants
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải biến thể.'
  } finally {
    loading.value = false
  }
}

const saveVariant = async (variant: Variant) => {
  error.value = ''
  try {
    await api.put(`/admin/variants/${variant.id}`, {
      size: variant.size,
      color: variant.color,
      sku: variant.sku,
      stock_quantity: variant.stock_quantity
    })
  } catch (err: any) {
    error.value = err.message ?? 'Cập nhật biến thể thất bại.'
    await loadVariants()
  }
}

const addVariant = async () => {
  if (!props.productId) return
  adding.value = true
  error.value = ''
  try {
    await api.post(`/admin/products/${props.productId}/variants`, { ...newVariant })
    newVariant.size = ''
    newVariant.color = ''
    newVariant.sku = ''
    newVariant.stock_quantity = 0
    await loadVariants()
  } catch (err: any) {
    error.value = err.message ?? 'Thêm biến thể thất bại.'
  } finally {
    adding.value = false
  }
}

const removeVariant = async (variant: Variant) => {
  try {
    await ElMessageBox.confirm(`Xóa biến thể "${variant.size} - ${variant.color}"?`, 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  error.value = ''
  try {
    await api.del(`/admin/variants/${variant.id}`)
    await loadVariants()
  } catch (err: any) {
    error.value = err.message ?? 'Xóa biến thể thất bại.'
  }
}

watch(() => props.productId, loadVariants)
onMounted(loadVariants)
</script>

<style scoped lang="scss">
.variant-manager-note {
  color: #6b7280;
  font-size: 13px;
}

.variant-manager-alert {
  margin-bottom: 12px;
}

.add-variant-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr auto;
  gap: 8px;
  margin-top: 12px;
  align-items: center;
}
</style>
```

- [ ] **Step 2: Typecheck**

Run: `cd frontend && npx nuxi typecheck` — no errors in this file.

- [ ] **Step 3: Commit**

```bash
git add frontend/components/ProductVariantManager.vue
git commit -m "feat: add ProductVariantManager component for post-creation variant editing"
```

---

### Task 9: Rebuild the Products admin page — Element Plus + image/variant tabs

**Files:**
- Modify: `frontend/pages/admin/products/index.vue` (full rewrite)

**Interfaces:**
- Consumes: `useApiClient()`; `/admin/products`, `/admin/categories`, `/admin/brands` (Phase 1); `<ProductImageManager>` (Task 7); `<ProductVariantManager>` (Task 8).

**Behavior change from Phase 1 Task 9:** the dialog no longer auto-closes after a successful save (create or update) — it stays open so the admin can immediately switch to the "Ảnh"/"Biến thể" tabs without reopening. A success toast (`ElMessage.success`) replaces the implicit "dialog closed = it worked" feedback. Creating a product now keeps `editingId` set to the newly-created product's id afterward, which is what unlocks the two tabs (they're `disabled` while `editingId` is null, i.e. for a brand-new unsaved product).

- [ ] **Step 1: Replace the whole file**

```html
<template>
  <div class="products-page">
    <div class="page-header">
      <h1>Sản phẩm</h1>
      <el-button type="primary" @click="openCreateForm">+ Thêm sản phẩm</el-button>
    </div>

    <el-alert v-if="loadError" :title="loadError" type="error" show-icon class="page-alert" />

    <el-table :data="products" v-loading="loading" stripe style="width: 100%">
      <el-table-column prop="name" label="Tên" />
      <el-table-column label="Danh mục">
        <template #default="{ row }">{{ row.category?.name }}</template>
      </el-table-column>
      <el-table-column label="Giá" width="130">
        <template #default="{ row }">{{ row.base_price.toLocaleString('vi-VN') }}đ</template>
      </el-table-column>
      <el-table-column label="Trạng thái" width="110">
        <template #default="{ row }">
          <el-tag :type="statusTagType(row.status)">{{ statusLabel(row.status) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Biến thể" width="90">
        <template #default="{ row }">{{ row.variants.length }}</template>
      </el-table-column>
      <el-table-column label="" width="160">
        <template #default="{ row }">
          <el-button link type="primary" @click="openEditForm(row)">Sửa</el-button>
          <el-button link type="danger" @click="removeProduct(row)">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="showForm" :title="editingId ? 'Sửa sản phẩm' : 'Thêm sản phẩm'" width="640px">
      <el-alert v-if="formError" :title="formError" type="error" show-icon class="page-alert" />

      <el-tabs v-model="activeTab">
        <el-tab-pane label="Thông tin" name="info">
          <el-form label-position="top">
            <el-form-item label="Tên sản phẩm" :error="formErrors.name" required>
              <el-input v-model="form.name" />
            </el-form-item>
            <el-form-item label="Danh mục" :error="formErrors.category_id" required>
              <el-select v-model="form.category_id" placeholder="Chọn danh mục" style="width: 100%">
                <el-option v-for="category in categories" :key="category.id" :label="category.name" :value="category.id" />
              </el-select>
            </el-form-item>
            <el-form-item label="Thương hiệu (không bắt buộc)">
              <el-select v-model="form.brand_id" placeholder="Không chọn" clearable style="width: 100%">
                <el-option v-for="brand in brands" :key="brand.id" :label="brand.name" :value="brand.id" />
              </el-select>
            </el-form-item>
            <el-form-item label="Chất liệu">
              <el-select v-model="form.material" style="width: 100%">
                <el-option label="Da bò thật (full-grain)" value="full_grain_leather" />
                <el-option label="Da lộn" value="suede" />
                <el-option label="Da PU" value="pu_leather" />
                <el-option label="Khác" value="other" />
              </el-select>
            </el-form-item>
            <el-form-item label="Giá gốc (VNĐ)" :error="formErrors.base_price" required>
              <el-input-number v-model="form.base_price" :min="0" :step="10000" style="width: 100%" />
            </el-form-item>
            <el-form-item label="Giá khuyến mãi (không bắt buộc)" :error="formErrors.sale_price">
              <el-input-number v-model="form.sale_price" :min="0" :step="10000" style="width: 100%" />
            </el-form-item>
            <el-form-item label="Trạng thái">
              <el-select v-model="form.status" style="width: 100%">
                <el-option label="Nháp" value="draft" />
                <el-option label="Đã đăng" value="published" />
                <el-option label="Lưu trữ" value="archived" />
              </el-select>
            </el-form-item>
          </el-form>

          <fieldset v-if="!editingId" class="variants-fieldset">
            <legend>Biến thể (size / màu)</legend>
            <div v-for="(variant, index) in form.variants" :key="index" class="variant-row">
              <el-input v-model="variant.size" placeholder="Size (vd: 41)" size="small" />
              <el-input v-model="variant.color" placeholder="Màu (vd: Nâu)" size="small" />
              <el-input v-model="variant.sku" placeholder="SKU" size="small" />
              <el-input-number v-model="variant.stock_quantity" :min="0" size="small" />
              <el-button link type="danger" size="small" @click="removeVariantRow(index)">Xóa</el-button>
            </div>
            <el-button size="small" @click="addVariantRow">+ Thêm biến thể</el-button>
          </fieldset>
          <p v-else class="variant-edit-note">Quản lý biến thể của sản phẩm đã lưu ở tab "Biến thể".</p>
        </el-tab-pane>

        <el-tab-pane label="Ảnh" name="images" :disabled="!editingId">
          <ProductImageManager :product-id="editingId" />
        </el-tab-pane>

        <el-tab-pane label="Biến thể" name="variants" :disabled="!editingId">
          <ProductVariantManager :product-id="editingId" />
        </el-tab-pane>
      </el-tabs>

      <template #footer>
        <el-button @click="closeForm">Đóng</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">Lưu</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

definePageMeta({ layout: 'admin' })

interface Category { id: number; name: string }
interface Brand { id: number; name: string }
interface Variant { id?: number; size: string; color: string; sku: string; stock_quantity: number }
interface Product {
  id: number
  name: string
  base_price: number
  sale_price: number | null
  material: 'full_grain_leather' | 'suede' | 'pu_leather' | 'other'
  status: 'draft' | 'published' | 'archived'
  category?: Category
  brand?: Brand
  variants: Variant[]
}

const api = useApiClient()

const products = ref<Product[]>([])
const categories = ref<Category[]>([])
const brands = ref<Brand[]>([])
const loading = ref(true)
const loadError = ref('')
const showForm = ref(false)
const activeTab = ref('info')
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

const statusTagType = (status: Product['status']) => ({
  draft: 'info',
  published: 'success',
  archived: 'warning'
})[status] as 'info' | 'success' | 'warning'

const loadAll = async () => {
  loading.value = true
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
  } finally {
    loading.value = false
  }
}

const openCreateForm = () => {
  editingId.value = null
  activeTab.value = 'info'
  Object.assign(form, emptyForm())
  showForm.value = true
}

const openEditForm = (product: Product) => {
  editingId.value = product.id
  activeTab.value = 'info'
  Object.assign(form, {
    name: product.name,
    category_id: product.category?.id ?? '',
    brand_id: product.brand?.id ?? null,
    material: product.material,
    base_price: product.base_price,
    sale_price: product.sale_price,
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
    const payload = {
      ...form,
      base_price: Number(form.base_price) || 0,
      sale_price: form.sale_price ? Number(form.sale_price) : null
    }
    if (editingId.value) {
      await api.put(`/admin/products/${editingId.value}`, payload)
    } else {
      const created = await api.post<{ product: Product }>('/admin/products', payload)
      editingId.value = created.product.id
    }
    ElMessage.success('Đã lưu sản phẩm')
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
  try {
    await ElMessageBox.confirm(`Xóa sản phẩm "${product.name}"?`, 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/products/${product.id}`)
    ElMessage.success('Đã xóa sản phẩm')
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

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-alert {
  margin-bottom: 16px;
}

.variants-fieldset {
  margin-top: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
}

.variant-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr auto;
  gap: 8px;
  margin-bottom: 8px;
  align-items: center;
}

.variant-edit-note {
  margin-top: 12px;
  padding: 12px;
  background-color: #f9fafb;
  border-radius: 8px;
  font-size: 13px;
  color: #6b7280;
}
</style>
```

- [ ] **Step 2: Typecheck + regression**

Run: `cd frontend && npx nuxi typecheck` — no errors in this file.
Run: `cd frontend && npm run test` — 12/12 still pass.

- [ ] **Step 3: Commit**

```bash
git add frontend/pages/admin/products/index.vue
git commit -m "feat: rebuild products admin page with Element Plus tabs for info/images/variants"
```

---

### Task 10: End-to-end verification

**Files:** none (verification only) — but be ready to fix real bugs this step finds; two were found and fixed the first time this task ran (see below), neither caught by any unit test or per-task review.

**Interfaces:** exercises Tasks 1-9 together against the real backend.

**Known environment prerequisites** (fix once per environment, not app code):
- `frontend/app.vue` must wrap `<NuxtPage />` in `<NuxtLayout>`. Without it, `definePageMeta({ layout: 'admin' })` is a silent no-op and the entire Element Plus sidebar/header built in Tasks 2-9 never renders — the page falls back to bare, unstyled content. This is a pre-existing bug predating this plan; fix it before Step 4 if `git log -- frontend/app.vue` shows it's not already fixed.
- `.env`'s `APP_URL` must include the actual port the backend dev server runs on (e.g. `http://localhost:8000`, not bare `http://localhost`). `ProductImageController` builds image URLs from `Storage::disk('public')->url()`, which is built from `APP_URL` — a mismatched port means every uploaded image 404s in the browser even though the upload API call itself succeeds and the file is genuinely written to disk.

- [ ] **Step 1: Run the full backend suite**

Run: `php artisan test`
Expected: all tests pass (62 total — count drifted from the original 59+2=61 estimate due to an extra test added during Task 10's image-URL bug-fix pass; the final-review fix pass added an assertion to an existing test rather than a new test method, so the count stayed at 62).

- [ ] **Step 2: Run the full frontend suite**

Run: `cd frontend && npm run test`
Expected: 12/12 pass (no new unit tests added in this plan per the UI-task convention — see Global Constraints).

- [ ] **Step 3: Start both dev servers** (or confirm already running)

Run (background): `php artisan serve`
Run (background): `cd frontend && npm run dev`

- [ ] **Step 4: Browser walkthrough (use the Playwright browser tools)**

1. Log in as an admin user. Confirm the sidebar now shows Element Plus styling (dark sidebar, icons, not the old plain `<nav>` list) and the header shows the real logged-in user's name, not "Admin User".
2. Navigate to `/admin`. Confirm the 4 stat cards show real numbers matching the seeded data (7 categories, 4 brands, etc. — not "1.234").
3. Go to Danh mục / Thương hiệu — confirm both render as Element Plus tables, create/edit (dialog opens, not inline panel)/delete all still work.
4. Go to Sản phẩm → "+ Thêm sản phẩm" — fill in info tab, add 1-2 variant rows, save. Confirm a success toast appears and the dialog **stays open** (per Task 9's behavior change).
5. Switch to the "Ảnh" tab (should now be enabled). Upload an image file. Confirm it appears as a thumbnail without a page reload, and note the served URL loads a real image (not a 404 — this is what `storage:link` from Task 7 fixes).
6. Switch to the "Biến thể" tab. Confirm the variants added at creation appear in the table. Edit a stock quantity inline (blur the field), confirm it saves. Add one more variant via the bottom row. Delete one variant.
7. Close the dialog, reopen the same product via "Sửa" — confirm the "Ảnh" and "Biến thể" tabs are enabled immediately (not requiring another save) and show the data from steps 5-6.
8. Delete the test product, confirm it disappears and its image file cleanup doesn't error.

- [ ] **Step 5: Report results**

If any step fails, that's a bug in Tasks 1-9 — fix it, re-run that task's typecheck/regression check, then repeat this task from Step 4.

No commit for this task — it's verification only.
