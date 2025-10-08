@extends('layouts.app')

@section('title', 'Dashboard - BellGas')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="dashboardData()" x-init="init()">
    <div class="max-w-7xl mx-auto">
        <!-- Welcome Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                Welcome back, <span x-text="user?.first_name || 'Customer'"></span>!
            </h1>
            <p class="text-gray-600">Manage your orders and account settings</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6" :class="{'animate-pulse': loading}">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center mr-4"
                         :class="loading ? 'bg-gray-200' : 'bg-blue-100'">
                        <i x-show="!loading" class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold mb-1"
                           :class="loading ? 'bg-gray-200 text-transparent rounded w-16' : 'text-gray-800'"
                           x-text="loading ? '...' : stats.total_orders"></p>
                        <p class="text-sm"
                           :class="loading ? 'bg-gray-200 text-transparent rounded w-24' : 'text-gray-600'">
                           <span x-show="!loading">Total Orders</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6" :class="{'animate-pulse': loading}">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center mr-4"
                         :class="loading ? 'bg-gray-200' : 'bg-green-100'">
                        <i x-show="!loading" class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold mb-1"
                           :class="loading ? 'bg-gray-200 text-transparent rounded w-16' : 'text-gray-800'"
                           x-text="loading ? '...' : stats.completed_orders"></p>
                        <p class="text-sm"
                           :class="loading ? 'bg-gray-200 text-transparent rounded w-24' : 'text-gray-600'">
                           <span x-show="!loading">Completed</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6" :class="{'animate-pulse': loading}">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center mr-4"
                         :class="loading ? 'bg-gray-200' : 'bg-yellow-100'">
                        <i x-show="!loading" class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold mb-1"
                           :class="loading ? 'bg-gray-200 text-transparent rounded w-16' : 'text-gray-800'"
                           x-text="loading ? '...' : stats.pending_orders"></p>
                        <p class="text-sm"
                           :class="loading ? 'bg-gray-200 text-transparent rounded w-24' : 'text-gray-600'">
                           <span x-show="!loading">Pending</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6" :class="{'animate-pulse': loading}">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center mr-4"
                         :class="loading ? 'bg-gray-200' : 'bg-purple-100'">
                        <i x-show="!loading" class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold mb-1"
                           :class="loading ? 'bg-gray-200 text-transparent rounded w-16' : 'text-gray-800'">
                           <span x-show="!loading">$</span><span x-text="loading ? '...' : stats.total_spent"></span>
                        </p>
                        <p class="text-sm"
                           :class="loading ? 'bg-gray-200 text-transparent rounded w-24' : 'text-gray-600'">
                           <span x-show="!loading">Total Spent</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Recent Orders -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-semibold">Recent Orders</h2>
                            <a href="/orders" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View All <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="divide-y divide-gray-200">
                        <template x-for="order in (loading ? [1, 2, 3] : recentOrders)" :key="loading ? order : (order?.id || Math.random())">
                            <div class="p-6 transition"
                                 :class="loading ? 'animate-pulse' : 'hover:bg-gray-50'">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex-1">
                                        <h3 class="font-medium mb-1"
                                            :class="loading ? 'bg-gray-200 text-transparent rounded w-32' : ''"
                                            x-text="loading ? '...' : (order?.order_number || 'N/A')"></h3>
                                        <p class="text-sm"
                                           :class="loading ? 'bg-gray-200 text-transparent rounded w-24' : 'text-gray-500'"
                                           x-text="loading ? '...' : formatDate(order?.created_at)"></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium"
                                          :class="loading ? 'bg-gray-200 text-transparent' : getStatusColor(order?.status)"
                                          x-text="loading ? '......' : (order?.status || 'UNKNOWN')"></span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <div class="text-sm"
                                         :class="loading ? 'bg-gray-200 text-transparent rounded w-40' : 'text-gray-600'">
                                        <span x-show="!loading">
                                            <span x-text="order?.items?.length || 0"></span> items •
                                            <span x-text="order?.fulfillment_method || 'N/A'"></span>
                                        </span>
                                        <span x-show="loading">...</span>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="font-semibold"
                                              :class="loading ? 'bg-gray-200 text-transparent rounded w-16' : ''">
                                            <span x-show="!loading">$<span x-text="order?.total_aud || '0.00'"></span></span>
                                            <span x-show="loading">...</span>
                                        </span>
                                        <a x-show="!loading" :href="'/orders/' + (order?.id || '#')"
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            View <i class="fas fa-external-link-alt ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="!loading && recentOrders.length === 0" class="p-12 text-center text-gray-500">
                            <i class="fas fa-shopping-bag text-4xl mb-4"></i>
                            <h3 class="text-lg font-medium mb-2">No orders yet</h3>
                            <p class="text-sm mb-4">Start shopping to see your orders here</p>
                            <a href="/products" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                                Shop Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Account Info -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="/products" 
                           class="flex items-center p-3 rounded-lg border-2 border-dashed border-gray-300 hover:border-blue-500 hover:bg-blue-50 transition group">
                            <i class="fas fa-shopping-cart text-gray-400 group-hover:text-blue-600 mr-3"></i>
                            <span class="text-gray-700 group-hover:text-blue-600">Browse Products</span>
                        </a>
                        
                        <a href="/orders" 
                           class="flex items-center p-3 rounded-lg border-2 border-dashed border-gray-300 hover:border-green-500 hover:bg-green-50 transition group">
                            <i class="fas fa-list text-gray-400 group-hover:text-green-600 mr-3"></i>
                            <span class="text-gray-700 group-hover:text-green-600">View All Orders</span>
                        </a>
                        
                        <a href="/addresses" 
                           class="flex items-center p-3 rounded-lg border-2 border-dashed border-gray-300 hover:border-purple-500 hover:bg-purple-50 transition group">
                            <i class="fas fa-map-marker-alt text-gray-400 group-hover:text-purple-600 mr-3"></i>
                            <span class="text-gray-700 group-hover:text-purple-600">Manage Addresses</span>
                        </a>
                        
                        <a href="/profile" 
                           class="flex items-center p-3 rounded-lg border-2 border-dashed border-gray-300 hover:border-orange-500 hover:bg-orange-50 transition group">
                            <i class="fas fa-user text-gray-400 group-hover:text-orange-600 mr-3"></i>
                            <span class="text-gray-700 group-hover:text-orange-600">Account Settings</span>
                        </a>
                    </div>
                </div>

                <!-- Account Overview -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">Account Overview</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Account Type:</span>
                            <span class="font-medium" x-text="user?.role || 'Customer'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Member Since:</span>
                            <span class="font-medium" x-text="formatDate(user?.created_at)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium text-sm" x-text="user?.email"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-medium" x-text="user?.phone_number || 'Not set'"></span>
                        </div>
                    </div>
                </div>

                <!-- Support -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-700 rounded-lg p-6 text-white">
                    <h3 class="text-lg font-semibold mb-3">Need Help?</h3>
                    <p class="text-blue-100 text-sm mb-4">
                        Our customer support team is here to help you with any questions about your orders or LPG services.
                    </p>
                    <div class="space-y-2">
                        <a href="tel:+61212345678" class="flex items-center text-blue-100 hover:text-white text-sm transition">
                            <i class="fas fa-phone mr-2"></i>
                            +61 2 1234 5678
                        </a>
                        <a href="mailto:support@bellgas.com.au" class="flex items-center text-blue-100 hover:text-white text-sm transition">
                            <i class="fas fa-envelope mr-2"></i>
                            support@bellgas.com.au
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mt-8">
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold">Recent Activity</h2>
                </div>
                
                <div class="p-6">
                    <div x-show="recentActivity.length === 0" class="text-center py-8 text-gray-500">
                        <i class="fas fa-history text-3xl mb-3"></i>
                        <p>No recent activity</p>
                    </div>
                    
                    <div class="space-y-4">
                        <template x-for="activity in recentActivity" :key="activity?.id || Math.random()">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-shopping-bag text-blue-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm" x-text="activity?.description || 'No description'"></p>
                                    <p class="text-xs text-gray-500" x-text="formatDate(activity?.created_at)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Authentication is handled globally in app.blade.php layout

