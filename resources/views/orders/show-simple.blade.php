@extends('layouts.app')

@section('title', 'Order Details - BellGas')

@section('content')
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

        <!-- Order Details -->
        <div id="orderContent" class="space-y-6 hidden">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-start">
                    <div>
                        <h1 id="orderNumber" class="text-3xl font-bold text-gray-800 mb-2"></h1>
                        <div class="flex items-center space-x-4 mb-4">
                            <span id="orderStatus" class="px-4 py-2 rounded-full text-sm font-medium"></span>
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
                            <button id="downloadReceiptBtn" onclick="downloadReceipt()" 
                                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition hidden">
                                <i class="fas fa-receipt mr-2"></i>Download Receipt
                            </button>
                            
                            <div class="flex space-x-2">
                                <button id="reorderBtn" onclick="reorderItems()" 
                                        class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition hidden">
                                    <i class="fas fa-redo mr-1"></i>Reorder
                                </button>
                                <button id="cancelBtn" onclick="cancelOrder()" 
                                        class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700 transition hidden">
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
                        <tbody id="orderItems" class="divide-y divide-gray-200">
                            <!-- Items will be populated here -->
                        </tbody>
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

        <!-- Error State -->
        <div id="errorState" class="text-center py-12 hidden">
            <div class="text-red-600 mb-4">
                <i class="fas fa-exclamation-circle text-6xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Order Not Found</h2>
            <p class="text-gray-600 mb-4">The order you're looking for doesn't exist or you don't have permission to view it.</p>
            <a href="/orders" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                View All Orders
            </a>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm hidden"></div>
</div>

<script>
let currentOrder = null;
const orderId = {{ $id }};

// Get authentication token
function getToken() {
    return localStorage.getItem('access_token');
}

// Format date
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

// Get status color classes
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

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    const colorClass = type === 'success' ? 'bg-green-500 text-white' :
                      type === 'error' ? 'bg-red-500 text-white' :
                      type === 'warning' ? 'bg-yellow-500 text-white' :
                      'bg-blue-500 text-white';
    
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${colorClass}`;
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

// Load order data
async function loadOrder() {
    try {
        const response = await axios.get(`/api/orders/${orderId}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

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

// Display order data
function displayOrder(order) {
    // Hide loading, show content
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('orderContent').classList.remove('hidden');

    // Header information
    document.getElementById('orderNumber').textContent = order.order_number;
    document.getElementById('orderTotal').textContent = order.total_aud;
    document.getElementById('orderDate').textContent = formatDate(order.created_at);
    
    // Status
    const statusElement = document.getElementById('orderStatus');
    statusElement.textContent = order.status;
    statusElement.className = `px-4 py-2 rounded-full text-sm font-medium ${getStatusColor(order.status)}`;
    
    // Fulfillment method
    const fulfillmentIcon = document.getElementById('fulfillmentIcon');
    fulfillmentIcon.className = order.fulfillment_method === 'DELIVERY' ? 'fas fa-truck mr-2' : 'fas fa-store mr-2';
    document.getElementById('fulfillmentMethod').textContent = order.fulfillment_method;

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

    // Address (for delivery orders)
    if (order.address && order.fulfillment_method === 'DELIVERY') {
        const addressSection = document.getElementById('addressSection');
        const addressContent = document.getElementById('addressContent');
        addressContent.innerHTML = `
            <p><strong>${order.address.first_name} ${order.address.last_name}</strong></p>
            <p>${order.address.street_address}</p>
            <p>${order.address.city}, ${order.address.state} ${order.address.postal_code}</p>
            <p>${order.address.phone_number}</p>
        `;
        addressSection.classList.remove('hidden');
    }

    // Order items
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
                            <p class="text-xs text-gray-400">
                                ${item.product_variant?.weight_kg || 0}kg • 
                                ${item.product_variant?.product?.category || 'Uncategorized'}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-4 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full text-sm font-medium">
                        ${item.quantity}
                    </span>
                </td>
                <td class="px-4 py-4 text-right font-medium">$${item.unit_price_aud}</td>
                <td class="px-4 py-4 text-right font-bold">$${item.total_price_aud}</td>
            </tr>
            `;
            itemsContainer.appendChild(row);
        });
    }

    // Customer notes
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
    try {
        const response = await axios.get(`/api/receipts/order/${currentOrder.order_number}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.data.success) {
            // Create and open receipt in new window
            const receiptData = response.data.data;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Receipt - ${receiptData.order_details.order_number}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .header { text-align: center; margin-bottom: 30px; }
                            .details { margin-bottom: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h1>BellGas Receipt</h1>
                            <p>Order: ${receiptData.order_details.order_number}</p>
                        </div>
                        <div class="details">
                            <p><strong>Date:</strong> ${receiptData.order_details.date}</p>
                            <p><strong>Status:</strong> ${receiptData.order_details.status}</p>
                            <p><strong>Customer:</strong> ${receiptData.customer_info.name}</p>
                            <p><strong>Email:</strong> ${receiptData.customer_info.email}</p>
                            <p><strong>Total:</strong> $${receiptData.order_details.pricing.total} ${receiptData.order_details.pricing.currency}</p>
                        </div>
                        <script>window.onload = function() { window.print(); }</script>
                    </body>
                </html>
            `);
            printWindow.document.close();
            showNotification('Receipt opened in new window', 'success');
        }
    } catch (error) {
        showNotification('Failed to download receipt', 'error');
    }
}

async function reorderItems() {
    try {
        const response = await axios.post(`/api/orders/${currentOrder.order_number}/reorder`, {}, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });
        
        showNotification('Items added to cart! Redirecting to checkout...', 'success');
        setTimeout(() => {
            window.location.href = '/checkout';
        }, 2000);
    } catch (error) {
        showNotification('Failed to reorder items', 'error');
    }
}

async function cancelOrder() {
    if (!confirm(`Are you sure you want to cancel order ${currentOrder.order_number}?`)) {
        return;
    }
    
    try {
        await axios.patch(`/api/orders/${currentOrder.order_number}/cancel`, {}, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });
        
        currentOrder.status = 'CANCELLED';
        showNotification('Order cancelled successfully', 'success');
        
        // Refresh the page to update status
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    } catch (error) {
        showNotification('Failed to cancel order', 'error');
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadOrder();
});
</script>

@endsection