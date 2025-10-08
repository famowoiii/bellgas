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

class NewPaidOrderEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-notifications'),
            new PrivateChannel('orders-updates'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'new-paid-order';
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
                'total_aud' => $this->order->total_aud,
                'status' => $this->order->status,
                'fulfillment_method' => $this->order->fulfillment_method,
                'customer_name' => $this->order->customer_name,
                'customer_email' => $this->order->customer_email,
                'customer_phone' => $this->order->customer_phone,
                'customer_notes' => $this->order->customer_notes,
                'created_at' => $this->order->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $this->order->updated_at->format('Y-m-d H:i:s'),
                'items_count' => $this->order->items->count(),
                'items' => $this->order->items->map(function ($item) {
                    return [
                        'quantity' => $item->quantity,
                        'product_name' => $item->productVariant->product->name,
                        'variant_name' => $item->productVariant->name,
                        'unit_price_aud' => $item->unit_price_aud,
                        'total_price_aud' => $item->total_price_aud,
                    ];
                }),
                'address' => $this->order->address ? [
                    'name' => $this->order->address->name,
                    'full_address' => $this->order->address->full_address,
                    'delivery_instructions' => $this->order->address->delivery_instructions,
                ] : null,
            ],
            'notification' => [
                'type' => 'success',
                'title' => 'New Paid Order!',
                'message' => "Order #{$this->order->order_number} has been paid successfully",
                'timestamp' => now()->toISOString(),
            ]
        ];
    }
}