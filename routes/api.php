<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\BrandController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ProductVariantController;
use App\Http\Controllers\Api\Admin\ProductImageController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\BannerController;
use App\Http\Controllers\Api\Admin\PageController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\BlogController;
use App\Http\Controllers\Api\Admin\DiscountCodeController;
use App\Http\Controllers\Api\Storefront\BlogController as StorefrontBlogController;
use App\Http\Controllers\Api\Storefront\CategoryController as StorefrontCategoryController;
use App\Http\Controllers\Api\Storefront\BrandController as StorefrontBrandController;
use App\Http\Controllers\Api\Storefront\SettingController as StorefrontSettingController;
use App\Http\Controllers\Api\Storefront\BannerController as StorefrontBannerController;
use App\Http\Controllers\Api\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\Api\Storefront\PageController as StorefrontPageController;
use App\Http\Controllers\Api\Storefront\MetaController as StorefrontMetaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', [AuthController::class, 'me'])->middleware('can:active-only');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile'])->middleware('can:active-only');

    // Customer account & loyalty
    Route::middleware('can:active-only')->group(function () {
        Route::get('/user/orders', [\App\Http\Controllers\Api\Customer\AccountController::class, 'orders']);
        Route::get('/user/orders/{code}', [\App\Http\Controllers\Api\Customer\AccountController::class, 'showOrder']);
        Route::get('/user/loyalty', [\App\Http\Controllers\Api\Customer\AccountController::class, 'loyalty']);
    });

    // Example of a role-protected route (using a simple closure check or dedicated middleware)
    Route::middleware(['can:active-only', 'can:admin-only'])->get('/admin/users', function () {
        return \App\Models\User::all();
    });
});

// Admin catalog management — accessible to both admin and staff (see design spec §5)
Route::middleware(['auth:sanctum', 'can:active-only', 'can:staff-only'])->prefix('admin')->group(function () {
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('products', ProductController::class);
    Route::post('products/{product}/variants', [ProductVariantController::class, 'store']);
    Route::put('variants/{variant}', [ProductVariantController::class, 'update']);
    Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy']);
    Route::post('products/{product}/images', [ProductImageController::class, 'store']);
    Route::delete('images/{image}', [ProductImageController::class, 'destroy']);
    Route::apiResource('banners', BannerController::class);
    Route::apiResource('pages', PageController::class);
    Route::apiResource('blog', BlogController::class);
    Route::apiResource('discount-codes', DiscountCodeController::class);
    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);
    Route::get('orders', [\App\Http\Controllers\Api\Admin\OrderController::class, 'index']);
    Route::get('orders/{id}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'show']);
    Route::patch('orders/{id}/status', [\App\Http\Controllers\Api\Admin\OrderController::class, 'updateStatus']);
});

// Public storefront API — no auth (see docs/superpowers/specs/2026-07-12-storefront-catalog-design.md §3)
Route::get('/categories', [StorefrontCategoryController::class, 'index']);
Route::get('/categories/{slug}', [StorefrontCategoryController::class, 'show']);
Route::get('/brands', [StorefrontBrandController::class, 'index']);
Route::get('/settings', [StorefrontSettingController::class, 'index']);
Route::get('/banners', [StorefrontBannerController::class, 'index']);
Route::get('/products', [StorefrontProductController::class, 'index']);
Route::get('/products/{slug}', [StorefrontProductController::class, 'show']);
Route::get('/products/{slug}/availability', [StorefrontProductController::class, 'availability']);
Route::get('/pages', [StorefrontPageController::class, 'index']);
Route::get('/pages/{slug}', [StorefrontPageController::class, 'show']);
Route::get('/blog', [StorefrontBlogController::class, 'index']);
Route::get('/blog/{slug}', [StorefrontBlogController::class, 'show']);
Route::get('/slugs', [StorefrontMetaController::class, 'slugs']);

// Storefront checkout & orders
Route::post('/orders', [\App\Http\Controllers\Api\Storefront\OrderController::class, 'store']);
Route::get('/orders/{code}', [\App\Http\Controllers\Api\Storefront\OrderController::class, 'show']);
Route::post('/discount-codes/validate', [\App\Http\Controllers\Api\Storefront\OrderController::class, 'validateDiscount']);

// Health check routes
Route::get('/health', [HealthController::class, 'index']);
Route::get('/version', [HealthController::class, 'version']);

// API documentation
Route::get('/', function () {
    return response()->json([
        'message' => 'Giày dép Hồng An API',
        'version' => '1.0.0',
        'endpoints' => [
            'auth' => [
                'register' => 'POST /api/register',
                'login' => 'POST /api/login',
                'logout' => 'POST /api/logout (authenticated)',
                'me' => 'GET /api/user (authenticated)',
                'update_profile' => 'PUT /api/user/profile (authenticated)',
            ],
            'health' => '/api/health',
            'version' => '/api/version',
        ]
    ]);
});
