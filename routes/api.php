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
});

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
