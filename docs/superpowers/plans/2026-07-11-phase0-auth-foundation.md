# Phase 0 — Auth Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the existing but mocked/broken auth scaffolding (fake `useAuth.ts`, hardcoded-`true` route middleware, non-wired `login.vue`, zero test coverage on `AuthController`) into a real, tested register/login/logout/role-gating flow that Phase 1+ admin features can build on.

**Architecture:** Backend already has a working `AuthController` (Sanctum personal-access-token auth) and role/status Gates in `AuthServiceProvider` — this plan adds test coverage and leaves both largely as-is. The real gap is the frontend: `useAuth.ts` is corrupted/fake and `auth.global.ts` hardcodes `isAuthenticated = true`. This plan replaces both with a real implementation backed by a small `useApiClient` composable (Bearer-token fetch wrapper), and wires `login.vue` + a new `register.vue` to it.

**Tech Stack:** Laravel 10.48 (Sanctum, PHPUnit), Nuxt 4 + TypeScript (Vue 3 Composition API, `useState`), Vitest (new — introduced in this plan) for frontend unit tests.

## Global Constraints

- Backend keeps Sanctum **token-based** auth (`Authorization: Bearer <token>`), not cookie/session SPA auth — matches the existing `AuthController`. Do not introduce `sanctum/csrf-cookie` or stateful-domain config.
- `users.role` enum is exactly `admin` / `staff` / `customer` (from migration `2026_03_16_200100_add_details_to_users_table.php`). Do not add new roles.
- Frontend styling is SCSS only (see `SCSS-INTEGRATION-SUMMARY.md`) — no Tailwind, no CSS-in-JS.
- No Pinia in this phase — auth session state uses Nuxt's built-in `useState`.
- All PHPUnit tests run against SQLite in-memory (never the real MySQL dev DB).
- All user-facing text is Vietnamese (default locale `vi` per `frontend/nuxt.config.ts`).

---

### Task 1: Backend test database + factory support for roles

**Files:**
- Modify: `phpunit.xml`
- Modify: `database/factories/UserFactory.php`

**Interfaces:**
- Produces: `UserFactory::admin()`, `UserFactory::staff()` state methods; a working `username` field on every factory-created user (required by the `users` table schema, currently missing from the factory and would fail on insert).

- [ ] **Step 1: Point PHPUnit at SQLite in-memory**

Edit `phpunit.xml` — uncomment the two DB env lines so tests never touch the real MySQL DB:

```xml
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
```

- [ ] **Step 2: Add `username` and role states to `UserFactory`**

The `users` table requires a unique, non-nullable `username` (added by migration `2026_03_16_200100_add_details_to_users_table.php`), but the stock factory doesn't set one — any `User::factory()->create()` call currently fails. Replace the `definition()` method and add state helpers:

```php
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'customer',
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'admin']);
    }

    /**
     * Indicate that the user is staff.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'staff']);
    }

    /**
     * Indicate that the user's account is not active.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'inactive']);
    }
```

Keep the existing `unverified()` method below these untouched.

- [ ] **Step 3: Verify the test DB wiring works**

Run: `php artisan test`
Expected: the two existing example tests (`tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`) still PASS, confirming SQLite in-memory boots correctly.

- [ ] **Step 4: Commit**

```bash
git add phpunit.xml database/factories/UserFactory.php
git commit -m "test: run PHPUnit against sqlite in-memory, add role factory states"
```

---

### Task 2: Feature tests for registration

**Files:**
- Create: `tests/Feature/Auth/RegisterTest.php`

**Interfaces:**
- Consumes: `UserFactory` from Task 1; existing `POST /api/register` (`AuthController::register`).

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Nguyen Van A',
            'username' => 'nguyenvana',
            'email' => 'a@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'access_token', 'token_type', 'user' => ['id', 'email', 'role']]);

        $this->assertDatabaseHas('users', [
            'email' => 'a@example.com',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $user = User::where('email', 'a@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_registration_requires_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'username', 'email', 'password']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Someone',
            'username' => 'someoneelse',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_when_password_confirmation_does_not_match(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Someone',
            'username' => 'someoneelse2',
            'email' => 'someoneelse2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }
}
```

- [ ] **Step 2: Run and confirm they pass**

Run: `php artisan test --filter=RegisterTest`
Expected: 4 tests PASS (this exercises existing `AuthController::register` code, which has no prior coverage — if any of these fail, the bug is in `AuthController`, not the test).

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Auth/RegisterTest.php
git commit -m "test: add feature coverage for POST /api/register"
```

---

### Task 3: Feature tests for login

**Files:**
- Create: `tests/Feature/Auth/LoginTest.php`

**Interfaces:**
- Consumes: `UserFactory` (incl. `inactive()` state from Task 1); existing `POST /api/login`.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'b@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'b@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'user'])
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'bnguyen',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'bnguyen',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)->assertJsonPath('user.id', $user->id);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'c@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'c@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_fails_for_inactive_account(): void
    {
        User::factory()->inactive()->create([
            'email' => 'd@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'd@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403)->assertJsonFragment(['message' => 'Account is inactive']);
    }

    public function test_login_requires_login_and_password_fields(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['login', 'password']);
    }
}
```

- [ ] **Step 2: Run and confirm they pass**

Run: `php artisan test --filter=LoginTest`
Expected: 5 tests PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Auth/LoginTest.php
git commit -m "test: add feature coverage for POST /api/login"
```

