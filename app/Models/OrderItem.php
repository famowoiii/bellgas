<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'order_id',
        'product_variant_id',
        'quantity',
        'unit_price_aud',
        'total_price_aud',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_aud' => 'decimal:2',
            'total_price_aud' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
