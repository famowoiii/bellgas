<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_event_id',
        'stripe_payment_intent_id',
        'event_type',
        'stripe_data',
        'processed',
    ];

    protected function casts(): array
    {
        return [
            'stripe_data' => 'array',
            'processed' => 'boolean',
        ];
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    public function markAsProcessed(): void
    {
        $this->update(['processed' => true]);
    }
}
