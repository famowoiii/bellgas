<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Order Buttons</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Test Order Status Buttons</h1>

        <div x-data="{
            updatingOrders: {},
            orders: [
                {id: 1, status: 'PAID', order_number: 'ORD-001', fulfillment_method: 'PICKUP'},
                {id: 2, status: 'PROCESSED', order_number: 'ORD-002', fulfillment_method: 'PICKUP'},
                {id: 3, status: 'WAITING_FOR_PICKUP', order_number: 'ORD-003'},
                {id: 4, status: 'PICKED_UP', order_number: 'ORD-004'}
            ],

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
            <div class="space-y-4">
                <template x-for="order in orders" :key="order.id">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4" x-text="'Order: ' + order.order_number + ' (Status: ' + order.status + ')'"></h3>

                        <div class="flex space-x-2">
                            <!-- Process Button (for PAID orders) -->
                            <button x-show="order.status === 'PAID'"
                                    @click="console.log('Process clicked for order:', order.id); processOrder(order)"
                                    :disabled="updatingOrders && updatingOrders[`${order.id}_PROCESSED`]"
                                    class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition disabled:opacity-50 cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed">
                                <span class="flex items-center">
                                    <i class="fas fa-cogs mr-1"></i>
                                    <span>Process</span>
                                </span>
                            </button>

                            <!-- Next Step Button (for PROCESSED orders) -->
                            <button x-show="order.status === 'PROCESSED'"
                                    @click="console.log('Next step clicked for order:', order.id); processNextStep(order)"
                                    :disabled="updatingOrders && (updatingOrders[`${order.id}_WAITING_FOR_PICKUP`] || updatingOrders[`${order.id}_ON_DELIVERY`])"
                                    class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition disabled:opacity-50 cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed">
                                <span class="flex items-center">
                                    <i class="fas fa-arrow-right mr-1"></i>
                                    <span x-text="order.fulfillment_method === 'PICKUP' ? 'Ready for Pickup' : 'Ship Order'"></span>
                                </span>
                            </button>

                            <!-- Complete Order Button -->
                            <button x-show="['WAITING_FOR_PICKUP', 'ON_DELIVERY', 'PICKED_UP'].includes(order.status)"
                                    @click="console.log('Complete clicked for order:', order.id); completeOrder(order)"
                                    :disabled="updatingOrders && (updatingOrders[`${order.id}_PICKED_UP`] || updatingOrders[`${order.id}_DONE`])"
                                    class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition disabled:opacity-50 flex items-center cursor-pointer hover:cursor-pointer disabled:cursor-not-allowed">
                                <i class="fas fa-check-circle mr-1"></i>
                                <span x-text="order.status === 'WAITING_FOR_PICKUP' ? 'Mark Picked Up' : order.status === 'PICKED_UP' ? 'Complete Order' : 'Mark Delivered'"></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-8 p-4 bg-blue-100 rounded">
                <h3 class="font-bold mb-2">Debug Information:</h3>
                <p><strong>Instructions:</strong> Open browser console (F12) to see click logs</p>
                <p><strong>Updating Orders:</strong> <span x-text="JSON.stringify(updatingOrders)"></span></p>
                <p><strong>Orders Data:</strong> <span x-text="JSON.stringify(orders, null, 2)"></span></p>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>