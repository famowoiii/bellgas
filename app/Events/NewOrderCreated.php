<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_id' => $this->order->user_id,
            'customer_name' => $this->order->user->first_name . ' ' . $this->order->user->last_name,
            'customer_email' => $this->order->user->email,
            'status' => $this->order->status,
            'total_amount' => $this->order->total_aud,
            'fulfillment_method' => $this->order->fulfillment_method,
            'created_at' => $this->order->created_at->toISOString(),
            'items_count' => $this->order->items()->count(),
            'message' => "New order {$this->order->order_number} received from {$this->order->user->first_name} {$this->order->user->last_name}",
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Private channel untuk admin (menerima notifikasi pesanan baru)
            new PrivateChannel('admin-orders'),
            // Public channel untuk admin yang selalu dapat diakses
            new Channel('public-admin-orders'),
            // Channel untuk customer (konfirmasi order mereka)
            new PrivateChannel('user.' . $this->order->user_id . '.orders'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }
}
