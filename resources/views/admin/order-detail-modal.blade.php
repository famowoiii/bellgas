<!-- Order Details Modal -->
<div x-show="showOrderModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-5xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">Order Details</h3>
                <button @click="showOrderModal = false" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div x-show="selectedOrder" class="p-6">
            <!-- Order Basic Information -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <h4 class="font-semibold mb-3 text-gray-800">Order Information</h4>
                    <div class="space-y-2">
                        <p class="text-sm"><strong>Order #:</strong> <span class="text-blue-600" x-text="selectedOrder?.order_number"></span></p>
                        <p class="text-sm"><strong>Status:</strong>
                            <span class="px-2 py-1 text-xs rounded-full"
                                  :class="getStatusColor(selectedOrder?.status)"
                                  x-text="selectedOrder?.status"></span>
                        </p>
                        <p class="text-sm"><strong>Date:</strong> <span x-text="formatDate(selectedOrder?.created_at)"></span></p>
                        <p class="text-sm"><strong>Total:</strong> <span class="text-green-600 font-semibold">$<span x-text="selectedOrder?.total_aud"></span></span></p>
                        <p class="text-sm"><strong>Payment Method:</strong> <span x-text="selectedOrder?.payment_method || 'Stripe'"></span></p>
                        <p class="text-sm"><strong>Fulfillment:</strong>
                            <span class="px-2 py-1 text-xs rounded"
                                  :class="selectedOrder?.fulfillment_method === 'PICKUP' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                                  x-text="selectedOrder?.fulfillment_method"></span>
                        </p>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-3 text-gray-800">Customer Information</h4>
                    <div class="space-y-2">
                        <p class="text-sm"><strong>Name:</strong> <span x-text="selectedOrder?.user?.first_name + ' ' + selectedOrder?.user?.last_name"></span></p>
                        <p class="text-sm"><strong>Email:</strong> <span class="text-blue-600" x-text="selectedOrder?.user?.email"></span></p>
                        <p class="text-sm"><strong>Phone:</strong> <span x-text="selectedOrder?.user?.phone_number"></span></p>
                        <p class="text-sm"><strong>Address:</strong> <span x-text="getDisplayAddress(selectedOrder)"></span></p>
                    </div>
                </div>
            </div>

            <!-- Customer Notes Section -->
            <div x-show="selectedOrder?.customer_notes && selectedOrder.customer_notes.trim()" class="mb-6">
                <h4 class="font-semibold mb-2 text-blue-600 flex items-center">
                    <i class="fas fa-comment-dots mr-2"></i>Customer Notes
                </h4>
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <p class="text-sm text-gray-700" x-text="selectedOrder?.customer_notes"></p>
                </div>
            </div>

            <!-- Order Items Section -->
            <div class="mb-6">
                <h4 class="font-semibold mb-3 text-gray-800 flex items-center">
                    <i class="fas fa-shopping-cart mr-2"></i>Order Items
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 rounded-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Product</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Type</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Price</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Quantity</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="item in selectedOrder?.items || []" :key="item.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div>
                                            <div class="font-medium text-gray-900" x-text="item.product_variant?.product?.name"></div>
                                            <div class="text-xs text-gray-500" x-text="item.product_variant?.name"></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs rounded"
                                              :class="item.product_variant?.product?.category === 'REFILL' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800'"
                                              x-text="item.product_variant?.product?.category"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right">$<span x-text="parseFloat(item.price_aud).toFixed(2)"></span></td>
                                    <td class="px-4 py-3 text-center" x-text="item.quantity"></td>
                                    <td class="px-4 py-3 text-right font-medium">$<span x-text="(parseFloat(item.price_aud) * item.quantity).toFixed(2)"></span></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right font-semibold">Subtotal:</td>
                                <td class="px-4 py-3 text-right font-semibold">$<span x-text="selectedOrder?.subtotal_aud"></span></td>
                            </tr>
                            <tr x-show="selectedOrder?.shipping_cost_aud > 0">
                                <td colspan="4" class="px-4 py-3 text-right">Delivery Fee:</td>
                                <td class="px-4 py-3 text-right">$<span x-text="selectedOrder?.shipping_cost_aud"></span></td>
                            </tr>
                            <tr class="border-t-2">
                                <td colspan="4" class="px-4 py-3 text-right font-bold text-lg">Total:</td>
                                <td class="px-4 py-3 text-right font-bold text-lg text-green-600">$<span x-text="selectedOrder?.total_aud"></span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-4 border-t">
                <div class="flex space-x-2">
                    <!-- Download Receipt -->
                    <button @click="downloadReceipt(selectedOrder)"
                            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition flex items-center">
                        <i class="fas fa-download mr-2"></i>Receipt
                    </button>
                </div>

                <div class="flex space-x-3">
                    <button @click="showOrderModal = false"
                            class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 transition">
                        Close
                    </button>

                    <!-- Status Update Buttons -->
                    <template x-if="selectedOrder?.status === 'PAID'">
                        <button @click="updateOrderStatus(selectedOrder, 'PROCESSED'); showOrderModal = false"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                            Process Order
                        </button>
                    </template>

                    <template x-if="selectedOrder?.status === 'PROCESSED'">
                        <button @click="updateOrderStatus(selectedOrder, 'DONE'); showOrderModal = false"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                            <span x-text="selectedOrder?.fulfillment_method === 'PICKUP' ? 'Mark as Picked Up' : 'Mark as Delivered'"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>