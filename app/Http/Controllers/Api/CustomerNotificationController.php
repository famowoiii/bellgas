<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerNotificationController extends Controller
{
    /**
     * Get order status updates for customer notification polling
     */
    public function getOrderStatusUpdates(Request $request)
    {
        $user = Auth::user();

        // Only customers can access
        if ($user->role !== 'CUSTOMER') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Get last check timestamp from request or use current time minus 30 seconds
        $lastCheck = $request->get('last_check', now()->subSeconds(30)->toDateTimeString());

        // Get user's orders that were updated after last check
        $updatedOrders = Order::with(['items.productVariant.product'])
            ->where('user_id', $user->id)
            ->where('updated_at', '>', $lastCheck)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'total_aud' => $order->total_aud,
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->toISOString(),
                    'updated_at' => $order->updated_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'updated_orders' => $updatedOrders,
            'count' => $updatedOrders->count(),
            'current_time' => now()->toDateTimeString()
        ]);
    }
}
