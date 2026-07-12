<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhere('guest_phone', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(15);

        return response()->json($orders);
    }

    public function show($id)
    {
        $order = Order::with(['items', 'user', 'discountCode'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        return response()->json($order);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,shipping,delivered,cancelled',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        $oldStatus = $order->status;
        $newStatus = $request->status;

        if ($oldStatus === $newStatus) {
            return response()->json($order);
        }

        // Validate state transitions
        if ($oldStatus === 'delivered' || $oldStatus === 'cancelled') {
            return response()->json([
                'message' => 'Không thể thay đổi trạng thái của đơn hàng đã giao hoặc đã hủy.'
            ], 422);
        }

        $allowed = false;
        if ($oldStatus === 'pending') {
            $allowed = in_array($newStatus, ['confirmed', 'cancelled']);
        } elseif ($oldStatus === 'confirmed') {
            $allowed = in_array($newStatus, ['shipping', 'cancelled']);
        } elseif ($oldStatus === 'shipping') {
            $allowed = in_array($newStatus, ['delivered', 'cancelled']);
        }

        if (!$allowed) {
            return response()->json([
                'message' => "Chuyển đổi trạng thái từ {$oldStatus} sang {$newStatus} không hợp lệ."
            ], 422);
        }

        $order->status = $newStatus;
        $order->save();

        return response()->json($order);
    }
}
