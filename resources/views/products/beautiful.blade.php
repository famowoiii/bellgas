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
        <div x-data="beautifulProducts()" x-init="loadProducts()" class="relative">

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

                            <!-- Stock Badge -->
                            <div class="absolute top-4 left-4">
                                <span :class="product.stock > 0 ? 'bg-green-500' : 'bg-red-500'"
                                      class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold text-white">
                                    <i :class="product.stock > 0 ? 'fas fa-check-circle' : 'fas fa-times-circle'" class="mr-2"></i>
                                    <span x-text="product.stock > 0 ? `${product.stock} in stock` : 'Out of stock'"></span>
                                </span>
                            </div>

                            <!-- Category Badge -->
                            <div class="absolute top-4 right-4">
                                <span class="bg-blue-500 bg-opacity-90 text-white px-3 py-1 rounded-full text-sm font-medium">
                                    <span x-text="product.category"></span>
                                </span>
                            </div>
                        </div>

                        <!-- Product Details -->
                        <div class="p-6">
                            <div class="mb-4">
                                <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors"
                                    x-text="product.name"></h3>
                                <p class="text-gray-600 text-sm leading-relaxed"
                                   x-text="product.description"></p>
                            </div>

                            <!-- Price -->
                            <div class="mb-6">
                                <div class="flex items-center justify-between">
                                    <div class="text-3xl font-bold text-blue-600">
                                        $<span x-text="product.price"></span>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        AUD
                                    </div>
                                </div>
                            </div>

                            <!-- Add to Cart Button -->
                            <button :id="'btn-' + product.id"
                                    @click="handleAddToCart(product)"
                                    :disabled="product.stock <= 0"
                                    :class="product.stock <= 0 ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                                    class="w-full py-3 px-6 rounded-lg text-white font-semibold transition-colors flex items-center justify-center space-x-2">

                                <i class="fas fa-cart-plus mr-2"></i>
                                <span x-text="product.stock > 0 ? 'Add to Cart' : 'Out of Stock'"></span>
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

<!-- Success Notification -->
<div id="successNotification"
     class="fixed bottom-8 right-8 z-50 transform translate-x-full transition-transform duration-500 ease-out">
    <div class="bg-green-500 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3 max-w-sm">
        <div class="flex-shrink-0">
            <i class="fas fa-check-circle text-xl"></i>
        </div>
        <div>
            <p class="font-semibold" id="notificationTitle">Success!</p>
            <p class="text-sm opacity-90" id="notificationMessage">Product added to cart</p>
        </div>
        <button onclick="hideNotification()" class="ml-4 text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<script>
