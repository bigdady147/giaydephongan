<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyLedger;
use App\Models\MembershipTier;
use App\Models\Order;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function orders(Request $request)
    {
        $orders = Order::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($orders);
    }

    public function showOrder($code)
    {
        $order = Order::with('items')
            ->where('order_code', $code)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại hoặc bạn không có quyền truy cập.'], 404);
        }

        return response()->json($order);
    }

    public function loyalty()
    {
        $user = auth()->user();
        $user->load('membershipTier');

        $nextTier = MembershipTier::where('min_points', '>', $user->points)
            ->orderBy('min_points', 'asc')
            ->first();

        $history = LoyaltyLedger::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'points' => $user->points,
            'current_tier' => $user->membershipTier,
            'next_tier' => $nextTier ? [
                'name' => $nextTier->name,
                'min_points' => $nextTier->min_points,
                'points_needed' => $nextTier->min_points - $user->points,
            ] : null,
            'history' => $history,
        ]);
    }
}
