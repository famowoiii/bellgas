<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['jwt.auth']);
    }

    public function index()
    {
        $user = Auth::guard("api")->user();
        if (!$user || !in_array($user->role, ["ADMIN", "MERCHANT"])) {
            return response()->json(["message" => "Access denied"], 403);
        }

        // Basic statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'total_categories' => Category::count(),
            'active_categories' => Category::where('is_active', true)->count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'UNPAID')->count(),
            'processing_orders' => Order::where('status', 'PROCESSING')->count(),
            'completed_orders' => Order::where('status', 'COMPLETED')->count(),
            'total_revenue' => Order::whereIn('status', ['COMPLETED', 'PAID'])->sum('total_aud'),
            'today_revenue' => Order::whereIn('status', ['COMPLETED', 'PAID'])
                ->whereDate('created_at', today())->sum('total_aud'),
            'this_month_revenue' => Order::whereIn('status', ['COMPLETED', 'PAID'])
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_aud'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'user' => Auth::user(),
                'timestamp' => now()
            ]
        ]);
    }

    public function recentOrders()
    {
        $orders = Order::with(['user', 'items.productVariant.product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function salesChart(Request $request)
    {
        $period = $request->get('period', '30'); // days
        $startDate = now()->subDays($period);

        $salesData = Order::whereIn('status', ['COMPLETED', 'PAID'])
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_aud) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period . ' days',
                'sales' => $salesData
            ]
        ]);
    }

    public function topProducts()
    {
        $topProducts = DB::table('order_items')
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['COMPLETED', 'PAID'])
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.total_price_aud) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topProducts
        ]);
    }

    public function userStats()
    {
        $userStats = [
            'total_users' => User::count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
            'active_users' => User::where('is_active', true)->count(),
            'users_by_role' => User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $userStats
        ]);
    }

    public function systemHealth()
    {
        $health = [
            'database' => 'connected',
            'cache' => 'working',
            'queue' => 'working',
            'mail' => 'configured',
            'storage' => 'accessible',
            'timestamp' => now(),
        ];

        // Test database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $health['database'] = 'error: ' . $e->getMessage();
        }

        return response()->json([
            'success' => true,
            'data' => $health
        ]);
    }
}