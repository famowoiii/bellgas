@extends('layouts.app')

@section('title', 'Products Debug - BellGas')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="debugProductsApp()" x-init="init()">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Products Debug</h1>
            <p class="text-gray-600">Find the perfect LPG solution for your needs</p>
        </div>

        <!-- Debug Info -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <h2 class="text-lg font-semibold mb-4">Debug Information</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p><strong>Loading:</strong> <span x-text="loading"></span></p>
                    <p><strong>Error:</strong> <span x-text="error || 'None'"></span></p>
                    <p><strong>Products Count:</strong> <span x-text="products.length"></span></p>
                </div>
                <div>
                    <p><strong>Alpine.js Working:</strong> <span x-text="'Yes'"></span></p>
                    <p><strong>Current Time:</strong> <span x-text="new Date().toLocaleTimeString()"></span></p>
                    <button @click="debugReload()" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">Reload Products</button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500 border-t-transparent"></div>
            <span class="ml-2 text-gray-600">Loading products...</span>
        </div>

        <!-- Error State -->
        <div x-show="error && !loading" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
            <div class="flex items-center space-x-2 text-red-800">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="font-medium">Error loading products</span>
            </div>
            <p class="text-red-600 text-sm mt-1" x-text="error"></p>
        </div>

        <!-- Products List (Simple) -->
        <div x-show="!loading && !error && products.length > 0" class="space-y-4">
            <h2 class="text-xl font-semibold">Products List</h2>
            <template x-for="product in products" :key="product.id">
                <div class="bg-white p-4 rounded-lg shadow border">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-lg" x-text="product.name"></h3>
                            <p class="text-gray-600 text-sm" x-text="product.description"></p>
                            <p class="text-blue-600 font-bold mt-2">$<span x-text="product.price"></span></p>
                            <p class="text-sm text-gray-500">Stock: <span x-text="product.stock"></span></p>
                        </div>
                        <button @click="testAddToCart(product)"
                                :disabled="product.stock <= 0"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 disabled:opacity-50">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && !error && products.length === 0" class="text-center py-12">
            <i class="fas fa-box text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-800 mb-2">No products found</h3>
            <p class="text-gray-600">Check back later for new products</p>
        </div>
    </div>
</div>

<script>
function debugProductsApp() {
    return {
        products: [],
        loading: false,
        error: null,

        async init() {
            console.log('🐛 Debug Products App initializing...');
            await this.loadProducts();
        },

        async debugReload() {
            console.log('🔄 Manual reload requested...');
            await this.loadProducts();
        },

        async loadProducts() {
            console.log('🌐 Starting loadProducts...');
            this.loading = true;
            this.error = null;

            try {
                console.log('📡 Calling axios.get(/api/products)...');
                const response = await axios.get('/api/products');
                console.log('📦 Raw response:', response);
                console.log('📦 Response data:', response.data);

                if (response.data && response.data.data) {
                    console.log('✅ Processing products data...');
                    this.products = response.data.data.map(product => {
                        const processed = {
                            id: product.id,
                            name: product.name || 'Unnamed Product',
                            description: product.description || 'Premium LPG product for your energy needs.',
                            price: product.variants && product.variants[0] ? product.variants[0].price_aud : '0.00',
                            variant_id: product.variants && product.variants[0] ? product.variants[0].id : null,
                            stock: product.variants && product.variants[0] ? product.variants[0].stock_quantity : 0,
                            category: product.category?.name || 'LPG Product',
                            image_url: product.image_url || (product.photos && product.photos[0] ? product.photos[0].url : null)
                        };
                        console.log('📝 Processed product:', processed);
                        return processed;
                    });

                    console.log('✅ Products loaded successfully:', this.products.length);
                } else {
                    console.warn('⚠️ No data found in response');
                    this.products = [];
                    this.error = 'No products data found';
                }
            } catch (error) {
                console.error('❌ Error loading products:', error);
                this.error = 'Failed to load products: ' + (error.message || 'Unknown error');
                this.products = [];
            } finally {
                this.loading = false;
                console.log('🏁 loadProducts finished. Products:', this.products.length);
            }
        },

        testAddToCart(product) {
            alert('Add to Cart clicked for: ' + product.name);
        }
    }
}
</script>
@endsection