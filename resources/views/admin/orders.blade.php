@extends('layouts.app')

@section('title', 'Order Management - BellGas Admin')

@section('head-scripts')
<style>
/* Custom CSS untuk memastikan animasi berputar bekerja */
.custom-spin {
    animation: custom-spin 1s linear infinite;
}

@keyframes custom-spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Fallback jika TailwindCSS tidak ter-load */
.animate-spin {
    animation: custom-spin 1s linear infinite;
}

/* Debugging border untuk spinner */
.debug-spinner {
    border: 2px solid #f3f4f6;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: custom-spin 1s linear infinite;
}
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8" x-data="adminOrders()" x-init="init()">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                        Order Management
                        <div x-show="isLoading" class="debug-spinner h-6 w-6 ml-3" style="border-width: 2px; border-top-color: #3b82f6;"></div>
                    </h1>
                    <p class="text-gray-600">
                        <span x-show="!isLoading">Manage and track customer orders</span>
                        <span x-show="isLoading" class="text-blue-600">Loading latest orders...</span>
                    </p>
                </div>
            </div>
            
            <!-- Filters and Actions -->
            <div class="flex space-x-3">
                <select x-model="statusFilter" @change="filterOrders()"
                        class="border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">All Status</option>
                    <option value="PENDING">Pending Payment</option>
                    <option value="PAID">Paid</option>
                    <option value="PROCESSED">Processed</option>
                    <option value="WAITING_FOR_PICKUP">Waiting for Pickup</option>
                    <option value="PICKED_UP">Picked Up</option>
                    <option value="ON_DELIVERY">On Delivery</option>
                    <option value="DONE">Done</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
                
                <button @click="refreshOrders()" 
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
                
                <button @click="exportOrders()" 
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-download mr-2"></i>Export
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
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.cancelled"></p>
                        <p class="text-gray-600 text-sm">Cancelled</p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Orders Table -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold">All Orders</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address & Notes</th>
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
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900" x-text="order.user?.first_name + ' ' + order.user?.last_name"></div>
                                        <div class="text-sm text-gray-500" x-text="order.user?.email"></div>
                                        <div x-show="order.user?.phone_number" class="text-xs text-gray-400">
                                            📞 <span x-text="order.user?.phone_number"></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-xs">
                                        <!-- Fulfillment Method -->
                                        <div class="text-sm font-medium mb-1" :class="order.fulfillment_method === 'PICKUP' ? 'text-blue-600' : 'text-green-600'" x-text="order.fulfillment_method === 'PICKUP' ? 'Pickup at Store' : 'Delivery'"></div>

                                        <!-- Complete Address -->
                                        <div class="text-sm text-gray-600 mb-2">
                                            <div x-show="order.fulfillment_method === 'PICKUP'" class="space-y-1">
                                                <div class="font-medium text-blue-700">BellGas LPG Store</div>
                                                <div>123 Main Street</div>
                                                <div>Melbourne VIC 3000</div>
                                            </div>
                                            <div x-show="order.fulfillment_method === 'DELIVERY' && order.address" class="space-y-1">
                                                <div class="font-medium text-green-700" x-text="(order.user?.first_name || '') + ' ' + (order.user?.last_name || '')"></div>
                                                <div x-text="order.address?.street_address"></div>
                                                <div x-text="(order.address?.suburb || '') + ' ' + (order.address?.state || '') + ' ' + (order.address?.postcode || '')"></div>
                                                <div x-show="order.address?.phone" class="text-xs text-gray-500">
                                                    📞 <span x-text="order.address?.phone"></span>
                                                </div>
                                            </div>
                                            <div x-show="order.fulfillment_method === 'DELIVERY' && !order.address" class="text-gray-500 italic">
                                                Address not specified
                                            </div>
                                        </div>

                                        <!-- Customer Notes -->
                                        <div x-show="order.customer_notes" class="mt-2 p-2 bg-yellow-50 border-l-4 border-yellow-400">
                                            <div class="text-xs font-medium text-yellow-800 mb-1">Customer Notes:</div>
                                            <div class="text-xs text-yellow-700" x-text="order.customer_notes"></div>
                                        </div>
                                        <div x-show="!order.customer_notes" class="text-xs text-gray-400 italic mt-1">
                                            No customer notes
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(order.created_at)"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="getStatusColor(order.status)"
                                          x-text="order.status"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<span x-text="order.total_aud"></span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button @click="viewOrder(order)" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <!-- Awaiting Payment Badge (for PENDING orders) -->
                                        <span x-show="order.status === 'PENDING'" 
                                              class="bg-red-100 text-red-800 px-3 py-1 rounded text-xs font-medium">
                                            <i class="fas fa-clock mr-1"></i>Awaiting Payment
                                        </span>

                                        <!-- Process Order Button (only for PAID orders) -->
                                        <button x-show="order.status === 'PAID'"
                                                @click="console.log('✅ Process clicked for order:', order.id, order); processOrder(order)"
                                                class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition cursor-pointer">
                                            <span class="flex items-center">
                                                <i class="fas fa-cogs mr-1"></i>
                                                <span>Process</span>
                                            </span>
                                        </button>
                                        
                                        <!-- Next Step Button (for PROCESSED orders) -->
                                        <button x-show="order.status === 'PROCESSED'"
                                                @click="console.log('✅ Next step clicked for order:', order.id, order); processNextStep(order)"
                                                class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition cursor-pointer">
                                            <span class="flex items-center">
                                                <i class="fas fa-arrow-right mr-1"></i>
                                                <span x-text="order.fulfillment_method === 'PICKUP' ? 'Ready for Pickup' : 'Ship Order'"></span>
                                            </span>
                                        </button>

                                        <!-- Complete Order Button (for WAITING_FOR_PICKUP/ON_DELIVERY/PICKED_UP orders) -->
                                        <button x-show="['WAITING_FOR_PICKUP', 'ON_DELIVERY', 'PICKED_UP'].includes(order.status)"
                                                @click="console.log('✅ Complete clicked for order:', order.id, order); completeOrder(order)"
                                                class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition flex items-center cursor-pointer">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            <span x-text="order.status === 'WAITING_FOR_PICKUP' ? 'Mark Picked Up' : order.status === 'PICKED_UP' ? 'Complete Order' : 'Mark Delivered'"></span>
                                        </button>
                                        
                                        <!-- Download Receipt Button (for PAID and processed orders) -->
                                        <button x-show="['PAID', 'PROCESSED', 'DONE'].includes(order.status)"
                                                @click="downloadReceipt(order)"
                                                class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition ml-1">
                                            <i class="fas fa-receipt mr-1"></i>Receipt
                                        </button>

                                        
                                        <!-- Status Badges -->
                                        <span x-show="order.status === 'PROCESSED'" 
                                              class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded text-xs font-medium ml-1">
                                            <i class="fas fa-cogs mr-1"></i>Processing
                                        </span>
                                        
                                        <span x-show="order.status === 'DONE'" 
                                              class="bg-green-100 text-green-800 px-3 py-1 rounded text-xs font-medium ml-1">
                                            <i class="fas fa-check-circle mr-1"></i>Completed
                                        </span>
                                        
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="text-gray-600 hover:text-gray-900">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div x-show="open" @click.away="open = false" 
                                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-20">
                                                <div class="py-1">
                                                    <button x-show="order.status === 'PAID'"
                                                            @click="updateOrderStatus(order, 'PROCESSED'); open = false"
                                                            :disabled="updatingOrders && updatingOrders[`${order.id}_PROCESSED`]"
                                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left disabled:opacity-50 cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed">
                                                        <div class="flex items-center">
                                                            <div x-show="updatingOrders && updatingOrders[`${order.id}_PROCESSED`]" class="debug-spinner h-5 w-5 mr-2"></div>
                                                            <span x-text="(updatingOrders && updatingOrders[`${order.id}_PROCESSED`]) ? 'Processing...' : 'Mark as Processed'"></span>
                                                        </div>
                                                    </button>
                                                    <button x-show="order.status === 'PROCESSED'"
                                                            @click="processNextStep(order); open = false"
                                                            :disabled="updatingOrders && updatingOrders[`${order.id}_${order.fulfillment_method === 'PICKUP' ? 'WAITING_FOR_PICKUP' : 'ON_DELIVERY'}`]"
                                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left disabled:opacity-50 cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed">
                                                        <div class="flex items-center">
                                                            <div x-show="updatingOrders && updatingOrders[`${order.id}_${order.fulfillment_method === 'PICKUP' ? 'WAITING_FOR_PICKUP' : 'ON_DELIVERY'}`]" class="debug-spinner h-5 w-5 mr-2"></div>
                                                            <span x-text="(updatingOrders && updatingOrders[`${order.id}_${order.fulfillment_method === 'PICKUP' ? 'WAITING_FOR_PICKUP' : 'ON_DELIVERY'}`]) ? 'Updating...' : (order.fulfillment_method === 'PICKUP' ? 'Ready for Pickup' : 'Ship Order')"></span>
                                                        </div>
                                                    </button>
                                                    <button x-show="order.status === 'WAITING_FOR_PICKUP'"
                                                            @click="updateOrderStatus(order, 'PICKED_UP'); open = false"
                                                            :disabled="updatingOrders && updatingOrders[`${order.id}_PICKED_UP`]"
                                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left disabled:opacity-50 cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed">
                                                        <div class="flex items-center">
                                                            <div x-show="updatingOrders && updatingOrders[`${order.id}_PICKED_UP`]" class="debug-spinner h-5 w-5 mr-2"></div>
                                                            <span x-text="(updatingOrders && updatingOrders[`${order.id}_PICKED_UP`]) ? 'Updating...' : 'Mark as Picked Up'"></span>
                                                        </div>
                                                    </button>
                                                    <button x-show="order.status === 'PICKED_UP'"
                                                            @click="updateOrderStatus(order, 'DONE'); open = false"
                                                            :disabled="updatingOrders && updatingOrders[`${order.id}_DONE`]"
                                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left disabled:opacity-50 cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed">
                                                        <div class="flex items-center">
                                                            <div x-show="updatingOrders && updatingOrders[`${order.id}_DONE`]" class="debug-spinner h-5 w-5 mr-2"></div>
                                                            <span x-text="(updatingOrders && updatingOrders[`${order.id}_DONE`]) ? 'Completing...' : 'Complete Order'"></span>
                                                        </div>
                                                    </button>
                                                    <button x-show="order.status === 'ON_DELIVERY'"
                                                            @click="updateOrderStatus(order, 'DONE'); open = false"
                                                            :disabled="updatingOrders && updatingOrders[`${order.id}_DONE`]"
                                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left disabled:opacity-50 cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed">
                                                        <div class="flex items-center">
                                                            <div x-show="updatingOrders && updatingOrders[`${order.id}_DONE`]" class="debug-spinner h-5 w-5 mr-2"></div>
                                                            <span x-text="(updatingOrders && updatingOrders[`${order.id}_DONE`]) ? 'Updating...' : 'Mark as Delivered'"></span>
                                                        </div>
                                                    </button>
                                                    <button x-show="['PICKED_UP', 'DONE'].includes(order.status) === false"
                                                            @click="updateOrderStatus(order, 'CANCELLED'); open = false"
                                                            :disabled="updatingOrders && updatingOrders[`${order.id}_CANCELLED`]"
                                                            class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100 w-full text-left disabled:opacity-50 cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed">
                                                        <div class="flex items-center">
                                                            <div x-show="updatingOrders && updatingOrders[`${order.id}_CANCELLED`]" class="debug-spinner h-5 w-5 mr-2" style="border-top-color: #ef4444;"></div>
                                                            <span x-text="(updatingOrders && updatingOrders[`${order.id}_CANCELLED`]) ? 'Cancelling...' : 'Cancel Order'"></span>
                                                        </div>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div x-show="filteredOrders.length === 0" class="p-12 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>No orders found</p>
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
                <!-- Order details content here -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-semibold mb-2">Order Information</h4>
                        <p class="text-sm"><strong>Order #:</strong> <span x-text="selectedOrder?.order_number"></span></p>
                        <p class="text-sm"><strong>Status:</strong> <span x-text="selectedOrder?.status"></span></p>
                        <p class="text-sm"><strong>Date:</strong> <span x-text="formatDate(selectedOrder?.created_at)"></span></p>
                        <p class="text-sm"><strong>Total:</strong> $<span x-text="selectedOrder?.total_aud"></span></p>
                        <p class="text-sm"><strong>Payment Method:</strong> <span x-text="selectedOrder?.payment_method"></span></p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-2">Customer Information</h4>
                        <p class="text-sm"><strong>Name:</strong> <span x-text="selectedOrder?.user?.first_name + ' ' + selectedOrder?.user?.last_name"></span></p>
                        <p class="text-sm"><strong>Email:</strong> <span x-text="selectedOrder?.user?.email"></span></p>
                        <p class="text-sm"><strong>Phone:</strong> <span x-text="selectedOrder?.user?.phone_number"></span></p>
                        <p class="text-sm"><strong>Fulfillment:</strong> <span x-text="selectedOrder?.fulfillment_method"></span></p>
                        <p class="text-sm"><strong>Address:</strong> <span x-text="getDisplayAddress(selectedOrder)"></span></p>
                    </div>
                </div>

                <!-- Customer Notes Section -->
                <div x-show="selectedOrder?.customer_notes" class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                    <h4 class="font-semibold text-yellow-800 mb-2">
                        <i class="fas fa-sticky-note mr-2"></i>Customer Notes
                    </h4>
                    <p class="text-sm text-yellow-700" x-text="selectedOrder?.customer_notes"></p>
                </div>

                <!-- Complete Address Information -->
                <div class="mb-6">
                    <h4 class="font-semibold mb-3">
                        <i class="fas fa-map-marker-alt mr-2"></i>Complete Address Information
                    </h4>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div x-show="selectedOrder?.fulfillment_method === 'PICKUP'" class="space-y-2">
                            <div class="font-medium text-blue-700">🏪 BellGas LPG Store (Pickup Location)</div>
                            <div class="text-gray-600">123 Main Street</div>
                            <div class="text-gray-600">Melbourne VIC 3000</div>
                            <div class="text-sm text-gray-500 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>Customer will collect the order from our store
                            </div>
                        </div>
                        <div x-show="selectedOrder?.fulfillment_method === 'DELIVERY' && selectedOrder?.address" class="space-y-2">
                            <div class="font-medium text-green-700">
                                🚚 Delivery Address for <span x-text="(selectedOrder?.user?.first_name || '') + ' ' + (selectedOrder?.user?.last_name || '')"></span>
                            </div>
                            <div class="text-gray-600" x-text="selectedOrder?.address?.street_address"></div>
                            <div class="text-gray-600" x-text="(selectedOrder?.address?.suburb || '') + ' ' + (selectedOrder?.address?.state || '') + ' ' + (selectedOrder?.address?.postcode || '')"></div>
                            <div x-show="selectedOrder?.address?.phone" class="text-sm text-gray-500 mt-2">
                                📞 Phone: <span x-text="selectedOrder?.address?.phone"></span>
                            </div>
                        </div>
                        <div x-show="selectedOrder?.fulfillment_method === 'DELIVERY' && !selectedOrder?.address" class="text-gray-500 italic">
                            ⚠️ Delivery address not specified
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="mb-6">
                    <h4 class="font-semibold mb-3">
                        <i class="fas fa-shopping-cart mr-2"></i>Order Items
                    </h4>
                    <div class="space-y-2">
                        <template x-for="item in selectedOrder?.items || []" :key="item.id">
                            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                <div>
                                    <div class="font-medium" x-text="item.product_variant?.product?.name || 'Product name not available'"></div>
                                    <div class="text-sm text-gray-500" x-text="'Variant: ' + (item.product_variant?.name || 'Standard')"></div>
                                    <div class="text-sm text-gray-500" x-text="'Quantity: ' + item.quantity"></div>
                                </div>
                                <div class="text-right">
                                    <div class="font-medium" x-text="'$' + (item.total_price_aud || '0.00')"></div>
                                    <div class="text-sm text-gray-500" x-text="'$' + (item.unit_price_aud || '0.00') + ' each'"></div>
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
                    <button @click="confirmOrder(selectedOrder)" 
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                        Confirm Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div x-show="showLoadingModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div x-show="showLoadingModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="bg-white rounded-lg shadow-xl p-8 max-w-md mx-4">
            <div class="text-center">
                <!-- Spinner -->
                <div class="mx-auto mb-4">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
                </div>

                <!-- Loading Message -->
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Processing...</h3>
                <p class="text-gray-600" x-text="loadingMessage"></p>

                <!-- Progress Indicator -->
                <div class="mt-4">
                    <div class="bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full animate-pulse w-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function adminOrders() {
    return {
        orders: [],
        filteredOrders: [],
        statusFilter: '',
        stats: {
            total: 0,
            pending: 0,
            completed: 0,
            cancelled: 0
        },
        showOrderModal: false,
        selectedOrder: null,
        lastSoundPlayed: null, // Prevent repeated notification sounds
        lastUpdateTimestamp: null,
        updatingOrders: {}, // Track which orders are being updated
        isLoading: false, // Track if data is being loaded
        showLoadingModal: false, // Track loading popup
        loadingMessage: '', // Message untuk loading popup
        
        async init() {
            await this.loadOrders();
            this.startRealTimeUpdates();
        },
        
        async loadOrders() {
            try {
                this.isLoading = true;
                console.log('🔄 Loading orders...');

                // Add cache-busting parameter to prevent stale data
                const timestamp = new Date().getTime();
                const response = await axios.get('/web/admin/orders/stats', {
                    params: {
                        _t: timestamp, // Cache-busting parameter
                        force_refresh: true
                    },
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache'
                    }
                });
                this.orders = response.data.orders || [];
                this.filteredOrders = this.orders;
                this.calculateStats();
                console.log('✅ Orders loaded:', this.orders.length, 'orders at', new Date().toLocaleTimeString());
            } catch (error) {
                console.error('❌ Failed to load orders:', error);
                this.showNotification('Failed to load orders: ' + (error.response?.data?.message || error.message), 'error');
            } finally {
                this.isLoading = false;
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
                pending: this.orders.filter(o => ['PENDING', 'PAID', 'PROCESSED'].includes(o.status)).length,
                completed: this.orders.filter(o => ['PICKED_UP', 'DONE'].includes(o.status)).length,
                cancelled: this.orders.filter(o => o.status === 'CANCELLED').length
            };
        },
        
        viewOrder(order) {
            this.selectedOrder = order;
            this.showOrderModal = true;
        },
        
        async updateOrderStatus(order, newStatus) {
            const orderKey = `${order.id}_${newStatus}`;

            try {
                // Set loading state
                this.updatingOrders[orderKey] = true;
                console.log('🔄 Loading state set for:', orderKey, 'State:', this.updatingOrders);

                console.log('Updating order:', order.order_number, 'to status:', newStatus);

                const response = await axios.put(`/web/admin/orders/${order.order_number}`, {
                    status: newStatus
                }, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // Update the local order object with the response data
                const updatedOrder = response.data.data;
                Object.assign(order, updatedOrder);

                this.calculateStats();

            } catch (error) {
                console.error('Failed to update order status:', error);
                console.error('Error details:', error.response?.data);

                // Show detailed error message for debugging
                let errorMessage = 'Failed to update order status';
                if (error.response?.data) {
                    const errorData = error.response.data;
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }

                    // Show additional debugging info for 400 errors
                    if (error.response.status === 400 && errorData.current_status && errorData.available_statuses) {
                        errorMessage += `\n\nCurrent: ${errorData.current_status}\nTried: ${errorData.attempted_status}\nAvailable: ${errorData.available_statuses.join(', ')}`;
                        console.error('Status transition error details:', errorData);
                    }
                } else {
                    errorMessage += ': ' + error.message;
                }

                this.showNotification(errorMessage, 'error');
            } finally {
                // Clear loading state
                delete this.updatingOrders[orderKey];
                console.log('✅ Loading state cleared for:', orderKey, 'Final state:', this.updatingOrders);
            }
        },
        
        async confirmOrder(order) {
            try {
                await this.updateOrderStatus(order, 'PROCESSED');
                this.showOrderModal = false;
            } catch (error) {
                console.error('Failed to confirm order:', error);
            }
        },

        async processOrder(order) {
            try {
                console.log('🔔 Processing paid order:', order.order_number);

                if (!confirm(`Process order ${order.order_number}? This will mark it as PROCESSED and ready for fulfillment.`)) {
                    return;
                }

                // Show loading popup
                this.showLoadingModal = true;
                this.loadingMessage = `Processing order ${order.order_number}...`;

                await this.updateOrderStatus(order, 'PROCESSED');

                // Hide loading popup
                this.showLoadingModal = false;
                this.showNotification(`Order ${order.order_number} processed successfully!`, 'success');

            } catch (error) {
                // Hide loading popup on error
                this.showLoadingModal = false;
                console.error('Failed to process order:', error);
                this.showNotification('Failed to process order: ' + (error.response?.data?.message || error.message), 'error');
            }
        },
        
        async processNextStep(order) {
            try {
                const nextStatus = order.fulfillment_method === 'PICKUP' ? 'WAITING_FOR_PICKUP' : 'ON_DELIVERY';
                const action = order.fulfillment_method === 'PICKUP' ? 'ready for pickup' : 'shipped for delivery';

                console.log(`🔔 Processing next step for order:`, order.order_number, `to ${nextStatus}`);

                if (!confirm(`Mark order ${order.order_number} as ${action}?`)) {
                    return;
                }

                // Show loading popup
                this.showLoadingModal = true;
                this.loadingMessage = `Updating order ${order.order_number} to ${action}...`;

                await this.updateOrderStatus(order, nextStatus);

                // Hide loading popup
                this.showLoadingModal = false;
                this.showNotification(`Order ${order.order_number} marked as ${action}!`, 'success');

            } catch (error) {
                // Hide loading popup on error
                this.showLoadingModal = false;
                console.error('Failed to process next step:', error);
                this.showNotification('Failed to process order: ' + (error.response?.data?.message || error.message), 'error');
            }
        },

        async completeOrder(order) {
            try {
                let nextStatus, action;

                if (order.status === 'WAITING_FOR_PICKUP') {
                    nextStatus = 'PICKED_UP';
                    action = 'picked up';
                } else if (order.status === 'ON_DELIVERY') {
                    nextStatus = 'DONE';
                    action = 'delivered';
                } else {
                    nextStatus = 'DONE';
                    action = 'completed';
                }

                console.log(`🔔 Completing order (${action}):`, order.order_number);

                if (!confirm(`Mark order ${order.order_number} as ${action}? This will ${nextStatus === 'DONE' ? 'complete' : 'update'} the order.`)) {
                    return;
                }

                // Show loading popup
                this.showLoadingModal = true;
                this.loadingMessage = `Marking order ${order.order_number} as ${action}...`;

                await this.updateOrderStatus(order, nextStatus);

                // Hide loading popup
                this.showLoadingModal = false;
                this.showNotification(`Order ${order.order_number} marked as ${action}!`, 'success');

            } catch (error) {
                // Hide loading popup on error
                this.showLoadingModal = false;
                console.error('Failed to complete order:', error);
                this.showNotification('Failed to complete order: ' + (error.response?.data?.message || error.message), 'error');
            }
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
                this.showNotification('Failed to download receipt: ' + (error.response?.data?.message || error.message), 'error');
            }
        },
        
        async exportOrders() {
            try {
                const response = await axios.get('/web/admin/orders/export', {
                    responseType: 'blob',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', 'orders-export.csv');
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
                
                this.showNotification('Orders exported successfully', 'success');
            } catch (error) {
                console.error('Failed to export orders:', error);
                this.showNotification('Failed to export orders', 'error');
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

        // Real-time functionality using WebSocket
        startRealTimeUpdates() {
            console.log('🔄 Setting up real-time updates for admin orders...');

            // Register this page instance globally for WebSocket callbacks
            window.adminOrdersPage = this;

            // Always use polling for admin orders for reliability
            this.fallbackToPolling();

            // Set up WebSocket listeners for real-time events
            this.setupWebSocketListeners();
        },

        setupWebSocketListeners() {
            if (window.Echo && typeof window.Echo.channel === 'function') {
                console.log('✅ Setting up WebSocket listeners for admin orders...');

                // Listen for new orders on admin-orders channel
                window.Echo.channel('admin-orders')
                    .listen('.order.created', (data) => {
                        console.log('🔔 New order created:', data);
                        this.handleNewOrder(data);
                    })
                    .listen('.order.status_changed', (data) => {
                        console.log('🔄 Order status changed:', data);
                        this.handleOrderStatusChange(data);
                    })
                    .listen('.order.updated', (data) => {
                        console.log('📝 Order updated:', data);
                        this.handleOrderUpdate(data);
                    });

                // Listen for real-time stats updates on public channel
                window.Echo.channel('public-admin-orders')
                    .listen('.order.created', (data) => {
                        console.log('📊 Stats update from new order');
                        this.refreshStats();
                    })
                    .listen('.order.status_changed', (data) => {
                        console.log('📊 Stats update from status change');
                        this.refreshStats();
                    });

                console.log('✅ WebSocket listeners set up successfully');
            } else {
                console.log('⚠️ WebSocket not available, using polling only');
            }
        },

        handleNewOrder(data) {
            // Show notification for new order
            this.showNotification(`New order #${data.order.order_number} from ${data.order.customer_name}`, 'success');

            // Play notification sound
            this.playNotificationSound('new-order');

            // Refresh orders list to include new order
            this.loadOrders();
        },

        handleOrderStatusChange(data) {
            // Show notification for status change
            this.showNotification(`Order #${data.order.order_number} status changed to ${data.new_status}`, 'info');

            // Update the specific order in the list if it exists
            this.updateOrderInList(data.order);

            // Refresh stats
            this.calculateStats();
        },

        handleOrderUpdate(data) {
            // Show notification for general update
            this.showNotification(`Order #${data.order.order_number} has been updated`, 'info');

            // Update the specific order in the list
            this.updateOrderInList(data.order);
        },

        updateOrderInList(updatedOrder) {
            const index = this.orders.findIndex(order => order.id === updatedOrder.id);
            if (index !== -1) {
                this.orders[index] = { ...this.orders[index], ...updatedOrder };
                this.filterOrders(); // Re-apply filters
                console.log(`📝 Updated order #${updatedOrder.order_number} in list`);
            }
        },

        async refreshStats() {
            try {
                const response = await axios.get('/web/admin/dashboard/stats', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (response.data.success) {
                    this.stats = response.data.data;
                }
            } catch (error) {
                console.error('Failed to refresh stats:', error);
            }
        },

        // Fallback polling method if WebSocket is not available
        fallbackToPolling() {
            console.log('🔄 Starting fallback polling...');
            this.lastUpdateTimestamp = new Date().toISOString();

            // Initial check
            this.checkForUpdates();

            // Set interval untuk polling setiap 3 detik untuk admin (faster updates)
            setInterval(() => {
                this.checkForUpdates();
            }, 3000);
        },

        // Method called by WebSocket events from main app
        refreshFromWebSocket() {
            console.log('🔔 Refreshing orders from WebSocket event');
            this.loadOrders();
        },

        async checkForUpdates() {
            try {
                // Use web-based route with session authentication
                const response = await axios.get('/web/admin/realtime/orders', {
                    params: {
                        since: this.lastUpdateTimestamp,
                        _t: new Date().getTime() // Cache-busting
                    },
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache'
                    },
                    withCredentials: true
                });

                if (response.data.success) {
                    // Update timestamp
                    this.lastUpdateTimestamp = response.data.timestamp;
                    
                    // Update stats if provided
                    if (response.data.stats) {
                        this.stats = response.data.stats;
                    }
                    
                    // Process updates if any
                    if (response.data.has_updates && response.data.updates.length > 0) {
                        console.log(`🔔 Received ${response.data.updates.length} real-time updates`);
                        
                        // Process each update
                        response.data.updates.forEach(update => {
                            this.processRealtimeUpdate(update);
                        });
                        
                        // Refresh orders list to get latest data
                        await this.loadOrders();
                    }
                }
                
            } catch (error) {
                console.error('❌ Real-time update error:', error);
                // Don't stop polling on errors, just log them
            }
        },

        processRealtimeUpdate(update) {
            // Show visual notification
            this.showRealtimeNotification(update);
            
            // Play sound based on update type
            this.playNotificationSound(update.sound || 'default');
            
            // Show browser notification if permission granted
            this.showBrowserNotification(update);
            
            // Log for debugging
            console.log('📝 Processing update:', update);
        },

        showRealtimeNotification(update) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'fixed top-20 right-4 bg-blue-600 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-sm transform transition-all duration-500 translate-x-full';
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-bell mr-3"></i>
                    <div>
                        <div class="font-semibold">Order Update</div>
                        <div class="text-sm">${update.message}</div>
                        <div class="text-xs opacity-75 mt-1">${update.order_number}</div>
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
            
            // Auto remove after 10 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => notification.remove(), 500);
                }
            }, 10000);
        },

        playNotificationSound(soundType = 'default') {
            try {
                // Prevent playing the same notification sound repeatedly
                if (this.lastSoundPlayed && Date.now() - this.lastSoundPlayed < 3000) {
                    console.log('🔇 Sound blocked - too soon after last notification');
                    return;
                }
                this.lastSoundPlayed = Date.now();

                // Different sounds for different notification types
                const sounds = {
                    'new_order': {
                        frequency: [800, 1000, 1200],
                        duration: 200,
                        volume: 0.4
                    },
                    'payment_confirmed': {
                        frequency: [600, 800],
                        duration: 150,
                        volume: 0.3
                    },
                    'status_change': {
                        frequency: [500],
                        duration: 100,
                        volume: 0.2
                    },
                    'default': {
                        frequency: [440],
                        duration: 100,
                        volume: 0.2
                    }
                };

                const sound = sounds[soundType] || sounds.default;
                
                // Create AudioContext for better sound generation
                if (window.AudioContext || window.webkitAudioContext) {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    
                    sound.frequency.forEach((freq, index) => {
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();
                        
                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);
                        
                        oscillator.frequency.setValueAtTime(freq, audioContext.currentTime);
                        oscillator.type = 'sine';
                        
                        gainNode.gain.setValueAtTime(sound.volume, audioContext.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + sound.duration / 1000);
                        
                        oscillator.start(audioContext.currentTime + index * 0.1);
                        oscillator.stop(audioContext.currentTime + (sound.duration / 1000) + index * 0.1);
                    });
                } else {
                    // Fallback to simple beep
                    console.log('🔔 ' + soundType.toUpperCase());
                }
            } catch (e) {
                console.log('🔇 Audio not supported:', e);
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
                const icon = update.type === 'NEW_ORDER' ? '🔔' : 
                           update.type === 'PAYMENT_CONFIRMED' ? '💳' : '📊';
                
                const notification = new Notification(`BellGas Admin - ${update.type.replace('_', ' ')}`, {
                    body: update.message,
                    icon: 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64">
                            <text y="50" font-size="50">${icon}</text>
                        </svg>
                    `),
                    badge: '/favicon.ico',
                    tag: 'bellgas-admin-' + update.order_id,
                    requireInteraction: update.priority === 'high',
                    silent: false
                });

                // Auto close after 5 seconds unless high priority
                if (update.priority !== 'high') {
                    setTimeout(() => notification.close(), 5000);
                }

                // Handle notification click
                notification.onclick = () => {
                    window.focus();
                    notification.close();
                    // Optionally focus on the specific order
                    console.log('Clicked on notification for order:', update.order_number);
                };
            } catch (e) {
                console.log('Browser notifications not supported:', e);
            }
        },

        getDisplayAddress(order) {
            if (order.fulfillment_method === 'PICKUP') {
                return 'BellGas LPG Store, 123 Main St';
            }

            if (order.address) {
                return `${order.address.street_address}, ${order.address.suburb}`;
            }

            return 'Address not specified';
        },

        showNotification(message, type = 'info') {
            console.log(`🔔 ${type.toUpperCase()}: ${message}`);

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 max-w-sm transform transition-all duration-500 translate-x-full ${
                type === 'success' ? 'bg-green-600 text-white' :
                type === 'error' ? 'bg-red-600 text-white' :
                type === 'warning' ? 'bg-yellow-600 text-white' :
                'bg-blue-600 text-white'
            }`;

            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${
                        type === 'success' ? 'fa-check-circle' :
                        type === 'error' ? 'fa-exclamation-circle' :
                        type === 'warning' ? 'fa-exclamation-triangle' :
                        'fa-info-circle'
                    } mr-3"></i>
                    <div>
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
        }
    }
}
</script>
@endsection