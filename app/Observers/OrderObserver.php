<?php

namespace App\Observers;

use App\Models\LoyaltyLedger;
use App\Models\MembershipTier;
use App\Models\Order;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if ($order->isDirty('status') && $order->status === 'delivered' && $order->user_id !== null) {
            $points = (int) floor($order->total / 10000);

            if ($points > 0) {
                // 1. Create loyalty ledger log
                LoyaltyLedger::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'points' => $points,
                    'type' => 'earn',
                    'note' => "Tích điểm từ đơn hàng {$order->order_code}",
                ]);

                // 2. Update user points & tier
                $user = $order->user;
                $newPoints = $user->points + $points;
                $user->points = $newPoints;

                // Find matching membership tier
                $tier = MembershipTier::where('min_points', '<=', $newPoints)
                    ->orderBy('min_points', 'desc')
                    ->first();

                if ($tier) {
                    $user->membership_tier_id = $tier->id;
                }

                $user->save();
            }
        }
    }
}
