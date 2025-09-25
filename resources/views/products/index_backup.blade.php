@extends('layouts.app')

@section('title', 'Products - BellGas')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="productsPage()" x-init="loadProducts()">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Our LPG Products</h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Quality LPG cylinders for all your home and business needs. Safe, reliable, and competitively priced.
        </p>
    </div>

    <!-- Filters and Search -->
    <div class="mb-8 bg-white rounded-lg shadow-md p-6">
        <div class="grid md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <div class="relative">
                    <input x-model="filters.search" 
                           @input="debounceSearch()"
                           type="text" 
                           placeholder="Search products..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- Category Filter -->
            <div>
                <select x-model="filters.category" 
                        @change="loadProducts()"
                        class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Categories</option>
                    <template x-for="category in categories" :key="category">
                        <option :value="category" x-text="category"></option>
                    </template>
                </select>
            </div>

            <!-- Sort -->
            <div>
                <select x-model="filters.sort" 
                        @change="loadProducts()"
                        class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Default Sort</option>
                    <option value="name_asc">Name A-Z</option>
                    <option value="name_desc">Name Z-A</option>
                    <option value="price_asc">Price Low-High</option>
                    <option value="price_desc">Price High-Low</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-600">Loading products...</p>
    </div>

    <!-- Products Grid -->
    <div x-show="!loading && products.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">
        <template x-for="product in products" :key="product.id">
            <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition transform hover:scale-105 overflow-hidden">
                <!-- Product Image -->
                <div class="h-48 bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center relative">
                    <i class="fas fa-fire text-6xl text-white opacity-80"></i>
                    <!-- Category Badge -->
                    <div class="absolute top-4 left-4">
                        <span class="bg-white text-blue-600 px-3 py-1 rounded-full text-xs font-semibold" 
                              x-text="product.category"></span>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="p-6">
                    <h3 class="text-xl font-semibold mb-2" x-text="product.name"></h3>
                    <p class="text-gray-600 mb-4 text-sm line-clamp-2" x-text="product.description"></p>

                    <!-- Variants -->
                    <div class="space-y-3 mb-6">
                        <template x-for="variant in product.variants?.filter(v => v.is_active)" :key="`product-${product.id}-variant-${variant.id}`">
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="flex-1">
                                    <h4 class="font-medium text-sm" x-text="variant.name"></h4>
                                    <p class="text-xs text-gray-500">
                                        <span x-text="variant.weight_kg"></span>kg
                                        <span x-show="variant.stock_quantity !== null" class="ml-2">
                                            • <span x-text="variant.available_stock"></span> available
                                        </span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-blue-600">$<span x-text="variant.price_aud"></span></div>
                                    <button @click="addToCart(variant.id, variant.name)"
                                            :disabled="variant.available_stock === 0 || addingToCartItems[variant.id] === true"
                                            class="mt-1 bg-blue-600 text-white px-4 py-2 rounded text-xs font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1 min-h-[32px]">
                                        <div x-show="addingToCartItems[variant.id]" class="animate-spin rounded-full h-3 w-3 border border-white border-t-transparent"></div>
                                        <i x-show="!addingToCartItems[variant.id]" class="fas fa-cart-plus"></i>
                                        <span x-show="addingToCartItems[variant.id]">Adding...</span>
                                        <span x-show="!addingToCartItems[variant.id]" x-text="variant.available_stock === 0 ? 'Out of Stock' : 'Add to Cart'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Product Details Link -->
                    <div class="pt-4 border-t">
                        <a :href="'/products/' + product.slug" 
                           class="text-blue-600 hover:text-blue-800 font-medium text-sm flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>View Details
                        </a>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && products.length === 0" class="text-center py-12">
        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No products found</h3>
        <p class="text-gray-500 mb-6">Try adjusting your search or filters</p>
        <button @click="clearFilters()" 
                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
            Clear Filters
        </button>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && pagination.total > pagination.per_page" 
         class="flex justify-center items-center space-x-4 mt-8">
        <button @click="loadPage(pagination.current_page - 1)" 
                :disabled="pagination.current_page <= 1"
                class="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition">
            <i class="fas fa-chevron-left mr-1"></i> Previous
        </button>
        
        <div class="flex items-center space-x-2">
            <template x-for="page in paginationPages" :key="page">
                <button @click="loadPage(page)" 
                        :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                        class="w-10 h-10 rounded-lg border transition"
                        x-text="page"></button>
            </template>
        </div>
        
        <button @click="loadPage(pagination.current_page + 1)" 
                :disabled="pagination.current_page >= pagination.last_page"
                class="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition">
            Next <i class="fas fa-chevron-right ml-1"></i>
        </button>
    </div>

    <!-- Quick Actions -->
    <div class="fixed bottom-6 right-6 z-40">
        <button @click="scrollToTop()" 
                class="bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition transform hover:scale-110">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>
