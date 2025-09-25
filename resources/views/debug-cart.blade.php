@extends('layouts.app')

@section('title', 'Debug Cart Issues')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="cartDebugApp()" x-init="init()">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Debug Cart Issues</h1>

        <!-- Authentication Status -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Authentication Status</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p><strong>window.isAuthenticated:</strong> <span x-text="window.isAuthenticated" :class="window.isAuthenticated ? 'text-green-600' : 'text-red-600'"></span></p>
                    <p><strong>JWT Token Available:</strong> <span x-text="window.JWT_TOKEN ? 'Yes' : 'No'" :class="window.JWT_TOKEN ? 'text-green-600' : 'text-red-600'"></span></p>
                    <p><strong>JWT Token (first 30 chars):</strong> <span class="font-mono text-sm" x-text="window.JWT_TOKEN ? window.JWT_TOKEN.substring(0, 30) + '...' : 'N/A'"></span></p>
                </div>
                <div>
                    <p><strong>Laravel Auth Check:</strong> {{ auth()->check() ? 'Yes' : 'No' }}</p>
                    <p><strong>Current User:</strong> {{ auth()->check() ? auth()->user()->email : 'None' }}</p>
                    <p><strong>Session JWT Token:</strong> {{ session('jwt_token') ? 'Available' : 'Not found' }}</p>
                </div>
            </div>
        </div>

        <!-- Global App Status -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Global App Status</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p><strong>window.app Available:</strong> <span x-text="window.app ? 'Yes' : 'No'" :class="window.app ? 'text-green-600' : 'text-red-600'"></span></p>
                    <p><strong>showNotification Method:</strong> <span x-text="(window.app && window.app.showNotification) ? 'Available' : 'Not Available'" :class="(window.app && window.app.showNotification) ? 'text-green-600' : 'text-red-600'"></span></p>
                    <p><strong>addToCart Method:</strong> <span x-text="(window.app && window.app.addToCart) ? 'Available' : 'Not Available'" :class="(window.app && window.app.addToCart) ? 'text-green-600' : 'text-red-600'"></span></p>
                    <p><strong>loadCart Method:</strong> <span x-text="(window.app && window.app.loadCart) ? 'Available' : 'Not Available'" :class="(window.app && window.app.loadCart) ? 'text-green-600' : 'text-red-600'"></span></p>
                </div>
                <div>
                    <p><strong>Cart Items Count:</strong> <span x-text="(window.app && window.app.cartItems) ? window.app.cartItems.length : 'N/A'"></span></p>
                    <p><strong>Cart Total:</strong> <span x-text="(window.app && window.app.cartTotal) ? '$' + window.app.cartTotal : 'N/A'"></span></p>
                    <p><strong>Cart Loading:</strong> <span x-text="(window.app && window.app.cartLoading !== undefined) ? (window.app.cartLoading ? 'Yes' : 'No') : 'N/A'"></span></p>
                    <p><strong>Notifications Count:</strong> <span x-text="(window.app && window.app.notifications) ? window.app.notifications.length : 'N/A'"></span></p>
                </div>
            </div>
        </div>

        <!-- Cart API Test -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Cart API Test</h2>
            <div class="space-y-4">
                <div class="flex space-x-4">
                    <button @click="testGetCart()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Test Get Cart
                    </button>
                    <button @click="testLoadCartMethod()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Test loadCart() Method
                    </button>
                    <button @click="testNotification()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                        Test Notification
                    </button>
                </div>
                <div x-show="apiResult" class="mt-4">
                    <h3 class="font-semibold mb-2">API Result:</h3>
                    <pre class="bg-gray-100 p-4 rounded text-sm overflow-auto" x-text="apiResult"></pre>
                </div>
            </div>
        </div>

        <!-- Cart Display Test -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Cart Display Test</h2>
            <div class="space-y-4">
                <button @click="openCart()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Open Cart Sidebar
                </button>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>Cart Open State:</strong> <span x-text="window.app ? (window.app.cartOpen ? 'Open' : 'Closed') : 'N/A'"></span>
                    </div>
                    <div>
                        <strong>Has Cart Items:</strong> <span x-text="window.app ? (window.app.hasCartItems ? 'Yes' : 'No') : 'N/A'"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Cart Data -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Live Cart Data</h2>
            <div x-show="window.app && window.app.cartItems && window.app.cartItems.length > 0">
                <p class="mb-2"><strong>Items in Cart:</strong></p>
                <template x-for="item in (window.app ? window.app.cartItems : [])" :key="item.id">
                    <div class="border border-gray-200 rounded p-3 mb-2">
                        <p><strong>Name:</strong> <span x-text="item.name"></span></p>
                        <p><strong>Quantity:</strong> <span x-text="item.quantity"></span></p>
                        <p><strong>Price:</strong> <span x-text="item.formatted_price"></span></p>
                        <p><strong>Total:</strong> <span x-text="item.formatted_total"></span></p>
                    </div>
                </template>
            </div>
            <div x-show="!window.app || !window.app.cartItems || window.app.cartItems.length === 0" class="text-gray-500">
                No items in cart or cart not loaded
            </div>
        </div>
    </div>
