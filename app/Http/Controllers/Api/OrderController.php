<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Cart;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Models\OrderEvent;
use App\Models\User;
use App\Models\Product;
use App\Mail\OrderConfirmation;
use App\Notifications\OrderStatusChanged;
use App\Services\OrderStatusService;
use App\Events\OrderUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected $orderStatusService;

    public function __construct(OrderStatusService $orderStatusService)
    {
        $this->orderStatusService = $orderStatusService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Order::with(['items.productVariant.product', 'address', 'user'])
            ->orderBy('created_at', 'desc');

        // If user is customer, only show their orders
        // If user is admin/merchant, show all orders
        if ($user->role === 'CUSTOMER') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate($request->get('per_page', 10));

        // Add status info and available actions for each order
        $orders->getCollection()->transform(function ($order) {
            $order->status_info = $this->orderStatusService->getStatusInfo($order->status, $order->fulfillment_method);
            $order->available_statuses = $this->orderStatusService->getAvailableStatuses($order);
            $order->admin_action = $this->orderStatusService->getAdminAction($order);
            $order->customer_message = $this->orderStatusService->getCustomerMessage($order);
            return $order;
        });

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|string|in:CARD,CASH',
            'fulfillment_method' => 'required|string|in:PICKUP,DELIVERY',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $cartItems = Cart::getCartItems($user->id);

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $total = $cartItems->sum('total');

            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $request->address_id,
                'status' => 'PENDING',
                'fulfillment_method' => $request->fulfillment_method,
                'subtotal_aud' => $total,
                'shipping_cost_aud' => 0,
                'total_aud' => $total,
                'customer_notes' => $request->notes,
            ]);

            // Validate fulfillment method for the order items
            $fulfillmentValidation = $this->orderStatusService->canUseFulfillmentMethod($order, $request->fulfillment_method);
            if (!$fulfillmentValidation['allowed']) {
                throw new \Exception($fulfillmentValidation['reason']);
            }

            foreach ($cartItems as $cartItem) {
                $productVariant = $cartItem->productVariant;

                if ($productVariant->stock_quantity < $cartItem->quantity) {
                    throw new \Exception("Insufficient stock for {$productVariant->product->name}");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price_aud' => $cartItem->price,
                    'total_price_aud' => $cartItem->total,
                ]);

                StockReservation::create([
                    'user_id' => $user->id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'quantity_reserved' => $cartItem->quantity,
                    'expires_at' => now()->addMinutes(15), // 15 minutes reservation
                ]);

                $productVariant->decrement('stock_quantity', $cartItem->quantity);
            }

            OrderEvent::create([
                'order_id' => $order->id,
                'event_type' => 'ORDER_CREATED',
                'description' => 'Order created successfully',
            ]);

            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            $order->load(['items.productVariant.product.photos', 'address']);

            // Broadcast real-time order creation event
            broadcast(new OrderUpdated($order, 'created'))->toOthers();

            // Send order confirmation email
            try {
                Mail::to($user->email)->send(new OrderConfirmation($order));
            } catch (\Exception $e) {
                \Log::warning('Failed to send order confirmation email: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Order $order)
    {
        $user = Auth::user();

        if ($order->user_id !== $user->id && !in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $order->load([
            'items.productVariant.product.photos',
            'address',
            'events' => function($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        // Add status information
        $order->status_info = $this->orderStatusService->getStatusInfo($order->status, $order->fulfillment_method);
        $order->available_statuses = $this->orderStatusService->getAvailableStatuses($order);
        $order->admin_action = $this->orderStatusService->getAdminAction($order);
        $order->customer_message = $this->orderStatusService->getCustomerMessage($order);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:PENDING,PAID,PROCESSED,WAITING_FOR_PICKUP,PICKED_UP,ON_DELIVERY,DONE,CANCELLED',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $oldStatus = $order->status;
            $newStatus = $request->status;

            // Use OrderStatusService to validate and update status
            $statusUpdated = $this->orderStatusService->updateStatus($order, $newStatus, [
                'admin_notes' => $request->notes,
                'updated_by' => $user->id
            ]);

            if (!$statusUpdated) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status transition',
                    'current_status' => $oldStatus,
                    'attempted_status' => $newStatus,
                    'available_statuses' => $this->orderStatusService->getAvailableStatuses($order),
                    'fulfillment_method' => $order->fulfillment_method
                ], 400);
            }

            // Update admin notes if provided
            if ($request->notes) {
                $order->update(['notes' => $request->notes]);
            }

            if ($newStatus === 'CANCELLED' && $oldStatus !== 'CANCELLED') {
                // Batch update stock quantities for better performance
                $stockUpdates = [];
                foreach ($order->items as $item) {
                    $stockUpdates[$item->product_variant_id] = ($stockUpdates[$item->product_variant_id] ?? 0) + $item->quantity;
                }

                foreach ($stockUpdates as $variantId => $quantity) {
                    ProductVariant::where('id', $variantId)->increment('stock_quantity', $quantity);
                }

                // Clean up stock reservations efficiently
                $productVariantIds = $order->items()->pluck('product_variant_id')->toArray();
                StockReservation::where('user_id', $order->user_id)
                    ->whereIn('product_variant_id', $productVariantIds)
                    ->delete();
            }

            DB::commit();

            // Only load minimal data needed for response first
            $order->load(['user:id,first_name,last_name,email', 'items:id,order_id,quantity,total_price_aud']);

            // Defer heavy operations after response to avoid blocking
            defer(function () use ($order, $oldStatus, $newStatus) {
                try {
                    // Load full relationships for broadcasting only when needed
                    $orderForBroadcast = Order::with(['items.productVariant.product', 'address', 'user'])
                        ->find($order->id);

                    // Broadcast real-time order status update event
                    broadcast(new OrderUpdated($orderForBroadcast, 'status_changed', $oldStatus, $newStatus))->toOthers();

                    // Send status change notification asynchronously
                    $order->user->notify(new OrderStatusChanged($order, $oldStatus, $newStatus));
                } catch (\Exception $e) {
                    \Log::warning('Failed to send order status notification or broadcast: ' . $e->getMessage());
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Order $order)
    {
        $user = Auth::user();

        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!in_array($order->status, ['PENDING', 'PAID'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Use OrderStatusService to update to CANCELLED
            $statusUpdated = $this->orderStatusService->updateStatus($order, 'CANCELLED', [
                'cancelled_by' => $user->id,
                'cancelled_reason' => 'Customer cancellation'
            ]);

            if (!$statusUpdated) {
                throw new \Exception('Cannot cancel order at this stage');
            }

            foreach ($order->items as $item) {
                $item->productVariant->increment('stock_quantity', $item->quantity);
            }

            // Clean up any stock reservations for this user and product variants in this order
            StockReservation::where('user_id', $order->user_id)
                ->whereIn('product_variant_id', $order->items()->pluck('product_variant_id'))
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reorder(Order $order)
    {
        $user = Auth::user();

        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        DB::beginTransaction();

        try {
            Cart::where('user_id', $user->id)->delete();

            foreach ($order->items as $item) {
                $productVariant = $item->productVariant;

                if ($productVariant->stock_quantity > 0) {
                    Cart::create([
                        'user_id' => $user->id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => min($item->quantity, $productVariant->stock_quantity),
                        'price' => $productVariant->price,
                    ]);
                }
            }

            DB::commit();

            $cartItems = Cart::getCartItems($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Items added to cart successfully',
                'data' => [
                    'cart_items' => $cartItems,
                    'cart_total' => $cartItems->sum('total')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stats()
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'PENDING')->count(),
            'paid' => Order::where('status', 'PAID')->count(),
            'processed' => Order::where('status', 'PROCESSED')->count(),
            'waiting_pickup' => Order::where('status', 'WAITING_FOR_PICKUP')->count(),
            'picked_up' => Order::where('status', 'PICKED_UP')->count(),
            'on_delivery' => Order::where('status', 'ON_DELIVERY')->count(),
            'done' => Order::where('status', 'DONE')->count(),
            'cancelled' => Order::where('status', 'CANCELLED')->count(),
            'total_revenue' => Order::whereIn('status', ['DONE', 'PAID', 'PROCESSED', 'WAITING_FOR_PICKUP', 'PICKED_UP', 'ON_DELIVERY'])
                ->sum('total_aud'),
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'this_month_orders' => Order::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        // Get all orders with relationships for admin
        $orders = Order::with(['items.productVariant.product', 'address', 'user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                $order->status_info = $this->orderStatusService->getStatusInfo($order->status, $order->fulfillment_method);
                $order->admin_action = $this->orderStatusService->getAdminAction($order);
                return $order;
            });

        return response()->json([
            'success' => true,
            'data' => $stats,
            'orders' => $orders
        ]);
    }

    public function dashboard()
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $totalRevenue = Order::whereIn('status', ['PAID', 'PROCESSED', 'WAITING_PICKUP', 'ON_DELIVERY', 'DONE'])
            ->sum('total_aud');
        $totalOrders = Order::count();
        $activeCustomers = User::where('role', 'CUSTOMER')
            ->where('is_active', true)
            ->count();
        $productsSold = OrderItem::sum('quantity');

        $metrics = [
            'total_revenue' => number_format($totalRevenue, 2),
            'total_orders' => $totalOrders,
            'active_customers' => $activeCustomers,
            'products_sold' => $productsSold
        ];

        return response()->json([
            'success' => true,
            'metrics' => $metrics
        ]);
    }

    public function recentOrders()
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $orders = Order::with(['user', 'items.productVariant.product', 'address'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                $order->status_info = $this->orderStatusService->getStatusInfo($order->status, $order->fulfillment_method);
                $order->admin_action = $this->orderStatusService->getAdminAction($order);
                return $order;
            });

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    public function topProducts()
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $topProducts = OrderItem::select('product_variant_id')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->with(['productVariant.product'])
            ->groupBy('product_variant_id')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->productVariant->product->name,
                    'variant' => $item->productVariant->name,
                    'quantity' => $item->total_quantity
                ];
            });

        return response()->json([
            'success' => true,
            'products' => $topProducts
        ]);
    }

    public function confirmPayment(Request $request, Order $order)
    {
        $user = Auth::user();

        // Ensure user can only confirm their own orders
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Only allow confirmation for pending orders
        if ($order->status !== 'PENDING') {
            return response()->json([
                'success' => false,
                'message' => 'Order is not in a state that can be confirmed'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'payment_method_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Use OrderStatusService to update to PAID
            $statusUpdated = $this->orderStatusService->updateStatus($order, 'PAID', [
                'payment_intent_id' => $request->payment_intent_id,
                'payment_method_id' => $request->payment_method_id
            ]);

            if (!$statusUpdated) {
                throw new \Exception('Cannot confirm payment for this order');
            }

            // Update order with payment details
            $order->update([
                'payment_method' => 'CARD', // Since we're using Stripe
                'stripe_payment_intent_id' => $request->payment_intent_id,
                'paid_at' => now()
            ]);

            DB::commit();

            // Load order with relationships
            $order->load(['items.productVariant.product.photos', 'address', 'events', 'user']);

            // Broadcast real-time payment confirmation event
            broadcast(new OrderUpdated($order, 'status_changed', 'PENDING', 'PAID'))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to confirm payment', [
                'order_id' => $order->id,
                'payment_intent_id' => $request->payment_intent_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time order updates for admin dashboard
     */
    public function realtimeUpdates(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $since = $request->get('since');
            $query = Order::with(['items.productVariant.product', 'address', 'user'])
                ->orderBy('updated_at', 'desc');

            // Filter orders updated since given timestamp
            if ($since) {
                $query->where('updated_at', '>', $since);
            } else {
                // If no timestamp provided, get recent orders from last 10 minutes
                $query->where('updated_at', '>', now()->subMinutes(10));
            }

            $updatedOrders = $query->get()->map(function ($order) {
                $order->status_info = $this->orderStatusService->getStatusInfo($order->status, $order->fulfillment_method);
                $order->admin_action = $this->orderStatusService->getAdminAction($order);
                return $order;
            });

            return response()->json([
                'success' => true,
                'data' => $updatedOrders,
                'timestamp' => now()->toISOString(),
                'count' => $updatedOrders->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Real-time orders update failed', [
                'error' => $e->getMessage(),
                'since' => $since ?? 'null'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get real-time updates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export orders data
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $orders = Order::with(['items.productVariant.product', 'address', 'user'])
                ->orderBy('created_at', 'desc')
                ->get();

            $csvData = [];
            $csvData[] = [
                'Order Number',
                'Customer Name',
                'Customer Email',
                'Status',
                'Fulfillment Method',
                'Total AUD',
                'Created At',
                'Updated At',
                'Address',
                'Items'
            ];

            foreach ($orders as $order) {
                $items = $order->items->map(function($item) {
                    return $item->productVariant->product->name . ' (' . $item->quantity . 'x)';
                })->join(', ');

                $csvData[] = [
                    $order->order_number,
                    $order->user->first_name . ' ' . $order->user->last_name,
                    $order->user->email,
                    $order->status,
                    $order->fulfillment_method,
                    number_format($order->total_aud, 2),
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->updated_at->format('Y-m-d H:i:s'),
                    $order->address ? $order->address->full_address : 'N/A',
                    $items
                ];
            }

            $filename = 'orders_export_' . now()->format('Y_m_d_H_i_s') . '.csv';

            $handle = fopen('php://temp', 'r+');
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            \Log::error('Order export failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export orders: ' . $e->getMessage()
            ], 500);
        }
    }
}