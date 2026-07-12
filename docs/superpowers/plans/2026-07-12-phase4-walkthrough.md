# Walkthrough — Phase 4: Cart, Checkout & Order Management Completed!

We have successfully implemented and verified **Phase 4 — Cart, Checkout & Order Management** of the shop system!

## Changes Made

### 1. Database & Models (Backend)
- Added migrations and models for:
  - `orders`: Tracks user/guest details, total summaries, note, status, and payment method.
  - `order_items`: Captures product snapshots (name, variant info like color/size, and price at purchase time) to preserve invoice integrity.
  - `discount_codes`: Manages percent/fixed discounts, expiration dates, validity requirements, and usage limits.
- Configured models, relations, unique `order_code` auto-generation, and model factories.

### 2. Storefront APIs & Checkout Validations (Backend)
- Implemented `/api/orders` endpoint supporting guest and registered checkouts, database transactional stock updates, validation checks, and automatic shipping fees calculation (freeship for orders >= 500k, otherwise 30k).
- Implemented `/api/discount-codes/validate` validation endpoint.
- Protected orders lookup (`GET /api/orders/{code}`) requiring ownership match or billing phone verification for guests.

### 3. Admin APIs & Order Management (Backend)
- Created admin dashboard APIs to list orders (with search and status filters), see complete order items/client details, and transition status step-by-step (`pending` -> `confirmed` -> `shipping` -> `delivered` / `cancelled`) under transactional locks.

### 4. Client Cart Composable (Frontend)
- Created the reactive `useCart.ts` composable synchronizing cart changes to local storage. Fully covered it with Vitest tests.
- Modified default layout header to render a reactive cart indicator badge.
- Added "Thêm vào giỏ" and "Mua ngay" buttons to the product page.

### 5. Cart, Checkout & Success Pages (Frontend)
- **Cart Page (`/gio-hang`)**: Dynamic table displaying products, price, quantity controls, and totals summary.
- **Checkout Page (`/thanh-toan`)**: Contact form (switching guest/authenticated mode), payment COD information, discount code application/validation, and order placement.
- **Success Page (`/dat-hang-thanh-cong`)**: Displays order confirmation details.

### 6. Admin Orders Dashboard (Frontend)
- Created `/admin/orders` dashboard to list orders by status tab, search orders, and view details in a side drawer. Includes state-action buttons to confirm, ship, deliver, or cancel the order.

---

## Verification Results

### 1. Backend PHPUnit Tests
All **14 tests (44 assertions)** for Phase 4 models, checkout, and admin order controller passed successfully:
```bash
Tests:    14 passed
```

### 2. Frontend Vitest Tests
All **23 unit tests** (including 2 new `useCart` tests) passed successfully:
```bash
 Test Files  7 passed (7)
      Tests  23 passed (23)
```
