<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $action; // 'created', 'updated', 'status_changed'
    public $oldStatus;
    public $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, string $action = 'updated', ?string $oldStatus = null, ?string $newStatus = null)
    {
        $this->order = $order->load(['items.productVariant.product', 'address', 'user']);
        $this->action = $action;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Public channel for admin dashboard (all admins)
            new Channel('admin-orders'),

            // Private channel for the customer who owns this order
            new PrivateChannel('user.' . $this->order->user_id . '.orders'),

            // Public channel for general order updates (for stats)
            new Channel('public-admin-orders')
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.' . $this->action;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status,
                'total_aud' => $this->order->total_aud,
                'fulfillment_method' => $this->order->fulfillment_method,
                'created_at' => $this->order->created_at,
                'updated_at' => $this->order->updated_at,
                'customer_name' => $this->order->user->first_name . ' ' . $this->order->user->last_name,
                'customer_email' => $this->order->user->email,
                'items_count' => $this->order->items->count(),
                'items' => $this->order->items->map(function($item) {
                    return [
                        'product_name' => $item->productVariant->product->name,
                        'variant_name' => $item->productVariant->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price_aud
                    ];
                })
            ],
            'action' => $this->action,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'timestamp' => now()->toISOString(),
            'message' => $this->getNotificationMessage()
        ];
    }

    /**
     * Get user-friendly notification message
     */
    private function getNotificationMessage(): string
    {
        switch ($this->action) {
            case 'created':
                return "New order #{$this->order->order_number} received from {$this->order->user->first_name}";
            case 'status_changed':
                return "Order #{$this->order->order_number} status changed to {$this->newStatus}";
            case 'updated':
            default:
                return "Order #{$this->order->order_number} has been updated";
        }
    }
}