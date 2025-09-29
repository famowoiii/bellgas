<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Admin Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Debug Admin Orders - Real Data</h1>

        <div x-data="{
            updatingOrders: {},
            orders: @json($orders->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'fulfillment_method' => $order->fulfillment_method,
                    'total_aud' => $order->total_aud,
                    'user' => $order->user ? [
                        'first_name' => $order->user->first_name,
                        'last_name' => $order->user->last_name
                    ] : null
                ];
            })),

            processOrder(order) {
                console.log('✅ Process button clicked for order:', order.id);
                alert('Process Order clicked: ' + order.order_number);
            },

            processNextStep(order) {
                console.log('✅ Next step button clicked for order:', order.id);
                alert('Next Step clicked: ' + order.order_number);
            },

            completeOrder(order) {
                console.log('✅ Complete button clicked for order:', order.id);
                alert('Complete Order clicked: ' + order.order_number);
            }
        }">
            <!-- Debug Info -->
            <div class="bg-blue-100 p-4 rounded mb-6">
                <h3 class="font-bold mb-2">Debug Information:</h3>
                <p><strong>Total Orders:</strong> <span x-text="orders.length"></span></p>
                <p><strong>Updating Orders:</strong> <span x-text="JSON.stringify(updatingOrders)"></span></p>
                <p><strong>Instructions:</strong> Open browser console (F12) to see click logs</p>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fulfillment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="order in orders" :key="order.id">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900" x-text="order.order_number"></div>
                                    <div class="text-sm text-gray-500" x-text="'$' + order.total_aud"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="order.user ? (order.user.first_name + ' ' + order.user.last_name) : 'N/A'"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800" x-text="order.status"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="order.fulfillment_method"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <!-- Process Button (for PAID orders) -->
                                    <button x-show="order.status === 'PAID'"
                                            @click="console.log('Process clicked for order:', order.id); processOrder(order)"
                                            :disabled="updatingOrders && updatingOrders[`${order.id}_PROCESSED`]"
                                            class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition disabled:opacity-50 cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed mr-2">
                                        <span class="flex items-center">
                                            <div x-show="updatingOrders && updatingOrders[`${order.id}_PROCESSED`]" class="animate-spin rounded-full h-3 w-3 border-2 border-white border-t-transparent mr-1"></div>
                                            <i x-show="!(updatingOrders && updatingOrders[`${order.id}_PROCESSED`])" class="fas fa-cogs mr-1"></i>
                                            <span x-text="(updatingOrders && updatingOrders[`${order.id}_PROCESSED`]) ? 'Processing...' : 'Process'"></span>
                                        </span>
                                    </button>

                                    <!-- Next Step Button (for PROCESSED orders) -->
                                    <button x-show="order.status === 'PROCESSED'"
                                            @click="console.log('Next step clicked for order:', order.id); processNextStep(order)"
                                            :disabled="updatingOrders && (updatingOrders[`${order.id}_WAITING_FOR_PICKUP`] || updatingOrders[`${order.id}_ON_DELIVERY`])"
                                            class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition disabled:opacity-50 cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed mr-2">
                                        <span class="flex items-center">
                                            <div x-show="updatingOrders && (updatingOrders[`${order.id}_WAITING_FOR_PICKUP`] || updatingOrders[`${order.id}_ON_DELIVERY`])" class="animate-spin rounded-full h-3 w-3 border-2 border-white border-t-transparent mr-1"></div>
                                            <i x-show="!(updatingOrders && (updatingOrders[`${order.id}_WAITING_FOR_PICKUP`] || updatingOrders[`${order.id}_ON_DELIVERY`]))" class="fas fa-arrow-right mr-1"></i>
                                            <span x-text="(updatingOrders && (updatingOrders[`${order.id}_WAITING_FOR_PICKUP`] || updatingOrders[`${order.id}_ON_DELIVERY`])) ? 'Updating...' : (order.fulfillment_method === 'PICKUP' ? 'Ready for Pickup' : 'Ship Order')"></span>
                                        </span>
                                    </button>

                                    <!-- Complete Order Button -->
                                    <button x-show="['WAITING_FOR_PICKUP', 'ON_DELIVERY', 'PICKED_UP'].includes(order.status)"
                                            @click="console.log('Complete clicked for order:', order.id); completeOrder(order)"
                                            :disabled="updatingOrders && (updatingOrders[`${order.id}_PICKED_UP`] || updatingOrders[`${order.id}_DONE`])"
                                            class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition disabled:opacity-50 flex items-center cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed mr-2">
                                        <div x-show="updatingOrders && (updatingOrders[`${order.id}_PICKED_UP`] || updatingOrders[`${order.id}_DONE`])" class="animate-spin rounded-full h-3 w-3 border-2 border-white border-t-transparent mr-1"></div>
                                        <i x-show="!(updatingOrders && (updatingOrders[`${order.id}_PICKED_UP`] || updatingOrders[`${order.id}_DONE`]))" class="fas fa-check-circle mr-1"></i>
                                        <span x-text="(updatingOrders && (updatingOrders[`${order.id}_PICKED_UP`] || updatingOrders[`${order.id}_DONE`])) ? 'Updating...' : (order.status === 'WAITING_FOR_PICKUP' ? 'Mark Picked Up' : order.status === 'PICKED_UP' ? 'Complete Order' : 'Mark Delivered')"></span>
                                    </button>

                                    <!-- Debug Button Visibility -->
                                    <div class="text-xs text-gray-500 mt-1">
                                        <span x-text="'Status: ' + order.status"></span><br>
                                        <span x-text="'PAID visible: ' + (order.status === 'PAID')"></span><br>
                                        <span x-text="'PROCESSED visible: ' + (order.status === 'PROCESSED')"></span><br>
                                        <span x-text="'COMPLETE visible: ' + (['WAITING_FOR_PICKUP', 'ON_DELIVERY', 'PICKED_UP'].includes(order.status))"></span>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="orders.length === 0">
                            <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                No orders found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Raw Data Debug -->
            <div class="mt-6 bg-gray-100 p-4 rounded">
                <h3 class="font-bold mb-2">Raw Orders Data:</h3>
                <pre class="text-xs overflow-auto" x-text="JSON.stringify(orders, null, 2)"></pre>
            </div>
        </div>
    </div>
</body>
</html>