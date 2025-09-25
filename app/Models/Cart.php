<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'product_variant_id',
        'quantity',
        'price',
        'is_preorder',
        'reserved_until',
        'notes',
        'original_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'is_preorder' => 'boolean',
        'reserved_until' => 'datetime',
        'original_price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->quantity * $this->price;
    }

    public static function getCartItems($userId = null, $sessionId = null)
    {
        $query = self::with(['productVariant.product.photos']);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }
        
        return $query->get();
    }

    public static function getCartTotal($userId = null, $sessionId = null): float
    {
        $items = self::getCartItems($userId, $sessionId);
        return $items->sum('total');
    }

    /**
     * Check if item is still available in stock
     */
    public function isStockAvailable(): bool
    {
        $availableStock = $this->productVariant->stock_quantity ?? 0;
        return $availableStock >= $this->quantity;
    }

    /**
     * Check if reservation has expired
     */
    public function isReservationExpired(): bool
    {
        if (!$this->reserved_until) {
            return false;
        }
        return now()->isAfter($this->reserved_until);
    }

    /**
     * Get the effective price (considering preorder discounts)
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->is_preorder && $this->original_price 
            ? $this->original_price 
            : $this->price;
    }

    /**
     * Clean expired reservations and out-of-stock items
     */
    public static function cleanExpiredItems($userId = null, $sessionId = null): int
    {
        $query = self::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        $removed = 0;
        
        // Get items with their stock info
        $items = $query->with('productVariant')->get();
        
        foreach ($items as $item) {
            $shouldRemove = false;
            
            // Remove if reservation expired
            if ($item->isReservationExpired()) {
                $shouldRemove = true;
            }
            
            // Remove if stock no longer available
            if (!$item->isStockAvailable()) {
                $shouldRemove = true;
            }
            
            if ($shouldRemove) {
                $item->delete();
                $removed++;
            }
        }
        
        return $removed;
    }

    /**
     * Add item to cart with stock validation
     */
    public static function addItemToCart($userId, $productVariantId, $quantity, $isPreorder = false, $notes = null): array
    {
        $productVariant = \App\Models\ProductVariant::find($productVariantId);
        
        if (!$productVariant) {
            return ['success' => false, 'message' => 'Product variant not found'];
        }

        // Clean expired items first across ALL users
        self::cleanExpiredItems();
        
        if (!$isPreorder) {
            // Calculate total reserved quantity across ALL active cart items
            $totalReservedByOthers = self::where('product_variant_id', $productVariantId)
                ->where('is_preorder', false)
                ->where('user_id', '!=', $userId)
                ->where(function($query) {
                    $query->whereNull('reserved_until')
                        ->orWhere('reserved_until', '>', now());
                })
                ->sum('quantity');

            // Check if item already exists in current user's cart
            $existingItem = self::where('user_id', $userId)
                ->where('product_variant_id', $productVariantId)
                ->where('is_preorder', $isPreorder)
                ->first();

            $currentUserQuantity = $existingItem ? $existingItem->quantity : 0;
            $newTotalQuantity = $currentUserQuantity + $quantity;
            $totalReservedGlobally = $totalReservedByOthers + $newTotalQuantity;

            // Check if total reserved exceeds available stock
            if ($totalReservedGlobally > $productVariant->stock_quantity) {
                $availableForUser = $productVariant->stock_quantity - $totalReservedByOthers;
                return [
                    'success' => false, 
                    'message' => "Insufficient stock. Only {$availableForUser} units available (others have reserved {$totalReservedByOthers})",
                    'available_stock' => $productVariant->stock_quantity,
                    'reserved_by_others' => $totalReservedByOthers,
                    'available_for_you' => max(0, $availableForUser),
                    'current_in_cart' => $currentUserQuantity
                ];
            }

            if ($existingItem) {
                // Update existing item
                $existingItem->update([
                    'quantity' => $newTotalQuantity,
                    'reserved_until' => now()->addMinutes(15),
                    'notes' => $notes
                ]);

                return ['success' => true, 'message' => 'Cart updated', 'item' => $existingItem];
            } else {
                // Create new cart item
                $cartItem = self::create([
                    'user_id' => $userId,
                    'product_variant_id' => $productVariantId,
                    'quantity' => $quantity,
                    'price' => $productVariant->price_aud,
                    'is_preorder' => false,
                    'reserved_until' => now()->addMinutes(15),
                    'notes' => $notes,
                    'original_price' => null
                ]);

                return ['success' => true, 'message' => 'Item added to cart', 'item' => $cartItem];
            }
        } else {
            // Preorder logic - no stock constraints
            $existingItem = self::where('user_id', $userId)
                ->where('product_variant_id', $productVariantId)
                ->where('is_preorder', $isPreorder)
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $quantity,
                    'reserved_until' => null,
                    'notes' => $notes
                ]);

                return ['success' => true, 'message' => 'Cart updated (preorder)', 'item' => $existingItem];
            } else {
                $cartItem = self::create([
                    'user_id' => $userId,
                    'product_variant_id' => $productVariantId,
                    'quantity' => $quantity,
                    'price' => $productVariant->price_aud,
                    'is_preorder' => true,
                    'reserved_until' => null,
                    'notes' => $notes,
                    'original_price' => $productVariant->price_aud
                ]);

                return ['success' => true, 'message' => 'Item added to cart (preorder)', 'item' => $cartItem];
            }
        }
    }
}
