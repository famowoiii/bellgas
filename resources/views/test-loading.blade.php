<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Loading Animation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6" x-data="{
        updatingOrders: {},

        async testUpdate(orderId, status) {
            const orderKey = `${orderId}_${status}`;

            try {
                // Set loading state
                this.updatingOrders[orderKey] = true;
                console.log('🔄 Loading state set for:', orderKey, 'State:', this.updatingOrders);

                // Simulate API call with delay
                await new Promise(resolve => setTimeout(resolve, 2000));

                alert('Update completed!');

            } catch (error) {
                console.error('Error:', error);
            } finally {
                // Clear loading state
                delete this.updatingOrders[orderKey];
                console.log('✅ Loading state cleared for:', orderKey, 'Final state:', this.updatingOrders);
            }
        }
    }">
        <h1 class="text-xl font-bold mb-4">Test Loading Animation</h1>

        <div class="space-y-4">
            <!-- Test Button 1 -->
            <button @click="testUpdate(123, 'PICKED_UP')"
                    :disabled="updatingOrders['123_PICKED_UP']"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed">
                <div class="flex items-center justify-center">
                    <div x-show="updatingOrders['123_PICKED_UP']" class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2"></div>
                    <span x-text="updatingOrders['123_PICKED_UP'] ? 'Updating...' : 'Mark as Picked Up'"></span>
                </div>
            </button>

            <!-- Test Button 2 -->
            <button @click="testUpdate(456, 'PROCESSED')"
                    :disabled="updatingOrders['456_PROCESSED']"
                    class="w-full px-4 py-2 bg-green-600 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed">
                <div class="flex items-center justify-center">
                    <div x-show="updatingOrders['456_PROCESSED']" class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2"></div>
                    <span x-text="updatingOrders['456_PROCESSED'] ? 'Processing...' : 'Mark as Processed'"></span>
                </div>
            </button>

            <!-- Debug Info -->
            <div class="mt-6 p-4 bg-gray-100 rounded">
                <h3 class="font-bold">Debug Info:</h3>
                <pre x-text="JSON.stringify(updatingOrders, null, 2)" class="text-sm mt-2"></pre>
            </div>
        </div>
    </div>
</body>
</html>