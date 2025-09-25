@extends('layouts.app')

@section('title', 'My Orders - BellGas')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="customerOrders()" x-init="init()">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">My Orders</h1>
                <p class="text-gray-600">Track your gas bottle orders and downloads</p>
            </div>
            
            <div class="flex space-x-3">
                <select x-model="statusFilter" @change="filterOrders()" 
                        class="border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">All Orders</option>
                    <option value="PENDING">Pending Payment</option>
                    <option value="PAID">Paid</option>
                    <option value="PROCESSED">Being Processed</option>
                    <option value="WAITING_FOR_PICKUP">Ready for Pickup</option>
                    <option value="PICKED_UP">Picked Up</option>
                    <option value="ON_DELIVERY">Out for Delivery</option>
                    <option value="DONE">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
                
                <button @click="refreshOrders()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>

        <!-- Order Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.total"></p>
                        <p class="text-gray-600 text-sm">Total Orders</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.pending"></p>
                        <p class="text-gray-600 text-sm">Pending Orders</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.completed"></p>
                        <p class="text-gray-600 text-sm">Completed</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800">$<span x-text="stats.totalSpent"></span></p>
                        <p class="text-gray-600 text-sm">Total Spent</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders List -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Order History</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="order in filteredOrders" :key="order.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900" x-text="order.order_number"></div>
                                        <div class="text-sm text-gray-500" x-text="order.fulfillment_method"></div>
                                        <div class="text-xs text-gray-400" x-text="order.items?.length + ' items'"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(order.created_at)"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="getStatusColor(order.status)"
                                          x-text="order.status"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<span x-text="order.total_aud"></span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button @click="viewOrder(order)" 
                                            class="text-blue-600 hover:text-blue-900 px-3 py-1 border border-blue-600 rounded text-xs">
                                        <i class="fas fa-eye mr-1"></i>Details
                                    </button>
                                    
                                    <!-- Download Receipt Button (for PAID and completed orders) -->
                                    <button x-show="['PAID', 'PROCESSED', 'WAITING_FOR_PICKUP', 'PICKED_UP', 'ON_DELIVERY', 'DONE'].includes(order.status)"
                                            @click="downloadReceipt(order)"
                                            class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition">
                                        <i class="fas fa-receipt mr-1"></i>Receipt
                                    </button>
                                    
                                    <!-- Reorder Button (for completed orders) -->
                                    <button x-show="order.status === 'DONE'" 
                                            @click="reorderItems(order)"
                                            class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">
                                        <i class="fas fa-redo mr-1"></i>Reorder
                                    </button>

                                    <!-- Cancel Button (for pending/paid orders) -->
                                    <button x-show="['PENDING', 'PAID'].includes(order.status)" 
                                            @click="cancelOrder(order)"
                                            class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition">
                                        <i class="fas fa-times mr-1"></i>Cancel
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div x-show="filteredOrders.length === 0" class="p-12 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>No orders found</p>
                    <a href="/products" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                        <i class="fas fa-plus mr-1"></i>Start Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div x-show="showOrderModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-96 overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Order Details</h3>
                    <button @click="showOrderModal = false" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div x-show="selectedOrder" class="p-6">
                <!-- Order details content -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-semibold mb-2">Order Information</h4>
                        <p class="text-sm"><strong>Order #:</strong> <span x-text="selectedOrder?.order_number"></span></p>
                        <p class="text-sm"><strong>Status:</strong> <span x-text="selectedOrder?.status"></span></p>
                        <p class="text-sm"><strong>Date:</strong> <span x-text="formatDate(selectedOrder?.created_at)"></span></p>
                        <p class="text-sm"><strong>Total:</strong> $<span x-text="selectedOrder?.total_aud"></span></p>
                        <p class="text-sm"><strong>Fulfillment:</strong> <span x-text="selectedOrder?.fulfillment_method"></span></p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-2">Delivery Details</h4>
                        <div class="text-sm" x-text="getDisplayAddress(selectedOrder)"></div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="mb-6">
                    <h4 class="font-semibold mb-2">Items Ordered</h4>
                    <div class="space-y-2">
                        <template x-for="item in selectedOrder?.items" :key="item.id">
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                                <div>
                                    <span class="font-medium" x-text="item.productVariant?.product?.name"></span>
                                    <span class="text-gray-500">- <span x-text="item.productVariant?.name"></span></span>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm">Qty: <span x-text="item.quantity"></span></div>
                                    <div class="font-medium">$<span x-text="item.total_price_aud"></span></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button @click="showOrderModal = false" 
                            class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 transition">
                        Close
                    </button>
                    <button x-show="['PAID', 'PROCESSED', 'WAITING_FOR_PICKUP', 'PICKED_UP', 'ON_DELIVERY', 'DONE'].includes(selectedOrder?.status)"
                            @click="downloadReceipt(selectedOrder)"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                        <i class="fas fa-receipt mr-2"></i>Download Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function customerOrders() {
    return {
        orders: [],
        filteredOrders: [],
        statusFilter: '',
        stats: {
            total: 0,
            pending: 0,
            completed: 0,
            totalSpent: 0
        },
        showOrderModal: false,
        selectedOrder: null,
        
        async init() {
            await this.loadOrders();
            this.startRealTimeUpdates();
        },
        
        async loadOrders() {
            try {
                // Get auth token
                const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
                
                let headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                };

                // Add authorization header if token exists
                if (token) {
                    headers['Authorization'] = `Bearer ${token}`;
                }

                const response = await fetch('/web/orders', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                this.orders = data.data?.data || [];
                this.filteredOrders = this.orders;
                this.calculateStats();
                
            } catch (error) {
                console.error('Failed to load orders:', error);
                this.showNotification('Failed to load orders', 'error');
            }
        },
        
        async refreshOrders() {
            await this.loadOrders();
            this.showNotification('Orders refreshed successfully', 'success');
        },
        
        filterOrders() {
            if (this.statusFilter) {
                this.filteredOrders = this.orders.filter(order => 
                    order.status === this.statusFilter
                );
            } else {
                this.filteredOrders = this.orders;
            }
        },
        
        calculateStats() {
            this.stats = {
                total: this.orders.length,
                pending: this.orders.filter(o => ['PENDING', 'PAID', 'PROCESSED', 'WAITING_FOR_PICKUP', 'PICKED_UP', 'ON_DELIVERY'].includes(o.status)).length,
                completed: this.orders.filter(o => o.status === 'DONE').length,
                totalSpent: this.orders
                    .filter(o => ['PAID', 'PROCESSED', 'WAITING_FOR_PICKUP', 'PICKED_UP', 'ON_DELIVERY', 'DONE'].includes(o.status))
                    .reduce((sum, order) => sum + parseFloat(order.total_aud), 0)
                    .toFixed(2)
            };
        },
        
        viewOrder(order) {
            this.selectedOrder = order;
            this.showOrderModal = true;
        },
        
        async downloadReceipt(order) {
            try {
                console.log('🔔 Downloading receipt for order:', order.order_number);
                this.showNotification('Preparing receipt download...', 'info');
                
                const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
                let response;
                
                // First try with JWT token if available
                if (token) {
                    console.log('🔑 Trying with JWT token...');
                    response = await fetch(`/api/receipts/order/${order.order_number}/pdf`, {
                        method: 'GET',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/pdf',
                            'Content-Type': 'application/json'
                        }
                    });
                }
                
                // If JWT fails or no token, try with session auth
                if (!response || !response.ok) {
                    console.log('🔄 Trying with session authentication...');
                    response = await fetch(`/web/receipts/order/${order.order_number}/pdf`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/pdf',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                }

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `BellGas_Receipt_${order.order_number}.pdf`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
                
                this.showNotification(`Receipt for order ${order.order_number} downloaded successfully!`, 'success');

            } catch (error) {
                console.error('Failed to download receipt:', error);
                this.showNotification('Failed to download receipt: ' + error.message, 'error');
            }
        },

        async reorderItems(order) {
            try {
                const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
                
                const response = await fetch(`/api/orders/${order.order_number}/reorder`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    this.showNotification('Items added to cart successfully!', 'success');
                    // Optionally redirect to cart
                    window.location.href = '/cart';
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Failed to reorder:', error);
                this.showNotification('Failed to reorder items', 'error');
            }
        },

        async cancelOrder(order) {
            if (!confirm(`Are you sure you want to cancel order ${order.order_number}?`)) {
                return;
            }

            try {
                const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
                
                const response = await fetch(`/api/orders/${order.order_number}/cancel`, {
                    method: 'PATCH',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    order.status = 'CANCELLED';
                    this.calculateStats();
                    this.showNotification('Order cancelled successfully', 'success');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Failed to cancel order:', error);
                this.showNotification('Failed to cancel order', 'error');
            }
        },
        
        getStatusColor(status) {
            const colors = {
                'PENDING': 'bg-red-100 text-red-800',
                'PAID': 'bg-blue-100 text-blue-800',
                'PROCESSED': 'bg-yellow-100 text-yellow-800',
                'WAITING_FOR_PICKUP': 'bg-purple-100 text-purple-800',
                'PICKED_UP': 'bg-indigo-100 text-indigo-800',
                'ON_DELIVERY': 'bg-orange-100 text-orange-800',
                'DONE': 'bg-green-100 text-green-800',
                'CANCELLED': 'bg-gray-100 text-gray-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        },
        
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-AU', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        getDisplayAddress(order) {
            if (!order) return '';
            
            if (order.fulfillment_method === 'PICKUP') {
                return 'Pickup at BellGas Store, 123 Main St, Melbourne VIC 3000';
            }
            
            if (order.address) {
                return `${order.address.street_address}, ${order.address.suburb} ${order.address.state} ${order.address.postcode}`;
            }
            
            return 'Address not specified';
        },

        showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600';
            
            notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-sm transform transition-all duration-500 translate-x-full`;
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <div>
                        <div class="font-semibold">${type.charAt(0).toUpperCase() + type.slice(1)}</div>
                        <div class="text-sm">${message}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Slide in animation
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => notification.remove(), 500);
                }
            }, 5000);
        },

        // ==================== REAL-TIME FUNCTIONALITY ====================

        startRealTimeUpdates() {
            console.log('🔄 Setting up WebSocket real-time updates for customer...');

            // Register this page instance globally for WebSocket callbacks
            window.customerOrdersPage = this;

            // Connect to WebSocket events through the global app instance
            if (window.app && window.app.echo) {
                console.log('✅ Using existing WebSocket connection for customer orders');
            } else {
                console.log('⚠️ WebSocket not available, falling back to polling');
                this.fallbackToPolling();
            }
        },

        // Fallback polling method if WebSocket is not available
        fallbackToPolling() {
            console.log('🔄 Starting fallback polling for customer...');
            this.lastUpdateTimestamp = new Date().toISOString();

            // Initial check
            this.checkForUpdates();

            // Set interval untuk polling setiap 15 detik (longer interval as fallback)
            setInterval(() => {
                this.checkForUpdates();
            }, 15000);
        },

        // Method called by WebSocket events from main app
        refreshFromWebSocket(event = null) {
            console.log('🔔 Refreshing customer orders from WebSocket event', event);

            // Show notification if event data is available
            if (event && event.order_number && event.new_status) {
                const statusText = event.new_status.replace(/_/g, ' ').toLowerCase()
                    .replace(/\b\w/g, l => l.toUpperCase());

                this.showNotification(
                    `Order ${event.order_number} status updated to: ${statusText}`,
                    'success'
                );
            }

            // Immediately refresh orders list and stats
            this.loadOrders();
            this.loadStats();
        },

        async checkForUpdates() {
            try {
                const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
                
                // Try with token first (JWT)
                let response;
                const queryParams = new URLSearchParams({
                    since: this.lastUpdateTimestamp
                });
                
                // Use web-based route with session authentication
                response = await fetch(`/web/realtime/customer-orders?${queryParams}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const data = await response.json();
                    
                    if (data.success) {
                        // Update timestamp
                        this.lastUpdateTimestamp = data.timestamp;
                        
                        // Update stats if provided
                        if (data.stats) {
                            this.stats = data.stats;
                        }
                        
                        // Process updates if any
                        if (data.has_updates && data.updates.length > 0) {
                            console.log(`🔔 Received ${data.updates.length} order updates`);
                            
                            // Process each update
                            data.updates.forEach(update => {
                                this.processCustomerUpdate(update);
                            });
                            
                            // Refresh orders list to get latest data
                            await this.loadOrders();
                        }
                    }
                }
                
            } catch (error) {
                console.error('❌ Customer real-time update error:', error);
                // Don't stop polling on errors, just log them
            }
        },

        processCustomerUpdate(update) {
            // Show visual notification
            this.showCustomerNotification(update);
            
            // Show browser notification if permission granted
            this.showBrowserNotification(update);
            
            // Log for debugging
            console.log('📝 Processing customer update:', update);
        },

        showCustomerNotification(update) {
            // Create notification element with better styling for customer
            const notification = document.createElement('div');
            const bgColor = this.getNotificationColor(update.priority);
            
            notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-sm transform transition-all duration-500 translate-x-full`;
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="text-2xl mr-3">${update.emoji}</div>
                    <div class="flex-1">
                        <div class="font-semibold">${update.message}</div>
                        <div class="text-sm opacity-90">${update.description}</div>
                        <div class="text-xs opacity-75 mt-1">Order: ${update.order_number}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Slide in animation
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove after 8 seconds (longer for customer to read)
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => notification.remove(), 500);
                }
            }, 8000);
        },

        getNotificationColor(priority) {
            switch (priority) {
                case 'high':
                    return 'bg-green-600';
                case 'medium':
                    return 'bg-blue-600';
                default:
                    return 'bg-gray-600';
            }
        },

        showBrowserNotification(update) {
            // Request permission if not granted
            if (Notification.permission === 'default') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        this.displayBrowserNotification(update);
                    }
                });
            } else if (Notification.permission === 'granted') {
                this.displayBrowserNotification(update);
            }
        },

        displayBrowserNotification(update) {
            try {
                const notification = new Notification(`BellGas - ${update.message}`, {
                    body: `${update.description}\nOrder: ${update.order_number}`,
                    icon: 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64">
                            <text y="50" font-size="50">${update.emoji}</text>
                        </svg>
                    `),
                    badge: '/favicon.ico',
                    tag: 'bellgas-customer-' + update.order_id,
                    requireInteraction: update.priority === 'high',
                    silent: false
                });

                // Auto close after 6 seconds unless high priority
                if (update.priority !== 'high') {
                    setTimeout(() => notification.close(), 6000);
                }

                // Handle notification click
                notification.onclick = () => {
                    window.focus();
                    notification.close();
                    console.log('Clicked on notification for order:', update.order_number);
                };
            } catch (e) {
                console.log('Browser notifications not supported:', e);
            }
        }
    }
}
</script>
@endsection