---

### Task 4: Feature tests for profile (me / update / logout)

**Files:**
- Create: `tests/Feature/Auth/ProfileTest.php`

**Interfaces:**
- Consumes: `UserFactory`; existing `GET /api/user`, `PUT /api/user/profile`, `POST /api/logout`.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)->assertJsonPath('id', $user->id);
    }

    public function test_guest_cannot_fetch_profile(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/profile', [
            'name' => 'Updated Name',
            'phone' => '0900000000',
        ]);

        $response->assertStatus(200)->assertJsonPath('user.name', 'Updated Name');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name', 'phone' => '0900000000']);
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');
        $logoutResponse->assertStatus(200);

        // Laravel's auth guard caches the resolved user on the guard instance,
        // which persists across sequential simulated HTTP calls within one
        // test method (unlike real requests, which each get a fresh guard).
        // Without this, the follow-up call below sees the pre-logout cached
        // user and wrongly returns 200 even though the token row is deleted.
        Auth::forgetGuards();

        $followUpResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');
        $followUpResponse->assertStatus(401);
    }
}
```

- [ ] **Step 2: Run and confirm they pass**

Run: `php artisan test --filter=ProfileTest`
Expected: 4 tests PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Auth/ProfileTest.php
git commit -m "test: add feature coverage for profile fetch/update/logout"
```

---

### Task 5: Role-gate tests (admin / staff / customer / guest)

**Files:**
- Create: `tests/Feature/Auth/RoleAccessTest.php`
- Create: `tests/Unit/AuthGatesTest.php`

**Interfaces:**
- Consumes: `UserFactory::admin()`/`staff()` (Task 1); existing `Gate::define('admin-only'|'staff-only'|'active-only')` in `app/Providers/AuthServiceProvider.php`; existing example route `GET /api/admin/users` in `routes/api.php`.

This finishes the Phase 0 goal of proving the role-gating middleware works for all three roles before Phase 1 builds real admin CRUD controllers on top of the same `can:` pattern.

- [ ] **Step 1: Write the failing feature test (against the real route)**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_route(): void
    {
        $this->getJson('/api/admin/users')->assertStatus(401);
    }

    public function test_customer_cannot_access_admin_route(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_staff_cannot_access_admin_only_route(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());

        $this->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_admin_can_access_admin_route(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/admin/users')->assertStatus(200);
    }
}
```

- [ ] **Step 2: Write the failing unit test (for the `staff-only` and `active-only` gates, which no route uses yet)**

```php
<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthGatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_gate_allows_admin_and_staff_but_not_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $customer = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('staff-only'));
        $this->assertTrue(Gate::forUser($staff)->allows('staff-only'));
        $this->assertFalse(Gate::forUser($customer)->allows('staff-only'));
    }

    public function test_active_only_gate_allows_active_and_denies_inactive(): void
    {
        $active = User::factory()->create(['status' => 'active']);
        $inactive = User::factory()->inactive()->create();

        $this->assertTrue(Gate::forUser($active)->allows('active-only'));
        $this->assertFalse(Gate::forUser($inactive)->allows('active-only'));
    }
}
```

- [ ] **Step 3: Run both and confirm they pass**

Run: `php artisan test --filter=RoleAccessTest` — Expected: 4 tests PASS.
Run: `php artisan test --filter=AuthGatesTest` — Expected: 2 tests PASS.

- [ ] **Step 4: Run the full backend suite as a final check**

Run: `php artisan test`
Expected: all tests PASS (ExampleTest ×2, RegisterTest ×4, LoginTest ×5, ProfileTest ×4, RoleAccessTest ×4, AuthGatesTest ×2 = 21 tests).

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Auth/RoleAccessTest.php tests/Unit/AuthGatesTest.php
git commit -m "test: add role-gate coverage for admin/staff/customer access"
```

---

### Task 6: Frontend test tooling (Vitest)

**Files:**
- Modify: `frontend/package.json`
- Create: `frontend/vitest.config.ts`
- Create: `frontend/tests/smoke.test.ts`

