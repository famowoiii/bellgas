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
        } else {
            // If no userId or sessionId, return empty collection to prevent loading all records
            return collect();
        }

        return $query->orderBy('created_at', 'desc')->limit(50)->get();
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
        } else {
            // If no specific user/session, limit cleanup scope to avoid timeout
            $query->where('updated_at', '<', now()->subHours(24));
        }

        // First, remove expired reservations directly with SQL for better performance
        $expiredCount = $query->where('reserved_until', '<', now())->delete();

        // Then handle stock validation with a reasonable limit
        $remainingQuery = clone $query;
        $items = $remainingQuery->with('productVariant')->limit(100)->get();

        $stockRemoved = 0;
        foreach ($items as $item) {
            if (!$item->isStockAvailable()) {
                $item->delete();
                $stockRemoved++;
            }
        }

        return $expiredCount + $stockRemoved;
    }

    /**
     * Add item to cart with stock validation
     */
    public static function addItemToCart($userId, $productVariantId, $quantity, $isPreorder = false, $notes = null, $sessionId = null): array
    {
        $productVariant = \App\Models\ProductVariant::find($productVariantId);
        
        if (!$productVariant) {
            return ['success' => false, 'message' => 'Product variant not found'];
        }

        // Clean expired items only for current user/session to avoid timeout
        self::cleanExpiredItems($userId, $sessionId);

        if (!$isPreorder) {
            // Calculate total reserved quantity across ALL active cart items (excluding current user/session)
            $query = self::where('product_variant_id', $productVariantId)
                ->where('is_preorder', false);

            if ($userId) {
                $query->where('user_id', '!=', $userId);
            } else if ($sessionId) {
                $query->where('session_id', '!=', $sessionId);
            }

            $totalReservedByOthers = $query->where(function($query) {
                    $query->whereNull('reserved_until')
                        ->orWhere('reserved_until', '>', now());
                })
                ->sum('quantity');

            // Check if item already exists in current user's/session's cart
            $existingItemQuery = self::where('product_variant_id', $productVariantId)
                ->where('is_preorder', $isPreorder);

            if ($userId) {
                $existingItemQuery->where('user_id', $userId);
            } else if ($sessionId) {
                $existingItemQuery->where('session_id', $sessionId);
            }

            $existingItem = $existingItemQuery->first();

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
                    'session_id' => $sessionId,
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
            $existingItemQuery = self::where('product_variant_id', $productVariantId)
                ->where('is_preorder', $isPreorder);

            if ($userId) {
                $existingItemQuery->where('user_id', $userId);
            } else if ($sessionId) {
                $existingItemQuery->where('session_id', $sessionId);
            }

            $existingItem = $existingItemQuery->first();

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
                    'session_id' => $sessionId,
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

    /**
     * Merge guest cart with user cart on login
     */
    public static function mergeGuestCartToUser($sessionId, $userId): array
    {
        if (!$sessionId || !$userId) {
            return ['success' => false, 'message' => 'Invalid session or user ID'];
        }

        $guestItems = self::where('session_id', $sessionId)
            ->where('user_id', null)
            ->get();

        if ($guestItems->isEmpty()) {
            return ['success' => true, 'message' => 'No guest cart items to merge', 'merged_count' => 0];
        }

        $mergedCount = 0;
        $errors = [];

        foreach ($guestItems as $guestItem) {
            try {
                $result = self::addItemToCart(
                    $userId,
                    $guestItem->product_variant_id,
                    $guestItem->quantity,
                    $guestItem->is_preorder,
                    $guestItem->notes,
                    null // No session ID needed for authenticated user
                );

                if ($result['success']) {
                    $guestItem->delete();
                    $mergedCount++;
                } else {
                    $errors[] = $result['message'];
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to merge item: " . $e->getMessage();
            }
        }

        return [
            'success' => true,
            'message' => "Merged {$mergedCount} items from guest cart",
            'merged_count' => $mergedCount,
            'errors' => $errors
        ];
    }
}
