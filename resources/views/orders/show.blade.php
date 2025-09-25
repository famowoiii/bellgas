@extends('layouts.app')

@section('title', 'Order Details - BellGas')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="orderDetailsPage()" x-init="init('{{ $id }}')">
    <div class="max-w-4xl mx-auto">
        <!-- Loading State -->
        <div x-show="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Loading order details...</p>
        </div>

        <!-- Order Details -->
        <div x-show="!loading && order" class="space-y-6">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2" x-text="order.order_number"></h1>
                        <div class="flex items-center space-x-4 mb-4">
                            <span class="px-4 py-2 rounded-full text-sm font-medium"
                                  :class="getStatusColor(order.status)"
                                  x-text="order.status"></span>
                            <span class="text-gray-600">
                                <i :class="order.fulfillment_method === 'DELIVERY' ? 'fas fa-truck' : 'fas fa-store'" class="mr-2"></i>
                                <span x-text="order.fulfillment_method"></span>
                            </span>
                        </div>
                        <p class="text-gray-600">
                            <i class="fas fa-calendar mr-2"></i>
                            Ordered on <span x-text="formatDate(order.created_at)"></span>
                        </p>
                    </div>
                    
                    <div class="mt-4 md:mt-0 text-right">
                        <div class="text-3xl font-bold text-gray-800 mb-4">$<span x-text="order.total_aud"></span></div>
                        <div class="flex flex-col md:items-end space-y-2">
                            <button x-show="['PAID', 'PROCESSING', 'DELIVERED'].includes(order.status)" 
                                    @click="downloadReceipt()"
                                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                                <i class="fas fa-receipt mr-2"></i>Download Receipt
                            </button>
                            
                            <div class="flex space-x-2">
                                <button x-show="order.status === 'DELIVERED'" 
                                        @click="reorderItems()"
                                        class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition">
                                    <i class="fas fa-redo mr-1"></i>Reorder
                                </button>
                                
                                <button x-show="['UNPAID', 'PAID'].includes(order.status)" 
                                        @click="cancelOrder()"
                                        class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700 transition">
                                    <i class="fas fa-times mr-1"></i>Cancel Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Progress -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Order Progress</h2>
                <div class="relative">
                    <div class="flex justify-between items-center">
                        <template x-for="(step, index) in orderSteps" :key="step.status">
                            <div class="flex flex-col items-center relative" :class="index < orderSteps.length - 1 ? 'flex-1' : ''">
                                <!-- Step Circle -->
                                <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 relative z-10"
                                     :class="step.completed ? 'bg-green-500 border-green-500 text-white' : 
                                            step.current ? 'bg-blue-500 border-blue-500 text-white' : 
                                            'bg-white border-gray-300 text-gray-400'">
                                    <i :class="step.icon"></i>
                                </div>
                                
                                <!-- Step Label -->
                                <div class="mt-2 text-center">
                                    <p class="text-sm font-medium" 
                                       :class="step.completed || step.current ? 'text-gray-800' : 'text-gray-400'"
                                       x-text="step.label"></p>
                                    <p x-show="step.date" class="text-xs text-gray-500" x-text="step.date"></p>
                                </div>
                                
                                <!-- Progress Line -->
                                <div x-show="index < orderSteps.length - 1" 
                                     class="absolute top-5 left-1/2 w-full h-0.5 -ml-5"
                                     :class="orderSteps[index + 1].completed ? 'bg-green-500' : 'bg-gray-300'"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Delivery/Pickup Information -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Delivery Address -->
                <div x-show="order.fulfillment_method === 'DELIVERY' && order.address" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">
                        <i class="fas fa-map-marker-alt text-blue-600 mr-2"></i>
                        Delivery Address
                    </h3>
                    <div class="space-y-2 text-sm">
                        <p class="font-medium" x-text="order.address.name"></p>
                        <p x-text="order.address.street_address"></p>
                        <p>
                            <span x-text="order.address.suburb"></span>, 
                            <span x-text="order.address.state"></span> 
                            <span x-text="order.address.postcode"></span>
                        </p>
                        <p x-text="order.address.country"></p>
                        
                        <div x-show="order.address.delivery_instructions" class="mt-3 p-3 bg-yellow-50 rounded border-l-4 border-yellow-400">
                            <p class="text-xs font-medium text-yellow-800 mb-1">Delivery Instructions:</p>
                            <p class="text-xs text-yellow-700" x-text="order.address.delivery_instructions"></p>
                        </div>
                    </div>
                </div>

                <!-- Pickup Information -->
                <div x-show="order.fulfillment_method === 'PICKUP'" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">
                        <i class="fas fa-store text-green-600 mr-2"></i>
                        Pickup Information
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="p-3 bg-green-50 rounded border-l-4 border-green-400">
                            <p class="font-medium text-green-800">BellGas Store Location</p>
                            <p class="text-green-700">123 Gas Street, Sydney NSW 2000</p>
                            <p class="text-green-700">Phone: +61 2 1234 5678</p>
                        </div>
                        
                        <div class="p-3 bg-blue-50 rounded border-l-4 border-blue-400">
                            <p class="font-medium text-blue-800 mb-2">Store Hours:</p>
                            <div class="text-blue-700 text-xs space-y-1">
                                <p>Monday - Friday: 8:00 AM - 6:00 PM</p>
                                <p>Saturday: 9:00 AM - 4:00 PM</p>
                                <p>Sunday: Closed</p>
                            </div>
                        </div>
                        
                        <div x-show="order.pickup_code" class="p-3 bg-orange-50 rounded border-l-4 border-orange-400">
                            <p class="font-medium text-orange-800">Pickup Code:</p>
                            <p class="text-lg font-mono font-bold text-orange-700" x-text="order.pickup_code"></p>
                            <p class="text-xs text-orange-600">Show this code when collecting your order</p>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">
                        <i class="fas fa-calculator text-gray-600 mr-2"></i>
                        Order Summary
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>$<span x-text="order.subtotal_aud"></span></span>
                        </div>
                        <div x-show="order.shipping_cost_aud > 0" class="flex justify-between">
                            <span>Shipping:</span>
                            <span>$<span x-text="order.shipping_cost_aud"></span></span>
                        </div>
                        <div class="flex justify-between font-bold text-lg border-t pt-2">
                            <span>Total:</span>
                            <span>$<span x-text="order.total_aud"></span></span>
                        </div>
                        
                        <div x-show="order.stripe_payment_intent_id" class="mt-4 p-3 bg-gray-50 rounded">
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Payment processed securely by Stripe
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Payment ID: <span class="font-mono" x-text="order.stripe_payment_intent_id"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">
                    <i class="fas fa-fire text-orange-600 mr-2"></i>
                    Order Items (<span x-text="order.items?.length || 0"></span>)
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Product</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Quantity</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Unit Price</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="item in order.items" :key="item.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-fire text-blue-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800" x-text="item.productVariant?.product?.name"></p>
                                                <p class="text-sm text-gray-500" x-text="item.productVariant?.name"></p>
                                                <p class="text-xs text-gray-400">
                                                    <span x-text="item.productVariant?.weight_kg"></span>kg • 
                                                    <span x-text="item.productVariant?.product?.category"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full text-sm font-medium" 
                                              x-text="item.quantity"></span>
                                    </td>
                                    <td class="px-4 py-4 text-right font-medium">$<span x-text="item.unit_price_aud"></span></td>
                                    <td class="px-4 py-4 text-right font-bold">$<span x-text="item.total_price_aud"></span></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Customer Notes -->
            <div x-show="order.customer_notes" class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">
                    <i class="fas fa-sticky-note text-yellow-600 mr-2"></i>
                    Customer Notes
                </h3>
                <div class="p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                    <p class="text-sm text-yellow-800" x-text="order.customer_notes"></p>
                </div>
            </div>

            <!-- Order Events/Timeline -->
            <div x-show="order.events && order.events.length > 0" class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">
                    <i class="fas fa-history text-gray-600 mr-2"></i>
                    Order History
                </h3>
                
                <div class="space-y-4">
                    <template x-for="event in order.events" :key="event.id">
                        <div class="flex items-start space-x-3 pb-4 border-b border-gray-100 last:border-b-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <i class="fas fa-circle text-blue-600 text-xs"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800" x-text="event.description"></p>
                                <p class="text-xs text-gray-500 mt-1" x-text="formatDate(event.created_at)"></p>
                                <div x-show="event.metadata" class="mt-2">
                                    <details class="text-xs text-gray-600">
                                        <summary class="cursor-pointer hover:text-gray-800">View details</summary>
                                        <pre class="mt-2 p-2 bg-gray-50 rounded text-xs overflow-x-auto" x-text="JSON.stringify(event.metadata, null, 2)"></pre>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center pt-6">
                <a href="/orders" class="text-blue-600 hover:text-blue-800 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                </a>
                
                <div class="flex space-x-3">
                    <button @click="printOrder()" 
                            class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    
                    <button x-show="order.status === 'DELIVERED'" 
                            @click="leaveReview()"
                            class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 transition">
                        <i class="fas fa-star mr-2"></i>Leave Review
                    </button>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <div x-show="!loading && !order" class="text-center py-12">
            <i class="fas fa-exclamation-circle text-6xl text-red-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">Order Not Found</h3>
            <p class="text-gray-500 mb-6">The order you're looking for doesn't exist or you don't have permission to view it.</p>
            <a href="/orders" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                View All Orders
            </a>
        </div>
    </div>
