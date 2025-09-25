@extends('layouts.app')

@section('title', 'Test Cart Functionality')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="testCartApp()" x-init="init()">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Test Cart Functionality</h1>

        <!-- Auth Status -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Authentication Status</h2>
            <div class="space-y-2">
                <p><strong>Authenticated:</strong> <span x-text="window.isAuthenticated ? 'Yes' : 'No'" :class="window.isAuthenticated ? 'text-green-600' : 'text-red-600'"></span></p>
                <p><strong>JWT Token:</strong> <span x-text="window.JWT_TOKEN ? 'Available' : 'Not found'" :class="window.JWT_TOKEN ? 'text-green-600' : 'text-red-600'"></span></p>
                <p><strong>Global App:</strong> <span x-text="window.app ? 'Available' : 'Not found'" :class="window.app ? 'text-green-600' : 'text-red-600'"></span></p>
            </div>

            @guest
            <div class="mt-4 p-4 bg-yellow-50 rounded-lg">
                <p class="text-yellow-800">You are not logged in. <a href="/quick-login/customer" class="text-blue-600 underline">Quick Login as Customer</a></p>
            </div>
            @endguest
        </div>

        <!-- Test Products -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Test Add to Cart</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="testProducts.length > 0">
                <template x-for="product in testProducts" :key="product.id">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold" x-text="product.name"></h3>
                        <p class="text-gray-600 text-sm" x-text="product.description"></p>
                        <p class="text-blue-600 font-bold mt-2">$<span x-text="product.price"></span></p>
                        <button @click="testAddToCart(product)"
                                :disabled="addingToCart[product.variant_id]"
                                class="w-full mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!addingToCart[product.variant_id]">Add to Cart</span>
                            <span x-show="addingToCart[product.variant_id]">Adding...</span>
                        </button>
                    </div>
                </template>
            </div>

            <div x-show="!loading && testProducts.length === 0" class="text-center py-8 text-gray-500">
                No products available for testing
            </div>
        </div>

        <!-- Cart Status -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Cart Status</h2>
            <div class="space-y-2">
                <p><strong>Cart Items:</strong> <span x-text="window.app ? window.app.cartCount : 0"></span></p>
                <p><strong>Cart Loading:</strong> <span x-text="window.app ? (window.app.cartLoading ? 'Yes' : 'No') : 'N/A'"></span></p>
                <button @click="reloadCart()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Reload Cart
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function testCartApp() {
    return {
        loading: false,
        testProducts: [],
        addingToCart: {},

        async init() {
            console.log('Test Cart App initializing...');
            await this.loadTestProducts();
        },

        async loadTestProducts() {
            this.loading = true;
            try {
                const response = await axios.get('/api/products');
                if (response.data && response.data.data) {
                    this.testProducts = response.data.data.slice(0, 4).map(product => ({
                        id: product.id,
                        name: product.name || 'Test Product',
                        description: product.description || 'Test description',
                        price: product.variants && product.variants[0] ? product.variants[0].price_aud : '0.00',
                        variant_id: product.variants && product.variants[0] ? product.variants[0].id : null,
                        stock: product.variants && product.variants[0] ? product.variants[0].stock_quantity : 0
                    })).filter(p => p.variant_id);
                }
            } catch (error) {
                console.error('Error loading test products:', error);
            } finally {
                this.loading = false;
            }
        },

        async testAddToCart(product) {
            console.log('🧪 Testing add to cart for:', product.name);

            if (!window.app) {
                alert('Global app not available');
                return;
            }

            if (!window.isAuthenticated) {
                alert('Please login first');
                return;
            }

            this.addingToCart[product.variant_id] = true;

            try {
                const result = await window.app.addToCart(product.variant_id, 1, {
                    isPreorder: false
                });

                console.log('🧪 Add to cart result:', result);

                if (result.success !== false) {
                    console.log('✅ Success! Notification should show now');
                } else {
                    console.error('❌ Failed:', result.error);
                }
            } catch (error) {
                console.error('🧪 Test error:', error);
                alert('Error: ' + error.message);
            } finally {
                this.addingToCart[product.variant_id] = false;
            }
        },

        async reloadCart() {
            if (window.app && window.app.loadCart) {
                await window.app.loadCart(true);
                console.log('Cart reloaded');
            }
        }
    };
}
</script>
@endsection