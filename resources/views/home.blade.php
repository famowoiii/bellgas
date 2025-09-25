@extends('layouts.app')

@section('title', 'BellGas - Premium LPG Services')

@section('content')
<div x-data="homeData()" x-init="loadProducts()">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-r from-blue-600 to-blue-800 text-white overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-20"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    Premium <span class="text-orange-400">LPG</span> Services
                </h1>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
                    Reliable, safe, and convenient LPG delivery and refill services across Sydney
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="/products" 
                       class="bg-orange-500 hover:bg-orange-600 text-white px-8 py-3 rounded-lg text-lg font-semibold transition transform hover:scale-105">
                        <i class="fas fa-shopping-cart mr-2"></i>Shop Now
                    </a>
                    <a href="/about" 
                       class="border-2 border-white text-white hover:bg-white hover:text-blue-600 px-8 py-3 rounded-lg text-lg font-semibold transition">
                        Learn More
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Floating Elements -->
        <div class="absolute top-20 left-10 w-20 h-20 bg-orange-400 rounded-full opacity-20 animate-bounce"></div>
        <div class="absolute bottom-20 right-10 w-16 h-16 bg-blue-300 rounded-full opacity-30 animate-pulse"></div>
        <div class="absolute top-1/2 right-20 w-12 h-12 bg-white rounded-full opacity-10 animate-bounce" style="animation-delay: 1s"></div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Why Choose BellGas?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    We provide safe, reliable, and convenient LPG services with a commitment to excellence
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-6 rounded-xl hover:shadow-lg transition transform hover:scale-105">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-truck text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Fast Delivery</h3>
                    <p class="text-gray-600">Quick and reliable delivery across Sydney metro area. Same-day delivery available for urgent needs.</p>
                </div>
                
                <div class="text-center p-6 rounded-xl hover:shadow-lg transition transform hover:scale-105">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Safety First</h3>
                    <p class="text-gray-600">All our cylinders are regularly inspected and certified. We follow strict safety protocols for your peace of mind.</p>
                </div>
                
                <div class="text-center p-6 rounded-xl hover:shadow-lg transition transform hover:scale-105">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-dollar-sign text-2xl text-orange-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Best Prices</h3>
                    <p class="text-gray-600">Competitive pricing with no hidden fees. Transparent costs for both delivery and pickup options.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Preview -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Our Products</h2>
                <p class="text-xl text-gray-600">Quality LPG cylinders for home and business use</p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <template x-for="product in featuredProducts" :key="product.id">
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition transform hover:scale-105 overflow-hidden">
                        <div class="h-48 bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center">
                            <i class="fas fa-fire text-6xl text-white opacity-80"></i>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-semibold mb-2" x-text="product.name"></h3>
                            <p class="text-gray-600 mb-4 text-sm" x-text="product.description"></p>
                            
                            <template x-for="variant in product.variants?.slice(0, 2)" :key="`product-${product.id}-variant-${variant.id}`">
                                <div class="flex justify-between items-center mb-2 p-2 bg-gray-50 rounded">
                                    <span class="text-sm font-medium" x-text="variant.name"></span>
                                    <div class="text-right">
                                        <span class="text-lg font-bold text-blue-600">$<span x-text="variant.price_aud"></span></span>
                                        <button @click="$parent.addToCart(variant.id)" 
                                            class="ml-2 bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition">
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </template>
                            
                            <div class="mt-4 pt-4 border-t">
                                <a :href="'/products/' + product.slug" 
                                   class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                    View Details <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            
            <div class="text-center mt-12">
                <a href="/products" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg text-lg font-semibold transition transform hover:scale-105">
                    View All Products
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">How It Works</h2>
                <p class="text-xl text-gray-600">Simple steps to get your LPG delivered or exchanged</p>
            </div>
            
            <div class="grid md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 relative">
                        <i class="fas fa-mouse-pointer text-2xl text-blue-600"></i>
                        <span class="absolute -top-2 -right-2 w-6 h-6 bg-blue-600 text-white text-sm rounded-full flex items-center justify-center">1</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Browse & Select</h3>
                    <p class="text-gray-600 text-sm">Choose from our range of LPG cylinders and services</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 relative">
                        <i class="fas fa-credit-card text-2xl text-green-600"></i>
                        <span class="absolute -top-2 -right-2 w-6 h-6 bg-green-600 text-white text-sm rounded-full flex items-center justify-center">2</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Secure Payment</h3>
                    <p class="text-gray-600 text-sm">Pay safely with our encrypted payment system</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4 relative">
                        <i class="fas fa-truck text-2xl text-orange-600"></i>
                        <span class="absolute -top-2 -right-2 w-6 h-6 bg-orange-600 text-white text-sm rounded-full flex items-center justify-center">3</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Fast Delivery</h3>
                    <p class="text-gray-600 text-sm">Get your order delivered or schedule pickup</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4 relative">
                        <i class="fas fa-check-circle text-2xl text-purple-600"></i>
                        <span class="absolute -top-2 -right-2 w-6 h-6 bg-purple-600 text-white text-sm rounded-full flex items-center justify-center">4</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Enjoy Service</h3>
                    <p class="text-gray-600 text-sm">Reliable LPG supply for your home or business</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Get Started?</h2>
            <p class="text-xl mb-8">Join thousands of satisfied customers who trust BellGas for their LPG needs</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/products" 
                   class="bg-orange-500 hover:bg-orange-600 text-white px-8 py-3 rounded-lg text-lg font-semibold transition transform hover:scale-105">
                    <i class="fas fa-shopping-cart mr-2"></i>Order Now
                </a>
                <a href="/contact" 
                   class="border-2 border-white text-white hover:bg-white hover:text-blue-600 px-8 py-3 rounded-lg text-lg font-semibold transition">
                    <i class="fas fa-phone mr-2"></i>Contact Us
                </a>
            </div>
        </div>
    </section>
</div>

<script>
function homeData() {
    return {
        featuredProducts: [],
        
        async loadProducts() {
            try {
                const response = await axios.get('/api/products?limit=3');
                this.featuredProducts = response.data.data.slice(0, 3);
            } catch (error) {
                console.error('Failed to load products:', error);
            }
        }
    }
}
</script>
@endsection