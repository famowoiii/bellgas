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

class PaymentCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $paymentMethod;
    public $amount;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, string $paymentMethod, float $amount)
    {
        $this->order = $order;
        $this->paymentMethod = $paymentMethod;
        $this->amount = $amount;
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
            'payment_method' => $this->paymentMethod,
            'amount_paid' => $this->amount,
            'currency' => 'AUD',
            'status' => $this->order->status,
            'paid_at' => now()->toISOString(),
            'message' => "Payment of $" . number_format($this->amount, 2) . " completed for order {$this->order->order_number}",
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
            // Channel untuk admin (notifikasi pembayaran masuk)
            new Channel('admin-payments'),
            new Channel('admin-orders'),
            // Channel untuk customer (konfirmasi pembayaran)
            new PrivateChannel('user.' . $this->order->user_id . '.payments'),
            new PrivateChannel('user.' . $this->order->user_id . '.orders'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'payment.completed';
    }
}
