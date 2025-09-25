@extends('layouts.app')

@section('title', 'Products - BellGas')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="productsApp()">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Our Products</h1>
            <p class="text-gray-600">Find the perfect LPG solution for your needs</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Products</label>
                    <input type="text"
                           x-model="search"
                           @input.debounce.300ms="loadProducts(1)"
                           placeholder="Search products..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select x-model="selectedCategory"
                            @change="loadProducts(1)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        <template x-for="category in categories" :key="category.id">
                            <option :value="category.id" x-text="category.name"></option>
                        </template>
                    </select>
                </div>

                <!-- Sort -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                    <select x-model="sortBy"
                            @change="loadProducts(1)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="name">Name A-Z</option>
                        <option value="-name">Name Z-A</option>
                        <option value="price">Price Low to High</option>
                        <option value="-price">Price High to Low</option>
                        <option value="-created_at">Newest First</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading && products.length === 0"
             class="flex items-center justify-center py-12">
            <div class="flex items-center space-x-3 text-gray-600">
                <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500 border-t-transparent"></div>
                <span>Loading products...</span>
            </div>
        </div>

        <!-- Error State -->
        <div x-show="error && !loading"
             class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
            <div class="flex items-center space-x-2 text-red-800">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="font-medium">Error loading products</span>
            </div>
            <p class="text-red-600 text-sm mt-1" x-text="error"></p>
            <button @click="loadProducts(1)"
                    class="mt-3 px-4 py-2 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg text-sm transition-colors">
                <i class="fas fa-redo mr-1"></i> Retry
            </button>
        </div>

        <!-- Products Grid -->
        <div x-show="!loading || products.length > 0">
            <!-- Products Count -->
            <div class="mb-6">
                <p class="text-gray-600" x-show="pagination.total">
                    Showing <span x-text="((pagination.current_page - 1) * pagination.per_page) + 1"></span> to
                    <span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> of
                    <span x-text="pagination.total"></span> products
                </p>
            </div>

            <!-- Products Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                <template x-for="product in products" :key="product.id">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                        <!-- Product Image -->
                        <div class="aspect-square bg-gray-100 relative overflow-hidden">
                            <template x-if="getProductImage(product)">
                                <img :src="getProductImage(product)"
                                     :alt="product.name"
                                     class="w-full h-full object-cover"
                                     @error="handleImageError($event)">
                            </template>
                            <template x-if="!getProductImage(product)">
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-fire text-4xl text-gray-400"></i>
                                </div>
                            </template>

                            <!-- Stock Badge -->
                            <div class="absolute top-2 left-2">
                                <span :class="{
                                    'bg-green-100 text-green-800': product.total_stock > 10,
                                    'bg-yellow-100 text-yellow-800': product.total_stock > 0 && product.total_stock <= 10,
                                    'bg-red-100 text-red-800': product.total_stock === 0
                                }" class="px-2 py-1 text-xs font-medium rounded-full">
                                    <span x-show="product.total_stock > 0" x-text="`${product.total_stock} in stock`"></span>
                                    <span x-show="product.total_stock === 0">Out of stock</span>
                                </span>
                            </div>
                        </div>

                        <!-- Product Details -->
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-2" x-text="product.name"></h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2" x-text="product.description"></p>

                            <!-- Variants -->
                            <div class="space-y-3">
                                <template x-for="variant in product.variants" :key="variant.id">
                                    <div class="border border-gray-200 rounded-lg p-3 hover:border-gray-300 transition-colors">
                                        <div class="flex items-center justify-between mb-2">
                                            <div>
                                                <h4 class="font-medium text-sm text-gray-800" x-text="variant.name"></h4>
                                                <p class="text-xs text-gray-600" x-text="variant.description"></p>
                                                <div class="flex items-center space-x-2 mt-1">
                                                    <span :class="{
                                                        'text-green-600': variant.available_stock > 10,
                                                        'text-yellow-600': variant.available_stock > 0 && variant.available_stock <= 10,
                                                        'text-red-600': variant.available_stock === 0
                                                    }" class="text-xs font-medium">
                                                        <span x-show="variant.available_stock > 0" x-text="`${variant.available_stock} available`"></span>
                                                        <span x-show="variant.available_stock === 0">Out of stock</span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-lg font-bold text-blue-600">$<span x-text="parseFloat(variant.price_aud).toFixed(2)"></span></div>
                                                <!-- MODERN ADD TO CART BUTTON -->
                                                <button @click="addToCartModern(variant.id, variant.name, product.name)"
                                                        :disabled="variant.available_stock === 0 || isAddingToCart(variant.id)"
                                                        :class="{
                                                            'bg-blue-600 hover:bg-blue-700 text-white': variant.available_stock > 0 && !isAddingToCart(variant.id),
                                                            'bg-gray-400 text-white cursor-not-allowed': variant.available_stock === 0,
                                                            'bg-blue-400 text-white cursor-not-allowed': isAddingToCart(variant.id)
                                                        }"
                                                        class="mt-2 px-4 py-2 rounded-lg text-xs font-medium transition-all duration-200 flex items-center justify-center gap-2 min-h-[36px] w-full">

                                                    <!-- Loading State -->
                                                    <template x-if="isAddingToCart(variant.id)">
                                                        <div class="flex items-center space-x-2">
                                                            <div class="animate-spin rounded-full h-3 w-3 border border-white border-t-transparent"></div>
                                                            <span>Adding...</span>
                                                        </div>
                                                    </template>

                                                    <!-- Default State -->
                                                    <template x-if="!isAddingToCart(variant.id) && variant.available_stock > 0">
                                                        <div class="flex items-center space-x-2">
                                                            <i class="fas fa-shopping-cart text-xs"></i>
                                                            <span>Add to Cart</span>
                                                        </div>
                                                    </template>

                                                    <!-- Out of Stock State -->
                                                    <template x-if="!isAddingToCart(variant.id) && variant.available_stock === 0">
                                                        <span>Out of Stock</span>
                                                    </template>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && products.length === 0 && !error"
                 class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-800 mb-2">No products found</h3>
                <p class="text-gray-600 mb-4">Try adjusting your search or filter criteria</p>
                <button @click="clearFilters()"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Clear Filters
                </button>
            </div>

            <!-- Pagination -->
            <div x-show="pagination.last_page > 1"
                 class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 rounded-lg">
                <div class="flex flex-1 justify-between sm:hidden">
                    <button @click="loadProducts(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1"
                            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Previous
                    </button>
                    <button @click="loadProducts(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing page <span class="font-medium" x-text="pagination.current_page"></span> of
                            <span class="font-medium" x-text="pagination.last_page"></span>
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <button @click="loadProducts(pagination.current_page - 1)"
                                    :disabled="pagination.current_page <= 1"
                                    class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>

                            <template x-for="page in paginationPages" :key="page">
                                <button @click="loadProducts(page)"
                                        :class="{
                                            'bg-blue-600 text-white': page === pagination.current_page,
                                            'text-gray-900 hover:bg-gray-50': page !== pagination.current_page
                                        }"
                                        class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 focus:z-20 focus:outline-offset-0"
                                        x-text="page">
                                </button>
                            </template>

                            <button @click="loadProducts(pagination.current_page + 1)"
                                    :disabled="pagination.current_page >= pagination.last_page"
                                    class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay for Page Changes -->
        <div x-show="loading && products.length > 0"
             class="fixed inset-0 bg-black bg-opacity-25 z-40 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent"></div>
                <span class="text-gray-700">Loading products...</span>
            </div>
        </div>
    </div>