**Interfaces:**
- Produces: `npm run test` command usable by every later frontend task in this plan and in Phase 1.

- [ ] **Step 1: Add test dependencies and script to `package.json`**

Edit `frontend/package.json` — add to `devDependencies` and add a `scripts.test` entry:

```json
{
  "name": "giaydephongan-frontend",
  "type": "module",
  "private": true,
  "scripts": {
    "build": "nuxt build",
    "dev": "nuxt dev",
    "generate": "nuxt generate",
    "preview": "nuxt preview",
    "postinstall": "nuxt prepare",
    "test": "vitest run"
  },
  "dependencies": {
    "@nuxtjs/i18n": "^9.5.6",
    "nuxt": "^4.0.3",
    "vue": "^3.5.18",
    "vue-router": "^4.5.1"
  },
  "devDependencies": {
    "@types/node": "^20.0.0",
    "@vitejs/plugin-vue": "^5.1.4",
    "sass": "^1.90.0",
    "typescript": "^5.0.0",
    "vitest": "^2.1.4",
    "happy-dom": "^15.11.6"
  }
}
```

- [ ] **Step 2: Install**

Run: `cd frontend && npm install`
Expected: exits 0, `node_modules` contains `vitest`.

- [ ] **Step 3: Add Vitest config**

```typescript
// frontend/vitest.config.ts
import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'happy-dom',
    globals: true
  },
  resolve: {
    alias: {
      '~': fileURLToPath(new URL('./', import.meta.url))
    }
  }
})
```

- [ ] **Step 4: Add a smoke test**

```typescript
// frontend/tests/smoke.test.ts
import { describe, it, expect } from 'vitest'

describe('vitest tooling', () => {
  it('runs', () => {
    expect(1 + 1).toBe(2)
  })
})
```

- [ ] **Step 5: Run and confirm it passes**

Run: `cd frontend && npx vitest run tests/smoke.test.ts`
Expected: 1 test PASS.

- [ ] **Step 6: Commit**

```bash
git add frontend/package.json frontend/package-lock.json frontend/vitest.config.ts frontend/tests/smoke.test.ts
git commit -m "chore: add Vitest test tooling to frontend"
```

---

### Task 7: Nuxt route rules (SPA-only auth/admin pages) + config cleanup

**Files:**
- Modify: `frontend/nuxt.config.ts` (whole file)

**Interfaces:**
- Produces: `/login`, `/register`, `/admin/**` render as client-only (no SSR) — required because Task 9's auth check reads `localStorage`, which doesn't exist during server render. This also matches the design spec's decision (checkout/account/admin are SPA-only; only the public storefront is statically prerendered).

This also fixes a real existing bug while the file is open for edit anyway: the file currently defines the `app:` key **twice** in the same config object — the second definition silently wins and drops the page `title` and `meta.description` set in the first one.

- [ ] **Step 1: Replace the whole file**

```typescript
// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  // Development tools
  devtools: { enabled: true },

  // Compatibility
  compatibilityDate: '2025-07-15',

  // Runtime config for API
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api'
    }
  },

  // CSS configuration
  css: [
    '~/assets/scss/main.scss'
  ],

  // Vite configuration for SCSS
  vite: {
    css: {
      preprocessorOptions: {
        scss: {
          additionalData: '@import "~/assets/scss/base/_variables.scss";'
        }
      }
    }
  },

  // Nitro configuration
  nitro: {
    preset: 'node-server'
  },

  // Auth/admin pages are client-only (SPA) — they read localStorage and
  // don't need SEO, unlike the public storefront (see docs/superpowers/specs/2026-07-11-shop-system-design.md, section 2)
  routeRules: {
    '/login': { ssr: false },
    '/register': { ssr: false },
    '/admin/**': { ssr: false }
  },

  // App configuration
  app: {
    head: {
      title: 'Giày dép Hồng An',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Giày da nam chính hãng - Hồng An' }
      ],
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap' }
      ]
    }
  },

  modules: ['@nuxtjs/i18n'],

  // i18n configuration
  i18n: {
    langDir: '../locales',
    locales: [
      {
        code: 'vi',
        iso: 'vi-VN',
        name: 'Tiếng Việt',
        file: 'vi.json'
      },
      {
        code: 'en',
        iso: 'en-US',
        name: 'English',
        file: 'en.json'
      }
    ],
    defaultLocale: 'vi',
    strategy: 'prefix_except_default',
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'i18n_redirected',
      redirectOn: 'root',
      alwaysRedirect: true,
      fallbackLocale: 'vi'
    }
  }
})
```

- [ ] **Step 2: Verify the config loads without errors**

Run: `cd frontend && npx nuxi prepare`
Expected: exits 0, regenerates `.nuxt/` without throwing a config validation error.

