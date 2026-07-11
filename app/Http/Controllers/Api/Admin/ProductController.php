<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\UniqueSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'variants'])->orderByDesc('id');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $validator = $this->validatorFor($request);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $variants = $data['variants'] ?? [];
        unset($data['variants']);
        $data['slug'] = $data['slug'] ?? UniqueSlug::make($data['name'], 'products');

        $product = DB::transaction(function () use ($data, $variants) {
            $product = Product::create($data);

            foreach ($variants as $variant) {
                $product->variants()->create($variant);
            }

            return $product;
        });

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('variants', 'category', 'brand'),
        ], 201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load('variants', 'images', 'category', 'brand'));
    }

    public function update(Request $request, Product $product)
    {
        $request->mergeIfMissing(['base_price' => $product->base_price]);

        $validator = $this->validatorFor($request, $product->id, sometimes: true);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        unset($data['variants']);

        $product->update($data);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->load('variants', 'category', 'brand'),
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    private function validatorFor(Request $request, ?int $productId = null, bool $sometimes = false)
    {
        $prefix = $sometimes ? 'sometimes|required' : 'required';

        return Validator::make($request->all(), [
            'category_id' => "{$prefix}|exists:categories,id",
            'brand_id' => 'nullable|exists:brands,id',
            'name' => "{$prefix}|string|max:255",
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'material' => "{$prefix}|in:full_grain_leather,suede,pu_leather,other",
            'base_price' => "{$prefix}|integer|min:0",
            'sale_price' => 'nullable|integer|min:0|lt:base_price',
            'status' => "{$prefix}|in:draft,published,archived",
            'thumbnail' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'variants' => 'array',
            'variants.*.size' => 'required_with:variants|string|max:50',
            'variants.*.color' => 'required_with:variants|string|max:100',
            'variants.*.sku' => ['required_with:variants', 'string', 'max:255', 'distinct', Rule::unique('product_variants', 'sku')],
            'variants.*.stock_quantity' => 'required_with:variants|integer|min:0',
            'variants.*.price_override' => 'nullable|integer|min:0',
        ]);
    }
}
