@extends('layouts.app')

@section('title', 'Shopping Cart - BellGas')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="cart()" x-init="init()">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Shopping Cart</h1>
            <p class="text-gray-600">Review your items before checkout</p>
        </div>

        <!-- Cart Items -->
        <div x-show="!loading" class="grid lg:grid-cols-3 gap-8">
            <!-- Cart Items List -->
            <div class="lg:col-span-2">
                <div x-show="items.length === 0" class="bg-white rounded-lg shadow-md p-12 text-center">
                    <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Your cart is empty</h3>
                    <p class="text-gray-500 mb-6">Add some products to get started</p>
                    <a href="/products" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        Continue Shopping
                    </a>
                </div>

                <div x-show="items.length > 0" class="space-y-4">
                    <template x-for="item in items" :key="item.id">
                        <div class="bg-white rounded-lg shadow-md p-6 flex items-center space-x-4">
                            <!-- Product Image -->
                            <div class="flex-shrink-0 w-20 h-20">
                                <img :src="item.productVariant.product?.photos?.[0]?.url || '/images/default-product.png'" 
                                     :alt="item.productVariant.product?.name"
                                     class="w-full h-full object-cover rounded-lg">
                            </div>

                            <!-- Product Details -->
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900" x-text="item.productVariant.product?.name"></h3>
                                <p class="text-sm text-gray-500" x-text="item.productVariant.name"></p>
                                <div class="mt-2">
                                    <span class="text-lg font-bold text-blue-600">$<span x-text="item.effective_price"></span></span>
                                    <span x-show="item.is_preorder" class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">
                                        Pre-order
                                    </span>
                                </div>
                                
                                <!-- Stock Status -->
                                <div x-show="!item.stock_available" class="mt-2">
                                    <span class="text-sm text-red-600">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Out of stock
                                    </span>
                                </div>
                                
                                <div x-show="item.reservation_expired" class="mt-2">
                                    <span class="text-sm text-orange-600">
                                        <i class="fas fa-clock mr-1"></i>
                                        Reservation expired
                                    </span>
                                </div>
                            </div>

                            <!-- Quantity Controls -->
                            <div class="flex items-center space-x-3">
                                <button @click="decrementQuantity(item)" 
                                        :disabled="item.quantity <= 1 || updating"
                                        class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                
                                <input type="number" 
                                       x-model.number="item.quantity"
                                       @change="updateQuantity(item)"
                                       min="1" max="10"
                                       class="w-16 text-center border border-gray-300 rounded px-2 py-1"
                                       :disabled="updating">
                                
                                <button @click="incrementQuantity(item)" 
                                        :disabled="item.quantity >= 10 || updating"
                                        class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>

                            <!-- Item Total -->
                            <div class="text-right">
                                <div class="text-lg font-bold text-gray-900">$<span x-text="item.total"></span></div>
                                <button @click="removeItem(item)" 
                                        class="text-sm text-red-600 hover:text-red-800 mt-2">
                                    <i class="fas fa-trash mr-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Cart Summary -->
            <div x-show="items.length > 0" class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h3 class="text-lg font-semibold mb-4">Order Summary</h3>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal (<span x-text="totalItems"></span> items)</span>
                            <span class="font-semibold">$<span x-text="subtotal"></span></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Delivery Fee</span>
                            <span class="font-semibold" x-text="deliveryFee > 0 ? '$' + deliveryFee : 'FREE'"></span>
                        </div>
                        
                        <div class="border-t pt-3">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span class="text-blue-600">$<span x-text="total"></span></span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <button @click="proceedToCheckout()" 
                                :disabled="!canProceedToCheckout || updating"
                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-credit-card mr-2"></i>
                            Proceed to Checkout
                        </button>
                        
                        <a href="/products" 
                           class="block w-full text-center border border-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 transition">
                            Continue Shopping
                        </a>
                        
                        <button @click="clearCart()" 
                                class="w-full text-red-600 hover:text-red-800 py-2 text-sm">
                            Clear Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex justify-center items-center py-12">
            <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
        </div>
    </div>
</div>

