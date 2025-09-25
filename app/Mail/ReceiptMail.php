<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $receipt;
    public string $orderNumber;

    public function __construct(array $receipt)
    {
        $this->receipt = $receipt;
        $this->orderNumber = $receipt['receipt_info']['order_number'] ?? 'Unknown';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your BellGas Receipt - Order ' . $this->orderNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.receipt',
            with: [
                'receipt' => $this->receipt,
                'orderNumber' => $this->orderNumber
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}