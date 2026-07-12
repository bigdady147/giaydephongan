<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    public function index()
    {
        return response()->json(Page::where('is_active', true)->orderBy('title')->get(['title', 'slug']));
    }

    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return response()->json($page);
    }
}
