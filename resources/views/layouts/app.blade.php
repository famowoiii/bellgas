<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-data" content="{{ json_encode(auth()->user()) }}">
    <script>window.isAuthenticated = true;</script>
    @if(session('jwt_token'))
    <script>
        window.JWT_TOKEN = '{{ session('jwt_token') }}';
        console.log('🚀 JWT Token from session:', window.JWT_TOKEN.substring(0, 20) + '...');
    </script>
    @elseif(session('frontend_token'))
    <script>
        window.JWT_TOKEN = '{{ session('frontend_token') }}';
        console.log('🚀 Frontend Token from session:', window.JWT_TOKEN.substring(0, 20) + '...');
    </script>
    @else
    <script>
        window.JWT_TOKEN = '';
        console.log('⚠️ No JWT token in session');
    </script>
    @endif
@else
    <script>window.isAuthenticated = false;</script>
@endauth
    <title>@yield('title', 'BellGas - Premium LPG Services')</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Configure Tailwind
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#3b82f6',
                        accent: '#f59e0b',
                        success: '#10b981',
                        danger: '#ef4444'
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Pusher and Laravel Echo for WebSocket connections -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>

    <!-- Axios for HTTP requests -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Ensure DOM is ready before Alpine starts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ DOM Content Loaded');

            // Setup axios defaults with JWT token
            const setupAxiosAuth = () => {
                // Force use window.JWT_TOKEN as primary source
                const windowToken = window.JWT_TOKEN;

                console.log('🔍 Token Debug:');
                console.log('  - Window JWT_TOKEN:', windowToken ? windowToken.substring(0, 20) + '...' : 'EMPTY');
                console.log('  - Window JWT_TOKEN full length:', windowToken ? windowToken.length : 'N/A');

                if (windowToken && windowToken.trim() !== '' && windowToken !== 'undefined') {
                    // Force set axios defaults
                    axios.defaults.headers.common['Authorization'] = 'Bearer ' + windowToken;
                    axios.defaults.headers.common['Accept'] = 'application/json';
                    axios.defaults.headers.common['Content-Type'] = 'application/json';

                    // Store for fallback
                    localStorage.setItem('access_token', windowToken);

                    console.log('✅ Axios token FORCED set:', windowToken.substring(0, 20) + '...');
                    console.log('✅ Authorization header:', axios.defaults.headers.common['Authorization'].substring(0, 30) + '...');

                    // Test immediate API call
                    console.log('🧪 Testing immediate API call...');
                    setTimeout(() => {
                        axios.get('/api/orders').then(response => {
                            console.log('✅ Immediate API test SUCCESS:', response.data.data.length, 'orders');
                        }).catch(error => {
                            console.error('❌ Immediate API test FAILED:', error.response?.status, error.message);
                        });
                    }, 100);

                } else {
                    console.error('❌ NO VALID JWT TOKEN AVAILABLE');
                }
            };

            // Setup axios immediately
            setupAxiosAuth();

            // Setup axios request interceptor for JWT - FORCE TOKEN
            axios.interceptors.request.use(function (config) {
                // ALWAYS use window.JWT_TOKEN as first priority
                const token = window.JWT_TOKEN || localStorage.getItem('access_token');

                console.log('🔧 Interceptor FORCE mode:');
                console.log('  - URL:', config.url);
                console.log('  - Token available:', token ? 'YES (' + token.substring(0, 20) + '...)' : 'NO');

                if (token && token !== 'undefined' && token.trim() !== '') {
                    // FORCE override any existing auth header
                    config.headers.Authorization = 'Bearer ' + token;
                    console.log('🔥 FORCE attached Authorization:', config.headers.Authorization.substring(0, 30) + '...');
                } else {
                    console.error('🚨 CRITICAL: No token available for API request!');
                }
                return config;
            });

            // Also setup axios when window loads
            window.addEventListener('load', setupAxiosAuth);
        });
    </script>

    @stack('head-scripts')
