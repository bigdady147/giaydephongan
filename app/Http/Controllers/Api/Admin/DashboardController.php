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
