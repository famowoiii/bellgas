<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
    /**
     * Simulate stock reduction (for testing purposes)
     */
    public function reduceStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $productVariant = ProductVariant::find($request->product_variant_id);
        
        $oldStock = $productVariant->stock_quantity;
        $newStock = max(0, $oldStock - $request->quantity);
        
        $productVariant->update(['stock_quantity' => $newStock]);

        return response()->json([
            'success' => true,
            'message' => 'Stock reduced successfully',
            'data' => [
                'product_variant_id' => $productVariant->id,
                'product_name' => $productVariant->product->name,
                'variant_name' => $productVariant->name,
                'old_stock' => $oldStock,
                'reduced_by' => $request->quantity,
                'new_stock' => $newStock
            ]
        ]);
    }

    /**
     * Simulate stock increase (for testing purposes)
     */
    public function increaseStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $productVariant = ProductVariant::find($request->product_variant_id);
        
        $oldStock = $productVariant->stock_quantity;
        $newStock = $oldStock + $request->quantity;
        
        $productVariant->update(['stock_quantity' => $newStock]);

        return response()->json([
            'success' => true,
            'message' => 'Stock increased successfully',
            'data' => [
                'product_variant_id' => $productVariant->id,
                'product_name' => $productVariant->product->name,
                'variant_name' => $productVariant->name,
                'old_stock' => $oldStock,
                'increased_by' => $request->quantity,
                'new_stock' => $newStock
            ]
        ]);
    }

    /**
     * Get current stock for a product variant
     */
    public function getStock($productVariantId)
    {
        $productVariant = ProductVariant::with('product')->find($productVariantId);
        
        if (!$productVariant) {
            return response()->json([
                'success' => false,
                'message' => 'Product variant not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'product_variant_id' => $productVariant->id,
                'product_name' => $productVariant->product->name,
                'variant_name' => $productVariant->name,
                'current_stock' => $productVariant->stock_quantity,
                'price_aud' => $productVariant->price_aud
            ]
        ]);
    }
}