- [ ] **Step 3: Commit**

```bash
git add frontend/nuxt.config.ts
git commit -m "fix: merge duplicate app config key, add SPA route rules for auth/admin"
```

---

### Task 8: `useApiClient` composable

**Files:**
- Create: `frontend/composables/useApiClient.ts`
- Create: `frontend/composables/useApiClient.test.ts`

**Interfaces:**
- Consumes: `runtimeConfig.public.apiBase` (already configured, see Task 7).
- Produces: `getAuthHeaders(token)`, `resolveApiUrl(base, path)`, `parseApiError(error)`, `getStoredToken()`, `setStoredToken(token)`, and the `useApiClient()` composable exposing `get/post/put/del`. Task 9 (`useAuth`) consumes all of these.

Note on test strategy: `getAuthHeaders`, `resolveApiUrl`, `parseApiError`, `getStoredToken`/`setStoredToken` are plain functions with no Nuxt-runtime dependency, so they're unit tested directly below. The `useApiClient()` wrapper itself calls Nuxt's auto-imported `useRuntimeConfig()`/`$fetch`, which only exist inside a running Nuxt app — it is verified end-to-end in Task 13 instead of mocked here.

- [ ] **Step 1: Write the failing tests for the pure functions**

```typescript
// frontend/composables/useApiClient.test.ts
import { describe, it, expect, beforeEach } from 'vitest'
import { getAuthHeaders, resolveApiUrl, parseApiError, getStoredToken, setStoredToken } from './useApiClient'

describe('getAuthHeaders', () => {
  it('returns headers without Authorization when token is null', () => {
    expect(getAuthHeaders(null)).toEqual({ Accept: 'application/json' })
  })

  it('adds Bearer Authorization header when token is provided', () => {
    expect(getAuthHeaders('abc123')).toEqual({
      Accept: 'application/json',
      Authorization: 'Bearer abc123'
    })
  })
})

describe('resolveApiUrl', () => {
  it('joins base and path with exactly one slash', () => {
    expect(resolveApiUrl('http://localhost:8000/api', '/login')).toBe('http://localhost:8000/api/login')
  })

  it('handles base with trailing slash and path without leading slash', () => {
    expect(resolveApiUrl('http://localhost:8000/api/', 'login')).toBe('http://localhost:8000/api/login')
  })
})

describe('parseApiError', () => {
  it('extracts message and validation errors from a Laravel-style error response', () => {
    const error = {
      response: {
        status: 422,
        _data: { message: 'Validation error', errors: { email: ['The email field is required.'] } }
      }
    }
    expect(parseApiError(error)).toEqual({
      status: 422,
      message: 'Validation error',
      errors: { email: ['The email field is required.'] }
    })
  })

  it('falls back to a generic message when the response has no message', () => {
    expect(parseApiError({}).message).toBe('Đã xảy ra lỗi, vui lòng thử lại.')
  })
})

describe('token storage', () => {
  beforeEach(() => {
    window.localStorage.clear()
  })

  it('returns null when no token is stored', () => {
    expect(getStoredToken()).toBeNull()
  })

  it('stores and retrieves a token', () => {
    setStoredToken('xyz')
    expect(getStoredToken()).toBe('xyz')
  })

  it('removes the token when set to null', () => {
    setStoredToken('xyz')
    setStoredToken(null)
    expect(getStoredToken()).toBeNull()
  })
})
```

- [ ] **Step 2: Run and confirm it fails**

Run: `cd frontend && npx vitest run composables/useApiClient.test.ts`
Expected: FAIL — `Cannot find module './useApiClient'`.

- [ ] **Step 3: Implement `useApiClient.ts`**