</div>

<script>
function productsApp() {
    return {
        // Data
        products: [],
        categories: [],
        loading: false,
        error: null,
        addingToCartItems: {},

        // Filters
        search: '',
        selectedCategory: '',
        sortBy: 'name',

        // Pagination
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 12,
            total: 0
        },

        // Initialize
        async init() {
            console.log('🚀 MODERN Products app initializing...');
            await this.loadCategories();
            await this.loadProducts(1);
        },

        // DIRECT ADD TO CART SYSTEM - BYPASS window.app
        async addToCartModern(variantId, variantName, productName) {
            console.log('🛒 DIRECT addToCartModern:', variantId, variantName, productName);

            // Set loading state
            this.setCartItemLoading(variantId, true);

            try {
                // Check authentication
                if (!window.isAuthenticated) {
                    alert('Please login to add items to cart');
                    setTimeout(() => window.location.href = '/login', 1000);
                    return { success: false, error: 'Not authenticated' };
                }

                // Get JWT token
                const token = window.JWT_TOKEN;
                if (!token) {
                    alert('Authentication token not available. Please login again.');
                    setTimeout(() => window.location.href = '/login', 1000);
                    return { success: false, error: 'No token' };
                }

                // Direct API call
                console.log('📤 DIRECT API call to add to cart');
                const response = await axios.post('/api/cart', {
                    product_variant_id: variantId,
                    quantity: 1
                }, {
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    timeout: 10000
                });

                console.log('📥 DIRECT API response:', response.data);

                if (response.data.success) {
                    // Show modern notification
                    this.showModernNotification(`${variantName} added to cart!`, 'success');

                    // Update parent cart - FORCE RELOAD
                    console.log('🔄 FORCE reloading parent cart');
                    try {
                        if (window.app && window.app.loadCart) {
                            await window.app.loadCart(true);
                            console.log('✅ Parent cart reloaded successfully');
                        } else {
                            console.warn('⚠️ Parent app loadCart not available');
                            // Manual cart counter update
                            this.updateCartCounter();
                        }
                    } catch (e) {
                        console.error('❌ Parent cart reload failed:', e);
                        // Manual cart counter update as fallback
                        this.updateCartCounter();
                    }

                    console.log('✅ DIRECT addToCart success');
                    return { success: true, data: response.data.data };
                } else {
                    throw new Error(response.data.message || 'Failed to add to cart');
                }

            } catch (error) {
                console.error('❌ DIRECT addToCart error:', error);

                let errorMessage = 'Failed to add item to cart';
                if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                } else if (error.message) {
                    errorMessage = error.message;
                }

                this.showModernNotification(errorMessage, 'error');
                return { success: false, error: errorMessage };
            } finally {
                this.setCartItemLoading(variantId, false);
            }
        },

        // MODERN NOTIFICATION SYSTEM - SELF CONTAINED
        showModernNotification(message, type = 'success') {
            console.log('🔔 DIRECT showModernNotification:', message, type);

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed bottom-6 right-6 z-50 px-5 py-4 rounded-xl shadow-2xl border-l-4 backdrop-blur-sm transform transition-all duration-500 ease-out text-white ${
                type === 'success' ? 'bg-emerald-500 border-emerald-400' :
                type === 'error' ? 'bg-red-500 border-red-400' :
                type === 'warning' ? 'bg-amber-500 border-amber-400' :
                'bg-blue-500 border-blue-400'
            }`;

            notification.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="${
                            type === 'success' ? 'bg-emerald-600' :
                            type === 'error' ? 'bg-red-600' :
                            type === 'warning' ? 'bg-amber-600' :
                            'bg-blue-600'
                        } w-8 h-8 rounded-full flex items-center justify-center">
                            <i class="${
                                type === 'success' ? 'fas fa-check' :
                                type === 'error' ? 'fas fa-exclamation' :
                                type === 'warning' ? 'fas fa-exclamation-triangle' :
                                'fas fa-info'
                            } text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold leading-5">${message}</p>
                        <p class="text-xs opacity-75 mt-1">Just now</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 text-white/80 hover:text-white transition-colors duration-200">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            `;

            // Add to page
            document.body.appendChild(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.transform = 'translateX(100%) scale(95%)';
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        if (notification.parentElement) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, 5000);

            // Play notification sound
            try {
                const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+D2xGUgBzuD1fPTeigHKmrE7+GCNQCYH0Sf2+vfcS0AOXDt/W7T8ALm1bvP4WYHKonY6+9DPwGKmXKaY5LMBX4eIGNB1kfg8xvyN0N3OaNXoV2+dFQG1aVG3Kd9VlZAy7dF6dtdJKJl0xJXIa9ysW5bfcZnwqNyOUZDvMfrbNJlkZlbdNZ/FDU8g/vQxlJMrnpTdJNb7BqsQRYo/e9xzZZSy4E5fK13aGk3o5W9sXtP7GvQj29DFKcQF1DXYoVHwbDJPGxPGOpcH2j4D+Xaql0gF2JqWYs53KFTj5qDh/M4KJt2fz6uS8vPq9E3QMqGdNMQ5GBiHUIhk1O5HKJ7YXhGm9c8QLNwp1K6L9Jp3Z8dImfyVAjOLt0VDpZB3kdGHu0Oj9MV7YN8QFQJ1rBNtj1BSWt4Jq1Fkx8PDFcAJhYJuT3jCxUJKo0ZrBqWHd8jCBUO0lR7iVUaGZMQnTD2DktApjITdD+1HG0IrkdnJGOqRZYhUfmhGg7PXjVEQENHs7VGo5TfBsKLVZNI9U0e3CJqBEYT6tU4QTsT1YsKN/I=');
                audio.volume = 0.3;
                audio.play().catch(e => console.log('Sound play failed:', e));
            } catch (e) {
                console.log('Sound not supported:', e);
            }
        },

        // MANUAL CART COUNTER UPDATE
        async updateCartCounter() {
            console.log('🔢 MANUAL cart counter update');
            try {
                const token = window.JWT_TOKEN;
                if (!token) {
                    console.warn('No token for cart counter update');
                    return;
                }

                const response = await axios.get('/api/cart', {
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json'
                    },
                    timeout: 5000
                });

                console.log('📊 Cart API response for counter:', response.data);

                if (response.data.success) {
                    const items = response.data.data.items || [];
                    const count = items.reduce((sum, item) => sum + parseInt(item.quantity), 0);

                    // Update cart badge in navigation
                    const cartBadge = document.querySelector('.fa-shopping-cart').parentElement.querySelector('span');
                    if (cartBadge) {
                        cartBadge.textContent = count;
                        cartBadge.style.display = count > 0 ? 'flex' : 'none';
                        console.log('✅ Cart badge updated:', count);
                    }

                    // Force update window.app if available
                    if (window.app && window.app.cartItems !== undefined) {
                        window.app.cartItems = items.map(item => ({
                            ...item,
                            name: item.productVariant?.product?.name || 'Product',
                            variant_name: item.productVariant?.name || 'Standard',
                            image: item.productVariant?.product?.photos?.[0]?.url || '/placeholder.jpg',
                            formatted_price: '$' + parseFloat(item.price || 0).toFixed(2),
                            formatted_total: '$' + parseFloat(item.total || 0).toFixed(2)
                        }));
                        window.app.cartTotal = response.data.data.total || 0;
                        console.log('✅ window.app cart updated manually');
                    }
                }
            } catch (error) {
                console.error('❌ Manual cart update failed:', error);
            }
        },

        // Cart Loading Management
        setCartItemLoading(variantId, loading) {
            this.addingToCartItems = { ...this.addingToCartItems, [variantId]: loading };
        },

        isAddingToCart(variantId) {
            return this.addingToCartItems[variantId] || false;
        },

        // Load Categories
        async loadCategories() {
            try {
                const response = await axios.get('/api/categories');
                if (response.data && response.data.data) {
                    this.categories = response.data.data;
                }
            } catch (error) {
                console.error('Failed to load categories:', error);
                // Categories are optional, don't show error to user
                this.categories = [];
            }
        },

        // Load Products
        async loadProducts(page = 1) {
            this.loading = true;
            this.error = null;

            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                    sort: this.sortBy
                });

                if (this.search) params.append('search', this.search);
                if (this.selectedCategory) params.append('category_id', this.selectedCategory);

                const response = await axios.get(`/api/products?${params}`);

                if (response.data.message === "Products retrieved successfully") {
                    // Process products data - add missing fields
                    this.products = response.data.data.map(product => ({
                        ...product,
                        total_stock: product.variants.reduce((sum, variant) => sum + variant.stock_quantity, 0),
                        variants: product.variants.map(variant => ({
                            ...variant,
                            available_stock: variant.stock_quantity
                        }))
                    }));

                    // Handle pagination
                    if (response.data.pagination) {
                        this.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            per_page: response.data.pagination.per_page,
                            total: response.data.pagination.total
                        };
                    } else {
                        // Default pagination when no pagination data
                        this.pagination = {
                            current_page: 1,
                            last_page: 1,
                            per_page: response.data.data.length,
                            total: response.data.data.length
                        };
                    }

                    // Scroll to top on page change
                    if (page !== 1) {
                        this.scrollToTop();
                    }
                } else {
                    throw new Error(response.data.message || 'Failed to load products');
                }
            } catch (error) {
                console.error('Error loading products:', error);
                this.error = error.response?.data?.message || error.message || 'Failed to load products';
            } finally {
                this.loading = false;
            }
        },

        // Clear Filters
        clearFilters() {
            this.search = '';
            this.selectedCategory = '';
            this.sortBy = 'name';
            this.loadProducts(1);
        },

        // Scroll to Top
        scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // Pagination Helper
        get paginationPages() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;

            // Always show first page
            if (last > 0) pages.push(1);

            // Show current page and surrounding pages
            for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
                if (!pages.includes(i)) pages.push(i);
            }

            // Always show last page if more than 1 page
            if (last > 1 && !pages.includes(last)) pages.push(last);

            return pages.sort((a, b) => a - b);
        },

        // Image Helper Functions
        getProductImage(product) {
            // Priority: photos -> image_url -> default
            if (product.photos && product.photos.length > 0) {
                return product.photos[0].url;
            }

            if (product.image_url) {
                return product.image_url;
            }

            // Default fallback
            return null;
        },

        handleImageError(event) {
            // Set fallback image when loading fails
            event.target.style.display = 'none';
            const parent = event.target.parentElement;
            if (parent && !parent.querySelector('.fallback-icon')) {
                const fallback = document.createElement('div');
                fallback.className = 'w-full h-full flex items-center justify-center fallback-icon';
                fallback.innerHTML = '<i class="fas fa-fire text-4xl text-gray-400"></i>';
                parent.appendChild(fallback);
            }
        }
    }
}
</script>

@endsection