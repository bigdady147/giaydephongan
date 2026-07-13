<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index(string $slug)
    {
        $product = Product::where('slug', $slug)->where('status', 'published')->firstOrFail();

        $reviews = $product->reviews()
            ->where('status', 'approved')
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($reviews);
    }

    public function store(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)->where('status', 'published')->firstOrFail();

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        if (Review::where('product_id', $product->id)->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Bạn đã đánh giá sản phẩm này rồi.'], 422);
        }

        $data = $validator->validated();

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Cảm ơn bạn đã gửi đánh giá, đánh giá sẽ hiển thị sau khi được duyệt.',
            'review' => $review,
        ], 201);
    }
}
