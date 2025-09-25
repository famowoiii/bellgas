<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus
    ) {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusMessages = [
            'PENDING_PAYMENT' => 'Your order is waiting for payment.',
            'PAID' => 'Your order has been paid and is being processed.',
            'PROCESSING' => 'Your order is being prepared.',
            'READY_FOR_PICKUP' => 'Your order is ready for pickup!',
            'COMPLETED' => 'Your order has been completed. Thank you!',
            'CANCELLED' => 'Your order has been cancelled.',
            'REFUNDED' => 'Your order has been refunded.',
        ];

        $message = new MailMessage();
        $message->subject('Order Status Update - ' . $this->order->order_number)
                ->greeting('Hi ' . $notifiable->first_name . ',')
                ->line('Your order status has been updated.')
                ->line('Order Number: ' . $this->order->order_number)
                ->line('Previous Status: ' . str_replace('_', ' ', $this->oldStatus))
                ->line('Current Status: ' . str_replace('_', ' ', $this->newStatus));

        if (isset($statusMessages[$this->newStatus])) {
            $message->line($statusMessages[$this->newStatus]);
        }

        $message->action('View Order Details', url('/orders/' . $this->order->id))
                ->line('Thank you for choosing us!');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }
}