```typescript
// frontend/composables/useApiClient.ts
export interface ApiError {
  status: number
  message: string
  errors?: Record<string, string[]>
}

export function getAuthHeaders(token: string | null): Record<string, string> {
  const headers: Record<string, string> = {
    Accept: 'application/json'
  }
  if (token) {
    headers.Authorization = `Bearer ${token}`
  }
  return headers
}

export function resolveApiUrl(base: string, path: string): string {
  const cleanBase = base.replace(/\/+$/, '')
  const cleanPath = path.replace(/^\/+/, '')
  return `${cleanBase}/${cleanPath}`
}

export function parseApiError(error: any): ApiError {
  const status = error?.response?.status ?? 0
  const data = error?.response?._data ?? {}
  return {
    status,
    message: data.message || 'Đã xảy ra lỗi, vui lòng thử lại.',
    errors: data.errors
  }
}

const AUTH_TOKEN_KEY = 'auth_token'

export function getStoredToken(): string | null {
  if (typeof window === 'undefined') return null
  return window.localStorage.getItem(AUTH_TOKEN_KEY)
}

export function setStoredToken(token: string | null): void {
  if (typeof window === 'undefined') return
  if (token) {
    window.localStorage.setItem(AUTH_TOKEN_KEY, token)
  } else {
    window.localStorage.removeItem(AUTH_TOKEN_KEY)
  }
}

export const useApiClient = () => {
  const config = useRuntimeConfig()

  const request = async <T>(path: string, options: { method?: string; body?: unknown } = {}): Promise<T> => {
    const url = resolveApiUrl(config.public.apiBase, path)
    const token = getStoredToken()

    try {
      return await $fetch<T>(url, {
        method: (options.method ?? 'GET') as any,
        body: options.body,
        headers: getAuthHeaders(token)
      })
    } catch (error) {
      throw parseApiError(error)
    }
  }

  return {
    get: <T>(path: string) => request<T>(path),
    post: <T>(path: string, body?: unknown) => request<T>(path, { method: 'POST', body }),
    put: <T>(path: string, body?: unknown) => request<T>(path, { method: 'PUT', body }),
    del: <T>(path: string) => request<T>(path, { method: 'DELETE' })
  }
}
```

- [ ] **Step 4: Run and confirm it passes**

Run: `cd frontend && npx vitest run composables/useApiClient.test.ts`
Expected: 9 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add frontend/composables/useApiClient.ts frontend/composables/useApiClient.test.ts
git commit -m "feat: add useApiClient Bearer-token fetch wrapper"
```

---

### Task 9: `useAuth` composable (replaces the corrupted/fake one)

**Files:**
- Modify: `frontend/composables/useAuth.ts` (full rewrite — current file is corrupted with stray bytes and only fakes login via `localStorage`)
- Create: `frontend/composables/useAuth.test.ts`

**Interfaces:**
- Consumes: `useApiClient`, `getStoredToken`, `setStoredToken` from Task 8.
- Produces: `useAuth()` returning `{ user, isAuthenticated, isAdmin, isStaff, fetchUser, login, register, logout }`; pure functions `computeIsAdmin(user)`, `computeIsStaff(user)`, and the `AuthUser` type. Tasks 10, 11, 12 all consume `useAuth()`.

Note on test strategy: same reasoning as Task 8 — `computeIsAdmin`/`computeIsStaff` are pure and unit tested here; the `useAuth()` wrapper (which calls Nuxt's `useState`) is verified end-to-end in Task 13.

- [ ] **Step 1: Write the failing tests for the pure role functions**

```typescript
// frontend/composables/useAuth.test.ts
import { describe, it, expect } from 'vitest'
import { computeIsAdmin, computeIsStaff, type AuthUser } from './useAuth'

const makeUser = (role: AuthUser['role']): AuthUser => ({
  id: 1,
  name: 'Test',
  username: 'test',
  email: 't@example.com',
  role,
  status: 'active'
})

describe('computeIsAdmin', () => {
  it('is true only for admin role', () => {
    expect(computeIsAdmin(makeUser('admin'))).toBe(true)
    expect(computeIsAdmin(makeUser('staff'))).toBe(false)
    expect(computeIsAdmin(makeUser('customer'))).toBe(false)
    expect(computeIsAdmin(null)).toBe(false)
  })
})

describe('computeIsStaff', () => {
  it('is true for staff and admin, false for customer', () => {
    expect(computeIsStaff(makeUser('staff'))).toBe(true)
    expect(computeIsStaff(makeUser('admin'))).toBe(true)
    expect(computeIsStaff(makeUser('customer'))).toBe(false)
    expect(computeIsStaff(null)).toBe(false)
  })
})
```

- [ ] **Step 2: Run and confirm it fails**

Run: `cd frontend && npx vitest run composables/useAuth.test.ts`
Expected: FAIL — `computeIsAdmin`/`computeIsStaff` not exported (current file doesn't even parse cleanly due to corruption).

- [ ] **Step 3: Replace `useAuth.ts` entirely**

```typescript
// frontend/composables/useAuth.ts
import { computed } from 'vue'
import { getStoredToken, setStoredToken, useApiClient } from './useApiClient'

export interface AuthUser {
  id: number
  name: string
  username: string
  email: string
  role: 'admin' | 'staff' | 'customer'
  status: string
}

interface LoginPayload {
  login: string
  password: string
}

interface RegisterPayload {
  name: string
  username: string
  email: string
  password: string
  password_confirmation: string
  phone?: string
}

export function computeIsAdmin(user: AuthUser | null): boolean {
  return user?.role === 'admin'
}

export function computeIsStaff(user: AuthUser | null): boolean {
  return user?.role === 'staff' || user?.role === 'admin'
}

