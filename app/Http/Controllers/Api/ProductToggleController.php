<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductToggleController extends Controller
{
    /**
     * Toggle product active status
     */
    public function toggleStatus($productId): JsonResponse
    {
        // Check if user is merchant or admin
        if (!in_array(auth()->user()->role, ['MERCHANT', 'ADMIN'])) {
            return response()->json([
                'message' => 'Unauthorized. Only merchants and admins can toggle products.'
            ], 403);
        }

        try {
            $product = Product::findOrFail($productId);

            // Toggle the status
            $product->is_active = !$product->is_active;
            $product->save();

            return response()->json([
                'message' => $product->is_active ? 'Product activated successfully' : 'Product deactivated successfully',
                'data' => [
                    'id' => $product->id,
                    'is_active' => $product->is_active,
                    'status' => $product->is_active ? 'active' : 'inactive'
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to toggle product status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}