function beautifulProducts() {
    return {
        products: [],
        loading: true,
        loadingCart: {},

        async loadProducts() {
            console.log('🎨 Beautiful Products: Loading...');
            this.loading = true;

            try {
                const response = await fetch('/api/products');
                const data = await response.json();
                console.log('📦 Products data:', data);

                if (data.data && Array.isArray(data.data)) {
                    this.products = data.data.map(product => ({
                        id: product.id,
                        name: product.name || 'LPG Product',
                        description: product.description || 'Premium quality LPG product for your needs',
                        price: product.variants?.[0]?.price_aud || '0.00',
                        variant_id: product.variants?.[0]?.id || null,
                        stock: product.variants?.[0]?.stock_quantity || 0,
                        category: product.category?.name || (product.category_id === 1 ? 'Refill' : 'Full Tank'),
                        image: product.image_url || product.photos?.[0]?.url || null
                    }));
                    console.log('✅ Products loaded:', this.products.length);
                } else {
                    console.warn('No products found');
                    this.products = [];
                }
            } catch (error) {
                console.error('❌ Error loading products:', error);
                this.products = [];
            } finally {
                this.loading = false;
            }
        },

        handleAddToCart(product) {
            console.log('🛒 Button clicked for:', product.name);

            // Simple validation
            if (!product.variant_id) {
                this.showSimpleNotification('Product tidak tersedia', 'error');
                return;
            }

            if (!window.isAuthenticated) {
                this.showSimpleNotification('Silakan login terlebih dahulu', 'error');
                return;
            }

            // Call the actual add to cart function
            this.performAddToCart(product);
        },

        async performAddToCart(product) {
            console.log('🚀 Adding to cart:', product.name);

            try {
                const response = await fetch('/api/cart', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + window.JWT_TOKEN
                    },
                    body: JSON.stringify({
                        product_variant_id: product.variant_id,
                        quantity: 1,
                        is_preorder: false
                    })
                });

                const data = await response.json();
                console.log('📦 Response:', data);

                if (data.success) {
                    this.showSimpleNotification(`${product.name} berhasil ditambahkan ke cart!`, 'success');

                    // Force cart refresh with guaranteed methods
                    await this.guaranteedCartRefresh();
                } else {
                    this.showSimpleNotification(data.message || 'Gagal menambahkan ke cart', 'error');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.showSimpleNotification('Terjadi kesalahan saat menambahkan ke cart', 'error');
            }
        },

        async guaranteedCartRefresh() {
            console.log('🔄 === GUARANTEED CART REFRESH START ===');

            try {
                // Method 1: Force update window.app cartItems directly
                if (window.app) {
                    console.log('🔄 Method 1: Direct app refresh');

                    // Force clear cache and reload
                    window.app.cartItems = [];
                    window.app.cartLastLoaded = null;

                    if (window.app.loadCart) {
                        await window.app.loadCart(true);
                        console.log('✅ App loadCart completed, items:', window.app.cartItems.length);
                    }

                    // Trigger Alpine.js reactivity
                    if (window.app.$nextTick) {
                        window.app.$nextTick(() => {
                            console.log('✅ Alpine reactivity triggered');
                        });
                    }
                }

                // Method 2: Fetch and manually update ALL cart elements
                console.log('🔄 Method 2: Manual element update');
                const cartResponse = await fetch('/api/cart', {
                    headers: {
                        'Authorization': 'Bearer ' + window.JWT_TOKEN,
                        'Accept': 'application/json'
                    }
                });

                if (cartResponse.ok) {
                    const cartData = await cartResponse.json();
                    console.log('📊 Fresh cart data:', cartData);

                    let totalItems = 0;
                    if (cartData.success && cartData.data && cartData.data.items) {
                        if (Array.isArray(cartData.data.items)) {
                            totalItems = cartData.data.items.reduce((sum, item) => sum + parseInt(item.quantity || 0), 0);
                        } else {
                            totalItems = cartData.data.count || Object.keys(cartData.data.items).length;
                        }
                    }

                    console.log('📊 Calculated total items:', totalItems);

                    // Update ALL possible cart count elements
                    const selectors = [
                        '[data-testid="cart-count"]',
                        '.cart-count',
                        '[data-cart-count]',
                        '[id*="cart"]',
                        '[class*="cart-count"]'
                    ];

                    let elementsFound = 0;
                    selectors.forEach(selector => {
                        const elements = document.querySelectorAll(selector);
                        elements.forEach(el => {
                            el.textContent = totalItems;
                            el.style.display = totalItems > 0 ? 'flex' : 'none';
                            elementsFound++;
                            console.log(`✅ Updated ${selector} element to: ${totalItems}`);
                        });
                    });

                    console.log(`🎯 Total elements updated: ${elementsFound}`);

                    // Method 3: Reload entire page if needed (last resort)
                    if (elementsFound === 0) {
                        console.warn('⚠️ No cart elements found, will reload page in 2 seconds');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                }

                console.log('🎉 GUARANTEED CART REFRESH COMPLETE');
            } catch (error) {
                console.error('❌ Guaranteed refresh failed:', error);
            }
        },

        showSimpleNotification(message, type = 'success') {
            // Simple notification using existing notification system
            if (typeof showNotification === 'function') {
                showNotification(type === 'success' ? 'Berhasil!' : 'Error', message, type);
            } else {
                // Fallback
                const div = document.createElement('div');
                div.style.cssText = `
                    position: fixed; top: 20px; right: 20px; z-index: 9999;
                    padding: 15px 20px; border-radius: 8px; color: white;
                    background: ${type === 'success' ? '#10b981' : '#ef4444'};
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                `;
                div.textContent = message;
                document.body.appendChild(div);

                setTimeout(() => div.remove(), 3000);
            }
        },

    };
}

// Beautiful notification system
function showNotification(title, message, type = 'success') {
    const notification = document.getElementById('successNotification');
    const titleEl = document.getElementById('notificationTitle');
    const messageEl = document.getElementById('notificationMessage');

    titleEl.textContent = title;
    messageEl.textContent = message;

    // Change colors based on type
    const container = notification.querySelector('div');
    container.className = container.className.replace(/bg-\w+-500/,
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    );

    // Show notification
    notification.style.transform = 'translateX(0)';

    // Auto hide after 4 seconds
    setTimeout(() => {
        hideNotification();
    }, 4000);
}

function hideNotification() {
    const notification = document.getElementById('successNotification');
    notification.style.transform = 'translateX(100%)';
}
</script>
@endsection