<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmation - ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-confirmation',
            with: [
                'order' => $this->order,
                'user' => $this->order->user,
                'items' => $this->order->items->load('productVariant.product.photos'),
                'address' => $this->order->address,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
