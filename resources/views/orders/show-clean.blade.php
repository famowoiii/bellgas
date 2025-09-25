@extends('layouts.app')

@section('title', 'Order Details - BellGas')

@section('content')
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <i class="fas fa-fire text-2xl text-orange-500"></i>
                        <span class="text-xl font-bold text-gray-800">BellGas</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/orders" class="text-gray-600 hover:text-gray-800">My Orders</a>
                    <a href="/logout" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="/orders" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                </a>
            </div>

            <!-- Loading State -->
            <div id="loading" class="text-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <p class="text-gray-600">Loading order details...</p>
            </div>

            <!-- Error State -->
            <div id="errorState" class="text-center py-12 hidden">
                <div class="text-red-600 mb-4">
                    <i class="fas fa-exclamation-circle text-6xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Order Not Found</h2>
                <p class="text-gray-600 mb-4">The order you're looking for doesn't exist or you don't have permission to view it.</p>
                <a href="/orders" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    View All Orders
                </a>
            </div>

            <!-- Order Content -->
            <div id="orderContent" class="space-y-6 hidden">
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-start">
                        <div>
                            <h1 id="orderNumber" class="text-3xl font-bold text-gray-800 mb-2"></h1>
                            <div class="flex items-center space-x-4 mb-4">
                                <span id="orderStatus" class="px-4 py-2 rounded-full text-sm font-medium"></span>
                                <span id="confirmedBadge" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium hidden">
                                    <i class="fas fa-check-circle mr-1"></i>Confirmed by Admin
                                </span>
                                <span id="paymentPendingBadge" class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-medium hidden">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Payment Required
                                </span>
                                <span class="text-gray-600">
                                    <i id="fulfillmentIcon" class="mr-2"></i>
                                    <span id="fulfillmentMethod"></span>
                                </span>
                            </div>
                            <p class="text-gray-600">
                                <i class="fas fa-calendar mr-2"></i>
                                Ordered on <span id="orderDate"></span>
                            </p>
                        </div>
                        
                        <div class="mt-4 md:mt-0 text-right">
                            <div class="text-3xl font-bold text-gray-800 mb-4">$<span id="orderTotal"></span></div>
                            <div class="space-y-2">
                                <!-- Pay Now Button for UNPAID orders -->
                                <button id="payNowBtn" onclick="payNow()" 
                                        class="bg-green-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-green-700 transition hidden">
                                    <i class="fas fa-credit-card mr-2"></i>Pay Now
                                </button>
                                
                                <button id="downloadReceiptBtn" onclick="downloadReceipt()" 
                                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 hidden">
                                    <i class="fas fa-receipt mr-2"></i>Download Receipt
                                </button>
                                
                                <div class="flex space-x-2">
                                    <button id="reorderBtn" onclick="reorderItems()" 
                                            class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 hidden">
                                        <i class="fas fa-redo mr-1"></i>Reorder
                                    </button>
                                    <button id="cancelBtn" onclick="cancelOrder()" 
                                            class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700 hidden">
                                        <i class="fas fa-times mr-1"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div id="addressSection" class="bg-white rounded-lg shadow-md p-6 hidden">
                    <h3 class="text-lg font-semibold mb-4">
                        <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>
                        Delivery Address
                    </h3>
                    <div id="addressContent" class="text-gray-700"></div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold">Order Items</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody id="orderItems" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Customer Notes -->
                <div id="customerNotesSection" class="bg-white rounded-lg shadow-md p-6 hidden">
                    <h3 class="text-lg font-semibold mb-4">
                        <i class="fas fa-sticky-note text-yellow-600 mr-2"></i>
                        Customer Notes
                    </h3>
                    <div class="p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                        <p id="customerNotes" class="text-sm text-yellow-800"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm hidden"></div>

    <script>
        // Global variables
        let currentOrder = null;
        const orderId = '{{ $id }}';
        let echo = null;

        // Authentication token management
        function getToken() {
            return localStorage.getItem('access_token') || localStorage.getItem('jwt_token') || '';
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');
            const colorClasses = {
                success: 'bg-green-500 text-white',
                error: 'bg-red-500 text-white', 
                warning: 'bg-yellow-500 text-white',
                info: 'bg-blue-500 text-white'
            };
            
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${colorClasses[type] || colorClasses.info}`;
            notification.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button class="ml-4 hover:opacity-75" onclick="hideNotification()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            notification.classList.remove('hidden');
            
            setTimeout(() => hideNotification(), 5000);
        }

        function hideNotification() {
            document.getElementById('notification').classList.add('hidden');
        }

        // Setup WebSocket connection - register with main app for notifications
        function setupWebSocket() {
            console.log('🔌 Registering order detail page for WebSocket notifications...');

            // Register this page as an order detail page for the specific order
            window.orderDetailPage = {
                orderId: orderId,
                handleOrderStatusUpdate: handleOrderStatusUpdate
            };

            console.log('✅ Registered order detail page for order:', orderId);
            console.log('🔔 Will receive notifications from main app WebSocket connection');
        }

        // Get current user ID from token or session
        function getCurrentUserId() {
            try {
                // Try to get from token payload
                const token = getToken();
                if (token) {
                    const payload = JSON.parse(atob(token.split('.')[1]));
                    return payload.sub || payload.user_id;
                }

                // Fallback to session data
                const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
                return userData.id;
            } catch (error) {
                console.warn('Could not get current user ID:', error);
                return null;
            }
        }

        // Handle real-time order status updates
        function handleOrderStatusUpdate(event) {
            console.log('🔄 Processing order status update:', event);

            // Check if this update is for the current order
            // Event data comes from OrderStatusUpdated broadcastWith() method
            if (event.order_number === orderId) {
                console.log(`✅ Status update for current order ${orderId}: ${event.previous_status} → ${event.new_status}`);

                // Immediately update the order status display
                updateOrderStatus(event.new_status);

                // Show notification with more detailed message
                const statusText = event.new_status.replace(/_/g, ' ').toLowerCase()
                    .replace(/\b\w/g, l => l.toUpperCase());

                showNotification(
                    `Order status updated to: ${statusText}`,
                    'success'
                );

                // Immediately refresh the complete order data to ensure all fields are current
                console.log('🔄 Immediately refreshing order data after status update...');
                loadOrder();

            } else {
                console.log(`ℹ️ Status update for different order (${event.order_number}), ignoring`);
            }
        }

        // Update order status display in real-time
        function updateOrderStatus(newStatus) {
            console.log(`🎯 Updating order status display to: ${newStatus}`);

            // Update status badge
            const statusEl = document.getElementById('orderStatus');
            if (statusEl) {
                statusEl.textContent = newStatus;
                statusEl.className = `px-4 py-2 rounded-full text-sm font-medium ${getStatusColor(newStatus)}`;
            }

            // Update badges visibility
            const confirmedBadge = document.getElementById('confirmedBadge');
            const paymentPendingBadge = document.getElementById('paymentPendingBadge');

            if (confirmedBadge) {
                if (['PROCESSING', 'READY_FOR_PICKUP', 'SHIPPED', 'DELIVERED'].includes(newStatus)) {
                    confirmedBadge.classList.remove('hidden');
                } else {
                    confirmedBadge.classList.add('hidden');
                }
            }

            if (paymentPendingBadge) {
                if (newStatus === 'UNPAID') {
                    paymentPendingBadge.classList.remove('hidden');
                } else {
                    paymentPendingBadge.classList.add('hidden');
                }
            }

            // Update action buttons
            updateActionButtons(newStatus);

            // Update current order object
            if (currentOrder) {
                currentOrder.status = newStatus;
            }
        }

        // Update action buttons based on status
        function updateActionButtons(status) {
            const payNowBtn = document.getElementById('payNowBtn');
            const downloadReceiptBtn = document.getElementById('downloadReceiptBtn');
            const reorderBtn = document.getElementById('reorderBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            // Hide all buttons first
            [payNowBtn, downloadReceiptBtn, reorderBtn, cancelBtn].forEach(btn => {
                if (btn) btn.classList.add('hidden');
            });

            // Show appropriate buttons based on status
            if (status === 'UNPAID' && payNowBtn) {
                payNowBtn.classList.remove('hidden');
            }

            if (['PAID', 'PROCESSING', 'DELIVERED'].includes(status) && downloadReceiptBtn) {
                downloadReceiptBtn.classList.remove('hidden');
            }

            if (status === 'DELIVERED' && reorderBtn) {
                reorderBtn.classList.remove('hidden');
            }

            if (['UNPAID', 'PAID'].includes(status) && cancelBtn) {
                cancelBtn.classList.remove('hidden');
            }
        }

        // Format date function
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-AU', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Get status color
        function getStatusColor(status) {
            const colors = {
                'UNPAID': 'bg-red-100 text-red-800',
                'PAID': 'bg-blue-100 text-blue-800',
                'PROCESSING': 'bg-yellow-100 text-yellow-800',
                'READY_FOR_PICKUP': 'bg-purple-100 text-purple-800',
                'SHIPPED': 'bg-purple-100 text-purple-800',
                'DELIVERED': 'bg-green-100 text-green-800',
                'CANCELLED': 'bg-gray-100 text-gray-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }

        // Load order data
        async function loadOrder() {
            try {
                console.log('Loading order:', orderId);
                
                const response = await axios.get(`/api/orders/${orderId}`, {
                    headers: {
                        'Authorization': `Bearer ${getToken()}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                console.log('Order response:', response.data);

                if (response.data.success) {
                    currentOrder = response.data.data;
                    displayOrder(currentOrder);
                } else {
                    showError();
                }
            } catch (error) {
                console.error('Error loading order:', error);
                showError();
            }
        }

        // Display order
        function displayOrder(order) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('orderContent').classList.remove('hidden');

            // Basic info
            document.getElementById('orderNumber').textContent = order.order_number || 'N/A';
            document.getElementById('orderTotal').textContent = order.total_aud || '0.00';
            document.getElementById('orderDate').textContent = formatDate(order.created_at);
            
            // Status
            const statusEl = document.getElementById('orderStatus');
            statusEl.textContent = order.status || 'UNKNOWN';
            statusEl.className = `px-4 py-2 rounded-full text-sm font-medium ${getStatusColor(order.status)}`;
            
            // Fulfillment
            const fulfillmentIcon = document.getElementById('fulfillmentIcon');
            fulfillmentIcon.className = order.fulfillment_method === 'DELIVERY' ? 'fas fa-truck mr-2' : 'fas fa-store mr-2';
            document.getElementById('fulfillmentMethod').textContent = order.fulfillment_method || 'N/A';

            // Show confirmed badge for orders that have been processed by admin
            if (['PROCESSING', 'READY_FOR_PICKUP', 'SHIPPED', 'DELIVERED'].includes(order.status)) {
                document.getElementById('confirmedBadge').classList.remove('hidden');
            }

            // Show payment pending badge and Pay Now button for UNPAID orders
            if (order.status === 'UNPAID') {
                document.getElementById('paymentPendingBadge').classList.remove('hidden');
                document.getElementById('payNowBtn').classList.remove('hidden');
            }

            // Show buttons based on status
            if (['PAID', 'PROCESSING', 'DELIVERED'].includes(order.status)) {
                document.getElementById('downloadReceiptBtn').classList.remove('hidden');
            }
            if (order.status === 'DELIVERED') {
                document.getElementById('reorderBtn').classList.remove('hidden');
            }
            if (['UNPAID', 'PAID'].includes(order.status)) {
                document.getElementById('cancelBtn').classList.remove('hidden');
            }

            // Address
            if (order.address && order.fulfillment_method === 'DELIVERY') {
                document.getElementById('addressSection').classList.remove('hidden');
                document.getElementById('addressContent').innerHTML = `
                    <p><strong>${order.address.name || 'N/A'}</strong></p>
                    <p>${order.address.street_address || 'N/A'}</p>
                    <p>${order.address.suburb || 'N/A'}, ${order.address.state || 'N/A'} ${order.address.postcode || 'N/A'}</p>
                `;
            }

            // Items
            const itemsContainer = document.getElementById('orderItems');
            itemsContainer.innerHTML = '';
            
            if (order.items && order.items.length > 0) {
                order.items.forEach(item => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50';
                    row.innerHTML = `
                        <td class="px-4 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-fire text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">${item.product_variant?.product?.name || 'Unknown Product'}</p>
                                    <p class="text-sm text-gray-500">${item.product_variant?.name || ''}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full text-sm font-medium">
                                ${item.quantity || 0}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-right font-medium">$${item.unit_price_aud || '0.00'}</td>
                        <td class="px-4 py-4 text-right font-bold">$${item.total_price_aud || '0.00'}</td>
                    </tr>
                    `;
                    itemsContainer.appendChild(row);
                });
            } else {
                itemsContainer.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No items found</td></tr>';
            }

            // Notes
            if (order.customer_notes) {
                document.getElementById('customerNotes').textContent = order.customer_notes;
                document.getElementById('customerNotesSection').classList.remove('hidden');
            }
        }

        function showError() {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('errorState').classList.remove('hidden');
        }

        // Order actions
        async function downloadReceipt() {
            if (!currentOrder) return;
            
            console.log('🔔 Downloading receipt for order:', currentOrder.order_number);
            showNotification('Preparing receipt download...', 'info');
            
            const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
            
            try {
                let response;
                
                // First try with JWT token if available
                if (token) {
                    console.log('🔑 Trying with JWT token...');
                    response = await fetch(`/api/receipts/order/${currentOrder.order_number}/pdf`, {
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
                    response = await fetch(`/web/receipts/order/${currentOrder.order_number}/pdf`, {
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
                link.download = `BellGas_Receipt_${currentOrder.order_number}.pdf`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
                showNotification('Receipt downloaded successfully!', 'success');

            } catch (error) {
                console.error('❌ Receipt download failed:', error);
                showNotification('Failed to download receipt. Please try again.', 'error');
            }
        }

        function reorderItems() {
            if (!currentOrder) return;
            showNotification('Reorder feature coming soon!', 'info');
        }

        function payNow() {
            if (!currentOrder) return;
            
            console.log('🔔 Redirecting to payment for order:', currentOrder.order_number);
            showNotification('Redirecting to payment...', 'info');
            
            // Redirect to checkout page with order parameter
            window.location.href = `/checkout?order=${currentOrder.order_number}`;
        }

        async function cancelOrder() {
            if (!currentOrder) return;
            
            if (!confirm(`Are you sure you want to cancel order ${currentOrder.order_number}?`)) {
                return;
            }
            
            try {
                const response = await axios.patch(`/api/orders/${currentOrder.order_number}/cancel`, {}, {
                    headers: {
                        'Authorization': `Bearer ${getToken()}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.data.success) {
                    currentOrder.status = 'CANCELLED';
                    showNotification('Order cancelled successfully', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification('Failed to cancel order', 'error');
                }
            } catch (error) {
                console.error('Cancel order error:', error);
                showNotification('Failed to cancel order', 'error');
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, initializing...');

            // Setup token from session if available
            @if(session('jwt_token'))
                localStorage.setItem('access_token', '{{ session('jwt_token') }}');
                console.log('Token set from session');
            @endif

            // Initialize WebSocket connection
            setupWebSocket();

            // Load order data
            loadOrder();
        });
    </script>
@endsection