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

class ReportController extends Controller
{
    /**
     * Export orders to CSV
     */
    public function exportOrders(Request $request)
    {
        // Check if user is merchant or admin
        if (!in_array(auth()->user()->role, ['MERCHANT', 'ADMIN'])) {
            return response()->json([
                'message' => 'Unauthorized. Only merchants and admins can export orders.'
            ], 403);
        }

        try {
            $query = Order::with(['user', 'items.productVariant.product', 'address']);

            // Apply filters
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('fulfillment_method') && $request->fulfillment_method) {
                $query->where('fulfillment_method', $request->fulfillment_method);
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
            }

            $orders = $query->orderBy('created_at', 'desc')->get();

            // Generate CSV content
            $csvData = [];

            // Add headers
            $csvData[] = [
                'Order Number',
                'Customer Name',
                'Customer Email',
                'Customer Phone',
                'Status',
                'Fulfillment Method',
                'Subtotal (AUD)',
                'Shipping Cost (AUD)',
                'Total (AUD)',
                'Payment Method',
                'Address',
                'Items',
                'Customer Notes',
                'Order Date',
                'Completed Date'
            ];

            foreach ($orders as $order) {
                $itemsDescription = $order->items->map(function ($item) {
                    return $item->quantity . 'x ' . $item->productVariant->product->name . ' (' . $item->productVariant->name . ')';
                })->join('; ');

                $address = $order->fulfillment_method === 'PICKUP'
                    ? 'Pickup at Store - BellGas LPG Store, 123 Main Street, Melbourne VIC 3000'
                    : ($order->address ? "{$order->address->street_address}, {$order->address->suburb} {$order->address->state} {$order->address->postcode}" : 'N/A');

                $csvData[] = [
                    $order->order_number,
                    $order->user->first_name . ' ' . $order->user->last_name,
                    $order->user->email,
                    $order->user->phone_number ?? 'N/A',
                    $order->status,
                    $order->fulfillment_method,
                    $order->subtotal_aud,
                    $order->shipping_cost_aud,
                    $order->total_aud,
                    $order->payment_method ?? 'Stripe',
                    $address,
                    $itemsDescription,
                    $order->customer_notes ?? '',
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->completed_at ? $order->completed_at->format('Y-m-d H:i:s') : ''
                ];
            }

            // Create CSV content
            $output = fopen('php://temp', 'r+');
            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }
            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);

            // Generate filename with timestamp
            $filename = 'bellgas_orders_export_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

            return response($csvContent)
                ->header('Content-Type', 'text/csv; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            \Log::error('Export orders error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to export orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate sales report
     */
    public function salesReport(Request $request): JsonResponse
    {
        // Check if user is merchant or admin
        if (!in_array(auth()->user()->role, ['MERCHANT', 'ADMIN'])) {
            return response()->json([
                'message' => 'Unauthorized. Only merchants and admins can view reports.'
            ], 403);
        }

        try {
            $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
            $dateTo = $request->get('date_to', Carbon::now()->endOfDay());

            if (is_string($dateFrom)) {
                $dateFrom = Carbon::parse($dateFrom)->startOfDay();
            }
            if (is_string($dateTo)) {
                $dateTo = Carbon::parse($dateTo)->endOfDay();
            }

            // Sales by status
            $salesByStatus = Order::whereBetween('created_at', [$dateFrom, $dateTo])
                ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_aud) as total'))
                ->groupBy('status')
                ->get();

            // Sales by fulfillment method
            $salesByFulfillment = Order::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNotIn('status', ['CANCELLED'])
                ->select('fulfillment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_aud) as total'))
                ->groupBy('fulfillment_method')
                ->get();

            // Sales by product type
            $salesByProductType = DB::table('orders')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
                ->whereNotIn('orders.status', ['CANCELLED'])
                ->select(
                    'products.category as product_type',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.quantity * order_items.price_aud) as total_revenue')
                )
                ->groupBy('products.category')
                ->get();

            // Daily sales trend
            $dailySales = Order::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNotIn('status', ['CANCELLED'])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('SUM(total_aud) as revenue')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            // Top products
            $topProducts = DB::table('order_items')
                ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
                ->whereNotIn('orders.status', ['CANCELLED'])
                ->select(
                    'products.name',
                    'products.category as product_type',
                    DB::raw('SUM(order_items.quantity) as total_sold'),
                    DB::raw('SUM(order_items.quantity * order_items.price_aud) as total_revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.category')
                ->orderBy('total_revenue', 'desc')
                ->limit(10)
                ->get();

            // Summary metrics
            $totalOrders = Order::whereBetween('created_at', [$dateFrom, $dateTo])->count();
            $totalRevenue = Order::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNotIn('status', ['CANCELLED'])
                ->sum('total_aud');
            $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'from' => $dateFrom->format('Y-m-d'),
                        'to' => $dateTo->format('Y-m-d')
                    ],
                    'summary' => [
                        'total_orders' => $totalOrders,
                        'total_revenue' => round($totalRevenue, 2),
                        'average_order_value' => round($averageOrderValue, 2)
                    ],
                    'sales_by_status' => $salesByStatus,
                    'sales_by_fulfillment' => $salesByFulfillment,
                    'sales_by_product_type' => $salesByProductType,
                    'daily_sales' => $dailySales,
                    'top_products' => $topProducts
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Sales report error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate sales report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate customer report
     */
    public function customerReport(Request $request): JsonResponse
    {
        // Check if user is merchant or admin
        if (!in_array(auth()->user()->role, ['MERCHANT', 'ADMIN'])) {
            return response()->json([
                'message' => 'Unauthorized. Only merchants and admins can view reports.'
            ], 403);
        }

        try {
            $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
            $dateTo = $request->get('date_to', Carbon::now()->endOfDay());

            if (is_string($dateFrom)) {
                $dateFrom = Carbon::parse($dateFrom)->startOfDay();
            }
            if (is_string($dateTo)) {
                $dateTo = Carbon::parse($dateTo)->endOfDay();
            }

            // Top customers by revenue
            $topCustomers = DB::table('users')
                ->join('orders', 'users.id', '=', 'orders.user_id')
                ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
                ->whereNotIn('orders.status', ['CANCELLED'])
                ->where('users.role', 'CUSTOMER')
                ->select(
                    'users.id',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                    DB::raw('COUNT(orders.id) as total_orders'),
                    DB::raw('SUM(orders.total_aud) as total_spent'),
                    DB::raw('AVG(orders.total_aud) as avg_order_value')
                )
                ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->orderBy('total_spent', 'desc')
                ->limit(20)
                ->get();

            // New customers in period
            $newCustomers = User::where('role', 'CUSTOMER')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count();

            // Customer retention (customers who ordered more than once)
            $repeatCustomers = DB::table('users')
                ->join('orders', 'users.id', '=', 'orders.user_id')
                ->where('users.role', 'CUSTOMER')
                ->whereNotIn('orders.status', ['CANCELLED'])
                ->select('users.id')
                ->groupBy('users.id')
                ->havingRaw('COUNT(orders.id) > 1')
                ->get()
                ->count();

            $totalCustomersWithOrders = DB::table('users')
                ->join('orders', 'users.id', '=', 'orders.user_id')
                ->where('users.role', 'CUSTOMER')
                ->whereNotIn('orders.status', ['CANCELLED'])
                ->distinct('users.id')
                ->count();

            $retentionRate = $totalCustomersWithOrders > 0 ? ($repeatCustomers / $totalCustomersWithOrders) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'from' => $dateFrom->format('Y-m-d'),
                        'to' => $dateTo->format('Y-m-d')
                    ],
                    'summary' => [
                        'new_customers' => $newCustomers,
                        'repeat_customers' => $repeatCustomers,
                        'retention_rate' => round($retentionRate, 2)
                    ],
                    'top_customers' => $topCustomers
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Customer report error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate customer report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}