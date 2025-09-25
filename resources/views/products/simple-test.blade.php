@extends('layouts.app')

@section('title', 'Simple Products Test')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Simple Products Test</h1>

        <!-- Static Test -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Static Test (Should Always Show)</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-lg shadow border">
                    <h3 class="font-semibold">Test Product 1</h3>
                    <p class="text-gray-600">This is a static test product</p>
                    <p class="text-blue-600 font-bold">$99.99</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow border">
                    <h3 class="font-semibold">Test Product 2</h3>
                    <p class="text-gray-600">This is another static test product</p>
                    <p class="text-blue-600 font-bold">$149.99</p>
                </div>
            </div>
        </div>

        <!-- Alpine.js Test -->
        <div x-data="{ message: 'Alpine.js is working!', count: 0 }" class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Alpine.js Test</h2>
            <p x-text="message"></p>
            <button @click="count++" class="bg-blue-600 text-white px-4 py-2 rounded mt-2">Click me</button>
            <p>Count: <span x-text="count"></span></p>
        </div>

        <!-- API Test -->
        <div x-data="apiTest()" x-init="init()" class="mb-8">
            <h2 class="text-xl font-semibold mb-4">API Test</h2>
            <div x-show="loading">Loading...</div>
            <div x-show="error" x-text="error" class="text-red-600"></div>
            <div x-show="!loading && !error">
                <p>Products loaded: <span x-text="products.length"></span></p>
                <div x-show="products.length > 0">
                    <template x-for="product in products.slice(0, 3)" :key="product.id">
                        <div class="bg-gray-100 p-3 rounded mb-2">
                            <strong x-text="product.name"></strong> -
                            <span x-text="product.description"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function apiTest() {
    return {
        products: [],
        loading: true,
        error: null,

        async init() {
            console.log('🧪 API Test initializing...');
            await this.loadProducts();
        },

        async loadProducts() {
            this.loading = true;
            this.error = null;

            try {
                console.log('🌐 Fetching from /api/products...');
                const response = await axios.get('/api/products');
                console.log('📦 Response:', response.data);

                if (response.data && response.data.data) {
                    this.products = response.data.data;
                    console.log('✅ Products loaded:', this.products.length);
                } else {
                    this.error = 'No data in response';
                }
            } catch (error) {
                console.error('❌ API Error:', error);
                this.error = 'Failed to load: ' + (error.message || 'Unknown error');
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endsection