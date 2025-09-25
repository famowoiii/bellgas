<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * Get all customers (Admin/Merchant only)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = User::where('role', 'CUSTOMER')
            ->withCount(['orders', 'addresses'])
            ->with(['orders' => function($query) {
                $query->select('user_id', 'total_aud', 'status', 'created_at')
                    ->latest()
                    ->limit(3);
            }]);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Sort functionality
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $customers = $query->paginate($request->get('per_page', 15));

        // Add additional statistics for each customer
        $customers->getCollection()->transform(function ($customer) {
            $customer->total_spent = $customer->orders()
                ->whereIn('status', ['PAID', 'PROCESSED', 'WAITING_PICKUP', 'ON_DELIVERY', 'DONE'])
                ->sum('total_aud');

            $customer->last_order_date = $customer->orders()
                ->latest()
                ->value('created_at');

            $customer->average_order_value = $customer->orders()
                ->whereIn('status', ['PAID', 'PROCESSED', 'WAITING_PICKUP', 'ON_DELIVERY', 'DONE'])
                ->avg('total_aud') ?? 0;

            return $customer;
        });

        return response()->json([
            'success' => true,
            'data' => $customers,
            'meta' => [
                'total_customers' => User::where('role', 'CUSTOMER')->count(),
                'active_customers' => User::where('role', 'CUSTOMER')->where('is_active', true)->count(),
                'inactive_customers' => User::where('role', 'CUSTOMER')->where('is_active', false)->count(),
            ]
        ]);
    }

    /**
     * Get detailed customer information
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $customer = User::where('role', 'CUSTOMER')
            ->with([
                'orders' => function($query) {
                    $query->with(['items.productVariant.product', 'address'])
                        ->orderBy('created_at', 'desc');
                },
                'addresses'
            ])
            ->find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        // Calculate customer statistics
        $orderStats = [
            'total_orders' => $customer->orders->count(),
            'total_spent' => $customer->orders()
                ->whereIn('status', ['PAID', 'PROCESSED', 'WAITING_PICKUP', 'ON_DELIVERY', 'DONE'])
                ->sum('total_aud'),
            'average_order_value' => $customer->orders()
                ->whereIn('status', ['PAID', 'PROCESSED', 'WAITING_PICKUP', 'ON_DELIVERY', 'DONE'])
                ->avg('total_aud') ?? 0,
            'completed_orders' => $customer->orders()->where('status', 'DONE')->count(),
            'cancelled_orders' => $customer->orders()->where('status', 'CANCELLED')->count(),
            'pending_orders' => $customer->orders()
                ->whereIn('status', ['PENDING', 'PAID', 'PROCESSED', 'WAITING_PICKUP', 'ON_DELIVERY'])
                ->count(),
            'first_order_date' => $customer->orders()->oldest()->value('created_at'),
            'last_order_date' => $customer->orders()->latest()->value('created_at'),
        ];

        // Recent activity
        $recentActivity = $customer->orders()
            ->with(['items.productVariant.product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($order) {
                return [
                    'type' => 'order',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'total' => $order->total_aud,
                    'items_count' => $order->items->count(),
                    'date' => $order->created_at,
                    'fulfillment_method' => $order->fulfillment_method
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $customer,
                'statistics' => $orderStats,
                'recent_activity' => $recentActivity
            ]
        ]);
    }

    /**
     * Update customer information (Admin/Merchant only)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $customer = User::where('role', 'CUSTOMER')->find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $customer->id,
            'phone_number' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'is_active' => $request->get('is_active', $customer->is_active)
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $customer->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => $customer->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle customer active status
     */
    public function toggleStatus($id): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $customer = User::where('role', 'CUSTOMER')->find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $customer->is_active = !$customer->is_active;
        $customer->save();

        $status = $customer->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Customer {$status} successfully",
            'data' => [
                'id' => $customer->id,
                'is_active' => $customer->is_active
            ]
        ]);
    }

    /**
     * Delete customer (soft delete by deactivating)
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $customer = User::where('role', 'CUSTOMER')->find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        // Check if customer has pending orders
        $pendingOrders = $customer->orders()
            ->whereIn('status', ['PENDING', 'PAID', 'PROCESSED', 'WAITING_PICKUP', 'ON_DELIVERY'])
            ->count();

        if ($pendingOrders > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete customer with pending orders. Please complete or cancel all orders first.',
                'pending_orders' => $pendingOrders
            ], 400);
        }

        // Soft delete by deactivating
        $customer->is_active = false;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Customer deactivated successfully'
        ]);
    }

    /**
     * Get customer statistics overview
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $totalCustomers = User::where('role', 'CUSTOMER')->count();
        $activeCustomers = User::where('role', 'CUSTOMER')->where('is_active', true)->count();
        $newCustomersThisMonth = User::where('role', 'CUSTOMER')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Top customers by total spent
        $topCustomers = User::where('role', 'CUSTOMER')
            ->withSum(['orders' => function($query) {
                $query->whereIn('status', ['PAID', 'PROCESSED', 'WAITING_PICKUP', 'ON_DELIVERY', 'DONE']);
            }], 'total_aud')
            ->orderByDesc('orders_sum_total_aud')
            ->limit(10)
            ->get()
            ->map(function($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->full_name,
                    'email' => $customer->email,
                    'total_spent' => $customer->orders_sum_total_aud ?? 0,
                    'orders_count' => $customer->orders()->count()
                ];
            });

        // Customer growth over last 6 months
        $customerGrowth = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $customerGrowth[] = [
                'month' => $date->format('M Y'),
                'count' => User::where('role', 'CUSTOMER')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count()
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_customers' => $totalCustomers,
                    'active_customers' => $activeCustomers,
                    'inactive_customers' => $totalCustomers - $activeCustomers,
                    'new_customers_this_month' => $newCustomersThisMonth,
                    'customer_retention_rate' => $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 2) : 0
                ],
                'top_customers' => $topCustomers,
                'customer_growth' => $customerGrowth
            ]
        ]);
    }

    /**
     * Export customers data to CSV
     */
    public function export(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $query = User::where('role', 'CUSTOMER')
                ->withCount('orders')
                ->withSum(['orders' => function($query) {
                    $query->whereIn('status', ['PAID', 'PROCESSED', 'WAITING_PICKUP', 'ON_DELIVERY', 'DONE']);
                }], 'total_aud');

            // Apply filters if provided
            if ($request->has('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $customers = $query->get();

            $csvData = [];
            $csvData[] = [
                'ID',
                'First Name',
                'Last Name',
                'Email',
                'Phone',
                'Status',
                'Registration Date',
                'Total Orders',
                'Total Spent (AUD)',
                'Last Order Date'
            ];

            foreach ($customers as $customer) {
                $csvData[] = [
                    $customer->id,
                    $customer->first_name,
                    $customer->last_name,
                    $customer->email,
                    $customer->phone_number,
                    $customer->is_active ? 'Active' : 'Inactive',
                    $customer->created_at->format('Y-m-d H:i:s'),
                    $customer->orders_count,
                    number_format($customer->orders_sum_total_aud ?? 0, 2),
                    $customer->orders()->latest()->value('created_at')
                ];
            }

            $filename = 'customers_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
            $filePath = storage_path('app/exports/' . $filename);

            // Create exports directory if it doesn't exist
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            $file = fopen($filePath, 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);

            return response()->json([
                'success' => true,
                'message' => 'Customers data exported successfully',
                'data' => [
                    'filename' => $filename,
                    'download_url' => url('api/customers/download/' . $filename),
                    'records_count' => count($customers)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export customers data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download exported CSV file
     */
    public function download($filename)
    {
        $filePath = storage_path('app/exports/' . $filename);

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}