export const useAuth = () => {
  const user = useState<AuthUser | null>('auth_user', () => null)
  const api = useApiClient()

  const isAuthenticated = computed(() => user.value !== null)
  const isAdmin = computed(() => computeIsAdmin(user.value))
  const isStaff = computed(() => computeIsStaff(user.value))

  const fetchUser = async () => {
    if (!getStoredToken()) {
      user.value = null
      return null
    }
    try {
      user.value = await api.get<AuthUser>('/user')
      return user.value
    } catch {
      setStoredToken(null)
      user.value = null
      return null
    }
  }

  const login = async (payload: LoginPayload) => {
    const response = await api.post<{ access_token: string; user: AuthUser }>('/login', payload)
    setStoredToken(response.access_token)
    user.value = response.user
    return response.user
  }

  const register = async (payload: RegisterPayload) => {
    const response = await api.post<{ access_token: string; user: AuthUser }>('/register', payload)
    setStoredToken(response.access_token)
    user.value = response.user
    return response.user
  }

  const logout = async () => {
    try {
      await api.post('/logout')
    } finally {
      setStoredToken(null)
      user.value = null
    }
  }

  return {
    user,
    isAuthenticated,
    isAdmin,
    isStaff,
    fetchUser,
    login,
    register,
    logout
  }
}
```

- [ ] **Step 4: Run and confirm it passes**

Run: `cd frontend && npx vitest run composables/useAuth.test.ts`
Expected: 2 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add frontend/composables/useAuth.ts frontend/composables/useAuth.test.ts
git commit -m "fix: replace corrupted/fake useAuth with real Sanctum-backed implementation"
```

---

### Task 10: App-init plugin + real route middleware

