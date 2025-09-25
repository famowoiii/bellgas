@extends('layouts.app')

@section('title', 'My Orders - BellGas')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="simpleOrdersPage()" x-init="init()">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Orders</h1>
            <p class="text-gray-600">Track and manage your LPG orders</p>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-600">Loading orders...</span>
        </div>

        <!-- Orders List -->
        <div x-show="!loading">
            <div x-show="orders.length === 0" class="text-center py-12">
                <div class="text-gray-400 text-6xl mb-4">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No orders yet</h3>
                <p class="text-gray-500 mb-6">Start shopping to see your orders here</p>
                <a href="/products" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    Browse Products
                </a>
            </div>

            <div x-show="orders.length > 0" class="space-y-6">
                <template x-for="order in orders" :key="order.id">
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer"
                         @click="viewOrder(order)">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-blue-600 hover:text-blue-800" 
                                    x-text="'Order #' + order.order_number"></h3>
                                <p class="text-gray-600" x-text="'Placed on ' + formatDate(order.created_at)"></p>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 rounded-full text-sm font-medium"
                                      :class="getStatusColor(order.status)"
                                      x-text="order.status"></span>
                                <div class="mt-1 text-lg font-bold text-blue-600" 
                                     x-text="'$' + parseFloat(order.total_aud || order.total_amount || 0).toFixed(2)"></div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div x-show="order.items && order.items.length > 0" class="border-t pt-4">
                            <h4 class="font-medium text-gray-700 mb-2">Items:</h4>
                            <template x-for="item in order.items" :key="item.id">
                                <div class="flex justify-between text-sm text-gray-600 py-1">
                                    <span x-text="item.quantity + 'x ' + (item.product_variant?.product?.name || item.productVariant?.product?.name || 'Product')"></span>
                                    <span x-text="'$' + parseFloat(item.total_price_aud || item.price || 0).toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="mt-4 pt-4 border-t flex justify-between items-center">
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-mouse-pointer mr-1"></i>
                                Click to view details
                            </div>
                            <div class="flex space-x-2">
                                <span x-show="order.status === 'UNPAID'" 
                                      class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-credit-card mr-1"></i>
                                    Ready to Pay
                                </span>
                                <span x-show="order.status === 'PAID'" 
                                      class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-check mr-1"></i>
                                    Paid
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function simpleOrdersPage() {
    return {
        orders: [],
        loading: true,
        
        async init() {
            console.log('📦 Simple Orders Page Initializing...');
            
            // Refresh app state (auth + cart) first
            if (window.app && window.app.refreshState) {
                console.log('🔄 Refreshing app state...');
                await window.app.refreshState();
            } else {
                console.warn('⚠️ App refreshState not available');
            }
            
            await this.loadOrders();
        },
        
        async loadOrders() {
            this.loading = true;

            try {
                console.log('📡 Loading orders from API...');

                // Use the token from window.JWT_TOKEN set by the layout
                const token = window.JWT_TOKEN;
                console.log('🔥 Using window token:', token ? token.substring(0, 20) + '...' : 'EMPTY');

                if (!token) {
                    console.error('🚨 NO TOKEN AVAILABLE - Cannot load orders');
                    // Check if user is even logged in
                    if (!window.app || !window.app.isAuthenticated) {
                        console.error('🚨 User not authenticated, redirecting to login');
                        window.location.href = '/login';
                        return;
                    }
                    throw new Error('No authentication token available');
                }

                // Create fresh axios instance with forced headers
                const apiClient = axios.create({
                    baseURL: window.location.origin,
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                console.log('🔧 Created fresh axios client with token');
                const response = await apiClient.get('/api/orders');
                console.log('✅ Orders API response:', response.data);
                
                this.orders = response.data.data.data || response.data.data || [];
                console.log('📦 Orders loaded:', this.orders.length, 'orders');
                
            } catch (error) {
                console.error('❌ Failed to load orders:', error);
                
                // Show user-friendly message
                if (window.app && window.app.showNotification) {
                    window.app.showNotification('Failed to load orders. Please try refreshing the page.', 'error');
                } else {
                    alert('Failed to load orders. Please try refreshing the page.');
                }
            } finally {
                this.loading = false;
            }
        },
        
        getStatusColor(status) {
            const colors = {
                'UNPAID': 'bg-red-100 text-red-800',
                'PAID': 'bg-blue-100 text-blue-800',
                'PROCESSING': 'bg-yellow-100 text-yellow-800',
                'SHIPPED': 'bg-purple-100 text-purple-800',
                'DELIVERED': 'bg-green-100 text-green-800',
                'CANCELLED': 'bg-gray-100 text-gray-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        },
        
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            try {
                return new Date(dateString).toLocaleDateString('en-AU', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch (e) {
                return dateString;
            }
        },
        
        viewOrder(order) {
            console.log('🔍 Viewing order:', order.order_number, 'Status:', order.status);
            
            if (order.status === 'UNPAID') {
                // For unpaid orders, redirect to checkout/payment page
                console.log('💳 Redirecting to payment for unpaid order');
                if (window.app && window.app.showNotification) {
                    window.app.showNotification('Redirecting to payment...', 'info');
                }
                window.location.href = `/checkout?order=${order.order_number}`;
            } else {
                // For other orders, redirect to order details page
                console.log('📄 Redirecting to order details');
                window.location.href = `/orders/${order.order_number}`;
            }
        }
    }
}
</script>
@endsection