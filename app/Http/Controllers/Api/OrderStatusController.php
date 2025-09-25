<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderStatusController extends Controller
{
    protected $orderStatusService;

    public function __construct(OrderStatusService $orderStatusService)
    {
        $this->orderStatusService = $orderStatusService;
    }

    /**
     * Get available status transitions for an order
     */
    public function getAvailableStatuses(Order $order)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $availableStatuses = $this->orderStatusService->getAvailableStatuses($order);
        $currentStatusInfo = $this->orderStatusService->getStatusInfo($order->status, $order->fulfillment_method);
        $adminAction = $this->orderStatusService->getAdminAction($order);

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'current_status' => $order->status,
                'current_status_info' => $currentStatusInfo,
                'available_statuses' => $availableStatuses,
                'suggested_action' => $adminAction,
                'fulfillment_method' => $order->fulfillment_method
            ]
        ]);
    }

    /**
     * Get detailed status information for an order
     */
    public function getStatusInfo(Order $order)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $statusInfo = $this->orderStatusService->getStatusInfo($order->status, $order->fulfillment_method);
        $customerMessage = $this->orderStatusService->getCustomerMessage($order);
        $adminAction = $this->orderStatusService->getAdminAction($order);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'fulfillment_method' => $order->fulfillment_method,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at
                ],
                'status_info' => $statusInfo,
                'customer_message' => $customerMessage,
                'admin_action' => $adminAction,
                'timestamps' => [
                    'pickup_ready_at' => $order->pickup_ready_at,
                    'completed_at' => $order->completed_at,
                    'delivered_at' => $order->delivered_at,
                    'paid_at' => $order->paid_at
                ]
            ]
        ]);
    }

    /**
     * Update order status with validation
     */
    public function updateStatus(Request $request, Order $order)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:PENDING,PAID,PROCESSED,WAITING_PICKUP,PICKED_UP,ON_DELIVERY,DONE,CANCELLED',
            'notes' => 'nullable|string|max:1000'
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

            // Check if this is a valid transition
            $availableStatuses = $this->orderStatusService->getAvailableStatuses($order);
            if (!in_array($newStatus, $availableStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status transition',
                    'current_status' => $oldStatus,
                    'attempted_status' => $newStatus,
                    'available_statuses' => $availableStatuses
                ], 400);
            }

            // Use OrderStatusService to update the status
            $statusUpdated = $this->orderStatusService->updateStatus($order, $newStatus, [
                'admin_notes' => $request->notes,
                'updated_by' => $user->id,
                'updated_by_name' => $user->name
            ]);

            if (!$statusUpdated) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update order status',
                    'available_statuses' => $availableStatuses
                ], 400);
            }

            // Update admin notes if provided
            if ($request->notes) {
                $order->update(['notes' => $request->notes]);
            }

            // Handle stock restoration for cancelled orders
            if ($newStatus === 'CANCELLED' && $oldStatus !== 'CANCELLED') {
                foreach ($order->items as $item) {
                    $item->productVariant->increment('stock_quantity', $item->quantity);
                }
            }

            DB::commit();

            // Reload order with fresh data
            $order->refresh();
            $order->load(['items.productVariant.product', 'user', 'address']);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => [
                    'order' => $order,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'status_info' => $this->orderStatusService->getStatusInfo($newStatus, $order->fulfillment_method),
                    'customer_message' => $this->orderStatusService->getCustomerMessage($order),
                    'available_statuses' => $this->orderStatusService->getAvailableStatuses($order),
                    'admin_action' => $this->orderStatusService->getAdminAction($order)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all possible status flows for reference
     */
    public function getStatusFlows()
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $pickupFlow = [
            'PENDING' => ['PAID', 'CANCELLED'],
            'PAID' => ['PROCESSED', 'CANCELLED'],
            'PROCESSED' => ['WAITING_PICKUP', 'CANCELLED'],
            'WAITING_PICKUP' => ['PICKED_UP', 'CANCELLED'],
            'PICKED_UP' => ['DONE'],
            'DONE' => [],
            'CANCELLED' => []
        ];

        $deliveryFlow = [
            'PENDING' => ['PAID', 'CANCELLED'],
            'PAID' => ['PROCESSED', 'CANCELLED'],
            'PROCESSED' => ['ON_DELIVERY', 'CANCELLED'],
            'ON_DELIVERY' => ['DONE', 'CANCELLED'],
            'DONE' => [],
            'CANCELLED' => []
        ];

        $statusDescriptions = [];
        foreach (['PENDING', 'PAID', 'PROCESSED', 'WAITING_PICKUP', 'PICKED_UP', 'ON_DELIVERY', 'DONE', 'CANCELLED'] as $status) {
            $statusDescriptions[$status] = [
                'pickup' => $this->orderStatusService->getStatusInfo($status, 'PICKUP'),
                'delivery' => $this->orderStatusService->getStatusInfo($status, 'DELIVERY')
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'flows' => [
                    'pickup' => $pickupFlow,
                    'delivery' => $deliveryFlow
                ],
                'status_descriptions' => $statusDescriptions,
                'fulfillment_methods' => ['PICKUP', 'DELIVERY']
            ]
        ]);
    }
}