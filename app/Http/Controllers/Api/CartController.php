<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $sessionId = $userId ? null : $request->session()->getId(); // Support guest sessions
        
        // Clean expired items first
        $removed = Cart::cleanExpiredItems($userId, $sessionId);
        
        $items = Cart::getCartItems($userId, $sessionId);
        $total = Cart::getCartTotal($userId, $sessionId);
        
        $response = [
            'success' => true,
            'data' => [
                'items' => $items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'effective_price' => $item->effective_price,
                        'is_preorder' => $item->is_preorder,
                        'reserved_until' => $item->reserved_until,
                        'notes' => $item->notes,
                        'total' => $item->total,
                        'stock_available' => $item->isStockAvailable(),
                        'reservation_expired' => $item->isReservationExpired(),
                        'productVariant' => $item->productVariant->load('product.photos')
                    ];
                }),
                'total' => $total,
                'count' => $items->count()
            ]
        ];
        
        if ($removed > 0) {
            $response['message'] = "$removed expired/out-of-stock items removed from cart";
        }
        
        return response()->json($response);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1|max:10',
            'is_preorder' => 'boolean',
            'notes' => 'string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();

        // If no user, we need to handle guest cart differently
        if (!$userId) {
            // For guests, we'll need to use session-based storage
            $sessionId = $request->session()->getId();
            $result = $this->addGuestItemToCart($sessionId, $request);
        } else {
            // Use smart cart method for authenticated users
            $result = Cart::addItemToCart(
                $userId,
                $request->product_variant_id,
                $request->quantity,
                $request->boolean('is_preorder', false),
                $request->notes
            );
        }

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'id' => $result['item']->id,
                'quantity' => $result['item']->quantity,
                'price' => $result['item']->price,
                'is_preorder' => $result['item']->is_preorder,
                'reserved_until' => $result['item']->reserved_until,
                'notes' => $result['item']->notes,
                'total' => $result['item']->total,
                'productVariant' => $result['item']->productVariant->load('product.photos')
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();
        $sessionId = $userId ? null : $request->session()->getId(); // Support guest sessions

        $cartItem = Cart::where('id', $id)
            ->where(function($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        $productVariant = $cartItem->productVariant;

        // Use the same smart stock validation logic as Cart model
        if (!$cartItem->is_preorder) {
            // Calculate total reserved quantity across ALL active cart items (excluding this item)
            $totalReservedByOthers = Cart::where('product_variant_id', $productVariant->id)
                ->where('is_preorder', false)
                ->where('id', '!=', $cartItem->id) // Exclude current item
                ->where(function($query) {
                    $query->whereNull('reserved_until')
                        ->orWhere('reserved_until', '>', now());
                })
                ->sum('quantity');

            $totalReservedGlobally = $totalReservedByOthers + $request->quantity;

            // Check if total reserved exceeds available stock
            if ($totalReservedGlobally > $productVariant->stock_quantity) {
                $availableForUser = $productVariant->stock_quantity - $totalReservedByOthers;
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock. Only {$availableForUser} units available (others have reserved {$totalReservedByOthers})",
                    'available_stock' => $productVariant->stock_quantity,
                    'reserved_by_others' => $totalReservedByOthers,
                    'available_for_you' => max(0, $availableForUser)
                ], 400);
            }
        }

        $cartItem->update([
            'quantity' => $request->quantity,
            'price' => $productVariant->price_aud
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated',
            'data' => $cartItem->load(['productVariant.product.photos'])
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $userId = Auth::id();
        $sessionId = $userId ? null : $request->session()->getId(); // Support guest sessions

        $cartItem = Cart::where('id', $id)
            ->where(function($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
    }

    public function clear(Request $request)
    {
        $userId = Auth::id();
        $sessionId = $userId ? null : $request->session()->getId(); // Support guest sessions

        $query = Cart::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }
        
        $query->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }

    public function count(Request $request)
    {
        $userId = Auth::id();
        $sessionId = $userId ? null : $request->session()->getId(); // Support guest sessions
        
        $count = Cart::getCartItems($userId, $sessionId)->count();
        
        return response()->json([
            'success' => true,
            'data' => ['count' => $count]
        ]);
    }

    /**
     * Handle guest cart addition
     */
    private function addGuestItemToCart($sessionId, $request)
    {
        $productVariant = ProductVariant::find($request->product_variant_id);

        if (!$productVariant) {
            return ['success' => false, 'message' => 'Product variant not found'];
        }

        // Clean expired items first
        Cart::cleanExpiredItems(null, $sessionId);

        if (!$request->boolean('is_preorder', false)) {
            // Calculate total reserved quantity across ALL active cart items
            $totalReservedByOthers = Cart::where('product_variant_id', $request->product_variant_id)
                ->where('is_preorder', false)
                ->where('session_id', '!=', $sessionId)
                ->where(function($query) {
                    $query->whereNull('reserved_until')
                        ->orWhere('reserved_until', '>', now());
                })
                ->sum('quantity');

            // Check if item already exists in current session's cart
            $existingItem = Cart::where('session_id', $sessionId)
                ->where('product_variant_id', $request->product_variant_id)
                ->where('is_preorder', false)
                ->first();

            $currentSessionQuantity = $existingItem ? $existingItem->quantity : 0;
            $newTotalQuantity = $currentSessionQuantity + $request->quantity;
            $totalReservedGlobally = $totalReservedByOthers + $newTotalQuantity;

            // Check if total reserved exceeds available stock
            if ($totalReservedGlobally > $productVariant->stock_quantity) {
                $availableForSession = $productVariant->stock_quantity - $totalReservedByOthers;
                return [
                    'success' => false,
                    'message' => "Insufficient stock. Only {$availableForSession} units available (others have reserved {$totalReservedByOthers})",
                    'available_stock' => $productVariant->stock_quantity,
                    'reserved_by_others' => $totalReservedByOthers,
                    'available_for_you' => max(0, $availableForSession),
                    'current_in_cart' => $currentSessionQuantity
                ];
            }

            if ($existingItem) {
                // Update existing item
                $existingItem->update([
                    'quantity' => $newTotalQuantity,
                    'reserved_until' => now()->addMinutes(15),
                    'notes' => $request->notes
                ]);

                return ['success' => true, 'message' => 'Cart updated', 'item' => $existingItem];
            } else {
                // Create new cart item
                $cartItem = Cart::create([
                    'user_id' => null,
                    'session_id' => $sessionId,
                    'product_variant_id' => $request->product_variant_id,
                    'quantity' => $request->quantity,
                    'price' => $productVariant->price_aud,
                    'is_preorder' => false,
                    'reserved_until' => now()->addMinutes(15),
                    'notes' => $request->notes,
                    'original_price' => null
                ]);

                return ['success' => true, 'message' => 'Item added to cart', 'item' => $cartItem];
            }
        } else {
            // Preorder logic for guests
            $existingItem = Cart::where('session_id', $sessionId)
                ->where('product_variant_id', $request->product_variant_id)
                ->where('is_preorder', true)
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $request->quantity,
                    'reserved_until' => null,
                    'notes' => $request->notes
                ]);

                return ['success' => true, 'message' => 'Cart updated (preorder)', 'item' => $existingItem];
            } else {
                $cartItem = Cart::create([
                    'user_id' => null,
                    'session_id' => $sessionId,
                    'product_variant_id' => $request->product_variant_id,
                    'quantity' => $request->quantity,
                    'price' => $productVariant->price_aud,
                    'is_preorder' => true,
                    'reserved_until' => null,
                    'notes' => $request->notes,
                    'original_price' => $productVariant->price_aud
                ]);

                return ['success' => true, 'message' => 'Item added to cart (preorder)', 'item' => $cartItem];
            }
        }
    }
}
