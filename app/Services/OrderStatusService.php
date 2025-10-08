<?php

namespace App\Services;

use App\Models\Order;
use App\Events\OrderStatusUpdated;
use Illuminate\Support\Facades\Log;

class OrderStatusService
{
    /**
     * Get available next statuses for an order based on current status and fulfillment method
     */
    public function getAvailableStatuses(Order $order): array
    {
        $currentStatus = $order->status;
        $fulfillmentMethod = $order->fulfillment_method;
        $hasFullTankItems = $this->hasFullTankItems($order);

        switch ($currentStatus) {
            case 'PENDING':
                return ['PAID', 'CANCELLED'];

            case 'PAID':
                return ['PROCESSED', 'CANCELLED'];

            case 'PROCESSED':
                // Different flow for pickup vs delivery
                if ($fulfillmentMethod === 'PICKUP') {
                    return ['WAITING_FOR_PICKUP', 'CANCELLED'];
                } else { // DELIVERY
                    return ['ON_DELIVERY', 'CANCELLED'];
                }

            case 'WAITING_FOR_PICKUP':
                // For pickup orders: WAITING_FOR_PICKUP -> PICKED_UP -> DONE
                return ['PICKED_UP', 'CANCELLED'];

            case 'PICKED_UP':
                // For pickup orders: PICKED_UP -> DONE
                return ['DONE'];

            case 'ON_DELIVERY':
                // For delivery orders: ON_DELIVERY -> DONE
                return ['DONE', 'CANCELLED'];

            case 'DONE':
            case 'CANCELLED':
                return []; // No transitions possible from terminal states

            default:
                return [];
        }
    }

    /**
     * Update order status with validation and business logic
     */
    public function updateStatus(Order $order, string $newStatus, array $metadata = []): bool
    {
        $availableStatuses = $this->getAvailableStatuses($order);

        if (!in_array($newStatus, $availableStatuses)) {
            Log::warning('Invalid status transition attempted', [
                'order_id' => $order->id,
                'current_status' => $order->status,
                'attempted_status' => $newStatus,
                'available_statuses' => $availableStatuses
            ]);
            return false;
        }

        $previousStatus = $order->status;
        $order->status = $newStatus;

        // Set timestamps based on status
        switch ($newStatus) {
            case 'WAITING_FOR_PICKUP':
                $order->pickup_ready_at = now();
                break;

            case 'PICKED_UP':
                $order->picked_up_at = now();
                break;

            case 'ON_DELIVERY':
                // Could add delivery_started_at timestamp if needed
                break;

            case 'DONE':
                $order->completed_at = now();
                if ($order->fulfillment_method === 'DELIVERY') {
                    $order->delivered_at = now();
                }
                break;
        }

        $saved = $order->save();

        if ($saved) {
            // Broadcasting disabled to prevent Pusher connection errors
            // Real-time updates will be handled via polling instead

            Log::info('Order status updated successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'fulfillment_method' => $order->fulfillment_method,
                'metadata' => $metadata
            ]);
        }