</div>

<script>
function productsPage() {
    return {
        products: [],
        categories: [],
        loading: true,
        addingToCartItems: {}, // Track loading state per variant ID
        filters: {
            search: '',
            category: '',
            sort: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0
        },
        searchTimeout: null,
        
        async init() {
            // Initialize addingToCartItems as empty object
            this.addingToCartItems = {};
            await this.loadCategories();
            await this.loadProducts();
        },
        
        async loadCategories() {
            try {
                const response = await axios.get('/api/products/categories');
                this.categories = response.data.data;
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },
        
        async loadProducts(page = 1) {
            this.loading = true;
            
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page
                });
                
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.category) params.append('category', this.filters.category);
                if (this.filters.sort) params.append('sort', this.filters.sort);
                
                const response = await axios.get(`/api/products?${params}`);
                
                this.products = response.data.data;
                this.pagination = response.data.pagination;
                
            } catch (error) {
                console.error('Failed to load products:', error);
                this.showNotification('Failed to load products', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async loadPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                await this.loadProducts(page);
                this.scrollToTop();
            }
        },
        
        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadProducts(1);
            }, 500);
        },
        
        clearFilters() {
            this.filters = {
                search: '',
                category: '',
                sort: ''
            };
            this.loadProducts(1);
        },
        
        async addToCart(variantId, variantName) {
            console.log('🛒 Starting add to cart:', variantId, variantName);
            console.log('🔍 Auth check - window.app:', !!window.app);
            console.log('🔍 Auth check - isAuthenticated:', window.app?.isAuthenticated);

            // Check if user is authenticated first
            if (!window.app || !window.app.isAuthenticated) {
                console.log('❌ User not authenticated');
                if (window.app && window.app.showNotification) {
                    window.app.showNotification('Please login to add items to cart', 'error');
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                } else {
                    window.location.href = '/login';
                }
                return;
            }

            // Set per-item loading state (ensure object exists)
            if (!this.addingToCartItems) {
                this.addingToCartItems = {};
            }
            this.addingToCartItems = { ...this.addingToCartItems, [variantId]: true };

            try {
                // Always use direct API call for better control and immediate cart refresh
                console.log('📡 Adding to cart via direct API call');

                // Get JWT token
                const token = window.JWT_TOKEN;
                console.log('🔑 JWT token check:', !!token);
                if (!token) {
                    console.error('❌ No JWT token available');
                    console.error('💾 Available storage keys:', Object.keys(localStorage));
                    if (window.app && window.app.showNotification) {
                        window.app.showNotification('Authentication token not available', 'error');
                    }
                    return;
                }

                // Create API client with token
                const apiClient = axios.create({
                    baseURL: window.location.origin,
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                console.log('📤 Sending API request:', {
                    url: '/api/cart',
                    data: { product_variant_id: variantId, quantity: 1 }
                });

                const response = await apiClient.post('/api/cart', {
                    product_variant_id: variantId,
                    quantity: 1
                });

                console.log('📥 Full API response:', response);
                console.log('✅ Cart API response data:', response.data);
                console.log('📊 Response status:', response.status);

                // Show success notification
                console.log('🔔 Debug notification availability:');
                console.log('  - window.app exists:', !!window.app);
                console.log('  - window.app.showNotification exists:', !!window.app?.showNotification);
                console.log('  - window.app.showNotification type:', typeof window.app?.showNotification);

                const message = response.data.message || `${variantName} added to cart!`;
                console.log('🔔 Message to show:', message);

                if (window.app && typeof window.app.showNotification === 'function') {
                    console.log('✅ Using window.app.showNotification');
                    try {
                        window.app.showNotification(message, 'success');
                        console.log('✅ Notification sent successfully');
                    } catch (notifError) {
                        console.error('❌ Notification error:', notifError);
                        alert(message); // Fallback
                    }
                } else {
                    console.error('❌ FORCE window.app.showNotification not available');
                    alert(message);
                }

                // Debug cart update
                console.log('🛒 Debug cart update:');
                console.log('  - response.data.success:', response.data.success);
                console.log('  - response.data.data exists:', !!response.data.data);
                console.log('  - window.app exists:', !!window.app);
                console.log('  - window.app.cartItems exists:', !!window.app?.cartItems);
                console.log('  - current cartItems:', window.app?.cartItems);

                // Immediate cart update - langsung update cart tanpa reload
                if (response.data.success && response.data.data) {
                    const newCartItem = response.data.data;
                    console.log('➕ New cart item from API:', newCartItem);

                    // Update parent app cart directly if available
                    if (window.app && Array.isArray(window.app.cartItems)) {
                        console.log('🔄 Current cart before update:', window.app.cartItems.length, 'items');

                        // Add atau update item di cartItems
                        const existingIndex = window.app.cartItems.findIndex(
                            item => item.productVariant?.id === newCartItem.productVariant?.id
                        );

                        if (existingIndex >= 0) {
                            // Update existing item
                            window.app.cartItems[existingIndex] = newCartItem;
                            console.log('🔄 Updated existing cart item at index:', existingIndex);
                        } else {
                            // Add new item with reactive update
                            const newCartItems = [...window.app.cartItems, newCartItem];
                            window.app.cartItems = newCartItems;
                            console.log('➕ Added new cart item, total items:', window.app.cartItems.length);
                        }

                        // Force reactivity dengan nextTick
                        setTimeout(() => {
                            console.log('📊 Final cart count:', window.app.cartCount);
                            console.log('💰 Final cart total:', window.app.cartTotal);
                            console.log('🛒 Final cart items:', window.app.cartItems.length);
                        }, 100);
                    } else {
                        console.warn('⚠️ Cannot update cart directly - cartItems not available');
                    }
                } else {
                    console.warn('⚠️ No cart data to update from API response');
                }

                // Fallback: Force reload cart in parent app if direct update fails
                console.log('🔄 Checking cart reload function:', !!window.app?.loadCart);
                if (window.app && window.app.loadCart) {
                    console.log('🔄 Fallback: Starting cart reload in parent app');
                    try {
                        await window.app.loadCart();
                        console.log('✅ Fallback cart reload successful');
                    } catch (cartError) {
                        console.error('❌ Fallback cart reload failed:', cartError);
                    }
                } else {
                    console.error('❌ FORCE window.app.loadCart not available');
                }
            } catch (error) {
                console.error('❌ Error adding to cart:', error);
                let message = 'Failed to add item to cart';
                if (error.response && error.response.data && error.response.data.message) {
                    message = error.response.data.message;
                } else if (error.message) {
                    message = error.message;
                }

                if (window.app && window.app.showNotification) {
                    window.app.showNotification(message, 'error');
                } else {
                    alert(message);
                }
            } finally {
                // Reset per-item loading state
                if (this.addingToCartItems) {
                    this.addingToCartItems = { ...this.addingToCartItems, [variantId]: false };
                }
            }
        },
        
        scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        
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
        }
    }
}
</script>
@endsection