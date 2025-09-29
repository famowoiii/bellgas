@extends('layouts.app')

@section('title', 'Products - BellGas')

@section('content')
<div class="min-h-screen bg-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Simple Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Our Products</h1>
            <p class="text-xl text-gray-600">Find the perfect LPG solution for your needs</p>

            <!-- Debug Test Buttons -->
            <div class="mt-4">
                <button onclick="testClick()" class="bg-red-500 text-white px-4 py-2 rounded">Test Global Function</button>
                <button onclick="alert('Direct alert works!')" class="bg-green-500 text-white px-4 py-2 rounded ml-2">Test Direct Alert</button>
            </div>
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

                            <!-- Add to Cart Button - ENHANCED VERSION -->
                            <button type="button"
                                    @click.prevent="alert('Tombol Alpine diklik!'); addToCart(product)"
                                    :disabled="isAddingToCart(product.id) || product.stock <= 0"
                                    :data-product-id="product.id"
                                    :class="{
                                        'bg-blue-600 hover:bg-blue-700': product.stock > 0 && !isAddingToCart(product.id),
                                        'bg-green-500': isAddingToCart(product.id),
                                        'bg-gray-400': product.stock <= 0,
                                        'transform scale-95': isAddingToCart(product.id)
                                    }"
                                    class="w-full px-4 py-3 text-white rounded-lg transition-all duration-300 font-medium disabled:cursor-not-allowed relative overflow-hidden mb-2">

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

                            <!-- BACKUP GLOBAL BUTTON - STATIC -->
                            <button type="button"
                                    onclick="globalAddToCart(1, 'LPG Test Product', 1)"
                                    class="w-full px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm font-medium">
                                🚀 Test Add Product 1
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


<script>
// Test global function
window.testClick = function() {
    alert('Global function works!');
};

// Global add to cart function as backup
window.globalAddToCart = async function(productId, productName, variantId) {
    try {
        console.log('🛒 Global addToCart called!');
        alert('Global addToCart dipanggil untuk: ' + productName);

        const token = localStorage.getItem('access_token') || window.JWT_TOKEN;

        if (!token) {
            alert('Token tidak ditemukan - silakan login terlebih dahulu');
            window.location.href = '/login';
            return;
        }

        console.log('Making API call with token:', token.substring(0, 20) + '...');

        const response = await axios.post('/api/cart', {
            product_variant_id: variantId || productId,
            quantity: 1,
            is_preorder: false
        }, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        console.log('API Response:', response.data);

        if (response.data.success) {
            alert('✅ BERHASIL! ' + productName + ' berhasil ditambahkan ke keranjang!');
            // Force reload cart page if available
            if (window.location.pathname.includes('cart')) {
                window.location.reload();
            }
        } else {
            alert('❌ Gagal: ' + (response.data.message || 'Unknown error'));
        }

    } catch (error) {
        console.error('❌ Error:', error);
        alert('❌ ERROR: ' + (error.response?.data?.message || error.message));
    }
};

function beautifulProducts() {
    return {
        products: [],
        loading: true,
        addingToCart: {},

        isAddingToCart(productId) {
            return this.addingToCart[productId] || false;
        },

        async addToCart(product) {
            alert('Fungsi addToCart dipanggil untuk: ' + product.name);
            console.log('🛒 Adding to cart:', product.name);

            // Prevent double clicks
            if (this.addingToCart[product.id]) {
                return;
            }

            if (!product.variant_id) {
                this.showNotification('Product variant tidak tersedia', 'error');
                return;
            }

            if (product.stock <= 0) {
                this.showNotification('Produk sedang habis', 'error');
                return;
            }

            // Set loading state
            this.addingToCart[product.id] = true;

            try {
                // Get auth token from localStorage (set by main layout)
                const token = localStorage.getItem('access_token') || window.JWT_TOKEN;

                if (!token) {
                    this.showNotification('Silakan login terlebih dahulu', 'error');
                    setTimeout(() => window.location.href = '/login', 2000);
                    return;
                }

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
        },

        showNotification(message, type = 'info') {
            console.log('🔔 Beautiful Products showNotification:', message, type);

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
        }
    };
}

// Modern notification system with smooth animations
function showNotification(title, message, type = 'success') {
    showModernNotification(title, message, type);
}

function showModernNotification(title, message, type = 'success') {
    const notification = document.getElementById('notificationToast');
    const titleEl = document.getElementById('toastTitle');
    const messageEl = document.getElementById('toastMessage');
    const iconEl = document.getElementById('toastIcon');
    const containerEl = document.getElementById('toastContainer');

    // Set content
    titleEl.textContent = title;
    messageEl.textContent = message;

    // Set icon and colors based on type
    const colors = {
        success: { bg: 'bg-green-500', icon: 'fas fa-check-circle' },
        error: { bg: 'bg-red-500', icon: 'fas fa-times-circle' },
        warning: { bg: 'bg-yellow-500', icon: 'fas fa-exclamation-triangle' },
        info: { bg: 'bg-blue-500', icon: 'fas fa-info-circle' }
    };

    const config = colors[type] || colors.success;

    // Update container background
    containerEl.className = containerEl.className.replace(/bg-\w+-500/, config.bg);
    iconEl.className = config.icon + ' text-xl';

    // Show with smooth animation
    notification.classList.remove('translate-x-full', 'opacity-0');
    notification.classList.add('translate-x-0', 'opacity-100');

    // Auto hide after 4 seconds
    setTimeout(() => {
        hideModernNotification();
    }, 4000);
}

function hideModernNotification() {
    const notification = document.getElementById('notificationToast');
    notification.classList.remove('translate-x-0', 'opacity-100');
    notification.classList.add('translate-x-full', 'opacity-0');
}
</script>
@endsection