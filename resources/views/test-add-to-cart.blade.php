@extends('layouts.app')

@section('title', 'Add to Cart Test')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Add to Cart Test</h1>

        <!-- Authentication Status -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <h2 class="text-lg font-semibold mb-4">Authentication Status</h2>
            <div class="space-y-2">
                <p><strong>Authenticated:</strong> <span id="authStatus">{{ auth()->check() ? 'Yes' : 'No' }}</span></p>
                <p><strong>User:</strong> <span id="userInfo">{{ auth()->check() ? auth()->user()->email : 'Not logged in' }}</span></p>
                <p><strong>JWT Token:</strong> <span id="jwtStatus">{{ session('jwt_token') ? 'Available' : 'Missing' }}</span></p>
            </div>

            @guest
            <div class="mt-4">
                <a href="/quick-login/customer" class="bg-blue-600 text-white px-4 py-2 rounded">Quick Login as Customer</a>
            </div>
            @endguest
        </div>

        <!-- Test Product -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Test Add to Cart</h2>

            <div class="border p-4 rounded mb-4">
                <h3 class="font-semibold">Test Product: LPG Full Tank (9kg)</h3>
                <p class="text-gray-600">Price: $89.95</p>
                <p class="text-gray-600">Stock: 25</p>
                <p class="text-gray-600">Variant ID: 1</p>
            </div>

            <button id="testAddToCart" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
                Test Add to Cart
            </button>

            <div id="testResults" class="mt-6 hidden">
                <h4 class="font-semibold mb-2">Test Results:</h4>
                <pre id="resultOutput" class="bg-gray-100 p-4 rounded text-sm overflow-auto max-h-64"></pre>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🧪 Add to Cart Test Page Loaded');

    // Update authentication status from JavaScript
    document.getElementById('authStatus').textContent = window.isAuthenticated ? 'Yes' : 'No';

    // Test add to cart functionality
    document.getElementById('testAddToCart').addEventListener('click', async function() {
        console.log('🧪 === ADD TO CART TEST START ===');

        const results = [];
        const log = (message) => {
            console.log(message);
            results.push(message);
            document.getElementById('resultOutput').textContent = results.join('\n');
            document.getElementById('testResults').classList.remove('hidden');
        };

        log('🧪 Testing Add to Cart...');
        log('🔍 Authentication check: ' + (window.isAuthenticated ? 'AUTHENTICATED' : 'NOT AUTHENTICATED'));
        log('🔍 JWT Token: ' + (window.JWT_TOKEN ? 'AVAILABLE' : 'MISSING'));

        if (!window.isAuthenticated) {
            log('❌ FAILED: Not authenticated');
            return;
        }

        try {
            log('📡 Sending POST request to /api/cart...');

            const response = await axios.post('/api/cart', {
                product_variant_id: 1, // Test with first variant (9kg LPG)
                quantity: 1,
                is_preorder: false
            });

            log('✅ Response received:');
            log('📦 Status: ' + response.status);
            log('📦 Data: ' + JSON.stringify(response.data, null, 2));

            if (response.data.success) {
                log('🎉 SUCCESS: Product added to cart!');

                // Try to update cart if available
                if (window.app && window.app.loadCart) {
                    log('🔄 Updating cart display...');
                    await window.app.loadCart(true);
                    log('✅ Cart display updated. New count: ' + window.app.cartCount);
                } else {
                    log('⚠️ WARNING: Cannot update cart display - global app not available');
                }
            } else {
                log('❌ FAILED: ' + (response.data.message || 'Unknown error'));
            }

        } catch (error) {
            log('❌ ERROR: ' + error.message);
            if (error.response) {
                log('📦 Error Status: ' + error.response.status);
                log('📦 Error Data: ' + JSON.stringify(error.response.data, null, 2));
            }
        }

        log('🏁 === ADD TO CART TEST END ===');
    });
});
</script>
@endsection