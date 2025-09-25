@extends('layouts.app')

@section('title', 'Products - BellGas')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="simpleProductsApp()" x-init="init()">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Our Products</h1>
                    <p class="text-gray-600">Find the perfect LPG solution for your needs</p>
                </div>
                <div class="text-sm text-gray-500">
                    <p>Products: <span x-text="products.length"></span></p>
                    <p>Loading: <span x-text="loading ? 'Yes' : 'No'"></span></p>
                    <button @click="loadProducts()" class="mt-1 bg-gray-600 text-white px-3 py-1 rounded text-xs hover:bg-gray-700">Reload</button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500 border-t-transparent"></div>
            <span class="ml-2 text-gray-600">Loading products...</span>
        </div>

        <!-- Error State -->
        <div x-show="error && !loading" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
            <div class="flex items-center space-x-2 text-red-800">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="font-medium">Error loading products</span>
            </div>
            <p class="text-red-600 text-sm mt-1" x-text="error"></p>
        </div>

        <!-- Products Grid -->
        <div x-show="!loading && !error" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <template x-for="product in products" :key="product.id">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                    <!-- Product Image -->
                    <div class="aspect-square bg-gray-100 relative overflow-hidden">
                        <img x-show="product.image_url" :src="product.image_url" :alt="product.name"
                             class="w-full h-full object-cover">
                        <div x-show="!product.image_url" class="w-full h-full flex items-center justify-center">
                            <i class="fas fa-fire text-4xl text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Product Details -->
                    <div class="p-4">
                        <div class="mb-3">
                            <h3 class="font-semibold text-gray-900 text-lg mb-1" x-text="product.name"></h3>
                            <p class="text-sm text-blue-600 font-medium" x-text="product.category || 'LPG Product'"></p>
                        </div>

                        <p class="text-gray-600 text-sm mb-3" x-text="product.description || 'Premium LPG product for your energy needs.'"></p>

                        <!-- Product Info -->
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Category:</span>
                                <span class="font-medium" x-text="product.category"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Stock:</span>
                                <span class="font-medium" :class="product.stock > 0 ? 'text-green-600' : 'text-red-600'"
                                      x-text="product.stock > 0 ? product.stock + ' available' : 'Out of stock'"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Price:</span>
                                <span class="text-xl font-bold text-blue-600">$<span x-text="product.price || '0.00'"></span></span>
                            </div>
                        </div>

                        <!-- Add to Cart -->
                        <button @click="addToCart(product)"
                                :disabled="isAddingToCart(product.id) || product.stock <= 0"
                                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium">
                            <div class="flex items-center justify-center">
                                <div x-show="isAddingToCart(product.id)" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                                <span x-show="!isAddingToCart(product.id) && product.stock > 0">
                                    <i class="fas fa-cart-plus mr-2"></i>Add to Cart
                                </span>
                                <span x-show="isAddingToCart(product.id)">Adding to Cart...</span>
                                <span x-show="!isAddingToCart(product.id) && product.stock <= 0">Out of Stock</span>
                            </div>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && !error && products.length === 0" class="text-center py-12">
            <i class="fas fa-box text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-800 mb-2">No products found</h3>
            <p class="text-gray-600">Check back later for new products</p>
        </div>
    </div>
</div>

