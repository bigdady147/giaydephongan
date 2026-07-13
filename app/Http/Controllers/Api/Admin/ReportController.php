<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function revenue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $from = $request->filled('from') ? $request->input('from') : now()->subDays(29)->toDateString();
        $to = $request->filled('to') ? $request->input('to') : now()->toDateString();

        $baseQuery = fn () => Order::where('status', '!=', 'cancelled')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $daily = $baseQuery()
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'total_revenue' => (float) $baseQuery()->sum('total'),
            'total_orders' => $baseQuery()->count(),
            'daily' => $daily,
        ]);

    }

    public function topProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $from = $request->filled('from') ? $request->input('from') : now()->subDays(29)->toDateString();
        $to = $request->filled('to') ? $request->input('to') : now()->toDateString();
        $limit = (int) ($request->input('limit') ?? 10);

        $products = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereDate('orders.created_at', '>=', $from)
            ->whereDate('orders.created_at', '<=', $to)
            ->selectRaw('order_items.product_name_snapshot as name, SUM(order_items.quantity) as quantity_sold, SUM(order_items.subtotal) as revenue')
            ->groupBy('order_items.product_name_snapshot')
            ->orderByDesc('quantity_sold')
            ->limit($limit)
            ->get();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'products' => $products,
        ]);
    }
}
