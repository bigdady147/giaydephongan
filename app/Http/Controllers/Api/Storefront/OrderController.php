<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function validateDiscount(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $code = DiscountCode::where('code', $request->code)->first();

        if (!$code) {
            return response()->json([
                'valid' => false,
                'message' => 'Mã giảm giá không tồn tại.'
            ], 422);
        }

        if (!$code->isValidFor($request->subtotal)) {
            return response()->json([
                'valid' => false,
                'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn.'
            ], 422);
        }

        $discountAmount = $code->calculateDiscount($request->subtotal);

        return response()->json([
            'valid' => true,
            'discount_type' => $code->type,
            'discount_value' => $code->value,
            'discount_amount' => $discountAmount,
            'message' => 'Áp dụng mã giảm giá thành công.'
        ]);
    }

    public function store(Request $request)
    {
        $isAuth = auth('sanctum')->check();

        $rules = [
            'shipping_address' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount_code' => 'nullable|string',
            'note' => 'nullable|string',
        ];

        if (!$isAuth) {
            $rules['guest_name'] = 'required|string';
            $rules['guest_phone'] = 'required|string';
            $rules['guest_email'] = 'nullable|email';
        }

        $request->validate($rules);

        try {
            $order = DB::transaction(function () use ($request, $isAuth) {
                $subtotal = 0;
                $itemsToCreate = [];

                foreach ($request->items as $itemData) {
                    $variant = ProductVariant::with('product')->lockForUpdate()->find($itemData['variant_id']);

                    if ($variant->stock_quantity < $itemData['quantity']) {
                        throw new \Exception("Sản phẩm {$variant->product->name} (size {$variant->size}, màu {$variant->color}) không đủ hàng tồn kho.", 422);
                    }

                    // Decrement stock
                    $variant->decrement('stock_quantity', $itemData['quantity']);

                    $price = $variant->price_override !== null ? $variant->price_override : ($variant->product->sale_price ?? $variant->product->base_price);
                    $itemSubtotal = $price * $itemData['quantity'];
                    $subtotal += $itemSubtotal;

                    $itemsToCreate[] = [
                        'product_variant_id' => $variant->id,
                        'product_name_snapshot' => $variant->product->name,
                        'variant_snapshot' => "Size {$variant->size} - Màu {$variant->color}",
                        'price' => $price,
                        'quantity' => $itemData['quantity'],
                        'subtotal' => $itemSubtotal,
                    ];
                }

                // Shipping fee calculation (Free shipping for subtotal >= 500,000 VND, otherwise 30,000 VND)
                $shippingFee = $subtotal >= 500000 ? 0 : 30000;

                // Discount calculation
                $discountAmount = 0;
                $discountCodeId = null;

                if ($request->filled('discount_code')) {
                    $code = DiscountCode::lockForUpdate()->where('code', $request->discount_code)->first();
                    if ($code && $code->isValidFor($subtotal)) {
                        $discountAmount = $code->calculateDiscount($subtotal);
                        $discountCodeId = $code->id;
                        $code->increment('used_count');
                    }
                }

                $total = $subtotal + $shippingFee - $discountAmount;
                $total = max(0, $total);

                $orderData = [
                    'user_id' => $isAuth ? auth('sanctum')->id() : null,
                    'guest_name' => $isAuth ? null : $request->guest_name,
                    'guest_phone' => $isAuth ? null : $request->guest_phone,
                    'guest_email' => $isAuth ? null : $request->guest_email,
                    'shipping_address' => $request->shipping_address,
                    'status' => 'pending',
                    'payment_method' => 'cod',
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'discount_amount' => $discountAmount,
                    'total' => $total,
                    'discount_code_id' => $discountCodeId,
                    'note' => $request->note,
                ];

                $order = Order::create($orderData);

                foreach ($itemsToCreate as $item) {
                    $order->items()->create($item);
                }

                return $order;
            });

            return response()->json([
                'order_code' => $order->order_code,
                'total' => $order->total,
                'status' => $order->status,
                'message' => 'Đặt hàng thành công.'
            ], 201);

        } catch (\Exception $e) {
            $code = $e->getCode() === 422 ? 422 : 500;
            return response()->json([
                'message' => $e->getMessage()
            ], $code);
        }
    }

    public function show(Request $request, $code)
    {
        $order = Order::with('items')->where('order_code', $code)->first();

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        $user = auth('sanctum')->user();

        if ($order->user_id !== null) {
            if (!$user || $user->id !== $order->user_id) {
                return response()->json(['message' => 'Bạn không có quyền truy cập đơn hàng này.'], 403);
            }
        } else {
            // Guest order: requires phone verification
            $phone = $request->query('phone');
            if (empty($phone) || str_replace([' ', '.', '-'], '', $phone) !== str_replace([' ', '.', '-'], '', $order->guest_phone)) {
                return response()->json(['message' => 'Yêu cầu cung cấp số điện thoại chính xác để tra cứu.'], 403);
            }
        }

        return response()->json($order);
    }
}
