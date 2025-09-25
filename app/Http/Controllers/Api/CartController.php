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
        $sessionId = null; // For API with JWT, we don't use session
        
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
        
        // Use smart cart method
        $result = Cart::addItemToCart(
            $userId,
            $request->product_variant_id,
            $request->quantity,
            $request->boolean('is_preorder', false),
            $request->notes
        );

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
        $sessionId = null; // API uses JWT authentication, no session needed

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

        if ($productVariant->available_stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient stock. Available: {$productVariant->available_stock}"
            ], 400);
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
        $sessionId = null; // API uses JWT authentication, no session needed

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
        $sessionId = null; // API uses JWT authentication, no session needed

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
        $sessionId = null; // API uses JWT authentication, no session needed
        
        $count = Cart::getCartItems($userId, $sessionId)->count();
        
        return response()->json([
            'success' => true,
            'data' => ['count' => $count]
        ]);
    }
}
