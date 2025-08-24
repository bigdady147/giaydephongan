<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Health check routes
Route::get('/health', [HealthController::class, 'index']);
Route::get('/version', [HealthController::class, 'version']);

// API documentation
Route::get('/', function () {
    return response()->json([
        'message' => 'Giấy Đề Phòng An API',
        'version' => '1.0.0',
        'endpoints' => [
            'health' => '/api/health',
            'version' => '/api/version',
            'user' => '/api/user (authenticated)'
        ]
    ]);
});
