<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'order_number',
        'user_id',
        'address_id',
        'status',
        'fulfillment_method',
        'payment_method',
        'subtotal_aud',
        'shipping_cost_aud',
        'total_aud',
        'stripe_payment_intent_id',
        'paid_at',
        'customer_notes',
        'pickup_ready_at',
        'picked_up_at',
        'delivered_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_aud' => 'decimal:2',
            'shipping_cost_aud' => 'decimal:2',
            'total_aud' => 'decimal:2',
            'paid_at' => 'datetime',
            'pickup_ready_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'BG-' . strtoupper(Str::random(8));
            }
        });

        // Model events disabled to prevent Pusher broadcast errors
        // All events are now handled via polling for real-time updates

        // static::updating(function ($order) {
        //     if ($order->isDirty('status') && $order->getOriginal('status') !== null) {
        //         $previousStatus = $order->getOriginal('status');
        //         $newStatus = $order->status;

        //         static::updated(function ($updatedOrder) use ($previousStatus, $newStatus) {
        //             event(new \App\Events\OrderStatusUpdated($updatedOrder, $previousStatus, $newStatus));

        //             if ($newStatus === 'PAID' && $previousStatus === 'PENDING') {
        //                 event(new \App\Events\NewOrderCreated($updatedOrder->load('user', 'items.productVariant.product')));
        //             }
        //         });
        //     }
        // });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class);
    }

    public function pickupToken(): HasOne
    {
        return $this->hasOne(PickupToken::class);
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }
    
    /**
     * Get the display address for the order
     * For pickup orders, show store address
     * For delivery orders, show customer address
     */
    public function getDisplayAddress(): string
    {
        if ($this->fulfillment_method === 'PICKUP') {
            return 'Pickup at Store - BellGas LPG Store, 123 Main Street, Melbourne VIC 3000';
        }
        
        if ($this->address) {
            return "{$this->address->street_address}, {$this->address->suburb} {$this->address->state} {$this->address->postcode}";
        }
        
        return 'Address not specified';
    }
    
    /**
     * Get formatted address for delivery or pickup location
     */
    public function getFormattedAddress(): array
    {
        if ($this->fulfillment_method === 'PICKUP') {
            return [
                'type' => 'pickup',
                'location' => 'BellGas LPG Store',
                'address' => '123 Main Street',
                'suburb' => 'Melbourne',
                'state' => 'VIC',
                'postcode' => '3000',
                'full_address' => 'BellGas LPG Store, 123 Main Street, Melbourne VIC 3000'
            ];
        }
        
        if ($this->address) {
            return [
                'type' => 'delivery',
                'location' => 'Customer Address',
                'address' => $this->address->street_address,
                'suburb' => $this->address->suburb,
                'state' => $this->address->state,
                'postcode' => $this->address->postcode,
                'full_address' => "{$this->address->street_address}, {$this->address->suburb} {$this->address->state} {$this->address->postcode}"
            ];
        }
        
        return [
            'type' => 'unknown',
            'location' => 'Address not specified',
            'full_address' => 'Address not specified'
        ];
    }
}