        return $saved;
    }

    /**
     * Check if order has any full tank items
     */
    private function hasFullTankItems(Order $order): bool
    {
        // Cache this check since it might be called multiple times for the same order
        static $cache = [];
        $cacheKey = 'full_tank_' . $order->id;

        if (!isset($cache[$cacheKey])) {
            $cache[$cacheKey] = $order->items()
                ->whereHas('productVariant.product', function ($query) {
                    $query->where('category', 'FULL_TANK');
                })
                ->exists();
        }

        return $cache[$cacheKey];
    }

    /**
     * Get status display information
     */
    public function getStatusInfo(string $status, string $fulfillmentMethod = null): array
    {
        $statusMap = [
            'PENDING' => [
                'label' => 'Pending Payment',
                'description' => 'Awaiting customer payment',
                'color' => 'red',
                'icon' => 'fas fa-clock'
            ],
            'PAID' => [
                'label' => 'Payment Confirmed',
                'description' => 'Payment received, awaiting processing',
                'color' => 'blue',
                'icon' => 'fas fa-credit-card'
            ],
            'PROCESSED' => [
                'label' => 'Order Confirmed',
                'description' => 'Order confirmed and being prepared',
                'color' => 'yellow',
                'icon' => 'fas fa-cog'
            ],
            'WAITING_FOR_PICKUP' => [
                'label' => 'Ready for Pickup',
                'description' => 'Order ready for customer pickup at store',
                'color' => 'purple',
                'icon' => 'fas fa-box'
            ],
            'PICKED_UP' => [
                'label' => 'Picked Up',
                'description' => 'Customer has picked up the order',
                'color' => 'indigo',
                'icon' => 'fas fa-hand-holding'
            ],
            'ON_DELIVERY' => [
                'label' => 'Out for Delivery',
                'description' => 'Order is being delivered to customer',
                'color' => 'orange',
                'icon' => 'fas fa-truck'
            ],
            'DONE' => [
                'label' => 'Completed',
                'description' => $fulfillmentMethod === 'PICKUP'
                    ? 'Order completed - customer pickup confirmed'
                    : 'Order completed - delivery confirmed',
                'color' => 'green',
                'icon' => 'fas fa-check-circle'
            ],
            'CANCELLED' => [
                'label' => 'Cancelled',
                'description' => 'Order has been cancelled',
                'color' => 'gray',
                'icon' => 'fas fa-times-circle'
            ]
        ];

        return $statusMap[$status] ?? [
            'label' => $status,
            'description' => 'Unknown status',
            'color' => 'gray',
            'icon' => 'fas fa-question'
        ];
    }

    /**
     * Get suggested next action for admin
     */
    public function getAdminAction(Order $order): ?array
    {
        $status = $order->status;
        $fulfillmentMethod = $order->fulfillment_method;

        switch ($status) {
            case 'PAID':
                return [
                    'action' => 'process',
                    'label' => 'Process Order',
                    'description' => 'Confirm order and start preparation',
                    'next_status' => 'PROCESSED'
                ];

            case 'PROCESSED':
                if ($fulfillmentMethod === 'PICKUP') {
                    return [
                        'action' => 'ready_pickup',
                        'label' => 'Mark Ready for Pickup',
                        'description' => 'Order is ready for customer pickup',
                        'next_status' => 'WAITING_FOR_PICKUP'
                    ];
                } else {
                    return [
                        'action' => 'start_delivery',
                        'label' => 'Start Delivery',
                        'description' => 'Send order out for delivery',
                        'next_status' => 'ON_DELIVERY'
                    ];
                }

            case 'WAITING_FOR_PICKUP':
                return [
                    'action' => 'mark_picked_up',
                    'label' => 'Mark as Picked Up',
                    'description' => 'Customer has picked up the order',
                    'next_status' => 'PICKED_UP'
                ];

            case 'PICKED_UP':
                return [
                    'action' => 'complete_order',
                    'label' => 'Complete Order',
                    'description' => 'Finalize the pickup order',
                    'next_status' => 'DONE'
                ];

            case 'ON_DELIVERY':
                return [
                    'action' => 'confirm_delivery',
                    'label' => 'Confirm Delivery',
                    'description' => 'Order has been delivered to customer',
                    'next_status' => 'DONE'
                ];

            default:
                return null;
        }
    }

    /**
     * Get customer-facing status message
     */
    public function getCustomerMessage(Order $order): string
    {
        $status = $order->status;
        $fulfillmentMethod = $order->fulfillment_method;
        $orderNumber = $order->order_number;

        switch ($status) {
            case 'PENDING':
                return "Your order {$orderNumber} is awaiting payment.";

            case 'PAID':
                return "Payment confirmed for order {$orderNumber}. We're preparing your order.";

            case 'PROCESSED':
                if ($fulfillmentMethod === 'PICKUP') {
                    return "Your order {$orderNumber} is being prepared. We'll notify you when it's ready for pickup.";
                } else {
                    return "Your order {$orderNumber} is being prepared for delivery.";
                }

            case 'WAITING_FOR_PICKUP':
                return "Your order {$orderNumber} is ready for pickup at our store! Please bring your ID.";

            case 'PICKED_UP':
                return "Your order {$orderNumber} has been picked up successfully. We're finalizing your order.";

            case 'ON_DELIVERY':
                return "Your order {$orderNumber} is out for delivery. You should receive it shortly.";

            case 'DONE':
                if ($fulfillmentMethod === 'PICKUP') {
                    return "Thank you! Your order {$orderNumber} has been completed.";
                } else {
                    return "Your order {$orderNumber} has been delivered. Thank you for your business!";
                }

            case 'CANCELLED':
                return "Your order {$orderNumber} has been cancelled.";

            default:
                return "Your order {$orderNumber} status has been updated.";
        }
    }

    /**
     * Validate if order can use specific fulfillment method
     */
    public function canUseFulfillmentMethod(Order $order, string $method): array
    {
        $hasRefillItems = $order->items()
            ->whereHas('productVariant.product', function ($query) {
                $query->where('category', 'REFILL');
            })
            ->exists();

        $hasFullTankItems = $this->hasFullTankItems($order);

        // REFILL items can only be picked up (customer brings their own cylinder)
        if ($hasRefillItems && $method === 'DELIVERY') {
            return [
                'allowed' => false,
                'reason' => 'Refill orders must be picked up at the store as customer needs to bring their own cylinder.'
            ];
        }

        // FULL_TANK items can be either pickup or delivery
        if ($hasFullTankItems) {
            return [
                'allowed' => true,
                'reason' => 'Full tank orders support both pickup and delivery.'
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'Standard fulfillment rules apply.'
        ];
    }
}