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
                        <button type="button"
                                @click.prevent="addToCart(product)"
                                :disabled="isAddingToCart(product.id) || product.stock <= 0"
                                :data-product-id="product.id"
                                :class="{
                                    'bg-blue-600 hover:bg-blue-700': product.stock > 0 && !isAddingToCart(product.id),
                                    'bg-green-500': isAddingToCart(product.id),
                                    'bg-gray-400': product.stock <= 0,
                                    'transform scale-95': isAddingToCart(product.id)
                                }"
                                class="w-full px-4 py-3 text-white rounded-lg transition-all duration-300 font-medium disabled:cursor-not-allowed relative overflow-hidden">

                            <!-- Background loading animation -->
                            <div x-show="isAddingToCart(product.id)"
                                 class="absolute inset-0 bg-gradient-to-r from-green-400 to-green-600 animate-pulse"></div>

                            <!-- Content -->
                            <div class="relative flex items-center justify-center">
                                <!-- Loading spinner -->
                                <div x-show="isAddingToCart(product.id)"
                                     class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-3"
                                     x-transition:enter="transition-opacity ease-out duration-300"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"></div>

                                <!-- Normal state -->
                                <span x-show="!isAddingToCart(product.id) && product.stock > 0"
                                      x-transition:enter="transition-all ease-out duration-300"
                                      x-transition:enter-start="opacity-0 transform scale-95"
                                      x-transition:enter-end="opacity-100 transform scale-100">
                                    <i class="fas fa-cart-plus mr-2"></i>Tambah ke Keranjang
                                </span>

                                <!-- Loading state -->
                                <span x-show="isAddingToCart(product.id)"
                                      x-transition:enter="transition-all ease-out duration-300"
                                      x-transition:enter-start="opacity-0"
                                      x-transition:enter-end="opacity-100"
                                      class="font-semibold">
                                    <i class="fas fa-check mr-2"></i>Menambahkan...
                                </span>

                                <!-- Out of stock -->
                                <span x-show="!isAddingToCart(product.id) && product.stock <= 0"
                                      class="text-gray-300">
                                    <i class="fas fa-times mr-2"></i>Stok Habis
                                </span>
                            </div>

                            <!-- Success ripple effect -->
                            <div x-show="isAddingToCart(product.id)"
                                 class="absolute inset-0 bg-white bg-opacity-20 rounded-lg animate-ping"
                                 x-transition:enter="transition-opacity ease-out duration-500"
                                 x-transition:leave="transition-opacity ease-in duration-300"></div>
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

            // Modern toast notification with animation
            this.createModernNotification(message, type);
        },

        createModernNotification(message, type = 'success') {
            // Remove any existing notifications first
            document.querySelectorAll('.modern-toast').forEach(n => n.remove());

            const notification = document.createElement('div');
            notification.className = 'modern-toast';

            let bgColor, icon;
            switch (type) {
                case 'success':
                    bgColor = '#10b981';
                    icon = '✓';
                    break;
                case 'error':
                    bgColor = '#ef4444';
                    icon = '✕';
                    break;
                case 'warning':
                    bgColor = '#f59e0b';
                    icon = '⚠';
                    break;
                default:
                    bgColor = '#3b82f6';
                    icon = 'ℹ';
            }

            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                min-width: 350px;
                padding: 16px 20px;
                background: ${bgColor};
                color: white;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.3);
                font-family: system-ui, -apple-system, sans-serif;
                font-size: 14px;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 12px;
                transform: translateX(400px);
                transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                opacity: 0;
            `;

            notification.innerHTML = `
                <div style="
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    background: rgba(255,255,255,0.2);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                ">${icon}</div>
                <div style="flex: 1;">${message}</div>
                <button onclick="this.parentElement.remove()" style="
                    background: none;
                    border: none;
                    color: rgba(255,255,255,0.8);
                    cursor: pointer;
                    font-size: 18px;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">×</button>
            `;

            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
                notification.style.opacity = '1';
            }, 50);

            // Auto remove
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        },

        isAddingToCart(productId) {
            return this.addingToCart[productId] || false;
        },

        async addToCart(product) {
            console.log('🛒 Adding to cart:', product.name);

            // Prevent double clicks
            if (this.addingToCart[product.id]) {
                return;
            }

            if (!product.variant_id) {
                this.showNotification('Product variant not available', 'error');
                return;
            }

            if (product.stock <= 0) {
                this.showNotification('Produk sedang habis', 'error');
                return;
            }

            // Set loading state
            this.addingToCart[product.id] = true;

            try {
                // Direct API call for simplicity
                const response = await axios.post('/api/cart', {
                    product_variant_id: product.variant_id,
                    quantity: 1,
                    is_preorder: false
                }, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (response.data.success) {
                    // Success animation
                    await this.showSuccessAnimation(product);

                    // Show notification
                    this.showNotification(`✓ ${product.name} berhasil ditambahkan ke keranjang!`, 'success');

                    // Update cart counter
                    this.animateCartCounter();

                    // Try to update global cart if available
                    if (window.app && window.app.loadCart) {
                        await window.app.loadCart(true);
                    }
                } else {
                    throw new Error(response.data.message || 'Failed to add to cart');
                }
            } catch (error) {
                console.error('❌ Error adding to cart:', error);

                let errorMessage = 'Gagal menambahkan ke keranjang';
                if (error.response?.status === 401) {
                    errorMessage = 'Silakan login terlebih dahulu';
                    setTimeout(() => window.location.href = '/login', 2000);
                } else if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                }

                this.showNotification(errorMessage, 'error');
            } finally {
                this.addingToCart[product.id] = false;
            }
        },

        async showSuccessAnimation(product) {
            // Add a small delay for visual feedback
            return new Promise(resolve => {
                setTimeout(() => {
                    // Flash success color briefly
                    const button = document.querySelector(`button[data-product-id="${product.id}"]`);
                    if (button) {
                        // Apply success state temporarily
                        button.style.cssText = `
                            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
                            transform: scale(1.05) !important;
                            transition: all 0.2s ease !important;
                        `;

                        setTimeout(() => {
                            button.style.cssText = '';
                        }, 500);
                    }
                    resolve();
                }, 200);
            });
        },

        animateCartCounter() {
            // Animate the specific cart counter badge in header
            const cartCounter = document.querySelector('[data-testid="cart-count"]');
            if (cartCounter) {
                this.animateElement(cartCounter, {
                    scale: 1.4,
                    background: '#10b981',
                    color: 'white',
                    borderColor: '#059669',
                    transform: 'scale(1.4) pulse',
                    animation: 'cartBounce 0.6s ease-out'
                });
            }

            // Animate the cart button itself
            const cartButton = document.querySelector('[data-testid="cart-button"]');
            if (cartButton) {
                this.animateElement(cartButton, {
                    transform: 'scale(1.1) rotate(-5deg)',
                    color: '#10b981'
                }, 400);
            }

            // Animate shopping cart icons
            const cartIcons = document.querySelectorAll('.fa-shopping-cart');
            cartIcons.forEach(icon => {
                this.animateElement(icon, {
                    transform: 'scale(1.2) rotate(15deg)',
                    color: '#10b981'
                }, 500);
            });

            // Add cart bounce CSS animation if not exists
            this.addCartAnimations();
        },

        animateElement(element, styles, duration = 300) {
            if (!element) return;

            // Store original styles
            const originalStyles = {};
            Object.keys(styles).forEach(prop => {
                originalStyles[prop] = element.style[prop] || '';
            });

            // Apply animation styles
            element.style.transition = 'all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            Object.assign(element.style, styles);

            // Restore original styles after animation
            setTimeout(() => {
                Object.assign(element.style, originalStyles);
                element.style.transition = '';
            }, duration);
        },

        addCartAnimations() {
            if (!document.getElementById('cart-animations')) {
                const style = document.createElement('style');
                style.id = 'cart-animations';
                style.textContent = `
                    @keyframes cartBounce {
                        0% { transform: scale(1); }
                        20% { transform: scale(1.3) rotate(-5deg); }
                        40% { transform: scale(1.1) rotate(2deg); }
                        60% { transform: scale(1.2) rotate(-1deg); }
                        80% { transform: scale(1.05) rotate(0.5deg); }
                        100% { transform: scale(1) rotate(0deg); }
                    }
                    @keyframes cartPulse {
                        0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
                        50% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
                    }
                    .cart-success-animation {
                        animation: cartBounce 0.6s ease-out, cartPulse 0.8s ease-out;
                    }
                `;
                document.head.appendChild(style);
            }
        }
    }
}
</script>
@endsection