<script>
function simpleProductsApp() {
    return {
        products: [],
        loading: false,
        error: null,
        addingToCart: {},

        async init() {
            console.log('Customer Products with Enhanced Add to Cart initializing...');
            await this.loadProducts();
        },

        async loadProducts() {
            console.log('🛒 Loading products...');
            this.loading = true;
            this.error = null;

            try {
                console.log('📡 Fetching /api/products...');
                const response = await axios.get('/api/products');
                console.log('📦 Response received:', response.data);

                if (response.data && response.data.data) {
                    this.products = response.data.data.map(product => {
                        const processed = {
                            id: product.id,
                            name: product.name || 'Unnamed Product',
                            description: product.description || 'Premium LPG product for your energy needs.',
                            price: product.variants && product.variants[0] ? product.variants[0].price_aud : '0.00',
                            variant_id: product.variants && product.variants[0] ? product.variants[0].id : null,
                            stock: product.variants && product.variants[0] ? product.variants[0].stock_quantity : 0,
                            category: this.getCategoryName(product.category_id) || 'LPG Product',
                            image_url: product.image_url || (product.photos && product.photos[0] ? product.photos[0].url : null)
                        };
                        return processed;
                    });

                    console.log('✅ Products processed:', this.products.length);
                    console.log('📋 First product:', this.products[0]);
                } else {
                    console.warn('⚠️ No products data found');
                    this.products = [];
                    this.error = 'No products found';
                }
            } catch (error) {
                console.error('❌ Error loading products:', error);
                this.error = 'Failed to load products: ' + error.message;
                this.products = [];
            } finally {
                this.loading = false;
                console.log('🏁 Load products finished. Count:', this.products.length);
            }
        },

        getCategoryName(categoryId) {
            const categories = {
                1: 'Refill',
                2: 'Full Tank'
            };
            return categories[categoryId] || 'LPG Product';
        },

        showNotification(message, type = 'info') {
            console.log('🔔 Products page showNotification:', message, type);

            // Try to use global app notification first
            if (window.app && window.app.showNotification) {
                try {
                    window.app.showNotification(message, type);
                    return;
                } catch (error) {
                    console.warn('Global showNotification failed:', error);
                }
            }

            // Simple fallback notification
            try {
                const notification = document.createElement('div');
                notification.style.cssText = 'position:fixed; bottom:20px; right:20px; z-index:9999; padding:15px 20px; border-radius:8px; color:white; max-width:300px; font-family:Arial,sans-serif;';

                if (type === 'success') notification.style.backgroundColor = '#10b981';
                else if (type === 'error') notification.style.backgroundColor = '#ef4444';
                else if (type === 'warning') notification.style.backgroundColor = '#f59e0b';
                else notification.style.backgroundColor = '#3b82f6';

                notification.innerHTML = '<div style="display:flex;align-items:center;gap:10px;"><span>' + message + '</span><button onclick="this.parentElement.parentElement.remove()" style="background:none;border:none;color:white;cursor:pointer;font-size:16px;">×</button></div>';

                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 5000);
            } catch (error) {
                console.error('Fallback notification failed:', error);
                // Last resort
                console.log('NOTIFICATION:', message);
            }
        },

        isAddingToCart(productId) {
            return this.addingToCart[productId] || false;
        },

        async addToCart(product) {
            console.log('🛒 Products page: Adding to cart:', product.name);

            if (!product.variant_id) {
                console.error('❌ No variant_id for product:', product.name);
                this.showNotification('Product variant tidak tersedia', 'error');
                return;
            }

            if (product.stock <= 0) {
                console.error('❌ Product out of stock:', product.name);
                this.showNotification('Produk sedang habis', 'error');
                this.showNotification('Produk sedang habis');
                }
                return;
            }

            // Check authentication
            if (!window.isAuthenticated) {
                console.error('❌ User not authenticated');
                this.showNotification('Silakan login terlebih dahulu', 'error');
                this.showNotification('Silakan login terlebih dahulu');
                }
                setTimeout(() => window.location.href = '/login', 2000);
                return;
            }

            // Check if global app is available
            if (!window.app) {
                console.error('❌ Global app not available');
                alert('System error: Global app not available');
                return;
            }

            console.log('🔍 Checking global app methods:', {
                addToCart: typeof window.app.addToCart,
                showNotification: typeof window.app.showNotification,
                loadCart: typeof window.app.loadCart
            });

            if (!window.app.addToCart || typeof window.app.addToCart !== 'function') {
                console.error('❌ Global app addToCart method not available:', typeof window.app.addToCart);
                console.log('📋 Available app methods:', Object.getOwnPropertyNames(window.app));

                // Try direct API call as fallback
                console.log('🔄 Falling back to direct API call...');
                await this.directAddToCart(product);
                return;
            }

            this.addingToCart[product.id] = true;
            console.log('⏳ Setting loading state for product:', product.id);

            try {
                console.log('🚀 Calling global app.addToCart with:', {
                    variant_id: product.variant_id,
                    quantity: 1,
                    product_name: product.name
                });

                const result = await window.app.addToCart(product.variant_id, 1, {
                    isPreorder: false
                });

                console.log('🛒 addToCart result:', result);

                if (result && result.success !== false) {
                    console.log('✅ Successfully added to cart via global app');

                    // Show success notification
                    this.showNotification(`${product.name} berhasil ditambahkan ke cart!`, 'success');

                    // Force update cart display immediately
                    if (window.app && window.app.loadCart) {
                        console.log('🔄 Force reloading cart for immediate update...');
                        await window.app.loadCart(true);
                        console.log('✅ Cart forcefully reloaded, new count:', window.app.cartCount);
                    }
                } else {
                    console.error('❌ addToCart returned failure:', result);
                    throw new Error(result.error || 'Failed to add to cart');
                }
            } catch (error) {
                console.error('❌ Error in addToCart:', error);

                let errorMessage = 'Gagal menambahkan ke cart';
                if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                } else if (error.message) {
                    errorMessage = error.message;
                }

                this.showNotification(errorMessage, 'error');
                this.showNotification(errorMessage, 'error');
                }
            } finally {
                this.addingToCart[product.id] = false;
                console.log('🏁 Finished addToCart for product:', product.id);
            }
        },

        async directAddToCart(product) {
            console.log('🔄 Direct API addToCart for:', product.name);

            this.addingToCart[product.id] = true;

            try {
                const response = await axios.post('/api/cart', {
                    product_variant_id: product.variant_id,
                    quantity: 1,
                    is_preorder: false
                });

                console.log('🛒 Direct API response:', response.data);

                if (response.data.success) {
                    console.log('✅ Direct API addToCart success');

                    // Show success notification
                    this.showNotification(`${product.name} berhasil ditambahkan ke cart!`, 'success');

                    // Try to reload cart for immediate update
                    if (window.app && window.app.loadCart) {
                        console.log('🔄 Force reloading cart after direct API success...');
                        await window.app.loadCart(true);
                        console.log('✅ Cart reloaded after direct API, new count:', window.app.cartCount);
                    } else {
                        console.warn('⚠️ Cannot reload cart - loadCart method not available');
                        // As fallback, trigger a manual cart refresh event
                        window.dispatchEvent(new CustomEvent('cartUpdated', {
                            detail: { product: product.name, action: 'added' }
                        }));
                    }
                } else {
                    throw new Error(response.data.message || 'Failed to add to cart');
                }
            } catch (error) {
                console.error('❌ Direct API addToCart error:', error);

                let errorMessage = 'Gagal menambahkan ke cart';
                if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                } else if (error.message) {
                    errorMessage = error.message;
                }

                this.showNotification(errorMessage, 'error');
                this.showNotification(errorMessage, 'error');
                }
            } finally {
                this.addingToCart[product.id] = false;
            }
        }
    }
}
</script>
@endsection