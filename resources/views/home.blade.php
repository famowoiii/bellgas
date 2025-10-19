@extends('layouts.app')

@section('title', 'BellGas - Premium LPG Services')

@section('content')
<div>
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

    <!-- Product Categories Section (Static) -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Our Product Range</h2>
                <p class="text-xl text-gray-600">Browse our complete selection of LPG products and services</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Full Tank Category -->
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition transform hover:scale-105 overflow-hidden">
                    <div class="h-48 bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center">
                        <i class="fas fa-fire text-6xl text-white opacity-80"></i>
                    </div>
                    <div class="p-6">
                        <h3 class="text-2xl font-bold mb-3 text-gray-800">Full Tank Cylinders</h3>
                        <p class="text-gray-600 mb-4">
                            Complete LPG gas cylinders with full tank. Perfect for new setups or replacements.
                        </p>
                        <ul class="space-y-2 mb-6 text-sm text-gray-700">
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Various sizes available
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Safety certified
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Delivery or pickup options
                            </li>
                        </ul>
                        <a href="/products" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center px-4 py-3 rounded-lg font-semibold transition">
                            Browse Full Tanks
                        </a>
                    </div>
                </div>

                <!-- Refill Service Category -->
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition transform hover:scale-105 overflow-hidden">
                    <div class="h-48 bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center">
                        <i class="fas fa-sync-alt text-6xl text-white opacity-80"></i>
                    </div>
                    <div class="p-6">
                        <h3 class="text-2xl font-bold mb-3 text-gray-800">Refill Services</h3>
                        <p class="text-gray-600 mb-4">
                            Quick and convenient refill service for your existing LPG cylinders.
                        </p>
                        <ul class="space-y-2 mb-6 text-sm text-gray-700">
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Fast turnaround time
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Competitive pricing
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                All cylinder sizes
                            </li>
                        </ul>
                        <a href="/products" class="block w-full bg-green-600 hover:bg-green-700 text-white text-center px-4 py-3 rounded-lg font-semibold transition">
                            Browse Refills
                        </a>
                    </div>
                </div>

                <!-- Portable Category -->
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition transform hover:scale-105 overflow-hidden">
                    <div class="h-48 bg-gradient-to-br from-orange-500 to-orange-700 flex items-center justify-center">
                        <i class="fas fa-campground text-6xl text-white opacity-80"></i>
                    </div>
                    <div class="p-6">
                        <h3 class="text-2xl font-bold mb-3 text-gray-800">Portable Canisters</h3>
                        <p class="text-gray-600 mb-4">
                            Compact and portable LPG solutions for camping, outdoor activities, and emergencies.
                        </p>
                        <ul class="space-y-2 mb-6 text-sm text-gray-700">
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Lightweight & portable
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Perfect for camping
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Easy to store
                            </li>
                        </ul>
                        <a href="/products" class="block w-full bg-orange-600 hover:bg-orange-700 text-white text-center px-4 py-3 rounded-lg font-semibold transition">
                            Browse Portable
                        </a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-12">
                <a href="/products"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg text-lg font-semibold transition transform hover:scale-105 inline-block">
                    <i class="fas fa-shopping-cart mr-2"></i>View All Products
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
@endsection