</div>

<script>
function cartDebugApp() {
    return {
        apiResult: '',

        async init() {
            console.log('🐛 Cart Debug App initializing...');

            // Wait a bit for everything to load
            setTimeout(() => {
                this.logCurrentState();
            }, 1000);
        },

        logCurrentState() {
            console.log('🐛 CURRENT STATE DEBUG:');
            console.log('  - window.isAuthenticated:', window.isAuthenticated);
            console.log('  - window.JWT_TOKEN:', window.JWT_TOKEN ? window.JWT_TOKEN.substring(0, 20) + '...' : 'MISSING');
            console.log('  - window.app:', !!window.app);
            console.log('  - window.app.cartItems:', window.app ? window.app.cartItems : 'N/A');
            console.log('  - window.app.cartCount:', window.app ? window.app.cartCount : 'N/A');
            console.log('  - window.app.showNotification:', typeof (window.app ? window.app.showNotification : undefined));
        },

        async testGetCart() {
            console.log('🧪 Testing GET /api/cart...');
            try {
                const response = await axios.get('/api/cart');
                console.log('✅ GET /api/cart success:', response.data);
                this.apiResult = JSON.stringify(response.data, null, 2);
            } catch (error) {
                console.error('❌ GET /api/cart failed:', error);
                this.apiResult = 'ERROR: ' + (error.response?.data?.message || error.message);
            }
        },

        async testLoadCartMethod() {
            console.log('🧪 Testing window.app.loadCart()...');
            if (!window.app || !window.app.loadCart) {
                this.apiResult = 'ERROR: window.app.loadCart() not available';
                return;
            }

            try {
                const result = await window.app.loadCart(true);
                console.log('✅ loadCart() success:', result);
                this.apiResult = JSON.stringify({
                    result: result,
                    cartItems: window.app.cartItems,
                    cartCount: window.app.cartCount,
                    cartTotal: window.app.cartTotal
                }, null, 2);
            } catch (error) {
                console.error('❌ loadCart() failed:', error);
                this.apiResult = 'ERROR: ' + error.message;
            }
        },

        testNotification() {
            console.log('🧪 Testing notification...');
            if (!window.app || !window.app.showNotification) {
                this.apiResult = 'ERROR: window.app.showNotification() not available';
                return;
            }

            try {
                window.app.showNotification('Test notification - Cart Debug', 'success');
                this.apiResult = 'Notification sent successfully! Check for popup in bottom right.';
            } catch (error) {
                console.error('❌ Notification failed:', error);
                this.apiResult = 'ERROR: ' + error.message;
            }
        },

        openCart() {
            console.log('🧪 Testing cart sidebar open...');
            if (!window.app) {
                this.apiResult = 'ERROR: window.app not available';
                return;
            }

            try {
                window.app.cartOpen = true;
                this.apiResult = 'Cart sidebar should be open now. Check right side of screen.';
            } catch (error) {
                console.error('❌ Open cart failed:', error);
                this.apiResult = 'ERROR: ' + error.message;
            }
        }
    };
}
</script>
@endsection