**Files:**
- Create: `frontend/plugins/auth.client.ts`
- Modify: `frontend/middleware/auth.global.ts` (full rewrite — currently hardcodes `isAuthenticated = true` and `isAdmin = true`, i.e. it doesn't actually check anything)
- Modify: `frontend/layouts/admin.vue:59-70` (script block — `handleLogout` currently clears the old fake `isAuthenticated`/`userRole` localStorage keys instead of calling real logout)

**Interfaces:**
- Consumes: `useAuth()` from Task 9.

- [ ] **Step 1: Add a client plugin that hydrates auth state on app load**

```typescript
// frontend/plugins/auth.client.ts
export default defineNuxtPlugin(async () => {
  const { fetchUser } = useAuth()
  await fetchUser()
})
```

This runs once when the app boots in the browser (plugins run before route middleware), so `useAuth().user` is already populated from a stored token by the time the middleware below checks it.

- [ ] **Step 2: Replace the route middleware**

```typescript
// frontend/middleware/auth.global.ts
export default defineNuxtRouteMiddleware((to) => {
  if (import.meta.server) return

  const { isAuthenticated, isStaff } = useAuth()

  const isAdminRoute = to.path.startsWith('/admin')
  const isGuestOnlyRoute = to.path === '/login' || to.path === '/register'

  if (isGuestOnlyRoute && isAuthenticated.value) {
    return navigateTo(isStaff.value ? '/admin' : '/')
  }

  if (isAdminRoute) {
    if (!isAuthenticated.value) {
      return navigateTo('/login')
    }
    if (!isStaff.value) {
      return navigateTo('/')
    }
  }
})
```

- [ ] **Step 3: Fix the admin layout's logout button**

Replace the `<script setup>` block of `frontend/layouts/admin.vue`:

```html
<script setup>
const router = useRouter()
const { t } = useI18n()
const auth = useAuth()

const handleLogout = async () => {
  if (confirm(t('auth.logoutConfirm'))) {
    await auth.logout()
    router.push('/login')
  }
}
</script>
```

- [ ] **Step 4: Typecheck**

Run: `cd frontend && npx nuxi typecheck`
Expected: exits 0, no type errors in the three new/changed files.

- [ ] **Step 5: Commit**

```bash
git add frontend/plugins/auth.client.ts frontend/middleware/auth.global.ts frontend/layouts/admin.vue
git commit -m "fix: replace hardcoded auth middleware and fake logout with real session handling"
```

---

### Task 11: Wire `login.vue` to the real API

**Files:**
- Modify: `frontend/pages/login.vue` (template lines 1-101 and script lines 103-145 — see file for current content; style block from line ~147 onward is untouched)

**Interfaces:**
- Consumes: `useAuth()` from Task 9.

- [ ] **Step 1: Replace the template (lines 1-101)**

```html
<template>
  <div class="login-page">
    <!-- Left Section: Login Form -->
    <div class="login-left">
      <div class="login-container">
        <div class="logo-section">
          <div class="logo-icon">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="32" height="32">
              <rect width="24" height="24" rx="6" fill="#FF5C00"/>
              <path d="M6 12L10 16L18 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="logo-text">SOLESPHERE</span>
        </div>

        <div class="header-section">
          <h1>Welcome Back</h1>
          <p>Step into your style. Log in to your account.</p>
        </div>

        <div v-if="generalError" class="general-error">{{ generalError }}</div>

        <form class="login-form" @submit.prevent="handleLogin">
          <BaseInput
            v-model="loginForm.login"
            label="Email Address or Username"
            type="text"
            placeholder="name@example.com"
            has-icon
            required
            :error="errors.login"
          >
            <template #icon>
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            </template>
          </BaseInput>

          <div class="password-field">
            <BaseInput
              v-model="loginForm.password"
              label="Password"
              type="password"
              placeholder="••••••••"
              has-icon
              required
              :error="errors.password"
            >
              <template #icon>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              </template>
            </BaseInput>
          </div>

          <div class="form-utils">
            <label class="remember-me">
              <input type="checkbox" v-model="loginForm.remember">
              <span>Remember me</span>
            </label>
            <a href="#" class="forgot-password">Forgot password?</a>
          </div>

          <BaseButton type="submit" full-width :loading="loading">
            Sign In
          </BaseButton>
        </form>

        <div class="divider">
          <span>OR CONTINUE WITH</span>
        </div>

        <div class="social-logins">
          <SocialLoginButton provider="google" label="Google" @click="socialLogin('google')" />
          <SocialLoginButton provider="apple" label="Apple" @click="socialLogin('apple')" />
        </div>

        <div class="register-prompt">
          Don't have an account? <NuxtLink to="/register" class="join-link">Đăng ký ngay</NuxtLink>
        </div>
      </div>
    </div>

    <!-- Right Section: Branding/Image -->
    <div class="login-right">
      <div class="brand-content">
        <div class="new-collection-badge">NEW COLLECTION</div>
        <h2>UNLEASH THE ENERGY</h2>
        <p>Discover the exclusive SoleSphere Drop. Limited edition sneakers crafted for the urban explorer.</p>

        <div class="stats-card">
          <div class="avatars">
            <img src="https://i.pravatar.cc/150?u=1" alt="user">
            <img src="https://i.pravatar.cc/150?u=2" alt="user">
            <img src="https://i.pravatar.cc/150?u=3" alt="user">
          </div>
          <div class="stats-text">
            <strong>12k+</strong>
            <span>Community members</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Replace the script block**

```html
<script setup lang="ts">
import { reactive, ref } from 'vue'

const router = useRouter()
const auth = useAuth()

const loginForm = reactive({
  login: '',
  password: '',
  remember: false
})

const errors = reactive({
  login: '',
  password: ''
})

const generalError = ref('')
const loading = ref(false)

const resetErrors = () => {
  errors.login = ''
  errors.password = ''
  generalError.value = ''
}

const handleLogin = async () => {
  loading.value = true
  resetErrors()

  try {
    const user = await auth.login({ login: loginForm.login, password: loginForm.password })
    await router.push(user.role === 'admin' || user.role === 'staff' ? '/admin' : '/')
  } catch (err: any) {
    errors.login = err.errors?.login?.[0] ?? ''
    errors.password = err.errors?.password?.[0] ?? ''
    generalError.value = err.message ?? 'Đăng nhập thất bại, vui lòng thử lại.'
  } finally {
    loading.value = false
  }
}

const socialLogin = (provider: string) => {
  console.log(`Logging in with ${provider}`)
}
</script>
```

- [ ] **Step 3: Add a small style for the new error banner**

Add to the existing `<style scoped lang="scss">` block (right after `.header-section { ... }`):

```scss
.general-error {
  background-color: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 12px 16px;
  font-size: 14px;
  margin-bottom: 20px;
}
```

- [ ] **Step 4: Typecheck**

Run: `cd frontend && npx nuxi typecheck`
Expected: exits 0.

- [ ] **Step 5: Commit**

```bash
git add frontend/pages/login.vue
git commit -m "feat: wire login page to real auth API with role-based redirect"
```

---

### Task 12: `register.vue` page

**Files:**
- Create: `frontend/pages/register.vue`

**Interfaces:**
- Consumes: `useAuth()` from Task 9; `BaseInput`/`BaseButton` components (already exist in `frontend/components/`).

- [ ] **Step 1: Create the page**

```html
<template>
  <div class="register-page">
    <div class="register-container">
      <div class="header-section">
        <h1>Tạo tài khoản</h1>
        <p>Đăng ký để theo dõi đơn hàng và tích điểm thành viên.</p>
      </div>

      <div v-if="generalError" class="general-error">{{ generalError }}</div>

      <form class="register-form" @submit.prevent="handleRegister">
        <BaseInput v-model="form.name" label="Họ và tên" type="text" required :error="errors.name" />
        <BaseInput v-model="form.username" label="Tên đăng nhập" type="text" required :error="errors.username" />
        <BaseInput v-model="form.email" label="Email" type="email" required :error="errors.email" />
        <BaseInput v-model="form.phone" label="Số điện thoại (không bắt buộc)" type="text" :error="errors.phone" />
        <BaseInput v-model="form.password" label="Mật khẩu" type="password" required :error="errors.password" />
        <BaseInput v-model="form.password_confirmation" label="Xác nhận mật khẩu" type="password" required />

        <BaseButton type="submit" full-width :loading="loading">Đăng ký</BaseButton>
      </form>

      <div class="login-prompt">
        Đã có tài khoản? <NuxtLink to="/login">Đăng nhập</NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'

const router = useRouter()
const auth = useAuth()

const form = reactive({
  name: '',
  username: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: ''
})

const errors = reactive({
  name: '',
  username: '',
  email: '',
  phone: '',
  password: ''
})

const generalError = ref('')
const loading = ref(false)

const resetErrors = () => {
  errors.name = ''
  errors.username = ''
  errors.email = ''
  errors.phone = ''
  errors.password = ''
  generalError.value = ''
}

const handleRegister = async () => {
  loading.value = true
  resetErrors()

  try {
    await auth.register({ ...form })
    await router.push('/')
  } catch (err: any) {
    if (err.errors) {
      for (const field of Object.keys(errors) as Array<keyof typeof errors>) {
        errors[field] = err.errors[field]?.[0] ?? ''
      }
    }
    generalError.value = err.message ?? 'Đăng ký thất bại, vui lòng thử lại.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
.register-page {
  display: flex;
  justify-content: center;
  min-height: 100vh;
  padding: 60px 40px;
  background-color: white;
}

.register-container {
  width: 100%;
  max-width: 420px;
}

.header-section {
  margin-bottom: 32px;

  h1 {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 8px;
  }

  p {
    color: #6b7280;
    font-size: 14px;
  }
}

.general-error {
  background-color: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 12px 16px;
  font-size: 14px;
  margin-bottom: 20px;
}

.register-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.login-prompt {
  text-align: center;
  font-size: 14px;
  color: #6b7280;
  margin-top: 24px;
}
</style>
```

- [ ] **Step 2: Typecheck**

Run: `cd frontend && npx nuxi typecheck`
Expected: exits 0.

- [ ] **Step 3: Commit**

```bash
git add frontend/pages/register.vue
git commit -m "feat: add register page wired to real auth API"
```

---

### Task 13: End-to-end verification (real backend + real browser)

**Files:** none (verification only).

**Interfaces:** exercises the full stack built in Tasks 1-12.

- [ ] **Step 1: Run the full backend test suite one more time**

Run: `php artisan test`
Expected: all tests PASS.

- [ ] **Step 2: Run the full frontend test suite one more time**

Run: `cd frontend && npm run test`
Expected: all tests PASS (smoke + useApiClient + useAuth).

- [ ] **Step 3: Start both dev servers**

Run (background): `php artisan serve` (defaults to `http://localhost:8000`)
Run (background): `cd frontend && npm run dev` (defaults to `http://localhost:3000`)

- [ ] **Step 4: Seed one admin user for testing**

Run:
```bash
php artisan tinker --execute="App\Models\User::create(['name'=>'Admin','username'=>'admin','email'=>'admin@hongan.vn','password'=>bcrypt('password123'),'role'=>'admin','status'=>'active']);"
```

- [ ] **Step 5: Browser walkthrough (use the Playwright browser tools)**

1. Navigate to `http://localhost:3000/register`, fill in a new customer, submit. Expect redirect to `/`.
2. Navigate to `http://localhost:3000/admin`. Expect an automatic redirect away (this customer is not staff/admin).
3. Navigate to `http://localhost:3000/login`, log in as `admin@hongan.vn` / `password123`. Expect redirect to `/admin`.
4. While logged in as admin, navigate to `http://localhost:3000/login` directly. Expect an automatic redirect to `/admin` (guest-only route guard).
5. Reload `http://localhost:3000/admin` (full page reload, not client nav). Expect it to stay on `/admin` (confirms `auth.client.ts` plugin rehydrates the session from the stored token after a hard refresh) rather than bouncing to `/login`.

- [ ] **Step 6: Report results**

If any step in the walkthrough fails, that is a bug in Tasks 9-12 (not a plan gap) — fix it, re-run the relevant task's automated tests, then repeat this task from Step 5.

No commit for this task — it's verification only.
