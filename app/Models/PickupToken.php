<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PickupToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'pickup_code',
        'qr_code_url',
        'status',
        'expires_at',
        'verified_at',
        'verified_by',
        'verification_notes',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($token) {
            if (empty($token->pickup_code)) {
                $token->pickup_code = self::generateUniquePickupCode();
            }
            
            if (empty($token->expires_at)) {
                // Default: expire after 7 days
                $token->expires_at = now()->addDays(7);
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public static function generateUniquePickupCode(): string
    {
        do {
            $code = 'PK-' . strtoupper(Str::random(6));
        } while (self::where('pickup_code', $code)->exists());

        return $code;
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE' && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === 'EXPIRED';
    }

    public function markAsUsed(int $verifiedBy = null, string $notes = null): bool
    {
        return $this->update([
            'status' => 'USED',
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
            'verification_notes' => $notes,
        ]);
    }

    public function generateQrCode(): string
    {
        $qrData = json_encode([
            'pickup_code' => $this->pickup_code,
            'order_number' => $this->order->order_number,
            'expires_at' => $this->expires_at->toISOString()
        ]);

        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrData);
        $this->update(['qr_code_url' => $qrUrl]);
        
        return $qrUrl;
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'ACTIVE' => $this->isExpired() ? 'Expired' : 'Active',
            'USED' => 'Used',
            'EXPIRED' => 'Expired',
            default => 'Unknown'
        };
    }
}
