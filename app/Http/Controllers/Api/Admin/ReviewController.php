<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['product:id,name,slug', 'user:id,name'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->paginate(15));
    }

    public function updateStatus(Request $request, Review $review)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $review->update(['status' => $validator->validated()['status']]);
        $this->recomputeProductStats($review->product);

        return response()->json(['message' => 'Đã cập nhật trạng thái đánh giá', 'review' => $review]);
    }

    public function destroy(Review $review)
    {
        $product = $review->product;
        $review->delete();
        $this->recomputeProductStats($product);

        return response()->json(['message' => 'Đã xóa đánh giá']);
    }

    private function recomputeProductStats(Product $product): void
    {
        $approved = Review::where('product_id', $product->id)->where('status', 'approved');

        $product->update([
            'reviews_count' => $approved->count(),
            'avg_rating' => round((float) $approved->avg('rating'), 1),
        ]);
    }
}
