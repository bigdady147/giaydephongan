<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Banner;

class BannerController extends Controller
{
    public function index()
    {
        $grouped = Banner::currentlyActive()->orderBy('sort_order')->get()->groupBy('position');

        return response()->json([
            'homepage_hero' => $grouped->get('homepage_hero', collect())->values(),
            'homepage_promo' => $grouped->get('homepage_promo', collect())->values(),
        ]);
    }
}
