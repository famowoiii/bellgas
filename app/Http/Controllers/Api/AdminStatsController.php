<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminStatsController extends Controller
{
    /**
     * Get comprehensive admin dashboard statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

            // Main metrics
            $totalOrders = Order::count();
            $totalCustomers = User::where('role', 'CUSTOMER')->count();
            $totalRevenue = Order::whereNotIn('status', ['PENDING', 'CANCELLED'])
                ->sum('total_aud');

            // Calculate total products sold
            $totalProductsSold = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereNotIn('orders.status', ['PENDING', 'CANCELLED'])
                ->sum('order_items.quantity');

            // Growth calculations
            $currentMonthOrders = Order::where('created_at', '>=', $thisMonth)->count();
            $lastMonthOrders = Order::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();
            $ordersGrowth = $this->calculateGrowthPercentage($currentMonthOrders, $lastMonthOrders);

            $currentMonthRevenue = Order::where('created_at', '>=', $thisMonth)
                ->whereNotIn('status', ['PENDING', 'CANCELLED'])
                ->sum('total_aud');
            $lastMonthRevenue = Order::whereBetween('created_at', [$lastMonth, $lastMonthEnd])
                ->whereNotIn('status', ['PENDING', 'CANCELLED'])
                ->sum('total_aud');
            $revenueGrowth = $this->calculateGrowthPercentage($currentMonthRevenue, $lastMonthRevenue);

            $stats = [
                // Main dashboard metrics
                'total_revenue' => round($totalRevenue, 2),
                'total_orders' => $totalOrders,
                'total_customers' => $totalCustomers,
                'total_products_sold' => $totalProductsSold,

                // Today's metrics
                'orders_today' => Order::whereDate('created_at', $today)->count(),
                'revenue_today' => round(Order::whereDate('created_at', $today)
                    ->whereNotIn('status', ['PENDING', 'CANCELLED'])
                    ->sum('total_aud'), 2),

                // This month's metrics
                'orders_this_month' => $currentMonthOrders,
                'revenue_this_month' => round($currentMonthRevenue, 2),

                // Growth metrics
                'orders_growth' => $ordersGrowth,
                'revenue_growth' => $revenueGrowth,

                // Order status breakdown
                'pending_orders' => Order::where('status', 'PENDING')->count(),
                'paid_orders' => Order::where('status', 'PAID')->count(),
                'processing_orders' => Order::where('status', 'PROCESSED')->count(),
                'completed_orders' => Order::where('status', 'DONE')->count(),
                'cancelled_orders' => Order::where('status', 'CANCELLED')->count(),

                // Recent orders
                'recent_orders' => $this->getRecentOrders(),

                // Performance metrics
                'avg_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
            ];

            return response()->json([
                'success' => true,
                'timestamp' => now()->toISOString(),
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get admin stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent orders with details
     */
    private function getRecentOrders()
    {
        return Order::with(['user', 'items.productVariant.product'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer' => $order->user->first_name . ' ' . $order->user->last_name,
                    'customer_email' => $order->user->email,
                    'status' => $order->status,
                    'total' => $order->total_aud,
                    'fulfillment_method' => $order->fulfillment_method,
                    'items_count' => $order->items->count(),
                    'customer_notes' => $order->customer_notes,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $order->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Calculate growth percentage
     */
    private function calculateGrowthPercentage($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get order stats for charts
     */
    public function getOrderStats(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 7);
            $endDate = Carbon::now()->endOfDay();
            $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

            $orderStats = [];
            $revenueStats = [];

            for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
                $ordersCount = Order::whereDate('created_at', $date)->count();
                $revenue = Order::whereDate('created_at', $date)
                    ->whereNotIn('status', ['PENDING', 'CANCELLED'])
                    ->sum('total_aud');

                $orderStats[] = [
                    'date' => $date->format('Y-m-d'),
                    'label' => $date->format('M j'),
                    'orders' => $ordersCount,
                ];

                $revenueStats[] = [
                    'date' => $date->format('Y-m-d'),
                    'label' => $date->format('M j'),
                    'revenue' => round($revenue, 2),
                ];
            }

            return response()->json([
                'success' => true,
                'order_stats' => $orderStats,
                'revenue_stats' => $revenueStats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get order stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}