<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // For admin/merchant, show all products; for customers, show only active
        $showAll = auth()->check() && in_array(auth()->user()->role, ['ADMIN', 'MERCHANT']);

        // Build cache key based on request parameters
        $cacheKey = 'products_' . ($showAll ? 'all' : 'active') .
                    '_cat_' . ($request->get('category') ?? 'all') .
                    '_search_' . ($request->get('search') ?? 'none') .
                    '_page_' . $request->get('page', 1);

        // Cache products for 5 minutes (300 seconds)
        $products = Cache::remember($cacheKey, 300, function() use ($request, $showAll) {
            $query = Product::select('id', 'name', 'slug', 'description', 'weight_kg', 'category', 'is_active', 'created_at', 'updated_at')
                ->with([
                    'variants:id,product_id,name,weight_kg,price_aud,stock_quantity,is_active',
                    'photos:id,product_id,filename',
                    'category:id,name,slug'
                ]);

            // Only filter active products for non-admin users
            if (!$showAll) {
                $query->where('is_active', true);
            }

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Search by name
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            return $query->paginate(50); // Increased limit for admin
        });

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

        // Cache individual product for 10 minutes
        $cachedProduct = Cache::remember('product_' . $product->id, 600, function() use ($product) {
            $product->load([
                'variants' => function($query) {
                    $query->select('id', 'product_id', 'name', 'weight_kg', 'price_aud', 'stock_quantity', 'is_active', 'created_at')
                          ->where('is_active', true);
                },
                'photos:id,product_id,filename'
            ]);
            return $product;
        });

        return response()->json([
            'message' => 'Product retrieved successfully',
            'data' => [
                'id' => $cachedProduct->id,
                'name' => $cachedProduct->name,
                'slug' => $cachedProduct->slug,
                'description' => $cachedProduct->description,
                'weight_kg' => $cachedProduct->weight_kg,
                'category' => $cachedProduct->category,
                'is_active' => $cachedProduct->is_active,
                'created_at' => $cachedProduct->created_at,
                'variants' => $cachedProduct->variants->map(function ($variant) {
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
                'photos' => $cachedProduct->photos->map(function ($photo) {
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

    public function categories(): JsonResponse
    {
        $categories = Product::select('category')
            ->where('is_active', true)
            ->groupBy('category')
            ->get()
            ->pluck('category');

        return response()->json([
            'message' => 'Product categories retrieved successfully',
            'data' => $categories,
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
            'category_id' => 'nullable|exists:categories,id',
            'category' => 'nullable|string|in:REFILL,FULL_TANK',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'is_active' => 'boolean|integer|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        // Map category_id to category string for backward compatibility
        $categoryString = 'REFILL'; // default
        if (isset($validated['category_id'])) {
            $categoryString = $validated['category_id'] == 1 ? 'REFILL' : 'FULL_TANK';
        } elseif (isset($validated['category'])) {
            $categoryString = $validated['category'];
        }

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
            'weight_kg' => $validated['weight'] ?? 0,
            'category_id' => $validated['category_id'] ?? null,
            'category' => $categoryString,
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

        // Create photo record if image was uploaded
        if ($imagePath && $request->hasFile('image')) {
            $image = $request->file('image');
            $product->photos()->create([
                'filename' => time() . '_' . $image->getClientOriginalName(),
                'original_filename' => $image->getClientOriginalName(),
                'url' => $imagePath,
                'alt_text' => $product->name . ' Image',
                'sort_order' => 1,
                'is_primary' => true
            ]);
        }

        // Load the product with relationships for response
        $product->load(['variants', 'category', 'photos']);

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
                'category_id' => 'nullable|exists:categories,id',
                'category' => 'nullable|string|in:REFILL,FULL_TANK',
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

        // Map category_id to category string for backward compatibility
        $categoryString = $product->category; // default to existing
        if (isset($validated['category_id'])) {
            $categoryString = $validated['category_id'] == 1 ? 'REFILL' : 'FULL_TANK';
        } elseif (isset($validated['category'])) {
            $categoryString = $validated['category'];
        }

        // Handle image upload
        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'weight_kg' => $validated['weight'] ?? $product->weight_kg,
            'category_id' => $validated['category_id'] ?? $product->category_id,
            'category' => $categoryString,
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
            $imageUrl = '/uploads/products/' . $imageName;
            $updateData['image_url'] = $imageUrl;

            // Also update or create photo record for consistency
            $product->photos()->updateOrCreate(
                ['is_primary' => true], // Find primary photo or create new one
                [
                    'filename' => $imageName,
                    'original_filename' => $image->getClientOriginalName(),
                    'url' => $imageUrl,
                    'alt_text' => $product->name . ' Image',
                    'sort_order' => 1,
                    'is_primary' => true
                ]
            );

            \Log::info('Product image updated', [
                'product_id' => $product->id,
                'image_url' => $imageUrl,
                'filename' => $imageName
            ]);
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

        try {
            // Check if product has any orders
            $hasOrders = $product->variants()
                ->whereHas('orderItems')
                ->exists();

            if ($hasOrders) {
                // Instead of blocking deletion, deactivate the product
                $product->update(['is_active' => false]);

                return response()->json([
                    'message' => 'Product has been ordered previously so it has been deactivated instead of deleted.',
                    'action' => 'deactivated'
                ]);
            }

            // Try to delete the product and its related data
            DB::transaction(function() use ($product) {
                // Delete photos first
                $product->photos()->delete();

                // Delete variants
                $product->variants()->delete();

                // Delete the product itself
                $product->delete();
            });

            return response()->json([
                'message' => 'Product deleted successfully',
                'action' => 'deleted'
            ]);

        } catch (\Exception $e) {
            // If deletion fails due to constraints, deactivate instead
            \Log::error('Product deletion failed', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            try {
                $product->update(['is_active' => false]);
                return response()->json([
                    'message' => 'Could not delete product due to database constraints. Product has been deactivated instead.',
                    'action' => 'deactivated'
                ]);
            } catch (\Exception $deactivateError) {
                return response()->json([
                    'message' => 'Failed to delete or deactivate product: ' . $deactivateError->getMessage()
                ], 500);
            }
        }
    }
}

