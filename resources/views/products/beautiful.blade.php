@extends('layouts.app')

@section('title', 'Products - BellGas')


@section('content')
<div class="min-h-screen bg-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Simple Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Our Products</h1>
            <p class="text-xl text-gray-600">Find the perfect LPG solution for your needs</p>
        </div>

        <!-- Products Container -->
        <div x-data="beautifulProducts()" x-init="console.log('🚀 Beautiful Products component initialized'); loadProducts()" class="relative">

            <!-- Loading State -->
            <div x-show="loading" class="flex flex-col items-center justify-center py-20">
                <div class="relative">
                    <div class="w-20 h-20 border-4 border-blue-200 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 w-20 h-20 border-4 border-blue-600 rounded-full animate-spin border-t-transparent"></div>
                </div>
                <p class="text-gray-600 mt-6 text-lg font-medium">Loading amazing products...</p>
            </div>

            <!-- Products Grid -->
            <div x-show="!loading && products.length > 0"
                 x-transition:enter="transition-all duration-500"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">

                <template x-for="(product, index) in products" :key="product.id">
                    <div class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 overflow-hidden"
                         x-transition:enter="transition-all duration-500"
                         x-transition:enter-start="opacity-0 translate-y-8"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         :style="`transition-delay: ${index * 100}ms`">

                        <!-- Product Image -->
                        <div class="relative h-64 bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                            <img x-show="product.image"
                                 :src="product.image"
                                 :alt="product.name"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">

                            <div x-show="!product.image"
                                 class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-indigo-500">
                                <i class="fas fa-fire text-6xl text-white opacity-80"></i>
                            </div>

                            <!-- Overlay Gradient -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                            <!-- Stock Badge -->
                            <div x-show="product.stock > 0" class="absolute top-4 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                <i class="fas fa-check mr-1"></i>In Stock
                            </div>
                            <div x-show="product.stock <= 0" class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                <i class="fas fa-times mr-1"></i>Out of Stock
                            </div>
                        </div>

                        <!-- Product Details -->
                        <div class="p-6 space-y-4">
                            <!-- Product Name -->
                            <h3 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-300"
                                x-text="product.name"></h3>

                            <!-- Product Description -->
                            <p class="text-gray-600 text-sm leading-relaxed line-clamp-2"
                               x-text="product.description || 'Premium quality LPG product for all your needs.'"></p>

                            <!-- Weight Info -->
                            <div x-show="product.weight_kg" class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-weight-hanging mr-2 text-blue-500"></i>
                                <span x-text="product.weight_kg + ' kg'"></span>
                            </div>

                            <!-- Price -->
                            <div class="flex items-center justify-between">
                                <div class="text-2xl font-bold text-green-600">
                                    $<span x-text="product.price?.toFixed(2) || '0.00'"></span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Stock: <span x-text="product.stock" class="font-semibold"></span>
                                </div>
                            </div>

                            <!-- Add to Cart Button -->
                            <button type="button"
                                    @click.prevent="console.log('🛒 Add to cart clicked for:', product.name); addToCart(product)"
                                    :disabled="isAddingToCart(product.variant_id) || product.stock <= 0"
                                    :data-product-id="product.id"
                                    :class="{
                                        'bg-blue-600 hover:bg-blue-700': product.stock > 0 && !isAddingToCart(product.variant_id),
                                        'bg-green-500': isAddingToCart(product.variant_id),
                                        'bg-gray-400': product.stock <= 0,
                                        'transform scale-95': isAddingToCart(product.variant_id)
                                    }"
                                    class="w-full px-4 py-3 text-white rounded-lg transition-all duration-300 font-medium disabled:cursor-not-allowed relative overflow-hidden">

                                <!-- Background loading animation -->
                                <div x-show="isAddingToCart(product.variant_id)"
                                     class="absolute inset-0 bg-gradient-to-r from-green-400 to-green-600 animate-pulse"></div>

                                <!-- Content -->
                                <div class="relative flex items-center justify-center">
                                    <span x-show="!isAddingToCart(product.variant_id) && product.stock > 0">
                                        <i class="fas fa-shopping-cart mr-2"></i>Add to Cart
                                    </span>

                                    <span x-show="isAddingToCart(product.variant_id)"
                                          class="flex items-center">
                                        <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                                        Adding...
                                    </span>

                                    <span x-show="!isAddingToCart(product.variant_id) && product.stock <= 0"
                                          class="text-gray-300">
                                        <i class="fas fa-times mr-2"></i>Stok Habis
                                    </span>
                                </div>

                                <!-- Success ripple effect -->
                                <div x-show="isAddingToCart(product.variant_id)"
                                     class="absolute inset-0 bg-white bg-opacity-20 rounded-lg animate-ping"
                                     x-transition:enter="transition-opacity ease-out duration-500"
                                     x-transition:leave="transition-opacity ease-in duration-300"></div>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && products.length === 0" class="text-center py-20">
                <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-box-open text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-2xl font-semibold text-gray-700 mb-4">No Products Found</h3>
                <p class="text-gray-500 mb-8">We're working on adding amazing products for you!</p>
                <button @click="loadProducts()" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-semibold transition-colors">
                    <i class="fas fa-refresh mr-2"></i>Try Again
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function beautifulProducts() {
    return {
        products: [],
        loading: true,
        addingToCart: {},

        isAddingToCart(variantId) {
            return this.addingToCart[variantId] || false;
        },

        async addToCart(product) {
            console.log('🛒 Product page addToCart called:', product.name);

            if (!product.variant_id) {
                console.error('❌ No variant_id found for product:', product);
                this.showNotification('Product variant tidak tersedia', 'error');
                return;
            }

            if (product.stock <= 0) {
                this.showNotification('Produk sedang habis', 'error');
                return;
            }

            // Prevent double clicks
            if (this.addingToCart[product.variant_id]) {
                console.log('⚠️ Already adding to cart, preventing double click');
                return;
            }

            // Set loading state using variant_id
            this.addingToCart[product.variant_id] = true;

            try {
                // Get auth token from localStorage (set by main layout)
                const token = localStorage.getItem('access_token') || window.JWT_TOKEN;

                if (!token) {
                    this.showNotification('Silakan login terlebih dahulu', 'error');
                    setTimeout(() => window.location.href = '/login', 2000);
                    return;
                }

                console.log('📤 Sending POST request to /api/cart...');

                // Direct API call with axios for consistency
                const response = await axios.post('/api/cart', {
                    product_variant_id: product.variant_id,
                    quantity: 1,
                    is_preorder: false
                }, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });

                console.log('✅ Add to cart response:', response.data);

                if (response.data.success) {
                    // Show success message
                    this.showNotification('Produk berhasil ditambahkan ke keranjang!', 'success');

                    // Trigger cart refresh with multiple fallback methods
                    console.log('🔄 Checking window.app availability:', !!window.app);

                    // Method 1: Direct window.app call
                    if (window.app && typeof window.app.loadCart === 'function') {
                        console.log('🔄 Triggering cart refresh after successful add...');
                        await window.app.loadCart(true); // Force refresh
                        console.log('✅ Cart refreshed, new count:', window.app.cartCount);
                    } else {
                        console.warn('⚠️ window.app.loadCart not available, trying fallback methods...');
                    }

                    // Method 2: Custom event dispatch
                    console.log('📡 Dispatching cartItemAdded event');
                    window.dispatchEvent(new CustomEvent('cartItemAdded', {
                        detail: {
                            product: product,
                            timestamp: Date.now(),
                            forceRefresh: true
                        }
                    }));

                    // Method 3: Try delayed refresh
                    setTimeout(async () => {
                        if (window.app && typeof window.app.loadCart === 'function') {
                            console.log('🔄 Delayed cart refresh...');
                            await window.app.loadCart(true);
                            console.log('✅ Delayed cart refreshed');
                        }
                    }, 500);

                    console.log('🎉 Product added to cart successfully');
                } else {
                    console.error('❌ Server returned success: false');
                    this.showNotification(response.data.message || 'Gagal menambahkan produk', 'error');
                }
            } catch (error) {
                console.error('❌ Add to cart error:', error);

                if (error.response) {
                    console.error('Error response:', error.response.data);
                    console.error('Error status:', error.response.status);

                    if (error.response.status === 401) {
                        this.showNotification('Session expired, silakan login kembali', 'error');
                        setTimeout(() => window.location.href = '/login', 2000);
                    } else if (error.response.data && error.response.data.message) {
                        this.showNotification(error.response.data.message, 'error');
                    } else {
                        this.showNotification('Gagal menambahkan produk ke keranjang', 'error');
                    }
                } else {
                    this.showNotification('Network error. Please try again.', 'error');
                }
            } finally {
                // Reset loading state
                this.addingToCart[product.variant_id] = false;
            }
        },

        async loadProducts() {
            this.loading = true;

            try {
                console.log('📡 Loading products from /api/products...');
                const response = await axios.get('/api/products');

                console.log('📦 Raw API response:', response.data);
                if (response.data.data && Array.isArray(response.data.data)) {
                    // Transform data to include variant info
                    this.products = response.data.data.map(product => {
                        const transformedProduct = {
                            ...product,
                            variant_id: product.variants?.[0]?.id || null,
                            stock: product.variants?.[0]?.stock_quantity || 0,
                            price: parseFloat(product.variants?.[0]?.price_aud || 0),
                            image: product.photos?.[0]?.url || null
                        };
                        console.log('🔄 Transformed product:', product.name, 'Price:', transformedProduct.price);
                        return transformedProduct;
                    });
                    console.log(`✅ Loaded ${this.products.length} products with prices:`, this.products.map(p => `${p.name}: $${p.price}`));
                } else {
                    console.error('❌ Failed to load products:', response.data);
                    this.showNotification('Gagal memuat produk', 'error');
                }
            } catch (error) {
                console.error('❌ Error loading products:', error);
                this.showNotification('Error loading products', 'error');
            } finally {
                this.loading = false;
            }
        },

        showNotification(message, type = 'success') {
            console.log('🔔 showNotification called:', message, type);
            // Use global notification system if available
            if (window.app && typeof window.app.showNotification === 'function') {
                console.log('✅ Using global notification system');
                window.app.showNotification(message, type);
            } else {
                console.warn('⚠️ Global notification system not available, using fallback');
                // Wait a bit and try again
                setTimeout(() => {
                    if (window.app && typeof window.app.showNotification === 'function') {
                        console.log('✅ Using delayed global notification system');
                        window.app.showNotification(message, type);
                    } else {
                        console.error('❌ Global notification still not available, message:', message);
                        // Create a simple visual notification as fallback
                        const notification = document.createElement('div');
                        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                            type === 'success' ? 'bg-green-500 text-white' :
                            type === 'error' ? 'bg-red-500 text-white' :
                            'bg-blue-500 text-white'
                        }`;
                        notification.textContent = message;
                        document.body.appendChild(notification);
                        setTimeout(() => notification.remove(), 3000);
                    }
                }, 100);
            }
        }
    }
}
</script>
@endpush
@endsection