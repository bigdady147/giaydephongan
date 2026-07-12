<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;

class MetaController extends Controller
{
    public function slugs()
    {
        return response()->json([
            'products' => Product::where('status', 'published')->pluck('slug'),
            'categories' => Category::where('is_active', true)->pluck('slug'),
            'pages' => Page::where('is_active', true)->pluck('slug'),
            'blog' => BlogPost::published()->pluck('slug'),
        ]);
    }
}
