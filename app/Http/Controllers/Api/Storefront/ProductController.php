<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'nullable|string',
            'brand' => 'nullable|string',
            'material' => 'nullable|in:full_grain_leather,suede,pu_leather,other',
            'size' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:50',
            'price_min' => 'nullable|integer|min:0',
            'price_max' => 'nullable|integer|min:0',
            'q' => 'nullable|string|max:100',
            'sort' => 'nullable|in:newest,price_asc,price_desc,best_selling,featured',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $f = $validator->validated();

        $query = Product::where('status', 'published')
            ->when($f['category'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($f['brand'] ?? null, fn ($q, $slug) => $q->whereHas('brand', fn ($b) => $b->where('slug', $slug)))
            ->when($f['material'] ?? null, fn ($q, $material) => $q->where('material', $material))
            ->when($f['size'] ?? null, fn ($q, $size) => $q->whereHas('variants', fn ($v) => $v->where('size', $size)))
            ->when($f['color'] ?? null, fn ($q, $color) => $q->whereHas('variants', fn ($v) => $v->where('color', $color)))
            ->when(isset($f['price_min']), fn ($q) => $q->whereRaw('COALESCE(sale_price, base_price) >= ?', [(int) $f['price_min']]))
            ->when(isset($f['price_max']), fn ($q) => $q->whereRaw('COALESCE(sale_price, base_price) <= ?', [(int) $f['price_max']]))
            ->when($f['q'] ?? null, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"));

        match ($f['sort'] ?? 'newest') {
            'price_asc' => $query->orderByRaw('COALESCE(sale_price, base_price) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, base_price) desc'),
            'best_selling' => $query->orderByDesc('sold_count')->orderByDesc('created_at'),
            'featured' => $query->where('is_featured', true)->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        return response()->json(
            $query->select(['id', 'name', 'slug', 'thumbnail', 'base_price', 'sale_price', 'is_featured', 'created_at'])
                ->paginate(12)
        );
    }

    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)->where('status', 'published')
            ->with(['images', 'variants', 'category', 'brand'])
            ->firstOrFail();

        $related = Product::where('status', 'published')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'name', 'slug', 'thumbnail', 'base_price', 'sale_price', 'created_at']);

        return response()->json(array_merge($product->toArray(), ['related' => $related]));
    }

    public function availability(string $slug)
    {
        $product = Product::where('slug', $slug)->where('status', 'published')->firstOrFail();

        return response()->json([
            'base_price' => $product->base_price,
            'sale_price' => $product->sale_price,
            'variants' => $product->variants()->get(['id', 'size', 'color', 'stock_quantity', 'price_override']),
        ]);
    }
}