function dashboardData() {
    return {
        loading: true, // Initialize loading state
        user: null,
        stats: {
            total_orders: 0,
            completed_orders: 0,
            pending_orders: 0,
            total_spent: '0.00'
        },
        recentOrders: [],
        recentActivity: [],
        
        async init() {
            console.log('🏠 Dashboard initializing...');
            
            // Wait for app to be ready and authenticated
            await this.waitForAuth();
            
            // Get user from global app
            this.user = window.app?.user;
            
            // Load dashboard data
            await this.loadDashboardData();
        },
        
        async waitForAuth() {
            console.log('⏳ Waiting for authentication...');
            
            // Wait for window.app to be available and authenticated
            for (let i = 0; i < 50; i++) { // Max 5 seconds
                if (window.app && window.app.user) {
                    console.log('✅ Authentication ready');
                    return;
                }
                await new Promise(resolve => setTimeout(resolve, 100));
            }
            
            console.warn('⚠️ Authentication timeout - proceeding anyway');
        },
        
        async loadDashboardData() {
            try {
                console.log('📊 Loading dashboard data...');
                this.loading = true;

                // Load recent orders with proper pagination parameter
                const ordersResponse = await axios.get('/web/orders?per_page=5', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                console.log('📦 Orders response:', ordersResponse.data);

                // Handle both paginated and direct data response
                if (ordersResponse.data.data && ordersResponse.data.data.data) {
                    // Paginated response
                    this.recentOrders = ordersResponse.data.data.data || [];
                } else if (ordersResponse.data.data) {
                    // Direct data response
                    this.recentOrders = Array.isArray(ordersResponse.data.data) ? ordersResponse.data.data : [];
                } else {
                    this.recentOrders = [];
                }

                console.log('✅ Recent orders loaded:', this.recentOrders.length);

                // Calculate stats from orders
                this.calculateStats();

            } catch (error) {
                console.error('❌ Failed to load dashboard data:', error);
                this.recentOrders = []; // Ensure it's always an array
                this.calculateStats(); // Still calculate stats with empty data
            } finally {
                // Always set loading to false, even if there's an error
                this.loading = false;
                console.log('✅ Dashboard loading complete');
            }
        },
        
        calculateStats() {
            // Ensure recentOrders is always an array
            const orders = Array.isArray(this.recentOrders) ? this.recentOrders : [];
            
            this.stats.total_orders = orders.length;
            this.stats.completed_orders = orders.filter(o => o && ['DELIVERED', 'COMPLETED'].includes(o.status)).length;
            this.stats.pending_orders = orders.filter(o => o && ['UNPAID', 'PAID', 'PROCESSING'].includes(o.status)).length;
            this.stats.total_spent = orders
                .filter(o => o && o.status !== 'CANCELLED')
                .reduce((sum, order) => sum + parseFloat(order?.total_aud || 0), 0)
                .toFixed(2);
        },
        
        getStatusColor(status) {
            const colors = {
                'UNPAID': 'bg-red-100 text-red-800',
                'PAID': 'bg-blue-100 text-blue-800',
                'PROCESSING': 'bg-yellow-100 text-yellow-800',
                'SHIPPED': 'bg-purple-100 text-purple-800',
                'DELIVERED': 'bg-green-100 text-green-800',
                'CANCELLED': 'bg-gray-100 text-gray-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        },
        
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            
            try {
                // Create date object and validate it
                const date = new Date(dateString);
                
                // Check if date is valid
                if (isNaN(date.getTime())) {
                    console.warn('Invalid date string:', dateString);
                    return 'Invalid Date';
                }
                
                return date.toLocaleDateString('en-AU', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch (error) {
                console.error('Date formatting error:', error, 'for date:', dateString);
                return 'Date Error';
            }
        }
    }
}
</script>
@endsection