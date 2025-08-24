<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'API is running',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'environment' => config('app.env')
        ]);
    }

    /**
     * Version information
     */
    public function version(): JsonResponse
    {
        return response()->json([
            'version' => '1.0.0',
            'framework' => 'Laravel 12',
            'frontend' => 'Nuxt 4',
            'php_version' => PHP_VERSION,
            'environment' => config('app.env')
        ]);
    }
}
