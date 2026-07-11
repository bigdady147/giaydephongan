<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'size' => 'required|string|max:50',
            'color' => 'required|string|max:100',
            'sku' => 'required|string|max:255|unique:product_variants,sku',
            'stock_quantity' => 'required|integer|min:0',
            'price_override' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $variant = $product->variants()->create($validator->validated());

        return response()->json(['message' => 'Variant created successfully', 'variant' => $variant], 201);
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $validator = Validator::make($request->all(), [
            'size' => 'sometimes|required|string|max:50',
            'color' => 'sometimes|required|string|max:100',
            'sku' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($variant->id)],
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'price_override' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $variant->update($validator->validated());

        return response()->json(['message' => 'Variant updated successfully', 'variant' => $variant]);
    }

    public function destroy(ProductVariant $variant)
    {
        $variant->delete();

        return response()->json(['message' => 'Variant deleted successfully']);
    }
}
