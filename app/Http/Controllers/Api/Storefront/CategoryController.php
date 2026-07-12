<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(
            Category::where('is_active', true)
                ->withCount(['products as products_count' => fn ($q) => $q->where('status', 'published')])
                ->orderBy('sort_order')
                ->get()
        );
    }

    public function show(string $slug)
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return response()->json($category);
    }
}
