<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductImageController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:4096',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $path = $request->file('image')->store('products', 'public');

        $image = $product->images()->create([
            'url' => Storage::url($path),
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        return response()->json(['message' => 'Image uploaded successfully', 'image' => $image], 201);
    }

    public function destroy(ProductImage $image)
    {
        $path = str_replace('/storage/', '', $image->url);
        Storage::disk('public')->delete($path);
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }
}
