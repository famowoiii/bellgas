<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminNotificationController extends Controller
{
    /**
     * Get new paid orders for admin notification polling
     */
    public function getNewPaidOrders(Request $request)
    {
        // Use api guard (JWT)
        $user = Auth::guard('api')->user();

        // Debug logging
        \Log::info('Admin polling request', [
            'user_id' => $user ? $user->id : null,
            'user_role' => $user ? $user->role : null,
            'user_email' => $user ? $user->email : null,
            'has_token' => $request->bearerToken() ? 'yes' : 'no'
        ]);

        // Only admin/merchant can access
        if (!$user || !in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            \Log::warning('Admin polling unauthorized', [
                'user_role' => $user ? $user->role : 'no user',
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Admin or Merchant role required',
                'debug' => [
                    'authenticated' => $user ? true : false,
                    'role' => $user ? $user->role : null
                ]
            ], 403);
        }

        // Get last check timestamp from request or use current time minus 30 seconds
        $lastCheck = $request->get('last_check', now()->subSeconds(30)->toDateTimeString());

        // Get orders that became PAID after last check
        $newPaidOrders = Order::with(['user:id,first_name,last_name,email', 'items'])
            ->where('status', 'PAID')
            ->where('updated_at', '>', $lastCheck)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user->first_name . ' ' . $order->user->last_name,
                    'customer_email' => $order->user->email,
                    'total_aud' => $order->total_aud,
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->toISOString(),
                    'updated_at' => $order->updated_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'new_orders' => $newPaidOrders,
            'count' => $newPaidOrders->count(),
            'current_time' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get notification count (unread orders)
     */
    public function getNotificationCount(Request $request)
    {
        // Use api guard (JWT)
        $user = Auth::guard('api')->user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Count orders that are PAID but not yet processed
        $count = Order::where('status', 'PAID')
            ->whereDate('created_at', '>=', now()->subDays(1)) // Only today and yesterday
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }
}
