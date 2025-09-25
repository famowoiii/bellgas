<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController_v2 extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['variants', 'photos', 'category'])
            ->where('is_active', true);

        // Filter by product type (REFILL/FULL_TANK)
        if ($request->has('type')) {
            $query->where('category', $request->type);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(15);

        return response()->json([
            'message' => 'Products retrieved successfully',
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        if (!$product->is_active) {
            return response()->json([
                'message' => 'Product not found or not available'
            ], 404);
        }

        $product->load(['variants' => function($query) {
            $query->where('is_active', true);
        }, 'photos', 'category']);

        return response()->json([
            'message' => 'Product retrieved successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'category' => $product->category, // Product type: REFILL/FULL_TANK
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name,
                'is_active' => $product->is_active,
                'image_url' => $product->image_url,
                'can_be_delivered' => $product->canBeDelivered(),
                'is_refill' => $product->isRefill(),
                'is_full_tank' => $product->isFullTank(),
                'created_at' => $product->created_at,
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'weight_kg' => $variant->weight_kg,
                        'price_aud' => $variant->price_aud,
                        'stock_quantity' => $variant->stock_quantity,
                        'available_stock' => $variant->available_stock,
                        'is_active' => $variant->is_active,
                    ];
                }),
                'photos' => $product->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => $photo->url,
                        'alt_text' => $photo->alt_text,
                        'is_primary' => $photo->is_primary,
                        'sort_order' => $photo->sort_order,
                    ];
                }),
            ],
        ]);
    }

    // For merchants/admins only - create product
    public function store(Request $request): JsonResponse
    {
        // Check if user is merchant or admin
        if (!in_array(auth()->user()->role, ['MERCHANT', 'ADMIN'])) {
            return response()->json([
                'message' => 'Unauthorized. Only merchants and admins can create products.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:REFILL,FULL_TANK',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'is_active' => 'boolean|integer|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/products'), $imageName);
            $imagePath = '/uploads/products/' . $imageName;
        }

        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'], // REFILL or FULL_TANK
            'category_id' => $validated['category_id'],
            'is_active' => isset($validated['is_active']) ? (bool)$validated['is_active'] : true,
            'image_url' => $imagePath,
        ]);

        // Create default variant
        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Standard',
            'price_aud' => $validated['price'],
            'stock_quantity' => $validated['stock'],
            'weight_kg' => $validated['weight'] ?? 0,
            'is_active' => true,
        ]);

        // Load the product with relationships for response
        $product->load(['variants', 'category']);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    // Update product
    public function update(Request $request, $productId): JsonResponse
    {
        // Check if user is merchant or admin
        if (!in_array(auth()->user()->role, ['MERCHANT', 'ADMIN'])) {
            return response()->json([
                'message' => 'Unauthorized. Only merchants and admins can update products.'
            ], 403);
        }

        // Find the product or return 404
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'message' => 'Product not found.'
            ], 404);
        }

        \Log::info('Product update request data', $request->all());

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'required|string|in:REFILL,FULL_TANK',
                'category_id' => 'required|exists:categories,id',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'weight' => 'nullable|numeric|min:0',
                'is_active' => 'boolean|integer|in:0,1',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Product update validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Handle image upload
        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'category_id' => $validated['category_id'],
            'is_active' => isset($validated['is_active']) ? (bool)$validated['is_active'] : $product->is_active,
        ];

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image_url && file_exists(public_path($product->image_url))) {
                unlink(public_path($product->image_url));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/products'), $imageName);
            $updateData['image_url'] = '/uploads/products/' . $imageName;
        }

        $product->update($updateData);

        // Update the first (default) variant
        $variant = $product->variants()->first();
        if ($variant) {
            $variant->update([
                'price_aud' => $validated['price'],
                'stock_quantity' => $validated['stock'],
                'weight_kg' => $validated['weight'] ?? $variant->weight_kg,
            ]);
        }

        // Load the product with relationships for response
        $product->load(['variants', 'category']);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    // Get admin products with all data
    public function adminIndex(Request $request): JsonResponse
    {
        // Check if user is merchant or admin
        if (!in_array(auth()->user()->role, ['MERCHANT', 'ADMIN'])) {
            return response()->json([
                'message' => 'Unauthorized. Only merchants and admins can view all products.'
            ], 403);
        }

        $query = Product::with(['variants', 'photos', 'category']);

        // Filter by product type (REFILL/FULL_TANK)
        if ($request->has('type')) {
            $query->where('category', $request->type);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        // Transform the products for admin view
        $transformedProducts = $products->getCollection()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'category' => $product->category, // Product type
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name,
                'is_active' => $product->is_active,
                'image_url' => $product->image_url,
                'can_be_delivered' => $product->canBeDelivered(),
                'is_refill' => $product->isRefill(),
                'is_full_tank' => $product->isFullTank(),
                'created_at' => $product->created_at,
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'weight_kg' => $variant->weight_kg,
                        'price_aud' => $variant->price_aud,
                        'stock_quantity' => $variant->stock_quantity,
                        'available_stock' => $variant->available_stock,
                        'is_active' => $variant->is_active,
                    ];
                }),
                'total_stock' => $product->variants->sum('stock_quantity'),
                'primary_variant' => $product->variants->first(),
            ];
        });

        $products->setCollection($transformedProducts);

        return response()->json([
            'message' => 'Admin products retrieved successfully',
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    // Delete product
    public function destroy($productId): JsonResponse
    {
        // Check if user is merchant or admin
        if (!in_array(auth()->user()->role, ['MERCHANT', 'ADMIN'])) {
            return response()->json([
                'message' => 'Unauthorized. Only merchants and admins can delete products.'
            ], 403);
        }

        // Find the product or return 404
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'message' => 'Product not found.'
            ], 404);
        }

        // Check if product has any orders
        $hasOrders = $product->variants()
            ->whereHas('orderItems')
            ->exists();

        if ($hasOrders) {
            return response()->json([
                'message' => 'Cannot delete product that has been ordered. You can deactivate it instead.'
            ], 400);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}