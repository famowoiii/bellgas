@extends('layouts.app')

@section('title', 'Checkout - BellGas')

@push('head-scripts')
<!-- Stripe.js - Only load on checkout page -->
<script src="https://js.stripe.com/v3/"></script>
<!-- Alpine.js cloak styling -->
<style>
[x-cloak] { display: none !important; }

/* Fallback untuk tombol jika Alpine.js gagal */
button[data-testid="place-order-btn"]:empty::after {
    content: "🛒 Place Order";
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

/* Pastikan tombol minimal punya tinggi */
button[data-testid="place-order-btn"] {
    min-height: 48px;
}
</style>
<!-- Set user data from backend -->
<script>
window.checkoutUser = @json($user ?? null);
window.isUserAuthenticated = {{ $user ? 'true' : 'false' }};
console.log('🔐 Checkout user data set:', window.checkoutUser);
console.log('🔐 Is authenticated:', window.isUserAuthenticated);
</script>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8" x-data="checkoutPage()" x-init="init()">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Checkout</h1>
        
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Checkout Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Login Check -->
                <div x-show="!user" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                        <div>
                            <h3 class="font-medium text-yellow-800">Please login to continue</h3>
                            <p class="text-sm text-yellow-600 mt-1">You need to be logged in to place an order.</p>
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-3">
                        <a href="/login" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition">Login</a>
                        <a href="/register" class="border border-blue-600 text-blue-600 px-4 py-2 rounded text-sm hover:bg-blue-50 transition">Sign Up</a>
                    </div>
                </div>

                <!-- Delivery Method -->
                <div x-show="user" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">Delivery Method</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center p-4 border-2 rounded-lg transition"
                               :class="{
                                   'border-blue-500 bg-blue-50': form.fulfillment_method === 'DELIVERY' && canDeliverAllItems,
                                   'border-gray-300 hover:border-gray-400 cursor-pointer': canDeliverAllItems,
                                   'border-gray-200 bg-gray-100 opacity-50 cursor-not-allowed': !canDeliverAllItems
                               }">
                            <input x-model="form.fulfillment_method" 
                                   type="radio" 
                                   value="DELIVERY" 
                                   class="sr-only" 
                                   :disabled="!canDeliverAllItems">
                            <div class="flex-1">
                                <div class="flex items-center justify-center mb-2">
                                    <i class="fas fa-truck text-2xl" 
                                       :class="canDeliverAllItems ? 'text-blue-600' : 'text-gray-400'"></i>
                                </div>
                                <h4 class="font-medium text-center" 
                                    :class="canDeliverAllItems ? 'text-gray-900' : 'text-gray-500'">Delivery</h4>
                                <p class="text-sm text-center" 
                                   :class="canDeliverAllItems ? 'text-gray-500' : 'text-gray-400'">We'll deliver to your address</p>
                                <div x-show="!canDeliverAllItems" class="mt-2">
                                    <p class="text-xs text-red-600 text-center font-medium">Not available for refill items</p>
                                </div>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition"
                               :class="form.fulfillment_method === 'PICKUP' ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'"
                               data-testid="pickup-method">
                            <input x-model="form.fulfillment_method" type="radio" value="PICKUP" class="sr-only">
                            <div class="flex-1">
                                <div class="flex items-center justify-center mb-2">
                                    <i class="fas fa-store text-2xl text-green-600"></i>
                                </div>
                                <h4 class="font-medium text-center">Pickup</h4>
                                <p class="text-sm text-gray-500 text-center">Collect from our location</p>
                            </div>
                        </label>
                    </div>
                    
                    <!-- Delivery Restriction Warning -->
                    <div x-show="hasRefillItems" class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-amber-500"></i>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-amber-800">Delivery Restriction</h4>
                                <p class="text-sm text-amber-700 mt-1" x-text="deliveryRestrictionMessage"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div x-show="user && form.fulfillment_method === 'DELIVERY'" class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Delivery Address</h3>
                        <button @click="showAddressForm = true"
                                class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-plus mr-1"></i>Add New Address
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div x-show="addressesLoading" class="flex items-center justify-center py-6">
                        <div class="flex items-center space-x-3 text-gray-600">
                            <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent"></div>
                            <span>Loading your saved addresses...</span>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div x-show="addressesError && !addressesLoading" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center space-x-2 text-red-800">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span class="font-medium">Failed to load addresses</span>
                        </div>
                        <p class="text-red-600 text-sm mt-1" x-text="addressesError"></p>
                        <button @click="loadAddresses()"
                                class="mt-3 px-3 py-1 bg-red-100 hover:bg-red-200 text-red-800 rounded text-sm transition-colors">
                            <i class="fas fa-redo mr-1"></i>Retry Loading
                        </button>
                    </div>

                    <!-- Address Selection -->
                    <div x-show="addresses.length > 0" class="space-y-3">
                        <template x-for="address in addresses" :key="address.id">
                            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition"
                                   :class="form.address_id === address.id ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'">
                                <input x-model="form.address_id" :value="address.id" type="radio" class="mt-1 mr-3">
                                <div class="flex-1">
                                    <h4 class="font-medium" x-text="address.name"></h4>
                                    <p class="text-sm text-gray-600" x-text="address.full_address"></p>
                                    <p x-show="address.delivery_instructions" 
                                       class="text-xs text-gray-500 mt-1" 
                                       x-text="'Instructions: ' + address.delivery_instructions"></p>
                                </div>
                            </label>
                        </template>
                    </div>
                    
                    <!-- No addresses message -->
                    <div x-show="addresses.length === 0" class="text-center py-6 text-gray-500">
                        <i class="fas fa-map-marker-alt text-3xl mb-3"></i>
                        <p>No delivery addresses found.</p>
                        <button @click="showAddressForm = true" 
                                class="mt-3 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                            Add Your First Address
                        </button>
                    </div>

                    <!-- Add Address Form -->
                    <div x-show="showAddressForm" class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-medium mb-4">Add New Address</h4>
                        <form @submit.prevent="addAddress()" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <input x-model="newAddress.name" type="text" placeholder="Address Name (e.g., Home)" 
                                       required class="col-span-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <select x-model="newAddress.type" required class="col-span-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Type</option>
                                    <option value="HOME">Home</option>
                                    <option value="WORK">Work</option>
                                    <option value="OTHER">Other</option>
                                </select>
                                <input x-model="newAddress.street_address" type="text" placeholder="Street Address" 
                                       required class="col-span-2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <input x-model="newAddress.suburb" type="text" placeholder="Suburb" 
                                       required class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <input x-model="newAddress.postcode" type="text" placeholder="Postcode" 
                                       required class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <select x-model="newAddress.state" required class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select State</option>
                                    <option value="NSW">NSW</option>
                                    <option value="VIC">VIC</option>
                                    <option value="QLD">QLD</option>
                                    <option value="SA">SA</option>
                                    <option value="WA">WA</option>
                                    <option value="TAS">TAS</option>
                                    <option value="NT">NT</option>
                                    <option value="ACT">ACT</option>
                                </select>
                                <input x-model="newAddress.country" type="text" placeholder="Country" value="Australia" 
                                       readonly class="px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                                <textarea x-model="newAddress.delivery_instructions" placeholder="Delivery Instructions (Optional)" 
                                          rows="2" class="col-span-2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" @click="showAddressForm = false" 
                                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        :disabled="addingAddress"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 min-h-[40px]">
                                    <div x-show="addingAddress" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                                    <i x-show="!addingAddress" class="fas fa-plus"></i>
                                    <span x-show="addingAddress">Adding...</span>
                                    <span x-show="!addingAddress">Add Address</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Order Notes -->
                <div x-show="user" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">Order Notes (Optional)</h3>
                    <textarea x-model="form.customer_notes"
                              rows="3"
                              placeholder="Any special instructions for your order..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                              data-testid="customer-notes"></textarea>
                </div>

                <!-- Payment Method -->
                <div x-show="user" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">Payment Method</h3>
                    <div class="p-4 border-2 border-blue-500 bg-blue-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-credit-card text-2xl text-blue-600 mr-4"></i>
                            <div>
                                <h4 class="font-medium">Secure Card Payment</h4>
                                <p class="text-sm text-gray-600">Powered by Stripe - Your payment information is secure</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                    <h3 class="text-lg font-semibold mb-4">Order Summary</h3>
                    
                    <!-- Existing Order Header -->
                    <div x-show="currentOrder" class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-blue-800">Existing Order</h4>
                                <p class="text-sm text-blue-600" x-text="'#' + currentOrder?.order_number"></p>
                            </div>
                            <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded"
                                  x-show="currentOrder?.status === 'UNPAID'">UNPAID</span>
                        </div>
                    </div>

                    <!-- Cart Items -->
                    <div class="space-y-3 mb-6">
                        <!-- Show existing order items if available -->
                        <template x-show="currentOrder && currentOrder.items" x-for="item in currentOrder.items" :key="item.id">
                            <div class="flex items-center space-x-3 py-2 border-b border-gray-100">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-fire text-blue-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-sm" x-text="item.product_variant?.product?.name || 'Product'"></h4>
                                    <p class="text-xs text-gray-500" x-text="item.product_variant?.name"></p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium" x-text="'$' + parseFloat(item.unit_price_aud || 0).toFixed(2)"></div>
                                    <div class="text-xs text-gray-500" x-text="'Qty: ' + item.quantity"></div>
                                </div>
                            </div>
                        </template>

                        <!-- Show cart items if no existing order -->
                        <template x-show="!currentOrder" x-for="item in cart" :key="item.id">
                            <div class="flex items-center space-x-3 py-2 border-b border-gray-100" data-testid="checkout-cart-item">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-fire text-blue-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-sm" x-text="item.productVariant?.product?.name || 'Unknown Product'"></h4>
                                    <p class="text-xs text-gray-500" x-text="item.productVariant?.name || 'N/A'"></p>
                                    <p class="text-xs text-gray-500">Qty: <span x-text="item.quantity || 0"></span></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-sm">$<span x-text="(item.total || 0).toFixed(2)"></span></p>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Debug info -->
                    <div x-show="true" class="mb-4 p-3 bg-gray-100 rounded text-sm" data-testid="debug-info">
                        <strong>Debug Info:</strong><br>
                        Window.isAuthenticated: <span x-text="window.isAuthenticated" data-testid="is-authenticated"></span><br>
                        Window.app exists: <span x-text="!!window.app"></span><br>
                        Window.app.user: <span x-text="!!window.app?.user"></span><br>
                        Window.app.cartItems: <span x-text="!!window.app?.cartItems"></span><br>
                        Parent cartItems count: <span x-text="window.app?.cartItems?.length || 'N/A'"></span><br>
                        Window.app.cart: <span x-text="!!window.app?.cart"></span><br>
                        Parent cart count: <span x-text="window.app?.cart?.length || 'N/A'"></span><br>
                        Local cart count: <span x-text="localCart.length"></span><br>
                        Final cart count: <span x-text="cart.length" data-testid="final-cart-count"></span><br>
                        User logged in: <span x-text="!!user"></span><br>
                        User email: <span x-text="user?.email || 'No user'"></span><br>
                        Can place order: <span x-text="canPlaceOrder" data-testid="can-place-order"></span><br>
                        Show Place Order Button: <span x-text="user && cart.length > 0"></span><br>
                        Form items: <span x-text="form.items.length"></span><br>
                        <button @click="forceReloadCart()"
                                class="mt-2 bg-blue-500 text-white px-3 py-1 rounded text-xs"
                                data-testid="force-reload-cart">
                            🔄 Force Reload Cart
                        </button>
                    </div>

                    <!-- Empty cart message -->
                    <div x-show="cart.length === 0" class="text-center py-6 text-gray-500">
                        <i class="fas fa-shopping-cart text-3xl mb-3"></i>
                        <p>Your cart is empty</p>
                        <a href="/products" class="mt-3 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                            Shop Now
                        </a>
                    </div>

                    <!-- Totals -->
                    <div x-show="cart.length > 0" class="space-y-2 pt-4 border-t">
                        <div class="flex justify-between text-sm">
                            <span>Subtotal:</span>
                            <span>$<span x-text="subtotal"></span></span>
                        </div>
                        <div x-show="form.fulfillment_method === 'DELIVERY'" class="flex justify-between text-sm">
                            <span>Delivery:</span>
                            <span x-text="shippingCost > 0 ? '$' + shippingCost : 'Calculating...'"></span>
                        </div>
                        <div class="flex justify-between font-bold text-lg pt-2 border-t">
                            <span>Total:</span>
                            <span data-testid="order-total">$<span x-text="total"></span></span>
                        </div>
                    </div>

                    <!-- Place Order Button - Always Visible -->
                    <button @click="placeOrder()"
                            class="w-full mt-6 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            data-testid="place-order-btn">
                        <i class="fas fa-credit-card mr-2"></i>
                        <span>Place Order - $</span><span x-text="total">0.00</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stripe Payment Modal -->
    <div x-show="showPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Complete Payment</h3>
                <button @click="cancelPayment()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mb-4 p-3 bg-gray-50 rounded">
                <p class="text-sm text-gray-600">Order Total: <span class="font-bold">$<span x-text="total"></span></span></p>
            </div>
            
            <!-- Stripe Elements will be mounted here -->
            <div id="card-element" class="mb-4 p-3 border border-gray-300 rounded"></div>
            <div id="card-errors" class="text-red-600 text-sm mb-4"></div>
            
            <button @click="confirmPayment()" 
                    :disabled="!stripe || paymentProcessing"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition disabled:opacity-50">
                <span x-show="!paymentProcessing">Confirm Payment</span>
                <span x-show="paymentProcessing">Processing...</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function checkoutPage() {
    return {
        addresses: [],
        addressesLoading: false,
        addressesError: null,
        showAddressForm: false,
        newAddress: {
            name: '',
            type: '',
            street_address: '',
            suburb: '',
            state: '',
            postcode: '',
            country: 'Australia',
            delivery_instructions: ''
        },
        form: {
            fulfillment_method: 'PICKUP',
            address_id: null,
            customer_notes: '',
            items: []
        },
        shippingCost: 0,
        showPaymentModal: false,
        stripe: null,
        cardElement: null,
        paymentProcessing: false,
        currentOrder: null,
        addingAddress: false,
        localCart: [],
        
        get user() {
            // Primary source: User data passed from backend controller
            if (window.checkoutUser) {
                console.log('🔐 Using checkout user data:', window.checkoutUser);
                return window.checkoutUser;
            }
            
            // Secondary: JWT user data (for API authentication)
            if (window.app && window.app.user) {
                console.log('🔐 Using app user data:', window.app.user);
                return window.app.user;
            }
            
            // Tertiary: Meta tag user data (from layout)
            if (window.isAuthenticated === true) {
                try {
                    const userElement = document.querySelector('meta[name="user-data"]');
                    if (userElement && userElement.content) {
                        const userData = JSON.parse(userElement.content);
                        console.log('🔐 Using meta user data:', userData);
                        return userData;
                    }
                } catch (error) {
                    console.warn('Failed to parse user meta data:', error);
                }
            }
            
            // Fallback: Local storage cached data
            try {
                const cachedUserData = localStorage.getItem('user_data');
                if (cachedUserData) {
                    const userData = JSON.parse(cachedUserData);
                    console.log('🔐 Using cached user data:', userData);
                    return userData;
                }
            } catch (error) {
                console.warn('Failed to parse cached user data:', error);
            }
            
            console.log('🔐 No user data found');
            return null;
        },

        async forceReloadCart() {
            console.log('🔄 Manual cart reload requested...');

            // Try to reload via different methods
            if (window.app && window.app.loadCart) {
                try {
                    await window.app.loadCart(true);
                    this.localCart = window.app.cartItems || [];
                    console.log('✅ Parent cart reloaded:', this.localCart.length);
                } catch (error) {
                    console.warn('⚠️ Parent cart reload failed:', error);
                }
            }

            // Also try direct API call
            try {
                await this.loadCartDirect();
            } catch (error) {
                console.warn('⚠️ Direct cart reload failed:', error);
            }

            // Update form items
            this.updateOrderItems();
        },
        
        onCartUpdated() {
            console.log('📢 Cart updated notification received');
            this.localCart = window.app.cartItems || [];
            this.updateOrderItems();
        },

        setupCartWatcher() {
            // Watch for cart changes via custom events
            window.addEventListener('cartUpdated', (event) => {
                console.log('🔔 cartUpdated event received:', event.detail);
                this.localCart = event.detail.items || [];
                this.updateOrderItems();
            });

            // Also set up periodic check for cart changes
            setInterval(() => {
                if (window.app && window.app.cartItems) {
                    const currentCartString = JSON.stringify(window.app.cartItems);
                    const localCartString = JSON.stringify(this.localCart);

                    if (currentCartString !== localCartString) {
                        console.log('🔄 Cart change detected via polling');
                        this.localCart = window.app.cartItems;
                        this.updateOrderItems();
                    }
                }
            }, 2000); // Check every 2 seconds
        },

        async init() {
            console.log('🛒 Checkout page initializing...');

            // Register this instance globally for cart updates
            window.checkoutPage = this;

            // Set up cart watcher
            this.setupCartWatcher();
            // Check if we have an existing order to pay for
            const urlParams = new URLSearchParams(window.location.search);
            const orderNumber = urlParams.get('order');
            
            if (orderNumber) {
                console.log('💳 Loading existing order for payment:', orderNumber);
                await this.loadExistingOrder(orderNumber);
            } else {
                // Parallel loading for better performance
                console.log('🚀 Starting parallel initialization...');
                console.log('🔍 Authentication debug:', {
                    'window.isAuthenticated': window.isAuthenticated,
                    'window.app': !!window.app,
                    'window.app.user': !!window.app?.user,
                    'meta user-data': !!document.querySelector('meta[name="user-data"]'),
                    'current user getter result': !!this.user,
                    'user email': this.user?.email
                });
                
                const startTime = Date.now();
                
                // Start all operations in parallel
                const authPromise = this.waitForAuthEnhanced();
                const cartPromise = this.loadCartOptimized();
                
                // Wait for both auth and cart
                await Promise.all([authPromise, cartPromise]);

                // Force reload cart if empty to ensure latest data
                if (window.app && window.app.loadCart) {
                    console.log('🔄 Force reloading parent app cart for checkout...');
                    try {
                        await window.app.loadCart(true); // force refresh
                        console.log('✅ Parent cart reloaded, items:', window.app.cartItems?.length || 0);

                        // Also update local cart reference
                        if (window.app.cartItems && window.app.cartItems.length > 0) {
                            this.localCart = window.app.cartItems;
                        }
                    } catch (error) {
                        console.warn('⚠️ Failed to reload parent cart:', error);
                    }
                }
                
                const loadTime = Date.now() - startTime;
                console.log(`✅ Parallel initialization completed in ${loadTime}ms`);
                
                // Setup Stripe if authenticated and skip address loading for now
                if (this.user) {
                    this.setupStripe(); // This is synchronous
                    // Skip address loading temporarily due to timeout issues
                    console.log('⚠️ Address loading disabled temporarily - user can add new address');
                    this.addressesLoading = false;
                    this.addressesError = 'Address loading temporarily disabled. Please add a new address below.';
                }
                
                this.updateOrderItems();
            }

            // Listen for cart changes from parent app
            if (window.app) {
                // Watch for cart changes
                const originalLoadCart = window.app.loadCart;
                window.app.loadCart = async function(force) {
                    const result = await originalLoadCart.call(this, force);
                    // Notify checkout page of cart update
                    if (window.checkoutPage) {
                        window.checkoutPage.onCartUpdated();
                    }
                    return result;
                };
            }
        },
        
        async waitForAuthEnhanced() {
            console.log('⏳ Enhanced authentication check started...');
            const startTime = Date.now();
            
            // First check if we already have user data
            if (this.user) {
                console.log('✅ User already available:', this.user.email);
                return;
            }
            
            // Check for JWT token first
            const token = localStorage.getItem('access_token') || 
                         localStorage.getItem('jwt_token') || 
                         localStorage.getItem('frontend_token');
            
            if (!token) {
                console.log('❌ No JWT token found - user not authenticated');
                const loadTime = Date.now() - startTime;
                console.log(`⏱️ Auth check completed in ${loadTime}ms`);
                return;
            }
            
            console.log('🔑 JWT token found, optimized app initialization...');
            
            // Reduced wait time with faster intervals
            const maxWaitTime = 5000; // Max 5 seconds (reduced from 10)
            const checkInterval = 50; // Check every 50ms (reduced from 100ms)
            const maxAttempts = Math.floor(maxWaitTime / checkInterval);
            
            for (let i = 0; i < maxAttempts; i++) {
                if (window.app) {
                    if (window.app.user) {
                        const loadTime = Date.now() - startTime;
                        console.log(`✅ Authentication ready in ${loadTime}ms`);
                        return;
                    }
                    
                    // Trigger auth check less frequently
                    if (i % 20 === 0 && window.app.checkAuth) {
                        console.log('🔄 Triggering auth check...');
                        try {
                            window.app.checkAuth(); // Don't await to avoid blocking
                        } catch (error) {
                            console.warn('Background auth check failed:', error);
                        }
                    }
                }
                
                await new Promise(resolve => setTimeout(resolve, checkInterval));
            }
            
            const loadTime = Date.now() - startTime;
            console.warn(`⚠️ Authentication timeout after ${loadTime}ms - checking fallback sources`);
            
            // Last resort: check if we have cached user data
            if (this.user) {
                console.log('✅ Found user via fallback method');
            } else {
                console.warn('❌ No user found after all attempts');
            }
        },
        
        async loadCartOptimized() {
            console.log('🚀 Optimized cart loading started...');
            const startTime = Date.now();
            
            // Strategy 1: Check if cart already exists
            if (window.app && Array.isArray(window.app.cart) && window.app.cart.length > 0) {
                console.log('✅ Cart already available:', window.app.cart.length);
                return;
            }
            
            // Strategy 2: Load cart directly from API (fastest)
            try {
                console.log('📡 Loading cart directly from API...');
                const response = await axios.get('/api/cart');
                if (response.data.success) {
                    const cartData = response.data.data.items || [];
                    console.log('✅ Direct API cart load successful:', cartData.length);
                    
                    // Update both local and parent app cart
                    this.localCart = cartData;
                    if (window.app) {
                        window.app.cart = cartData;
                    }
                    
                    const loadTime = Date.now() - startTime;
                    console.log(`🚀 Cart loaded in ${loadTime}ms`);
                    return;
                }
            } catch (error) {
                console.warn('⚠️ Direct API cart load failed:', error.message);
            }
            
            // Strategy 3: Wait for parent app with reduced timeout
            console.log('🔄 Falling back to parent app cart...');
            const maxWaitTime = 3000; // Max 3 seconds
            const checkInterval = 50; // Check every 50ms for faster response
            const maxAttempts = Math.floor(maxWaitTime / checkInterval);
            
            for (let attempts = 0; attempts < maxAttempts; attempts++) {
                if (window.app) {
                    if (Array.isArray(window.app.cart)) {
                        console.log('✅ Parent app cart found:', window.app.cart.length);
                        const loadTime = Date.now() - startTime;
                        console.log(`🚀 Cart loaded via parent in ${loadTime}ms`);
                        return;
                    }
                    
                    // Try to trigger cart load every 10 attempts (500ms)
                    if (attempts % 10 === 0 && window.app.loadCart) {
                        try {
                            window.app.loadCart(); // Don't await to avoid blocking
                        } catch (error) {
                            console.warn('Background cart load failed:', error);
                        }
                    }
                }
                
                await new Promise(resolve => setTimeout(resolve, checkInterval));
            }
            
            console.warn('⚠️ Cart loading timeout - continuing with empty cart');
            const loadTime = Date.now() - startTime;
            console.log(`⏱️ Total cart load time: ${loadTime}ms`);
        },
        
        async loadCartDirect() {
            try {
                console.log('📡 Loading cart directly from API...');
                const startTime = Date.now();
                
                const response = await axios.get('/api/cart');
                
                if (response.data.success) {
                    const cartData = response.data.data.items || [];
                    const loadTime = Date.now() - startTime;
                    console.log(`✅ Cart loaded directly in ${loadTime}ms:`, cartData.length, 'items');
                    
                    // Set cart in parent app if available
                    if (window.app) {
                        window.app.cart = cartData;
                        console.log('✅ Cart set in parent app');
                    }
                    
                    // Also create local reference
                    this.localCart = cartData;
                } else {
                    console.warn('⚠️ Cart API response not successful');
                }
            } catch (error) {
                console.error('❌ Failed to load cart directly:', error);
            }
        },
        
        async loadExistingOrder(orderNumber) {
            try {
                console.log('📡 Loading existing order:', orderNumber);
                const response = await axios.get(`/api/orders/${orderNumber}`);
                
                if (response.data.success) {
                    this.currentOrder = response.data.data;
                    console.log('✅ Existing order loaded:', this.currentOrder);
                    
                    // Set up form with order data
                    this.form.fulfillment_method = this.currentOrder.fulfillment_method || 'DELIVERY';
                    this.form.address_id = this.currentOrder.address_id;
                    this.form.customer_notes = this.currentOrder.customer_notes || '';
                    
                    // Set up items from order
                    this.form.items = this.currentOrder.items || [];
                    
                    // Setup Stripe and skip address loading
                    if (this.user) {
                        this.setupStripe();
                        // Skip address loading temporarily
                        console.log('⚠️ Address loading disabled for existing orders');
                        this.addressesLoading = false;
                    }
                    
                    // Create payment intent for UNPAID orders
                    if (this.currentOrder.status === 'UNPAID') {
                        await this.createPaymentIntentForExistingOrder();
                    }
                } else {
                    console.error('❌ Failed to load order:', response.data.message);
                    if (window.app?.showNotification) {
                        window.app.showNotification('Order not found', 'error');
                    }
                    // Redirect back to orders page
                    window.location.href = '/orders';
                }
            } catch (error) {
                console.error('❌ Error loading existing order:', error);
                if (window.app?.showNotification) {
                    window.app.showNotification('Failed to load order', 'error');
                }
                window.location.href = '/orders';
            }
        },

        async createPaymentIntentForExistingOrder() {
            try {
                console.log('💳 Creating payment intent for existing order:', this.currentOrder.order_number);
                
                // Create payment intent using existing order data
                const paymentData = {
                    order_number: this.currentOrder.order_number,
                    amount: Math.round(parseFloat(this.currentOrder.total_aud || 0) * 100), // Convert to cents
                    currency: 'aud'
                };
                
                const response = await axios.post('/api/checkout/create-payment-intent-for-order', paymentData);
                
                if (response.data.clientSecret) {
                    this.clientSecret = response.data.clientSecret;
                    console.log('✅ Payment intent created for existing order');
                    
                    // Show payment modal
                    setTimeout(() => {
                        this.showPaymentModal = true;
                        // Mount card element safely
                        this.$nextTick(() => {
                            this.mountCardElement();
                        });
                    }, 1000);
                } else {
                    throw new Error('No client secret received');
                }
            } catch (error) {
                console.error('❌ Error creating payment intent for existing order:', error);
                if (window.app?.showNotification) {
                    window.app.showNotification('Failed to initialize payment', 'error');
                }
            }
        },

        async loadAddresses() {
            console.log('🏠 Loading delivery addresses...');
            this.addressesLoading = true;
            this.addressesError = null;

            try {
                const startTime = Date.now();

                // Try web route first (session-based)
                let response;
                try {
                    response = await axios.get('/web/addresses', {
                        timeout: 5000, // 5 second timeout
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                } catch (webError) {
                    console.warn('⚠️ Web addresses failed, trying API route:', webError.message);

                    // Fallback to API route with JWT
                    const token = localStorage.getItem('access_token') || localStorage.getItem('jwt_token');
                    if (token) {
                        response = await axios.get('/api/addresses', {
                            timeout: 5000,
                            headers: {
                                'Accept': 'application/json',
                                'Authorization': 'Bearer ' + token
                            }
                        });
                    } else {
                        throw webError; // Re-throw original error if no token
                    }
                }

                const loadTime = Date.now() - startTime;
                console.log(`✅ Addresses loaded in ${loadTime}ms`);

                this.addresses = response.data.data || [];
                console.log(`📍 Found ${this.addresses.length} saved addresses`);

                // Auto-select first address if none selected
                if (this.addresses.length > 0 && !this.form.address_id) {
                    this.form.address_id = this.addresses[0].id;
                    console.log(`✅ Auto-selected address: ${this.addresses[0].street_address.substring(0, 30)}...`);
                }

                this.addressesLoading = false;
            } catch (error) {
                console.error('❌ Failed to load addresses:', error);
                this.addressesError = 'Unable to load saved addresses. You can still add a new address below.';
                this.addressesLoading = false;

                // Show user-friendly notification
                if (window.app?.showNotification) {
                    window.app.showNotification('Could not load saved addresses. You can add a new address.', 'info');
                }
            }
        },
        
        async addAddress() {
            this.addingAddress = true;
            try {
                const response = await axios.post('/web/addresses', this.newAddress, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                this.addresses.push(response.data.data);
                this.form.address_id = response.data.data.id;
                this.showAddressForm = false;
                
                // Reset form
                this.newAddress = {
                    name: '',
                    type: '',
                    street_address: '',
                    suburb: '',
                    state: '',
                    postcode: '',
                    country: 'Australia',
                    delivery_instructions: ''
                };
                
                this.showNotification('Address added successfully!', 'success');
            } catch (error) {
                this.showNotification(
                    error.response?.data?.message || 'Failed to add address',
                    'error'
                );
            } finally {
                this.addingAddress = false;
            }
        },
        
        setupStripe() {
            this.stripe = Stripe('{{ config("services.stripe.public") }}');
            
            const elements = this.stripe.elements();
            this.cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#424770',
                        '::placeholder': {
                            color: '#aab7c4',
                        },
                    },
                    invalid: {
                        color: '#dc3545',
                    }
                },
            });
            
            // Listen for real-time validation errors on the card element
            this.cardElement.on('change', function(event) {
                const displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });
        },
        
        updateOrderItems() {
            // Get cart using the getter which handles all fallbacks
            const cart = this.cart;

            // Safety check for cart items
            if (!cart || !Array.isArray(cart)) {
                console.warn('⚠️ Cart is not available or not an array:', cart);
                this.form.items = [];
                return;
            }

            console.log('📦 Updating order items from cart:', cart.length, 'items');

            this.form.items = cart.map(item => {
                // Safety check for item structure
                if (!item || !item.productVariant || !item.productVariant.id) {
                    console.error('❌ Invalid cart item structure:', item);
                    return null;
                }

                return {
                    product_variant_id: item.productVariant.id,
                    quantity: item.quantity
                };
            }).filter(item => item !== null); // Remove any null items

            // Auto-switch to pickup if cart has refill items and delivery is selected
            if (this.hasRefillItems && this.form.fulfillment_method === 'DELIVERY') {
                console.log('📍 Auto-switching to PICKUP due to refill items in cart');
                this.form.fulfillment_method = 'PICKUP';

                // Show notification to user
                if (window.app?.showNotification) {
                    window.app.showNotification(
                        'Switched to pickup: Refill products cannot be delivered',
                        'warning'
                    );
                }
            }

            console.log('✅ Updated form items:', this.form.items.length);
            console.log('📦 Cart items count:', cart.length);
        },
        
        async placeOrder() {
            if (!this.canPlaceOrder) return;
            
            this.loading = true;
            
            // Debug logging
            console.log('Placing order with data:', this.form);
            console.log('Cart contents:', this.cart);
            console.log('Can place order:', this.canPlaceOrder);
            
            try {
                const response = await axios.post('/api/checkout/create-payment-intent', this.form);
                
                this.currentOrder = response.data.order;
                const clientSecret = response.data.clientSecret;
                
                // Show payment modal
                this.showPaymentModal = true;
                
                // Mount card element
                this.$nextTick(() => {
                    this.cardElement.mount('#card-element');
                });
                
                // Store client secret for payment
                this.clientSecret = clientSecret;
                
            } catch (error) {
                console.error('Checkout error:', error);
                console.error('Error response:', error.response);
                
                let errorMessage = 'Failed to create order';
                
                if (error.response?.status === 422) {
                    // Validation errors
                    const errors = error.response.data.errors;
                    if (errors) {
                        errorMessage = Object.values(errors).flat().join(', ');
                    } else {
                        errorMessage = error.response.data.message || 'Validation failed';
                    }
                } else {
                    errorMessage = error.response?.data?.message || errorMessage;
                }
                
                this.showNotification(errorMessage, 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async confirmPayment() {
            if (!this.stripe || !this.cardElement) return;
            
            this.paymentProcessing = true;
            
            const {error, paymentIntent} = await this.stripe.confirmCardPayment(this.clientSecret, {
                payment_method: {
                    card: this.cardElement,
                    billing_details: {
                        name: `${this.user.first_name} ${this.user.last_name}`,
                        email: this.user.email,
                    },
                }
            });
            
            if (error) {
                this.paymentProcessing = false;
                document.getElementById('card-errors').textContent = error.message;
                return;
            }

            // Payment successful with Stripe, now confirm with our backend
            if (paymentIntent && paymentIntent.status === 'succeeded') {
                try {
                    console.log('Payment intent succeeded:', paymentIntent.id);
                    
                    // Call our backend to update order status
                    const response = await axios.post(`/api/orders/${this.currentOrder.order_number}/confirm-payment`, {
                        payment_intent_id: paymentIntent.id,
                        payment_method_id: paymentIntent.payment_method
                    }, {
                        headers: {
                            'Authorization': `Bearer ${this.getToken()}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });

                    if (response.data.success) {
                        console.log('Order status updated successfully');
                        
                        // Payment successful
                        this.showPaymentModal = false;
                        if (window.app) {
                            window.app.cart = []; // Clear cart
                        }
                        
                        this.showNotification('Payment successful! Order status updated. Thank you for your order.', 'success');
                        
                        // Redirect to order confirmation
                        setTimeout(() => {
                            window.location.href = `/orders/${this.currentOrder.order_number}`;
                        }, 2000);
                    } else {
                        console.error('Failed to update order status:', response.data.message);
                        this.showNotification('Payment was processed but there was an issue updating your order. Please contact support.', 'warning');
                        
                        // Still redirect to order page
                        setTimeout(() => {
                            window.location.href = `/orders/${this.currentOrder.order_number}`;
                        }, 3000);
                    }

                } catch (apiError) {
                    console.error('Failed to confirm payment with backend:', apiError);
                    this.showNotification('Payment was processed but there was an issue confirming your order. Please contact support.', 'warning');
                    
                    // Still redirect to order page as payment went through Stripe
                    setTimeout(() => {
                        window.location.href = `/orders/${this.currentOrder.order_number}`;
                    }, 3000);
                }
            }
            
            this.paymentProcessing = false;
        },
        
        cancelPayment() {
            this.showPaymentModal = false;
            if (this.cardElement) {
                this.cardElement.unmount();
            }
        },
        
        get subtotal() {
            if (this.currentOrder) {
                return parseFloat(this.currentOrder.subtotal_aud || 0).toFixed(2);
            }
            return this.cart.reduce((sum, item) => sum + parseFloat(item.total), 0).toFixed(2);
        },
        
        get total() {
            if (this.currentOrder) {
                return parseFloat(this.currentOrder.total_aud || 0).toFixed(2);
            }
            const sub = parseFloat(this.subtotal);
            const shipping = this.form.fulfillment_method === 'DELIVERY' ? this.shippingCost : 0;
            return (sub + shipping).toFixed(2);
        },
        
        get cart() {
            // Primary: use cartItems from parent app (actual property name)
            if (window.app && window.app.cartItems && Array.isArray(window.app.cartItems)) {
                console.log('📱 Using parent app cartItems:', window.app.cartItems.length);
                return window.app.cartItems;
            }

            // Secondary: try parent app cart property (getter)
            if (window.app && window.app.cart && Array.isArray(window.app.cart)) {
                console.log('📱 Using parent app cart getter:', window.app.cart.length);
                return window.app.cart;
            }

            // Tertiary: use local cart if loaded via API
            if (Array.isArray(this.localCart) && this.localCart.length > 0) {
                console.log('💾 Using local cart:', this.localCart.length);
                return this.localCart;
            }

            // Last resort: empty array
            console.log('📭 No cart data available');
            return [];
        },
        
        get canPlaceOrder() {
            return this.user &&
                   this.cart.length > 0 && 
                   (this.form.fulfillment_method === 'PICKUP' || 
                    (this.form.fulfillment_method === 'DELIVERY' && this.form.address_id && this.canDeliverAllItems));
        },
        
        get hasRefillItems() {
            return this.cart.some(item => 
                item.productVariant?.product?.category === 'REFILL'
            );
        },
        
        get canDeliverAllItems() {
            // All items must be FULL_TANK for delivery
            return !this.hasRefillItems;
        },
        
        get deliveryRestrictionMessage() {
            if (!this.hasRefillItems) return '';
            
            const refillItems = this.cart
                .filter(item => item.productVariant?.product?.category === 'REFILL')
                .map(item => `${item.productVariant?.product?.name || 'Unknown'} - ${item.productVariant?.name || 'Unknown'}`)
                .join(', ');
            
            return `Refill products can only be picked up at the store: ${refillItems}`;
        },

        getToken() {
            return localStorage.getItem('access_token') || localStorage.getItem('jwt_token') || '';
        },

        mountCardElement() {
            try {
                const cardElementContainer = document.getElementById('card-element');

                if (!cardElementContainer) {
                    console.error('❌ Card element container not found');
                    return;
                }

                if (!this.cardElement) {
                    console.error('❌ Card element not initialized');
                    return;
                }

                // Clear any existing content
                cardElementContainer.innerHTML = '';

                // Unmount existing element if attached
                try {
                    if (this.cardElement._attached) {
                        console.log('🔄 Unmounting existing card element');
                        this.cardElement.unmount();
                    }
                } catch (unmountError) {
                    console.warn('⚠️ Error unmounting card element:', unmountError);
                }

                // Mount fresh card element
                console.log('🔧 Mounting card element');
                this.cardElement.mount('#card-element');
                console.log('✅ Card element mounted successfully');

            } catch (error) {
                console.error('❌ Error mounting card element:', error);

                // Show user-friendly error
                const cardErrors = document.getElementById('card-errors');
                if (cardErrors) {
                    cardErrors.textContent = 'Failed to load payment form. Please refresh the page and try again.';
                }
            }
        }
    }
}
</script>
@endpush
@endsection