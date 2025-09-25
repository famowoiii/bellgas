<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RealtimeController extends Controller
{
    /**
     * Get real-time order updates for admin dashboard
     */
    public function getOrderUpdates(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $since = $request->get('since', now()->subMinutes(5)->toISOString());
        $sinceCarbon = Carbon::parse($since);

        try {
            // Get new orders since timestamp
            $newOrders = Order::with(['user', 'items.productVariant.product', 'address'])
                ->where('created_at', '>', $sinceCarbon)
                ->orderBy('created_at', 'desc')
                ->get();

            // Get order status changes since timestamp
            $statusChanges = OrderEvent::with(['order.user'])
                ->where('created_at', '>', $sinceCarbon)
                ->where('event_type', 'STATUS_CHANGED')
                ->orderBy('created_at', 'desc')
                ->get();

            // Get payment confirmations since timestamp
            $paymentUpdates = OrderEvent::with(['order.user'])
                ->where('created_at', '>', $sinceCarbon)
                ->where('event_type', 'PAYMENT_CONFIRMED')
                ->orderBy('created_at', 'desc')
                ->get();

            $updates = [];

            // Process new orders
            foreach ($newOrders as $order) {
                $updates[] = [
                    'type' => 'NEW_ORDER',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user->first_name . ' ' . $order->user->last_name,
                    'customer_email' => $order->user->email,
                    'total' => $order->total_aud,
                    'fulfillment_method' => $order->fulfillment_method,
                    'status' => $order->status,
                    'items_count' => $order->items->count(),
                    'message' => "🔔 New order {$order->order_number} from {$order->user->first_name} {$order->user->last_name}",
                    'timestamp' => $order->created_at->toISOString(),
                    'priority' => 'high',
                    'sound' => 'new_order'
                ];
            }

            // Process status changes
            foreach ($statusChanges as $event) {
                $order = $event->order;
                $updates[] = [
                    'type' => 'STATUS_CHANGE',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user->first_name . ' ' . $order->user->last_name,
                    'old_status' => $this->extractOldStatus($event->description),
                    'new_status' => $order->status,
                    'message' => "📊 Order {$order->order_number} status updated to {$order->status}",
                    'timestamp' => $event->created_at->toISOString(),
                    'priority' => 'medium',
                    'sound' => 'status_change'
                ];
            }

            // Process payment confirmations
            foreach ($paymentUpdates as $event) {
                $order = $event->order;
                $updates[] = [
                    'type' => 'PAYMENT_CONFIRMED',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user->first_name . ' ' . $order->user->last_name,
                    'total' => $order->total_aud,
                    'message' => "💳 Payment confirmed for order {$order->order_number}",
                    'timestamp' => $event->created_at->toISOString(),
                    'priority' => 'high',
                    'sound' => 'payment_confirmed'
                ];
            }

            // Sort updates by timestamp (newest first)
            usort($updates, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            // Get current stats
            $stats = $this->getAdminStatsReal();

            return response()->json([
                'success' => true,
                'updates' => $updates,
                'stats' => $stats,
                'timestamp' => now()->toISOString(),
                'has_updates' => count($updates) > 0
            ]);

        } catch (\Exception $e) {
            \Log::error('Real-time order updates error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get updates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time order updates for customer
     */
    public function getCustomerOrderUpdates(Request $request): JsonResponse
    {
        $user = Auth::user();
        $since = $request->get('since', now()->subMinutes(5)->toISOString());
        $sinceCarbon = Carbon::parse($since);

        try {
            // Get customer's order status changes since timestamp
            $statusChanges = OrderEvent::whereHas('order', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with(['order'])
                ->where('created_at', '>', $sinceCarbon)
                ->whereIn('event_type', ['STATUS_CHANGED', 'PAYMENT_CONFIRMED', 'ORDER_CANCELLED'])
                ->orderBy('created_at', 'desc')
                ->get();

            $updates = [];

            foreach ($statusChanges as $event) {
                $order = $event->order;
                $updateType = $this->getCustomerUpdateType($event->event_type, $order->status);
                
                $updates[] = [
                    'type' => $updateType['type'],
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'total' => $order->total_aud,
                    'fulfillment_method' => $order->fulfillment_method,
                    'message' => $updateType['message'],
                    'description' => $updateType['description'],
                    'emoji' => $updateType['emoji'] ?? '📦',
                    'timestamp' => $event->created_at->toISOString(),
                    'priority' => $updateType['priority'],
                    'action_required' => $updateType['action_required'] ?? false
                ];
            }

            // Get customer's order stats
            $customerStats = $this->getCustomerStats($user->id);

            return response()->json([
                'success' => true,
                'updates' => $updates,
                'stats' => $customerStats,
                'timestamp' => now()->toISOString(),
                'has_updates' => count($updates) > 0
            ]);

        } catch (\Exception $e) {
            \Log::error('Customer real-time updates error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get updates'
            ], 500);
        }
    }

    /**
     * Get admin statistics untuk real-time dashboard
     */
    public function getAdminStats(Request $request): JsonResponse
    {
        try {
            $today = Carbon::today();

            $stats = [
                'orders_today' => Order::whereDate('created_at', $today)->count(),
                'pending_orders' => Order::whereIn('status', ['UNPAID', 'PAID'])->count(),
                'processing_orders' => Order::where('status', 'PROCESSING')->count(),
                'completed_orders' => Order::where('status', 'DELIVERED')->count(),
                'revenue_today' => Order::whereDate('created_at', $today)
                    ->where('status', '!=', 'CANCELLED')
                    ->sum('total_aud'),
                'recent_orders' => Order::with('user')
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(function ($order) {
                        return [
                            'order_number' => $order->order_number,
                            'customer' => $order->user->first_name . ' ' . $order->user->last_name,
                            'status' => $order->status,
                            'total' => $order->total_aud,
                            'created_at' => $order->created_at->toISOString(),
                        ];
                    }),
            ];

            return response()->json([
                'success' => true,
                'timestamp' => now()->toISOString(),
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get admin stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate message untuk order update
     */
    private function getOrderMessage(Order $order): string
    {
        $customerName = $order->user->first_name . ' ' . $order->user->last_name;
        
        switch ($order->status) {
            case 'UNPAID':
                return "New order {$order->order_number} from {$customerName} - Awaiting payment";
            case 'PAID':
                return "Order {$order->order_number} paid by {$customerName} - Ready to process";
            case 'PROCESSING':
                return "Order {$order->order_number} is being processed";
            case 'READY_FOR_PICKUP':
                return "Order {$order->order_number} ready for pickup";
            case 'SHIPPED':
                return "Order {$order->order_number} has been shipped";
            case 'DELIVERED':
                return "Order {$order->order_number} delivered to {$customerName}";
            case 'CANCELLED':
                return "Order {$order->order_number} has been cancelled";
            default:
                return "Order {$order->order_number} status updated";
        }
    }

    /**
     * Get admin statistics for real-time dashboard
     */
    private function getAdminStatsReal()
    {
        return [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'PENDING')->count(),
            'paid_orders' => Order::where('status', 'PAID')->count(),
            'processing_orders' => Order::where('status', 'PROCESSED')->count(),
            'completed_orders' => Order::where('status', 'DONE')->count(),
            'cancelled_orders' => Order::where('status', 'CANCELLED')->count(),
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => Order::whereDate('created_at', today())
                ->whereIn('status', ['PAID', 'PROCESSED', 'DONE'])
                ->sum('total_aud'),
            'total_revenue' => Order::whereIn('status', ['PAID', 'PROCESSED', 'DONE'])
                ->sum('total_aud'),
            'active_customers' => User::where('role', 'CUSTOMER')
                ->where('is_active', true)
                ->count()
        ];
    }

    /**
     * Get customer stats
     */
    private function getCustomerStats($userId)
    {
        $orders = Order::where('user_id', $userId);
        
        return [
            'total_orders' => $orders->count(),
            'pending_orders' => $orders->whereIn('status', ['PENDING', 'PAID', 'PROCESSED'])->count(),
            'completed_orders' => $orders->where('status', 'DONE')->count(),
            'cancelled_orders' => $orders->where('status', 'CANCELLED')->count(),
            'total_spent' => $orders->whereIn('status', ['PAID', 'PROCESSED', 'DONE'])
                ->sum('total_aud')
        ];
    }

    /**
     * Extract old status from event description
     */
    private function extractOldStatus($description)
    {
        if (preg_match('/from (\w+) to/', $description, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get customer update type and message
     */
    private function getCustomerUpdateType($eventType, $currentStatus)
    {
        switch ($eventType) {
            case 'PAYMENT_CONFIRMED':
                return [
                    'type' => 'PAYMENT_SUCCESS',
                    'message' => 'Payment Confirmed! ✅',
                    'description' => 'Your payment has been processed successfully. We are now preparing your order.',
                    'emoji' => '💳',
                    'priority' => 'high'
                ];
                
            case 'STATUS_CHANGED':
                switch ($currentStatus) {
                    case 'PROCESSED':
                        return [
                            'type' => 'ORDER_PROCESSING',
                            'message' => 'Order Being Processed 🔄',
                            'description' => 'Your order is being prepared and will be ready soon.',
                            'emoji' => '👨‍🍳',
                            'priority' => 'medium'
                        ];
                        
                    case 'DONE':
                        return [
                            'type' => 'ORDER_COMPLETED',
                            'message' => 'Order Completed! 🎉',
                            'description' => 'Your order has been completed successfully. Thank you for your business!',
                            'emoji' => '✅',
                            'priority' => 'high'
                        ];
                        
                    default:
                        return [
                            'type' => 'STATUS_UPDATE',
                            'message' => 'Order Status Updated 📋',
                            'description' => "Your order status has been updated to {$currentStatus}.",
                            'emoji' => '📊',
                            'priority' => 'medium'
                        ];
                }
                
            case 'ORDER_CANCELLED':
                return [
                    'type' => 'ORDER_CANCELLED',
                    'message' => 'Order Cancelled ❌',
                    'description' => 'Your order has been cancelled. If you have any questions, please contact us.',
                    'emoji' => '🚫',
                    'priority' => 'high'
                ];
                
            default:
                return [
                    'type' => 'GENERAL_UPDATE',
                    'message' => 'Order Update 📦',
                    'description' => 'There has been an update to your order.',
                    'emoji' => '📦',
                    'priority' => 'low'
                ];
        }
    }

    /**
     * Generate message untuk customer order update
     */
    private function getCustomerOrderMessage(Order $order): string
    {
        switch ($order->status) {
            case 'PENDING':
                return "Your order {$order->order_number} is awaiting payment";
            case 'PAID':
                return "Payment received for order {$order->order_number}";
            case 'PROCESSED':
                return "Your order {$order->order_number} is being processed";
            case 'DONE':
                return "Your order {$order->order_number} has been completed";
            case 'CANCELLED':
                return "Your order {$order->order_number} has been cancelled";
            default:
                return "Your order {$order->order_number} has been updated";
        }
    }
}