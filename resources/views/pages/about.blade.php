@extends('layouts.app')

@section('title', 'About Us - BellGas')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
    <div class="absolute inset-0 bg-black opacity-20"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">About BellGas</h1>
            <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
                Your trusted partner for safe, reliable, and convenient LPG services across Bellingen
            </p>
        </div>
    </div>
</section>

<!-- Our Story -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-6">Our Story</h2>
                <div class="space-y-4 text-gray-600 text-lg">
                    <p>
                        Founded in 2015, BellGas has been serving the Bellingen community with premium LPG services for over 8 years.
                        What started as a small local business has grown into one of Bellingen's most trusted LPG suppliers.
                    </p>
                    <p>
                        We recognized that customers needed a reliable, convenient way to access quality LPG cylinders for their 
                        homes and businesses. That's why we built our platform to make ordering and receiving LPG as simple as possible.
                    </p>
                    <p>
                        Today, we're proud to serve thousands of satisfied customers across Bellingen and surrounding areas, from individual households
                        to large commercial operations. Our commitment to safety, quality, and customer service remains at the
                        heart of everything we do.
                    </p>
                </div>
            </div>
            <div class="relative">
                <div class="aspect-w-4 aspect-h-3 bg-gradient-to-br from-blue-500 to-blue-700 rounded-lg flex items-center justify-center">
                    <i class="fas fa-fire text-8xl text-white opacity-80"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Mission -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Our Mission</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                To provide safe, reliable, and convenient LPG services while building lasting relationships with our customers
            </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center p-6 bg-white rounded-xl shadow-md">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-2xl text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Safety First</h3>
                <p class="text-gray-600">
                    We prioritize safety in everything we do, from cylinder inspection to delivery protocols. 
                    Your safety is our responsibility.
                </p>
            </div>
            
            <div class="text-center p-6 bg-white rounded-xl shadow-md">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-leaf text-2xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Environmental Care</h3>
                <p class="text-gray-600">
                    LPG is a cleaner-burning fuel that helps reduce environmental impact. We're committed to 
                    sustainable energy solutions.
                </p>
            </div>
            
            <div class="text-center p-6 bg-white rounded-xl shadow-md">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users text-2xl text-orange-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Customer Focus</h3>
                <p class="text-gray-600">
                    Every decision we make is centered around providing the best possible experience for our customers. 
                    Your satisfaction drives us.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Our Values -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Our Values</h2>
            <p class="text-xl text-gray-600">The principles that guide our business and shape our culture</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-handshake text-3xl text-blue-600"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Integrity</h3>
                <p class="text-gray-600 text-sm">We conduct business with honesty, transparency, and ethical practices.</p>
            </div>
            
            <div class="text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-award text-3xl text-green-600"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Excellence</h3>
                <p class="text-gray-600 text-sm">We strive for excellence in every aspect of our service delivery.</p>
            </div>
            
            <div class="text-center">
                <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lightbulb text-3xl text-purple-600"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Innovation</h3>
                <p class="text-gray-600 text-sm">We embrace technology and innovation to improve our services continuously.</p>
            </div>
            
            <div class="text-center">
                <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-heart text-3xl text-orange-600"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Community</h3>
                <p class="text-gray-600 text-sm">We're committed to supporting and giving back to our local community.</p>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-16 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Why Choose BellGas?</h2>
            <p class="text-xl">Here's what sets us apart from the competition</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-clock text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Same-Day Delivery</h3>
                <p class="text-blue-100">Need LPG urgently? We offer same-day delivery across Bellingen and surrounding areas.</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-certificate text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Certified Safe</h3>
                <p class="text-blue-100">All our cylinders are regularly inspected and certified to Australian standards.</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-dollar-sign text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Competitive Pricing</h3>
                <p class="text-blue-100">Fair, transparent pricing with no hidden fees or surprise charges.</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-phone text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">24/7 Support</h3>
                <p class="text-blue-100">Our customer support team is available around the clock to assist you.</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-recycle text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Cylinder Exchange</h3>
                <p class="text-blue-100">Easy cylinder exchange program - bring your empty, get a full one.</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-mobile-alt text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Easy Ordering</h3>
                <p class="text-blue-100">Order online, via phone, or through our mobile-friendly platform.</p>
            </div>
        </div>
    </div>
</section>

<!-- Our Team -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Meet Our Team</h2>
            <p class="text-xl text-gray-600">The dedicated professionals behind BellGas</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="w-20 h-20 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user text-2xl text-white"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Michael Chen</h3>
                <p class="text-blue-600 font-medium mb-2">Founder & CEO</p>
                <p class="text-gray-600 text-sm">With over 15 years in the energy sector, Michael founded BellGas with a vision to revolutionize LPG delivery.</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user text-2xl text-white"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Sarah Williams</h3>
                <p class="text-green-600 font-medium mb-2">Operations Manager</p>
                <p class="text-gray-600 text-sm">Sarah ensures our operations run smoothly and safely, overseeing delivery logistics and safety protocols.</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="w-20 h-20 bg-purple-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user text-2xl text-white"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">David Thompson</h3>
                <p class="text-purple-600 font-medium mb-2">Customer Service Lead</p>
                <p class="text-gray-600 text-sm">David leads our customer service team, ensuring every customer receives exceptional support and care.</p>
            </div>
        </div>
    </div>
</section>

<!-- Company Stats -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">BellGas by the Numbers</h2>
            <p class="text-xl text-gray-600">Our track record speaks for itself</p>
        </div>
        
        <div class="grid md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-4xl font-bold text-blue-600 mb-2">8+</div>
                <div class="text-gray-600">Years of Service</div>
            </div>
            
            <div>
                <div class="text-4xl font-bold text-green-600 mb-2">50,000+</div>
                <div class="text-gray-600">Cylinders Delivered</div>
            </div>
            
            <div>
                <div class="text-4xl font-bold text-purple-600 mb-2">5,000+</div>
                <div class="text-gray-600">Happy Customers</div>
            </div>
            
            <div>
                <div class="text-4xl font-bold text-orange-600 mb-2">99.8%</div>
                <div class="text-gray-600">Customer Satisfaction</div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-16 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Experience the BellGas Difference?</h2>
        <p class="text-xl mb-8">Join thousands of satisfied customers who trust us for their LPG needs</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/products" 
               class="bg-orange-500 hover:bg-orange-600 text-white px-8 py-3 rounded-lg text-lg font-semibold transition transform hover:scale-105">
                <i class="fas fa-shopping-cart mr-2"></i>Shop Now
            </a>
            <a href="/contact" 
               class="border-2 border-white text-white hover:bg-white hover:text-blue-600 px-8 py-3 rounded-lg text-lg font-semibold transition">
                <i class="fas fa-phone mr-2"></i>Get in Touch
            </a>
        </div>
    </div>
</section>
@endsection