</head>
<body class="bg-gray-50 font-sans antialiased" x-data="app()" x-init="window.app = this; console.log('🚀 window.app assigned:', !!window.app); console.log('🚀 showNotification available:', !!window.app.showNotification); console.log('🚀 showNotification type:', typeof window.app.showNotification); console.log('🚀 cartItems available:', !!window.app.cartItems); console.log('🚀 loadCart available:', !!window.app.loadCart); init();">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <i class="fas fa-fire text-2xl text-orange-500"></i>
                        <span class="text-xl font-bold text-gray-800">BellGas</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="/products" class="text-gray-600 hover:text-primary transition">Products</a>
                    <a href="/about" class="text-gray-600 hover:text-primary transition">About</a>
                    <a href="/contact" class="text-gray-600 hover:text-primary transition">Contact</a>

                    <!-- Cart -->
                    <div class="relative">
                        <button @click="toggleCart()" class="relative p-2 text-gray-600 hover:text-primary transition" data-testid="cart-button">
                            <i class="fas fa-shopping-cart text-lg"></i>
                            <span x-show="cartCount > 0" x-text="cartCount" data-testid="cart-count"
                                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"></span>
                        </button>
                    </div>

                    <!-- Bell Notification for Admin -->
                    <div x-show="user && (user.role === 'ADMIN' || user.role === 'MERCHANT')" class="relative" x-data="{ open: false }">
                        <button @click="open = !open; clearBellNotifications()"
                                class="relative p-2 text-gray-600 hover:text-blue-600 transition">
                            <i class="fas fa-bell text-lg" :class="bellCount > 0 ? 'text-red-500 animate-pulse' : ''"></i>
                            <span x-show="bellCount > 0" x-text="bellCount"
                                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"></span>
                        </button>

                        <!-- Bell Dropdown -->
                        <div x-show="open" @click.away="open = false"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border py-2 z-50 max-h-96 overflow-y-auto">
                            <div class="px-4 py-2 border-b">
                                <h4 class="font-semibold text-gray-800">New Order Notifications</h4>
                            </div>

                            <div x-show="bellNotifications.length === 0" class="p-4 text-center text-gray-500">
                                <i class="fas fa-bell-slash text-2xl mb-2"></i>
                                <p>No new notifications</p>
                            </div>

                            <template x-for="notification in bellNotifications" :key="notification.id">
                                <div class="px-4 py-3 border-b hover:bg-gray-50">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-shopping-bag text-green-600 text-xs"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-800" x-text="notification.message"></p>
                                            <p class="text-xs text-gray-500" x-text="notification.time"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div x-show="!user" class="flex items-center space-x-2">
                        <a href="/login" class="text-gray-600 hover:text-primary transition">Login</a>
                        <a href="/register" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Sign Up</a>
                    </div>

                    <div x-show="user" class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-primary transition">
                            <i class="fas fa-user"></i>
                            <span x-text="user?.first_name"></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 z-10">

                            <!-- Admin Menu -->
                            <div x-show="user?.role === 'ADMIN' || user?.role === 'MERCHANT'" class="border-b mb-2">
                                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">Admin Panel</div>
                                <a href="/admin/dashboard" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                    <i class="fas fa-tachometer-alt mr-2"></i>Admin Dashboard
                                </a>
                                <a href="/admin/orders" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                    <i class="fas fa-shopping-bag mr-2"></i>Order Management
                                </a>
                                <a href="/admin/products" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                    <i class="fas fa-box mr-2"></i>Product Management
                                </a>
                                <a href="/admin/customers" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                    <i class="fas fa-users mr-2"></i>Customer Management
                                </a>
                                <a href="/admin/settings" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                    <i class="fas fa-cog mr-2"></i>Settings
                                </a>
                            </div>

                            <!-- Customer Menu -->
                            <div x-show="user?.role === 'CUSTOMER' || !user?.role">
                                <a href="/dashboard" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                </a>
                                <a href="/orders" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-list mr-2"></i>My Orders
                                </a>
                                <a href="/profile" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user mr-2"></i>Profile
                                </a>
                                <a href="/addresses" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-map-marker-alt mr-2"></i>My Addresses
                                </a>
                            </div>

                            <div class="border-t my-1"></div>
                            <button @click="logout()"
                                :disabled="loadingStates.loggingOut"
                                class="block w-full text-left px-4 py-2 text-red-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                <div x-show="loadingStates.loggingOut" class="animate-spin rounded-full h-4 w-4 border border-red-400 border-t-transparent"></div>
                                <span x-show="!loadingStates.loggingOut">Logout</span>
                                <span x-show="loadingStates.loggingOut">Logging out...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-600 hover:text-primary">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div x-show="mobileMenuOpen" class="md:hidden bg-white border-t">
            <div class="px-4 py-2 space-y-2">
                <a href="/products" class="block py-2 text-gray-600 hover:text-primary">Products</a>
                <a href="/about" class="block py-2 text-gray-600 hover:text-primary">About</a>
                <a href="/contact" class="block py-2 text-gray-600 hover:text-primary">Contact</a>

                <div x-show="!user" class="border-t pt-2">
                    <a href="/login" class="block py-2 text-gray-600 hover:text-primary">Login</a>
                    <a href="/register" class="block py-2 text-primary font-medium">Sign Up</a>
                </div>

                <div x-show="user" class="border-t pt-2">
                    <div x-show="user?.role === 'ADMIN' || user?.role === 'MERCHANT'">
                        <a href="/admin/dashboard" class="block py-2 text-gray-600 hover:text-primary">Admin Dashboard</a>
                        <a href="/admin/orders" class="block py-2 text-gray-600 hover:text-primary">Order Management</a>
                    </div>
                    <div x-show="user?.role === 'CUSTOMER' || !user?.role">
                        <a href="/dashboard" class="block py-2 text-gray-600 hover:text-primary">Dashboard</a>
                        <a href="/orders" class="block py-2 text-gray-600 hover:text-primary">My Orders</a>
                        <a href="/profile" class="block py-2 text-gray-600 hover:text-primary">Profile</a>
                    </div>
                    <button @click="logout()" class="block w-full text-left py-2 text-red-600 hover:text-red-800">Logout</button>
                </div>
            </div>
        </div>
    </nav>


    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-fire text-2xl text-orange-500"></i>
                        <span class="text-xl font-bold">BellGas</span>
                    </div>
                    <p class="text-gray-400">Premium LPG services for your home and business needs.</p>
                </div>

                <div>
                    <h3 class="font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/products" class="hover:text-white transition">Products</a></li>
                        <li><a href="/about" class="hover:text-white transition">About Us</a></li>
                        <li><a href="/contact" class="hover:text-white transition">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold mb-4">Support</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/terms" class="hover:text-white transition">Terms of Service</a></li>
                        <li><a href="/privacy" class="hover:text-white transition">Privacy Policy</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold mb-4">Contact Info</h3>
                    <div class="space-y-2 text-gray-400">
                        <p><i class="fas fa-phone mr-2"></i>+61 123 456 789</p>
                        <p><i class="fas fa-envelope mr-2"></i>info@bellgas.com.au</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 BellGas. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- MODERN CART SIDEBAR -->
    <div x-show="cartOpen"
         x-transition:enter="transition-all duration-300 ease-out"
         x-transition:enter-start="translate-x-full opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition-all duration-300 ease-in"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="translate-x-full opacity-0"
         class="fixed inset-y-0 right-0 w-96 bg-white shadow-2xl z-50 overflow-hidden flex flex-col"
         data-testid="cart-sidebar">

        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-blue-600"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Shopping Cart</h2>
                    <p class="text-sm text-gray-600" x-show="hasCartItems" x-text="`${cartCount} items • ${cartTotalFormatted}`"></p>
                </div>
            </div>
            <button @click="cartOpen = false"
                    class="w-8 h-8 rounded-full hover:bg-white/50 flex items-center justify-center transition-colors">
                <i class="fas fa-times text-gray-500"></i>
            </button>
        </div>

        <!-- Loading State -->
        <div x-show="cartLoading" class="flex items-center justify-center py-12">
            <div class="flex items-center space-x-3 text-gray-600">
                <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent"></div>
                <span>Loading cart...</span>
            </div>
        </div>

        <!-- Error State - Improved -->
        <div x-show="cartLoadingError && !cartLoading && !hasCartItems" class="p-6">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-shopping-cart text-gray-400 text-lg"></i>
                </div>
                <h3 class="font-medium text-gray-800 mb-2">Cart temporarily unavailable</h3>
                <p class="text-gray-600 text-sm mb-4">We're having trouble loading your cart items right now.</p>
                <button @click="loadCart(true)"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition-colors">
                    <i class="fas fa-refresh mr-1"></i> Try Again
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!cartLoading && !cartLoadingError && !hasCartItems"
             class="flex-1 flex items-center justify-center py-12">
            <div class="text-center text-gray-500">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shopping-cart text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-800 mb-2">Your cart is empty</h3>
                <p class="text-gray-600 mb-4">Add some products to get started</p>
                <button @click="cartOpen = false; window.location.href = '/products'"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Browse Products
                </button>
            </div>
        </div>

        <!-- Cart Items -->
        <div x-show="!cartLoading && !cartLoadingError && hasCartItems"
             class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
            <template x-for="item in cartItems" :key="item.id">
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-gray-300 transition-colors">
                    <div class="flex space-x-4">
                        <!-- Product Image -->
                        <div class="w-16 h-16 bg-white rounded-lg overflow-hidden border border-gray-200 flex-shrink-0">
                            <img :src="item.image" :alt="item.name"
                                 class="w-full h-full object-cover"
                                 onerror="this.src='/placeholder.jpg'">
                        </div>

                        <!-- Product Details -->
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-gray-800 truncate" x-text="item.name"></h3>
                            <p class="text-sm text-gray-600" x-text="item.variant_name"></p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="font-semibold text-blue-600" x-text="item.formatted_price"></span>
                                <span class="text-sm text-gray-500" x-text="`Total: ${item.formatted_total}`"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Controls -->
                    <div class="flex items-center justify-between mt-4">
                        <div class="flex items-center space-x-3">
                            <button @click="console.log('🔽 CART MINUS clicked for item:', item.id, 'current qty:', item.quantity); updateCartQuantity(item.id, item.quantity - 1)"
                                    :disabled="isCartItemLoading(item.id)"
                                    class="w-8 h-8 rounded-full bg-white border border-gray-300 flex items-center justify-center hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <i class="fas fa-minus text-xs text-gray-600"></i>
                            </button>
                            <span class="w-8 text-center font-medium" x-text="item.quantity"></span>
                            <button @click="console.log('🔼 CART PLUS clicked for item:', item.id, 'current qty:', item.quantity); updateCartQuantity(item.id, item.quantity + 1)"
                                    :disabled="isCartItemLoading(item.id)"
                                    class="w-8 h-8 rounded-full bg-white border border-gray-300 flex items-center justify-center hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <i class="fas fa-plus text-xs text-gray-600"></i>
                            </button>
                        </div>

                        <!-- Remove Button -->
                        <button @click="console.log('🗑️ CART REMOVE clicked for item:', item.id); removeFromCart(item.id)"
                                :disabled="isCartItemLoading(item.id)"
                                class="px-3 py-1 text-red-600 hover:bg-red-50 rounded-lg text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-trash text-xs mr-1"></i>
                            <span x-show="!isCartItemLoading(item.id)">Remove</span>
                            <span x-show="isCartItemLoading(item.id)">...</span>
                        </button>
                    </div>

                    <!-- Loading overlay for individual items -->
                    <div x-show="isCartItemLoading(item.id)"
                         class="absolute inset-0 bg-white/50 rounded-lg flex items-center justify-center">
                        <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-500 border-t-transparent"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer with Total and Checkout -->
        <div x-show="!cartLoading && !cartLoadingError && hasCartItems"
             class="border-t bg-gray-50 p-6 space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-lg font-semibold text-gray-800">Total:</span>
                <span class="text-2xl font-bold text-blue-600" x-text="cartTotalFormatted"></span>
            </div>
            <div class="space-y-2">
                <a href="/checkout"
                   class="w-full bg-blue-600 text-white py-3 rounded-lg text-center block hover:bg-blue-700 transition-colors font-medium">
                    <i class="fas fa-credit-card mr-2"></i>
                    Proceed to Checkout
                </a>
                <button @click="cartOpen = false"
                        class="w-full bg-gray-200 text-gray-800 py-2 rounded-lg text-center hover:bg-gray-300 transition-colors">
                    Continue Shopping
                </button>
            </div>
        </div>
    </div>

    <!-- Cart Overlay -->
    <div x-show="cartOpen" @click="cartOpen = false"
         class="fixed inset-0 bg-black bg-opacity-50 z-40"></div>

    <!-- Audio element for notification sound -->
    <audio id="notificationSound" preload="auto">
        <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+D2xGUgBzuD1fPTeigHKmrE7+GCNQCYH0Sf2+vfcS0AOXDt/W7T8ALm1bvP4WYHKonY6+9DPwGKmXKaY5LMBX4eIGNB1kfg8xvyN0N3OaNXoV2+dFQG1aVG3Kd9VlZAy7dF6dtdJKJl0xJXIa9ysW5bfcZnwqNyOUZDvMfrbNJlkZlbdNZ/FDU8g/vQxlJMrnpTdJNb7BqsQRYo/e9xzZZSy4E5fK13aGk3o5W9sXtP7GvQj29DFKcQF1DXYoVHwbDJPGxPGOpcH2j4D+Xaql0gF2JqWYs53KFTj5qDh/M4KJt2fz6uS8vPq9E3QMqGdNMQ5GBiHUIhk1O5HKJ7YXhGm9c8QLNwp1K6L9Jp3Z8dImfyVAjOLt0VDpZB3kdGHu0Oj9MV7YN8QFQJ1rBNtj1BSWt4Jq1Fkx8PDFcAJhYJuT3jCxUJKo0ZrBqWHd8jCBUO0lR7iVUaGZMQnTD2DktApjITdD+1HG0IrkdnJGOqRZYhUfmhGg7PXjVEQENHs7VGo5TfBsKLVZNI9U0e3CJqBEYT6tU4QTsT1YsKN/I=" type="audio/wav">
    </audio>

    <!-- Modern Notifications Display - Bottom Right -->
    <div class="fixed bottom-6 right-6 z-50 space-y-3" style="max-width: 350px;">
        <template x-for="notification in notifications" :key="notification.id">
            <div :class="{
                'bg-emerald-500 border-emerald-400': notification.type === 'success',
                'bg-red-500 border-red-400': notification.type === 'error',
                'bg-blue-500 border-blue-400': notification.type === 'info',
                'bg-amber-500 border-amber-400': notification.type === 'warning'
            }"
            class="text-white px-5 py-4 rounded-xl shadow-2xl border-l-4 backdrop-blur-sm transform transition-all duration-500 ease-out"
            x-transition:enter="translate-x-full opacity-0 scale-95"
            x-transition:enter-end="translate-x-0 opacity-100 scale-100"
            x-transition:leave="translate-x-0 opacity-100 scale-100"
            x-transition:leave-end="translate-x-full opacity-0 scale-95">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div :class="{
                            'bg-emerald-600': notification.type === 'success',
                            'bg-red-600': notification.type === 'error',
                            'bg-blue-600': notification.type === 'info',
                            'bg-amber-600': notification.type === 'warning'
                        }" class="w-8 h-8 rounded-full flex items-center justify-center">
                            <i :class="{
                                'fas fa-check': notification.type === 'success',
                                'fas fa-exclamation': notification.type === 'error',
                                'fas fa-info': notification.type === 'info',
                                'fas fa-exclamation-triangle': notification.type === 'warning'
                            }" class="text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold leading-5" x-text="notification.message"></p>
                        <p class="text-xs opacity-75 mt-1">Just now</p>
                    </div>
                    <button @click="notifications = notifications.filter(n => n.id !== notification.id)"
                            class="flex-shrink-0 text-white/80 hover:text-white transition-colors duration-200">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Alpine.js App Logic -->
    <script>
        // Function to play notification sound
        function playNotificationSound() {
            try {
                const audio = document.getElementById('notificationSound');
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(e => console.log('Audio play failed:', e));
                }
            } catch (error) {
                console.log('Audio not supported:', error);
            }
        }

        function app() {
            return {
                user: null,
                cartOpen: false,
                mobileMenuOpen: false,
                cartItems: [],
                cartTotal: 0,
                cartLoading: false,
                cartLoadingError: null,
                cartLastLoaded: null,
                cartItemsLoading: {},
                notifications: [],
                bellNotifications: [],
                bellCount: 0,
                loadingStates: {
                    loggingOut: false,
                    addingToCart: false
                },

                get cartCount() {
                    const count = this.cartItems.reduce((total, item) => total + parseInt(item.quantity || 0), 0);
                    return count;
                },

                get cartTotalFormatted() {
                    return '$' + parseFloat(this.cartTotal || 0).toFixed(2);
                },

                get hasCartItems() {
                    return this.cartItems.length > 0;
                },

                get cartSummary() {
                    return {
                        count: this.cartCount,
                        total: this.cartTotal,
                        totalFormatted: this.cartTotalFormatted,
                        items: this.cartItems.length,
                        loading: this.cartLoading,
                        error: this.cartLoadingError
                    };
                },

                get isAuthenticated() {
                    return window.isAuthenticated === true;
                },

                get cart() {
                    // For compatibility with checkout page
                    return this.cartItems || [];
                },

                async init() {
                    console.log('🚀 App initializing...');
                    const startTime = Date.now();

                    this.loadUserData();

                    // Don't reset cart immediately, load from storage first
                    this.loadCartData(); // Load any existing cart data first

                    // Only load from API if authenticated
                    if (this.isAuthenticated) {
                        console.log('✅ User authenticated, loading cart from API');
                        try {
                            await this.loadCart(true); // Force refresh from API
                            console.log('✅ Cart loaded from API successfully');
                        } catch (error) {
                            console.warn('⚠️ Cart loading failed during init:', error);
                            // Clear error to prevent showing temporary unavailable
                            this.cartLoadingError = null;
                        }

                        // Initialize other features after cart is loaded
                        Promise.allSettled([
                            this.initializeWebSocket(),
                            this.loadNotifications(),
                            this.loadBellNotifications()
                        ]).catch(error => {
                            console.warn('⚠️ Some features failed to initialize:', error);
                        });
                    } else {
                        console.log('❌ User not authenticated, using localStorage cart only');
                        this.cartItems = []; // Only clear if not authenticated
                    }

                    const initTime = Date.now() - startTime;
                    console.log(`🏁 App initialization complete in ${initTime}ms. Cart items:`, this.cartItems.length);

                    // Force a cart count update for UI
                    this.$nextTick(() => {
                        console.log('📊 Final cart status:', {
                            items: this.cartItems.length,
                            count: this.cartCount,
                            total: this.cartTotal
                        });
                    });

                    // Setup cart event listeners
                    this.setupCartEventListeners();
                },

                setupCartEventListeners() {
                    console.log('🎧 Setting up cart event listeners');
                    window.addEventListener('cartItemAdded', (event) => {
                        console.log('📡 Cart item added event received:', event.detail);
                        if (event.detail.forceRefresh && this.loadCart) {
                            console.log('🔄 Force refreshing cart due to cartItemAdded event');
                            this.loadCart(true);
                        }
                    });
                },

                loadUserData() {
                    const sessionUser = @json(session('user_data'));
                    const authUser = @json(auth()->user());

                    this.user = sessionUser || authUser;
                    console.log('User loaded:', this.user);
                },

                loadCartData() {
                    const savedCart = localStorage.getItem('bellgas_cart');
                    if (savedCart) {
                        this.cartItems = JSON.parse(savedCart);
                    }
                },


                saveCartData() {
                    localStorage.setItem('bellgas_cart', JSON.stringify(this.cartItems));
                },

                toggleCart() {
                    this.cartOpen = !this.cartOpen;
                },


                updateCartQuantity(productId, newQuantity) {
                    if (newQuantity <= 0) {
                        this.cartItems = this.cartItems.filter(item => item.id !== productId);
                    } else {
                        const item = this.cartItems.find(item => item.id === productId);
                        if (item) {
                            item.quantity = newQuantity;
                        }
                    }
                    this.saveCartData();
                },


                loadNotifications() {
                    // Load from localStorage or API
                },

                // MODERN NOTIFICATION SYSTEM
                showNotification(message, type = 'success', duration = 5000) {
                    console.log('🔔 MODERN showNotification:', message, type);

                    const notification = {
                        id: 'notif_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                        message: String(message),
                        type: ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info',
                        timestamp: new Date(),
                        duration: duration
                    };

                    // Add to beginning of array for newest first
                    this.notifications = [notification, ...this.notifications];

                    // Auto remove after duration
                    setTimeout(() => {
                        this.removeNotification(notification.id);
                    }, duration);

                    // Play sound for success/error only
                    if (['success', 'error'].includes(type)) {
                        this.playNotificationSound();
                    }

                    console.log('✅ MODERN notification added:', notification.id);
                },

                removeNotification(notificationId) {
                    this.notifications = this.notifications.filter(n => n.id !== notificationId);
                    console.log('🗑️ Notification removed:', notificationId);
                },

                // ROBUST CART SYSTEM
                async loadCart(forceRefresh = false) {
                    console.log('🛒 MODERN loadCart called, forceRefresh:', forceRefresh);

                    // Skip if not authenticated
                    if (!this.isAuthenticated) {
                        this.cartItems = [];
                        this.cartLoadingError = null;
                        return { success: true, fromCache: false };
                    }

                    // Return cached if available and not forcing refresh
                    if (!forceRefresh && this.cartItems.length > 0 && this.cartLastLoaded &&
                        (Date.now() - this.cartLastLoaded < 30000)) { // 30 second cache
                        console.log('📦 Using cached cart data');
                        return { success: true, fromCache: true };
                    }

                    // Store current cart items in case of error
                    const currentCartItems = [...this.cartItems];
                    const currentCartTotal = this.cartTotal;

                    try {
                        this.cartLoading = true;
                        // Only clear error if we have no items, otherwise keep showing cart
                        if (this.cartItems.length === 0) {
                            this.cartLoadingError = null;
                        }

                        const token = window.JWT_TOKEN || localStorage.getItem('access_token');
                        if (!token) {
                            throw new Error('Please login to view cart');
                        }

                        const response = await axios.get('/api/cart', {
                            headers: {
                                'Authorization': 'Bearer ' + token,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            timeout: 10000 // 10 second timeout
                        });

                        if (response.data.success) {
                            // Process cart items with additional data
                            const processedItems = (response.data.data.items || []).map(item => {
                                console.log('🔍 Processing cart item:', item);
                                const processedItem = {
                                    ...item,
                                    name: item.productVariant?.product?.name || 'Unknown Product',
                                    variant_name: item.productVariant?.name || 'Standard',
                                    image: item.productVariant?.product?.photos?.[0]?.url || '/placeholder.jpg',
                                    // Fix price display - use effective_price if available, otherwise price
                                    display_price: item.effective_price || item.price || 0,
                                    formatted_price: '$' + parseFloat(item.effective_price || item.price || 0).toFixed(2),
                                    formatted_total: '$' + parseFloat(item.total || 0).toFixed(2)
                                };
                                console.log('✅ Processed cart item:', processedItem);
                                return processedItem;
                            });

                            this.cartItems = processedItems;
                            this.cartTotal = response.data.data.total || 0;
                            this.cartLastLoaded = Date.now();
                            this.cartLoadingError = null; // Clear any errors on success

                            console.log('✅ MODERN cart loaded:', this.cartItems.length, 'items');
                            console.log('💰 Cart total:', this.cartTotal);

                            // Trigger cart update event for other components
                            this.triggerCartUpdateEvent();

                            return { success: true, fromCache: false, items: this.cartItems };
                        } else {
                            throw new Error(response.data.message || 'Failed to load cart');
                        }
                    } catch (error) {
                        console.error('❌ MODERN loadCart error:', error);

                        // Handle different error types more gracefully
                        if (error.response?.status === 401) {
                            // Clear cart for authentication errors
                            this.cartItems = [];
                            this.cartTotal = 0;
                            this.cartLoadingError = 'Please login to view your cart';
                        } else if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
                            // For timeout errors, keep existing cart and don't show disruptive error
                            this.cartItems = currentCartItems;
                            this.cartTotal = currentCartTotal;
                            console.log('⚠️ Cart loading timeout - keeping existing cart');
                            // Only show error if we have no cart items
                            if (currentCartItems.length === 0) {
                                this.cartLoadingError = 'Cart loading slowly...';
                            }
                        } else {
                            // For other errors, keep existing cart if we have it
                            this.cartItems = currentCartItems;
                            this.cartTotal = currentCartTotal;
                            if (currentCartItems.length === 0) {
                                this.cartLoadingError = 'Having trouble loading cart';
                            }
                        }

                        return { success: false, error: this.cartLoadingError, hasItems: currentCartItems.length > 0 };
                    } finally {
                        this.cartLoading = false;
                    }
                },

                async addToCart(variantId, quantity = 1, options = {}) {
                    console.log('🛒 MODERN addToCart:', variantId, quantity, options);

                    if (!this.isAuthenticated) {
                        this.showNotification('Please login to add items to cart', 'error');
                        setTimeout(() => window.location.href = '/login', 2000);
                        return { success: false, error: 'Not authenticated' };
                    }

                    const requestId = 'add_' + Date.now();

                    try {
                        // Set loading state for this specific item
                        this.setCartItemLoading(variantId, true);

                        const token = window.JWT_TOKEN;
                        if (!token) {
                            throw new Error('Authentication token not available');
                        }

                        const payload = {
                            product_variant_id: variantId,
                            quantity: parseInt(quantity) || 1,
                            is_preorder: options.isPreorder || false,
                            notes: options.notes || null
                        };

                        console.log('📤 MODERN sending cart request:', payload);

                        const response = await axios.post('/api/cart', payload, {
                            headers: {
                                'Authorization': 'Bearer ' + token,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-Request-ID': requestId
                            },
                            timeout: 15000 // 15 second timeout
                        });

                        if (response.data.success) {
                            // Show success notification
                            const productName = response.data.data?.productVariant?.product?.name || 'Product';
                            const variantName = response.data.data?.productVariant?.name || '';
                            const displayName = variantName ? `${productName} (${variantName})` : productName;

                            const notificationMessage = response.data.message || `${displayName} berhasil ditambahkan ke cart!`;
                            console.log('🔔 Attempting to show notification:', notificationMessage);

                            this.showNotification(notificationMessage, 'success');
                            console.log('✅ Notification sent successfully');

                            // Force reload cart to get latest data
                            console.log('🔄 Reloading cart after successful add...');
                            await this.loadCart(true);
                            console.log('✅ Cart reloaded. New count:', this.cartCount);

                            console.log('✅ MODERN addToCart success');
                            return { success: true, data: response.data.data };
                        } else {
                            throw new Error(response.data.message || 'Failed to add item to cart');
                        }
                    } catch (error) {
                        console.error('❌ MODERN addToCart error:', error);

                        let errorMessage = 'Failed to add item to cart';
                        if (error.response?.data?.message) {
                            errorMessage = error.response.data.message;
                        } else if (error.message) {
                            errorMessage = error.message;
                        }

                        this.showNotification(errorMessage, 'error');
                        return { success: false, error: errorMessage };
                    } finally {
                        this.setCartItemLoading(variantId, false);
                    }
                },

                async updateCartQuantity(itemId, newQuantity) {
                    console.log('🔄 MODERN updateCartQuantity:', itemId, newQuantity);
                    console.log('🔐 JWT Token available:', !!window.JWT_TOKEN);
                    console.log('👤 User authenticated:', this.isAuthenticated);

                    if (newQuantity <= 0) {
                        console.log('⬇️ Quantity is 0 or less, removing item instead');
                        return await this.removeFromCart(itemId);
                    }

                    try {
                        this.setCartItemLoading(itemId, true);
                        console.log('⏳ Set loading state for item:', itemId);

                        const token = window.JWT_TOKEN;
                        if (!token) {
                            console.error('❌ No JWT token available for cart update');
                            this.showNotification('Authentication required', 'error');
                            return { success: false, error: 'No token' };
                        }

                        // Find the item in our current cart to verify it exists
                        const existingItem = this.cartItems.find(item => item.id === itemId);
                        console.log('🔍 Found item in current cart:', existingItem);

                        if (!existingItem) {
                            console.error('❌ Item not found in current cart items:', itemId);
                            console.error('📋 Available cart items:', this.cartItems.map(item => ({ id: item.id, name: item.name })));
                            this.showNotification('Item not found in cart', 'error');
                            return { success: false, error: 'Item not found locally' };
                        }

                        console.log('📤 Sending PUT request to /api/cart/' + itemId);
                        const response = await axios.put(`/api/cart/${itemId}`, {
                            quantity: parseInt(newQuantity)
                        }, {
                            headers: {
                                'Authorization': 'Bearer ' + token,
                                'Accept': 'application/json'
                            }
                        });

                        console.log('📥 Cart update response:', response.data);

                        if (response.data.success) {
                            console.log('✅ Cart update successful, reloading cart...');
                            await this.loadCart(true);
                            this.showNotification('Cart updated', 'success', 3000);
                            return { success: true };
                        } else {
                            console.error('❌ Server returned success: false');
                            this.showNotification(response.data.message || 'Update failed', 'error');
                            return { success: false, error: response.data.message };
                        }
                    } catch (error) {
                        console.error('❌ Update quantity error:', error);
                        console.error('❌ Error response:', error.response?.data);
                        console.error('❌ Error status:', error.response?.status);
                        this.showNotification('Failed to update cart', 'error');
                        return { success: false, error: error.message };
                    } finally {
                        console.log('🔄 Clearing loading state for item:', itemId);
                        this.setCartItemLoading(itemId, false);
                    }
                },

                async removeFromCart(itemId) {
                    console.log('🗑️ MODERN removeFromCart:', itemId);

                    try {
                        this.setCartItemLoading(itemId, true);
                        console.log('⏳ Set loading state for remove item:', itemId);

                        const token = window.JWT_TOKEN;
                        if (!token) {
                            console.error('❌ No JWT token available for cart removal');
                            this.showNotification('Authentication required', 'error');
                            return { success: false, error: 'No token' };
                        }

                        // Find the item in our current cart to verify it exists
                        const existingItem = this.cartItems.find(item => item.id === itemId);
                        console.log('🔍 Found item in current cart for removal:', existingItem);

                        if (!existingItem) {
                            console.error('❌ Item not found in current cart items:', itemId);
                            console.error('📋 Available cart items:', this.cartItems.map(item => ({ id: item.id, name: item.name })));
                            this.showNotification('Item not found in cart', 'error');
                            return { success: false, error: 'Item not found locally' };
                        }

                        console.log('📤 Sending DELETE request to /api/cart/' + itemId);
                        const response = await axios.delete(`/api/cart/${itemId}`, {
                            headers: {
                                'Authorization': 'Bearer ' + token,
                                'Accept': 'application/json'
                            }
                        });

                        console.log('📥 Cart remove response:', response.data);

                        if (response.data.success) {
                            console.log('✅ Item removal successful, reloading cart...');
                            await this.loadCart(true);
                            this.showNotification('Item removed from cart', 'success', 3000);
                            return { success: true };
                        } else {
                            console.error('❌ Server returned success: false');
                            this.showNotification(response.data.message || 'Removal failed', 'error');
                            return { success: false, error: response.data.message };
                        }
                    } catch (error) {
                        console.error('❌ Remove item error:', error);
                        console.error('❌ Error response:', error.response?.data);
                        console.error('❌ Error status:', error.response?.status);
                        this.showNotification('Failed to remove item', 'error');
                        return { success: false, error: error.message };
                    } finally {
                        console.log('🔄 Clearing loading state for remove item:', itemId);
                        this.setCartItemLoading(itemId, false);
                    }
                },

                setCartItemLoading(itemId, loading) {
                    if (!this.cartItemsLoading) this.cartItemsLoading = {};
                    this.cartItemsLoading = { ...this.cartItemsLoading, [itemId]: loading };
                },

                isCartItemLoading(itemId) {
                    return this.cartItemsLoading?.[itemId] || false;
                },

                triggerCartUpdateEvent() {
                    window.dispatchEvent(new CustomEvent('cartUpdated', {
                        detail: {
                            items: this.cartItems,
                            count: this.cartCount,
                            total: this.cartTotal,
                            timestamp: Date.now()
                        }
                    }));
                },

                async removeFromCart(itemId) {
                    console.log('🗑️ MODERN removeFromCart:', itemId);

                    try {
                        this.setCartItemLoading(itemId, true);
                        console.log('⏳ Set loading state for remove item:', itemId);

                        const token = window.JWT_TOKEN;
                        if (!token) {
                            console.error('❌ No JWT token available for cart removal');
                            this.showNotification('Authentication required', 'error');
                            return { success: false, error: 'No token' };
                        }

                        // Find the item in our current cart to verify it exists
                        const existingItem = this.cartItems.find(item => item.id === itemId);
                        console.log('🔍 Found item in current cart for removal:', existingItem);

                        if (!existingItem) {
                            console.error('❌ Item not found in current cart items:', itemId);
                            console.error('📋 Available cart items:', this.cartItems.map(item => ({ id: item.id, name: item.name })));
                            this.showNotification('Item not found in cart', 'error');
                            return { success: false, error: 'Item not found locally' };
                        }

                        console.log('📤 Sending DELETE request to /api/cart/' + itemId);
                        const response = await axios.delete(`/api/cart/${itemId}`, {
                            headers: {
                                'Authorization': 'Bearer ' + token,
                                'Accept': 'application/json'
                            }
                        });

                        console.log('📥 Cart remove response:', response.data);

                        if (response.data.success) {
                            console.log('✅ Item removal successful, reloading cart...');
                            await this.loadCart(true);
                            this.showNotification('Item removed from cart', 'success', 3000);
                            return { success: true };
                        } else {
                            console.error('❌ Server returned success: false');
                            this.showNotification(response.data.message || 'Removal failed', 'error');
                            return { success: false, error: response.data.message };
                        }
                    } catch (error) {
                        console.error('❌ Remove item error:', error);
                        console.error('❌ Error response:', error.response?.data);
                        console.error('❌ Error status:', error.response?.status);
                        this.showNotification('Failed to remove item', 'error');
                        return { success: false, error: error.message };
                    } finally {
                        console.log('🔄 Clearing loading state for remove item:', itemId);
                        this.setCartItemLoading(itemId, false);
                    }
                },

                async logout() {
                    this.loadingStates.loggingOut = true;

                    try {
                        const response = await fetch('/logout', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        if (response.ok) {
                            this.user = null;
                            this.cartItems = [];
                            this.saveCartData();
                            window.location.href = '/';
                        } else {
                            throw new Error('Logout failed');
                        }
                    } catch (error) {
                        console.error('Logout error:', error);
                        this.showNotification('Logout failed. Please try again.', 'error');
                    } finally {
                        this.loadingStates.loggingOut = false;
                    }
                },

                initializeWebSocket() {
                    if (typeof window.Echo === 'undefined') {
                        console.warn('Laravel Echo not available');
                        return;
                    }

                    // Wait for Echo to be properly initialized
                    setTimeout(() => {
                        try {
                            if (window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection) {
                                window.Echo.connector.pusher.connection.bind('connected', () => {
                                    console.log('✅ WebSocket connected successfully');
                                });

                                window.Echo.connector.pusher.connection.bind('error', (error) => {
                                    console.error('❌ WebSocket connection error:', error);
                                });
                            } else {
                                console.warn('⚠️ Pusher connector not ready yet');
                            }
                        } catch (error) {
                            console.warn('⚠️ WebSocket initialization error:', error);
                        }
                    }, 1000);

                    if (this.user && window.Echo && typeof window.Echo.private === 'function') {
                        try {
                            // Listen for order status updates
                            window.Echo.private(`user.${this.user.id}.orders`)
                                .listen('.order.status_changed', (data) => {
                                    console.log('📦 Order status updated:', data);
                                    this.showNotification(`Order ${data.order.order_number} status changed to ${data.new_status}`, 'info');

                                    // Notify order detail page if open
                                    if (window.orderDetailPage && window.orderDetailPage.handleOrderStatusUpdate) {
                                        console.log('🔔 Notifying order detail page of status update');
                                        window.orderDetailPage.handleOrderStatusUpdate(data);
                                    }

                                    // Refresh page if on orders page
                                    if (window.location.pathname.includes('/orders')) {
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 1000);
                                    }
                                });
                        } catch (error) {
                            console.warn('⚠️ WebSocket private channel setup failed:', error);
                        }

                        // Admin bell notifications for new orders
                        if ((this.user.role === 'ADMIN' || this.user.role === 'MERCHANT') &&
                            window.Echo && typeof window.Echo.channel === 'function') {
                            try {
                                window.Echo.channel('admin-orders')
                                    .listen('.order.created', (data) => {
                                        console.log('🔔 New order received:', data);
                                        this.addBellNotification(data);
                                        this.playNotificationSound();
                                    });
                            } catch (error) {
                                console.warn('⚠️ WebSocket channel setup failed:', error);
                            }
                        }
                    }
                },

                // Bell notification methods
                addBellNotification(data) {
                    const notification = {
                        id: Date.now(),
                        message: `New order #${data.order_number || data.order?.number || 'Unknown'} received`,
                        time: new Date().toLocaleTimeString(),
                        data: data
                    };

                    this.bellNotifications.unshift(notification);
                    this.bellCount = this.bellNotifications.length;

                    // Save to localStorage
                    this.saveBellNotifications();
                },

                clearBellNotifications() {
                    this.bellNotifications = [];
                    this.bellCount = 0;
                    this.saveBellNotifications();
                },

                loadBellNotifications() {
                    const saved = localStorage.getItem('bellgas_bell_notifications');
                    if (saved) {
                        this.bellNotifications = JSON.parse(saved);
                        this.bellCount = this.bellNotifications.length;
                    }
                },

                saveBellNotifications() {
                    localStorage.setItem('bellgas_bell_notifications', JSON.stringify(this.bellNotifications));
                },

                playNotificationSound() {
                    if (typeof playNotificationSound === 'function') {
                        playNotificationSound();
                    }
                }

            }
        }
    </script>

    <!-- WebSocket Configuration -->
    <script>
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: '{{ config('broadcasting.connections.reverb.app_key') }}',
            wsHost: '{{ config('broadcasting.connections.reverb.host') }}',
            wsPort: {{ config('broadcasting.connections.reverb.port') }},
            wssPort: {{ config('broadcasting.connections.reverb.port') }},
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
            auth: {
                headers: {
                    @if(session('frontend_token') || session('jwt_token'))
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Authorization': 'Bearer {{ session('frontend_token') ?? session('jwt_token') }}'
                    @else
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    @endif
                }
            }
        });
    </script>

    @stack('scripts')
</body>
</html>