@push('scripts')
<script>
function cart() {
    return {
        loading: true,
        updating: false,
        items: [],
        subtotal: 0,
        deliveryFee: 9.95,
        freeDeliveryThreshold: 100,

        get totalItems() {
            return this.items.reduce((sum, item) => sum + item.quantity, 0);
        },

        get total() {
            const sub = parseFloat(this.subtotal);
            const delivery = sub >= this.freeDeliveryThreshold ? 0 : this.deliveryFee;
            return (sub + delivery).toFixed(2);
        },

        get canProceedToCheckout() {
            return this.items.length > 0 && this.items.every(item =>
                item.stock_available && !item.reservation_expired
            );
        },

        async init() {
            await this.loadCart();
        },

        async loadCart() {
            try {
                this.loading = true;
                const token = this.getAuthToken();

                if (!token) {
                    // Load guest cart from session/API if available
                    try {
                        const response = await axios.get('/api/cart');
                        if (response.data.success) {
                            this.items = response.data.data.items || [];
                            this.subtotal = parseFloat(response.data.data.total || 0).toFixed(2);
                        }
                    } catch (error) {
                        console.log('No guest cart available or API error');
                        this.items = [];
                        this.subtotal = 0;
                    }
                    return;
                }

                const response = await axios.get('/api/cart', {
                    headers: { Authorization: `Bearer ${token}` }
                });

                if (response.data.success) {
                    this.items = response.data.data.items || [];
                    this.subtotal = parseFloat(response.data.data.total || 0).toFixed(2);
                }
            } catch (error) {
                console.error('Failed to load cart:', error);
                if (error.response?.status === 401) {
                    // User not authenticated, redirect to login
                    window.location.href = '/login';
                } else {
                    this.showNotification('Failed to load cart', 'error');
                }
            } finally {
                this.loading = false;
            }
        },

        async incrementQuantity(item) {
            console.log('🔼 Increment quantity clicked for item:', item.id);
            if (item.quantity >= 10) return;

            const newQuantity = item.quantity + 1;
            console.log('🔼 New quantity will be:', newQuantity);
            await this.updateItemQuantity(item, newQuantity);
        },

        async decrementQuantity(item) {
            console.log('🔽 Decrement quantity clicked for item:', item.id);
            if (item.quantity <= 1) return;

            const newQuantity = item.quantity - 1;
            console.log('🔽 New quantity will be:', newQuantity);
            await this.updateItemQuantity(item, newQuantity);
        },

        async updateQuantity(item) {
            // Triggered when input value changes directly
            const quantity = Math.max(1, Math.min(10, parseInt(item.quantity) || 1));
            if (quantity !== item.quantity) {
                item.quantity = quantity; // Update the display immediately
                await this.updateItemQuantity(item, quantity);
            }
        },

        async updateItemQuantity(item, newQuantity) {
            console.log('🔄 updateItemQuantity called for item:', item.id, 'new quantity:', newQuantity);
            try {
                this.updating = true;
                const token = this.getAuthToken();
                console.log('🔐 Auth token available:', !!token);

                if (!token) {
                    console.error('❌ No auth token available');
                    this.showNotification('Please login to update cart', 'error');
                    return;
                }

                console.log('📤 Sending PUT request to /api/cart/' + item.id);
                const response = await axios.put(`/api/cart/${item.id}`, {
                    quantity: newQuantity
                }, {
                    headers: { Authorization: `Bearer ${token}` }
                });

                console.log('📥 Update response:', response.data);

                if (response.data.success) {
                    // Update the item in the local array
                    const updatedItem = response.data.data;
                    const index = this.items.findIndex(i => i.id === item.id);
                    console.log('🔄 Updating item at index:', index);
                    if (index !== -1) {
                        this.items[index].quantity = updatedItem.quantity;
                        this.items[index].total = updatedItem.total;
                        this.items[index].price = updatedItem.price;
                    }

                    // Recalculate subtotal
                    this.subtotal = this.items.reduce((sum, item) =>
                        sum + parseFloat(item.total), 0
                    ).toFixed(2);

                    this.showNotification('Cart updated', 'success');
                } else {
                    // Revert the change if API call failed
                    await this.loadCart();
                    this.showNotification(response.data.message || 'Failed to update cart', 'error');
                }
            } catch (error) {
                console.error('Failed to update cart item:', error);
                // Revert the change
                await this.loadCart();

                if (error.response?.status === 400) {
                    this.showNotification(error.response.data.message || 'Insufficient stock', 'error');
                } else {
                    this.showNotification('Failed to update cart', 'error');
                }
            } finally {
                this.updating = false;
            }
        },

        async removeItem(item) {
            console.log('🗑️ Remove item clicked for:', item.id);
            if (!confirm('Are you sure you want to remove this item?')) return;

            try {
                this.updating = true;
                const token = this.getAuthToken();
                console.log('🔐 Auth token for delete:', !!token);

                if (!token) {
                    console.error('❌ No auth token for delete');
                    this.showNotification('Please login to modify cart', 'error');
                    return;
                }

                console.log('📤 Sending DELETE request to /api/cart/' + item.id);
                const response = await axios.delete(`/api/cart/${item.id}`, {
                    headers: { Authorization: `Bearer ${token}` }
                });

                console.log('📥 Delete response:', response.data);

                if (response.data.success) {
                    console.log('✅ Item deleted successfully, removing from local array');
                    this.items = this.items.filter(i => i.id !== item.id);
                    this.subtotal = this.items.reduce((sum, item) =>
                        sum + parseFloat(item.total), 0
                    ).toFixed(2);
                    this.showNotification('Item removed from cart', 'success');
                }
            } catch (error) {
                console.error('Failed to remove item:', error);
                this.showNotification('Failed to remove item', 'error');
            } finally {
                this.updating = false;
            }
        },

        async clearCart() {
            if (!confirm('Are you sure you want to clear your cart?')) return;

            try {
                this.updating = true;
                const token = this.getAuthToken();

                if (!token) {
                    this.showNotification('Please login to clear cart', 'error');
                    return;
                }

                const response = await axios.delete('/api/cart', {
                    headers: { Authorization: `Bearer ${token}` }
                });

                if (response.data.success) {
                    this.items = [];
                    this.subtotal = 0;
                    this.showNotification('Cart cleared', 'success');
                }
            } catch (error) {
                console.error('Failed to clear cart:', error);
                this.showNotification('Failed to clear cart', 'error');
            } finally {
                this.updating = false;
            }
        },

        proceedToCheckout() {
            if (!this.canProceedToCheckout) {
                this.showNotification('Please resolve cart issues before checkout', 'error');
                return;
            }

            window.location.href = '/checkout';
        },

        getAuthToken() {
            return localStorage.getItem('access_token');
        },

        showNotification(message, type = 'info') {
            if (window.app && window.app.showNotification) {
                window.app.showNotification(message, type);
            } else {
                alert(message);
            }
        }
    }
}
</script>
@endpush
@endsection