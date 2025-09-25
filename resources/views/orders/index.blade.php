@extends('layouts.app')

@section('title', 'My Orders - BellGas')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="ordersPage()" x-init="init()">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">My Orders</h1>
                <p class="text-gray-600">Track and manage your LPG orders</p>
            </div>
            
            <div class="flex space-x-3">
                <button @click="loadOrders()" 
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
                <a href="/products" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>New Order
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="grid md:grid-cols-4 gap-4">
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select x-model="filters.status" @change="filterOrders()" 
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Orders</option>
                        <option value="UNPAID">Unpaid</option>
                        <option value="PAID">Paid</option>
                        <option value="PROCESSING">Processing</option>
                        <option value="SHIPPED">Shipped</option>
                        <option value="DELIVERED">Delivered</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>
                </div>

                <!-- Fulfillment Method Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
                    <select x-model="filters.fulfillment_method" @change="filterOrders()" 
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Methods</option>
                        <option value="DELIVERY">Delivery</option>
                        <option value="PICKUP">Pickup</option>
                    </select>
                </div>

                <!-- Date Range Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                    <select x-model="filters.date_range" @change="filterOrders()" 
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input x-model="filters.search" @input="debounceFilter()" 
                           type="text" placeholder="Order number..." 
                           class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Loading orders...</p>
        </div>

        <!-- Orders List -->
        <div x-show="!loading && filteredOrders.length > 0" class="space-y-4">
            <template x-for="order in filteredOrders" :key="order.id">
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition">
                    <div class="p-6">
                        <!-- Order Header -->
                        <div class="flex flex-col md:flex-row md:justify-between md:items-start mb-4">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h3 class="text-lg font-semibold" x-text="order.order_number"></h3>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium"
                                          :class="getStatusColor(order.status)"
                                          x-text="order.status"></span>
                                </div>
                                <div class="text-sm text-gray-600 space-y-1">
                                    <p><i class="fas fa-calendar mr-2"></i>Ordered: <span x-text="formatDate(order.created_at)"></span></p>
                                    <p><i :class="order.fulfillment_method === 'DELIVERY' ? 'fas fa-truck' : 'fas fa-store'" class="mr-2"></i><span x-text="order.fulfillment_method"></span></p>
                                    <p x-show="order.address"><i class="fas fa-map-marker-alt mr-2"></i><span x-text="order.address?.name || order.address?.full_address"></span></p>
                                </div>
                            </div>
                            
                            <div class="mt-4 md:mt-0 md:text-right">
                                <div class="text-2xl font-bold text-gray-800 mb-2">$<span x-text="order.total_aud"></span></div>
                                <div class="flex flex-col md:items-end space-y-2">
                                    <button @click="viewOrderDetails(order)" 
                                            class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition">
                                        View Details
                                    </button>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex space-x-2">
                                        <button x-show="order.status === 'PAID' || order.status === 'PROCESSING'" 
                                                @click="downloadReceipt(order.id)"
                                                class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition">
                                            <i class="fas fa-receipt mr-1"></i>Receipt
                                        </button>
                                        
                                        <button x-show="order.status === 'DELIVERED'" 
                                                @click="reorderItems(order)"
                                                class="bg-orange-600 text-white px-3 py-1 rounded text-xs hover:bg-orange-700 transition">
                                            <i class="fas fa-redo mr-1"></i>Reorder
                                        </button>
                                        
                                        <button x-show="['UNPAID', 'PAID'].includes(order.status)" 
                                                @click="cancelOrder(order)"
                                                class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition">
                                            <i class="fas fa-times mr-1"></i>Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items Summary -->
                        <div class="border-t pt-4">
                            <h4 class="font-medium text-gray-800 mb-3">Items (<span x-text="order.items?.length || 0"></span>)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <template x-for="item in (order.items || []).slice(0, 3)" :key="item.id">
                                    <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-fire text-blue-600"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-sm truncate" x-text="item.productVariant?.product?.name"></p>
                                            <p class="text-xs text-gray-500" x-text="item.productVariant?.name + ' × ' + item.quantity"></p>
                                            <p class="text-xs font-medium">$<span x-text="item.total_price_aud"></span></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div x-show="(order.items?.length || 0) > 3" class="mt-2 text-center">
                                <span class="text-sm text-gray-500">+<span x-text="(order.items?.length || 0) - 3"></span> more items</span>
                            </div>
                        </div>

                        <!-- Order Notes -->
                        <div x-show="order.customer_notes" class="border-t pt-4 mt-4">
                            <h5 class="font-medium text-gray-700 mb-2">Order Notes</h5>
                            <p class="text-sm text-gray-600" x-text="order.customer_notes"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && filteredOrders.length === 0" class="text-center py-12">
            <i class="fas fa-shopping-bag text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">
                <span x-show="filters.status || filters.search">No orders found matching your filters</span>
                <span x-show="!filters.status && !filters.search">No orders yet</span>
            </h3>
            <p class="text-gray-500 mb-6">
                <span x-show="filters.status || filters.search">Try adjusting your search criteria</span>
                <span x-show="!filters.status && !filters.search">Start shopping to see your orders here</span>
            </p>
            <div class="flex justify-center space-x-3">
                <button x-show="filters.status || filters.search" 
                        @click="clearFilters()" 
                        class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                    Clear Filters
                </button>
                <a href="/products" 
                   class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-shopping-cart mr-2"></i>Shop Now
                </a>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div x-show="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-96 overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold">Order Details</h3>
                    <button @click="showDetailsModal = false" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div x-show="selectedOrder" class="p-6">
                <!-- Order Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-semibold text-lg mb-3">Order Information</h4>
                        <div class="space-y-2 text-sm">
                            <p><strong>Order Number:</strong> <span x-text="selectedOrder?.order_number"></span></p>
                            <p><strong>Status:</strong> 
                                <span class="px-2 py-1 rounded-full text-xs font-medium"
                                      :class="getStatusColor(selectedOrder?.status)"
                                      x-text="selectedOrder?.status"></span>
                            </p>
                            <p><strong>Order Date:</strong> <span x-text="formatDate(selectedOrder?.created_at)"></span></p>
                            <p><strong>Fulfillment Method:</strong> <span x-text="selectedOrder?.fulfillment_method"></span></p>
                            <p><strong>Total Amount:</strong> <span class="text-lg font-bold">$<span x-text="selectedOrder?.total_aud"></span></span></p>
                        </div>
                    </div>

                    <div x-show="selectedOrder?.address">
                        <h4 class="font-semibold text-lg mb-3">Delivery Address</h4>
                        <div class="text-sm">
                            <p class="font-medium" x-text="selectedOrder?.address?.name"></p>
                            <p x-text="selectedOrder?.address?.street_address"></p>
                            <p><span x-text="selectedOrder?.address?.suburb"></span>, <span x-text="selectedOrder?.address?.state"></span> <span x-text="selectedOrder?.address?.postcode"></span></p>
                            <p x-text="selectedOrder?.address?.country"></p>
                            <div x-show="selectedOrder?.address?.delivery_instructions" class="mt-2 p-2 bg-yellow-50 rounded">
                                <p class="text-xs font-medium text-yellow-800">Delivery Instructions:</p>
                                <p class="text-xs text-yellow-700" x-text="selectedOrder?.address?.delivery_instructions"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="mb-6">
                    <h4 class="font-semibold text-lg mb-3">Order Items</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left">Product</th>
                                    <th class="px-4 py-2 text-center">Qty</th>
                                    <th class="px-4 py-2 text-right">Unit Price</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="item in selectedOrder?.items" :key="item.id">
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div>
                                                <p class="font-medium" x-text="item.productVariant?.product?.name"></p>
                                                <p class="text-xs text-gray-500" x-text="item.productVariant?.name"></p>
                                                <p class="text-xs text-gray-500"><span x-text="item.productVariant?.weight_kg"></span>kg</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center" x-text="item.quantity"></td>
                                        <td class="px-4 py-3 text-right">$<span x-text="item.unit_price_aud"></span></td>
                                        <td class="px-4 py-3 text-right font-medium">$<span x-text="item.total_price_aud"></span></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right font-medium">Subtotal:</td>
                                    <td class="px-4 py-3 text-right font-medium">$<span x-text="selectedOrder?.subtotal_aud"></span></td>
                                </tr>
                                <tr x-show="selectedOrder?.shipping_cost_aud > 0">
                                    <td colspan="3" class="px-4 py-3 text-right font-medium">Shipping:</td>
                                    <td class="px-4 py-3 text-right font-medium">$<span x-text="selectedOrder?.shipping_cost_aud"></span></td>
                                </tr>
                                <tr class="border-t-2 border-gray-300">
                                    <td colspan="3" class="px-4 py-3 text-right font-bold text-lg">Total:</td>
                                    <td class="px-4 py-3 text-right font-bold text-lg">$<span x-text="selectedOrder?.total_aud"></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Customer Notes -->
                <div x-show="selectedOrder?.customer_notes" class="mb-6">
                    <h4 class="font-semibold text-lg mb-3">Customer Notes</h4>
                    <div class="p-3 bg-gray-50 rounded">
                        <p class="text-sm" x-text="selectedOrder?.customer_notes"></p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3">
                    <button @click="showDetailsModal = false" 
                            class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 transition">
                        Close
                    </button>
                    <button x-show="selectedOrder && ['PAID', 'PROCESSING', 'DELIVERED'].includes(selectedOrder.status)" 
                            @click="downloadReceipt(selectedOrder.id)"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                        <i class="fas fa-receipt mr-2"></i>Download Receipt
                    </button>
                    <button x-show="selectedOrder?.status === 'DELIVERED'" 
                            @click="reorderItems(selectedOrder)"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        <i class="fas fa-redo mr-2"></i>Reorder
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ordersPage() {
    return {
        orders: [],
        filteredOrders: [],
        loading: true,
        showDetailsModal: false,
        selectedOrder: null,
        filters: {
            status: '',
            fulfillment_method: '',
            date_range: '',
            search: ''
        },
        filterTimeout: null,
        lastUpdateTimestamp: null,
        
        async init() {
            await this.loadOrders();
            this.startRealTimeUpdates();
        },
        
        async loadOrders() {
            this.loading = true;
            
            try {
                const response = await axios.get('/api/orders');
                this.orders = response.data.data || [];
                this.filteredOrders = this.orders;
                
            } catch (error) {
                console.error('Failed to load orders:', error);
                this.showNotification('Failed to load orders', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        filterOrders() {
            let filtered = [...this.orders];
            
            // Filter by status
            if (this.filters.status) {
                filtered = filtered.filter(order => order.status === this.filters.status);
            }
            
            // Filter by fulfillment method
            if (this.filters.fulfillment_method) {
                filtered = filtered.filter(order => order.fulfillment_method === this.filters.fulfillment_method);
            }
            
            // Filter by date range
            if (this.filters.date_range) {
                const now = new Date();
                const filterDate = new Date();
                
                switch (this.filters.date_range) {
                    case 'today':
                        filterDate.setHours(0, 0, 0, 0);
                        break;
                    case 'week':
                        filterDate.setDate(now.getDate() - 7);
                        break;
                    case 'month':
                        filterDate.setMonth(now.getMonth() - 1);
                        break;
                    case 'year':
                        filterDate.setFullYear(now.getFullYear() - 1);
                        break;
                }
                
                filtered = filtered.filter(order => 
                    new Date(order.created_at) >= filterDate
                );
            }
            
            // Filter by search
            if (this.filters.search) {
                filtered = filtered.filter(order => 
                    order.order_number.toLowerCase().includes(this.filters.search.toLowerCase())
                );
            }
            
            this.filteredOrders = filtered;
        },
        
        debounceFilter() {
            clearTimeout(this.filterTimeout);
            this.filterTimeout = setTimeout(() => {
                this.filterOrders();
            }, 300);
        },
        
        clearFilters() {
            this.filters = {
                status: '',
                fulfillment_method: '',
                date_range: '',
                search: ''
            };
            this.filteredOrders = this.orders;
        },
        
        viewOrderDetails(order) {
            this.selectedOrder = order;
            this.showDetailsModal = true;
        },
        
        async downloadReceipt(orderId) {
            try {
                const response = await axios.get(`/api/receipts/order/${orderId}`);
                
                // Create and download PDF (simplified - in real app you'd generate PDF)
                const receiptData = response.data.receipt;
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head><title>Receipt - ${receiptData.receipt_info.receipt_number}</title></head>
                        <body style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
                            <h1>${receiptData.business_info.name}</h1>
                            <h2>Receipt #${receiptData.receipt_info.receipt_number}</h2>
                            <p><strong>Order:</strong> ${receiptData.receipt_info.order_number}</p>
                            <p><strong>Date:</strong> ${receiptData.receipt_info.issued_at}</p>
                            <p><strong>Total:</strong> $${receiptData.order_details.pricing.total}</p>
                            <script>setTimeout(() => window.print(), 100);<\/script>
                        </body>
                    </html>
                `);
                printWindow.document.close();
                
                this.showNotification('Receipt opened in new window', 'success');
                
            } catch (error) {
                this.showNotification('Failed to download receipt', 'error');
            }
        },
        
        async reorderItems(order) {
            try {
                const response = await axios.post(`/api/orders/${order.id}/reorder`);
                
                this.showNotification('Items added to cart! Redirecting to checkout...', 'success');
                
                // Refresh cart and redirect
                await this.loadCart();
                
                setTimeout(() => {
                    window.location.href = '/checkout';
                }, 2000);
                
            } catch (error) {
                this.showNotification('Failed to reorder items', 'error');
            }
        },
        
        async cancelOrder(order) {
            if (!confirm(`Are you sure you want to cancel order ${order.order_number}?`)) {
                return;
            }
            
            try {
                await axios.patch(`/api/orders/${order.id}/cancel`);
                
                // Update order status locally
                order.status = 'CANCELLED';
                
                this.showNotification('Order cancelled successfully', 'success');
                
            } catch (error) {
                this.showNotification('Failed to cancel order', 'error');
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
            return new Date(dateString).toLocaleDateString('en-AU', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        // Real-time functionality for customer orders
        startRealTimeUpdates() {
            console.log('🔄 Starting real-time order updates for customer...');
            this.lastUpdateTimestamp = new Date().toISOString();
            
            // Poll for updates every 10 seconds (longer interval for customers)
            setInterval(() => {
                this.checkForOrderUpdates();
            }, 10000);
        },
        
        async checkForOrderUpdates() {
            try {
                const response = await axios.get(`/api/realtime/customer-orders`, {
                    params: {
                        since: this.lastUpdateTimestamp
                    }
                });
                
                if (response.data.success && response.data.updates.length > 0) {
                    console.log('📨 New order updates received:', response.data.updates);
                    
                    // Process each update
                    response.data.updates.forEach(update => {
                        this.processOrderUpdate(update);
                        this.showRealtimeNotification(update);
                    });
                    
                    // Refresh orders to get latest data
                    await this.loadOrders();
                }
                
                // Update timestamp
                this.lastUpdateTimestamp = response.data.timestamp;
                
            } catch (error) {
                console.error('❌ Failed to check for order updates:', error);
            }
        },
        
        processOrderUpdate(update) {
            // Find and update the specific order in our local data
            const orderIndex = this.orders.findIndex(order => order.id === update.order_id);
            if (orderIndex !== -1) {
                // Update order status
                this.orders[orderIndex].status = update.status;
                this.orders[orderIndex].updated_at = update.updated_at;
                
                // Re-apply filters
                this.filterOrders();
            }
        },
        
        showRealtimeNotification(update) {
            // Create and show notification
            const notification = document.createElement('div');
            notification.className = 'fixed top-20 right-4 bg-blue-600 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-sm transform transition-all duration-500 translate-x-full';
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-bell mr-3 text-lg"></i>
                    <div>
                        <p class="font-medium">Order Update</p>
                        <p class="text-sm opacity-90">${update.message}</p>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Slide in animation
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 500);
            }, 5000);
        },

        showNotification(message, type = 'info') {
            // Use parent app notification if available
            if (window.app && window.app.showNotification) {
                window.app.showNotification(message, type);
            } else {
                // Fallback to alert
                if (type === 'error') {
                    alert('Error: ' + message);
                } else {
                    alert(message);
                }
            }
        },
        
        async loadCart() {
            // Delegate to parent app if available
            if (window.app && window.app.loadCart) {
                await window.app.loadCart();
            }
        }
    }
}
</script>
@endsection