</div>

<script>
function orderDetailsPage() {
    return {
        order: null,
        loading: true,
        orderId: null,
        
        async init(id) {
            this.orderId = id;
            await this.loadOrder();
        },
        
        async loadOrder() {
            this.loading = true;
            
            try {
                const response = await axios.get(`/api/orders/${this.orderId}`);
                this.order = response.data.data;
                
            } catch (error) {
                console.error('Failed to load order:', error);
                if (error.response?.status !== 404) {
                    this.showNotification('Failed to load order details', 'error');
                }
            } finally {
                this.loading = false;
            }
        },
        
        get orderSteps() {
            if (!this.order) return [];
            
            const steps = [
                { status: 'UNPAID', label: 'Order Placed', icon: 'fas fa-shopping-cart', completed: false, current: false },
                { status: 'PAID', label: 'Payment Confirmed', icon: 'fas fa-credit-card', completed: false, current: false },
                { status: 'PROCESSING', label: 'Processing', icon: 'fas fa-cogs', completed: false, current: false },
                { status: 'SHIPPED', label: 'Shipped/Ready', icon: 'fas fa-truck', completed: false, current: false },
                { status: 'DELIVERED', label: 'Delivered', icon: 'fas fa-check-circle', completed: false, current: false }
            ];
            
            const statusOrder = ['UNPAID', 'PAID', 'PROCESSING', 'SHIPPED', 'DELIVERED'];
            const currentStatusIndex = statusOrder.indexOf(this.order.status);
            
            steps.forEach((step, index) => {
                if (index < currentStatusIndex) {
                    step.completed = true;
                } else if (index === currentStatusIndex) {
                    step.current = true;
                    step.date = this.formatDate(this.order.updated_at);
                }
            });
            
            return steps;
        },
        
        async downloadReceipt() {
            try {
                const response = await axios.get(`/api/receipts/order/${this.order.id}`);
                
                // Simple print receipt
                const receiptData = response.data.receipt;
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Receipt - ${receiptData.receipt_info.receipt_number}</title>
                            <style>
                                body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
                                .header { text-align: center; border-bottom: 2px solid #333; padding: 20px 0; }
                                .details { margin: 20px 0; }
                                .items { width: 100%; border-collapse: collapse; margin: 20px 0; }
                                .items th, .items td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                                .items th { background-color: #f2f2f2; }
                                .total { font-weight: bold; font-size: 18px; }
                            </style>
                        </head>
                        <body>
                            <div class="header">
                                <h1>${receiptData.business_info.name}</h1>
                                <h2>Receipt #${receiptData.receipt_info.receipt_number}</h2>
                                <p>Order: ${receiptData.receipt_info.order_number}</p>
                                <p>Date: ${receiptData.receipt_info.issued_at}</p>
                            </div>
                            
                            <div class="details">
                                <p><strong>Customer:</strong> ${receiptData.customer_info.name}</p>
                                <p><strong>Email:</strong> ${receiptData.customer_info.email}</p>
                                <p><strong>Total:</strong> $${receiptData.order_details.pricing.total} ${receiptData.order_details.pricing.currency}</p>
                            </div>
                            
                            <script>window.onload = function() { window.print(); }</script>
                        </body>
                    </html>
                `);
                printWindow.document.close();
                
                this.showNotification('Receipt opened in new window', 'success');
                
            } catch (error) {
                this.showNotification('Failed to download receipt', 'error');
            }
        },
        
        async reorderItems() {
            try {
                const response = await axios.post(`/api/orders/${this.order.id}/reorder`);
                
                this.showNotification('Items added to cart! Redirecting to checkout...', 'success');
                
                setTimeout(() => {
                    window.location.href = '/checkout';
                }, 2000);
                
            } catch (error) {
                this.showNotification('Failed to reorder items', 'error');
            }
        },
        
        async cancelOrder() {
            if (!confirm(`Are you sure you want to cancel order ${this.order.order_number}?`)) {
                return;
            }
            
            try {
                await axios.patch(`/api/orders/${this.order.id}/cancel`);
                
                this.order.status = 'CANCELLED';
                this.showNotification('Order cancelled successfully', 'success');
                
            } catch (error) {
                this.showNotification('Failed to cancel order', 'error');
            }
        },
        
        printOrder() {
            window.print();
        },
        
        leaveReview() {
            this.showNotification('Review system coming soon!', 'info');
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

        showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                type === 'warning' ? 'bg-yellow-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    notification.remove();
                }
            }, 5000);
        }
    }
}
</script>
@endsection