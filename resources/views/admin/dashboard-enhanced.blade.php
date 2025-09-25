@extends('layouts.app')

@section('title', 'Admin Dashboard - BellGas')

@section('content')
<!-- Admin Layout with Sidebar -->
<div class="flex min-h-screen bg-gray-100" x-data="adminDashboard()" x-init="init()">
    <!-- Sidebar -->
    <div class="w-64 bg-white shadow-lg">
        <!-- Admin Logo/Title -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-crown text-white"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800">Admin Panel</h1>
                    <p class="text-sm text-gray-500">BellGas Management</p>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="mt-6">
            <div class="px-4">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Main</div>
                <ul class="space-y-2">
                    <li>
                        <a href="/admin/dashboard"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg bg-blue-50 text-blue-700 border border-blue-200">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="/admin/orders"
                           class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-shopping-bag mr-3"></i>
                            Order Management
                        </a>
                    </li>
                    <li>
                        <a href="/admin/products"
                           class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-box mr-3"></i>
                            Product Management
                        </a>
                    </li>
                    <li>
                        <a href="/admin/customers"
                           class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-users mr-3"></i>
                            Customer Management
                        </a>
                    </li>
                </ul>

                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-6 mb-3">System</div>
                <ul class="space-y-2">
                    <li>
                        <a href="/admin/settings"
                           class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-cog mr-3"></i>
                            Settings
                        </a>
                    </li>
                </ul>

                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-6 mb-3">Quick Actions</div>
                <ul class="space-y-2">
                    <li>
                        <a href="/products"
                           class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-external-link-alt mr-3"></i>
                            View Site
                        </a>
                    </li>
                    <li>
                        <a href="/dashboard"
                           class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-user mr-3"></i>
                            Customer View
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-hidden">
        <div class="p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                    <p class="text-gray-600">Manage your BellGas business operations</p>
                    <div class="flex items-center mt-2 space-x-4 text-sm text-gray-500">
                        <span x-show="realtimeEnabled" class="flex items-center">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                            Real-time monitoring active
                        </span>
                        <span>Last updated: <span x-text="formatTime(lastUpdateTime)"></span></span>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="flex space-x-3">
                    <button @click="refreshData()"
                            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                        <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': loading }"></i>Refresh
                    </button>

                    <!-- Real-time Controls -->
                    <div class="relative" x-data="{ showControls: false }">
                        <button @click="showControls = !showControls"
                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition relative">
                            <i class="fas fa-broadcast-tower mr-2"></i>Live
                            <span x-show="realtimeEnabled" class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse"></span>
                        </button>

                        <div x-show="showControls" @click.away="showControls = false"
                             class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border py-2 z-10">
                            <div class="px-4 py-2 border-b">
                                <h3 class="text-sm font-semibold text-gray-700">Real-time Settings</h3>
                            </div>
                            <div class="px-4 py-2 space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="realtimeEnabled" @change="toggleRealtime()"
                                           class="rounded border-gray-300 text-green-600 mr-2">
                                    <span class="text-sm">Auto-refresh (30s)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="soundEnabled" @change="toggleSound()"
                                           class="rounded border-gray-300 text-green-600 mr-2">
                                    <span class="text-sm">Sound notifications</span>
                                </label>
                            </div>
                            <div class="px-4 py-2 border-t">
                                <button @click="testNotification()"
                                        class="text-xs text-blue-600 hover:text-blue-800">
                                    Test notification
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="relative" x-data="{ showNotifications: false }">
                        <button @click="showNotifications = !showNotifications"
                                class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition relative">
                            <i class="fas fa-bell mr-2"></i>Alerts
                            <span x-show="newNotificationsCount > 0"
                                  x-text="newNotificationsCount"
                                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"></span>
                        </button>

                        <div x-show="showNotifications" @click.away="showNotifications = false"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border z-10 max-h-96 overflow-hidden">
                            <div class="px-4 py-3 border-b flex justify-between items-center">
                                <h3 class="text-sm font-semibold text-gray-700">Recent Notifications</h3>
                                <button @click="clearNotifications()" class="text-xs text-red-600 hover:text-red-800">
                                    Clear all
                                </button>
                            </div>

                            <div class="max-h-80 overflow-y-auto">
                                <template x-for="notification in recentNotifications" :key="notification.id">
                                    <div class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0 mt-1">
                                                <i :class="getNotificationIcon(notification.type)"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-gray-900" x-text="notification.message"></p>
                                                <p class="text-xs text-gray-500" x-text="formatTime(notification.timestamp)"></p>
                                            </div>
                                            <button @click="removeNotification(notification.id)"
                                                    class="text-gray-400 hover:text-gray-600">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="recentNotifications.length === 0" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-bell-slash text-2xl mb-2"></i>
                                    <p class="text-sm">No notifications</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="/admin/orders"
                       class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-list mr-2"></i>All Orders
                    </a>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800">$<span x-text="metrics.total_revenue"></span></p>
                            <p class="text-gray-600 text-sm">Total Revenue</p>
                            <p class="text-xs text-green-600" x-text="metrics.revenue_change"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800" x-text="metrics.total_orders"></p>
                            <p class="text-gray-600 text-sm">Total Orders</p>
                            <p class="text-xs text-blue-600" x-text="metrics.orders_change"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-users text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800" x-text="metrics.active_customers"></p>
                            <p class="text-gray-600 text-sm">Active Customers</p>
                            <p class="text-xs text-purple-600" x-text="metrics.customers_change"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-fire text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800" x-text="metrics.products_sold"></p>
                            <p class="text-gray-600 text-sm">Products Sold</p>
                            <p class="text-xs text-orange-600" x-text="metrics.products_change"></p>
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
                                <div class="flex space-x-2">
                                    <select x-model="orderFilter" @change="filterOrders()"
                                            class="text-sm border border-gray-300 rounded px-3 py-1">
                                        <option value="">All Orders</option>
                                        <option value="PENDING">Pending</option>
                                        <option value="PAID">Paid</option>
                                        <option value="PROCESSED">Processing</option>
                                        <option value="ON_DELIVERY">On Delivery</option>
                                        <option value="WAITING_PICKUP">Waiting Pickup</option>
                                        <option value="DONE">Completed</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                            <template x-for="order in filteredOrders" :key="order.id">
                                <div class="p-4 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <h3 class="font-medium" x-text="order.order_number"></h3>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                      :class="getStatusColor(order.status)"
                                                      x-text="order.status"></span>
                                                <span x-show="order.fulfillment_method"
                                                      class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs"
                                                      x-text="order.fulfillment_method"></span>
                                            </div>

                                            <div class="text-sm text-gray-600">
                                                <p><span class="font-medium" x-text="order.user?.first_name + ' ' + order.user?.last_name"></span></p>
                                                <p x-text="formatDate(order.created_at)"></p>
                                                <p>$<span x-text="order.total_aud"></span></p>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-2">
                                            <button @click="viewOrderDetails(order)"
                                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <div class="relative" x-data="{ open: false }">
                                                <button @click="open = !open"
                                                        class="text-gray-600 hover:text-gray-800">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div x-show="open" @click.away="open = false"
                                                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 z-10">
                                                    <button @click="updateOrderStatus(order, 'PROCESSED')"
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                        Mark Processing
                                                    </button>
                                                    <button @click="updateOrderStatus(order, 'ON_DELIVERY')"
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                        Mark On Delivery
                                                    </button>
                                                    <button @click="updateOrderStatus(order, 'DONE')"
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                        Mark Complete
                                                    </button>
                                                    <button @click="updateOrderStatus(order, 'CANCELLED')"
                                                            class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">
                                                        Cancel Order
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="filteredOrders.length === 0" class="p-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4"></i>
                                <p>No orders found</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side Panel -->
                <div class="space-y-6">
                    <!-- Order Status Distribution -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Order Status</h3>
                        <div class="space-y-3">
                            <template x-for="status in orderStatusStats" :key="status.name">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm" x-text="status.name"></span>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-20 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full"
                                                 :class="status.color"
                                                 :style="'width: ' + status.percentage + '%'"></div>
                                        </div>
                                        <span class="text-sm font-medium" x-text="status.count"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Top Products</h3>
                        <div class="space-y-3">
                            <template x-for="product in topProducts" :key="product.name">
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <p class="font-medium text-sm" x-text="product.name"></p>
                                        <p class="text-xs text-gray-500" x-text="product.variant"></p>
                                    </div>
                                    <span class="text-sm font-bold" x-text="product.quantity + ' sold'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- System Health -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">System Health</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm">API Status</span>
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Online</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Payment Gateway</span>
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Connected</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Email Service</span>
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Active</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Real-time Updates</span>
                                <span x-show="realtimeEnabled" class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Active</span>
                                <span x-show="!realtimeEnabled" class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">Inactive</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg p-6 text-white">
                        <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                        <div class="space-y-2">
                            <button @click="exportOrders()"
                                    class="w-full text-left flex items-center p-2 rounded hover:bg-blue-700 transition">
                                <i class="fas fa-download mr-2"></i>
                                Export Orders
                            </button>
                            <button @click="sendNotifications()"
                                    class="w-full text-left flex items-center p-2 rounded hover:bg-blue-700 transition">
                                <i class="fas fa-bell mr-2"></i>
                                Send Notifications
                            </button>
                            <button @click="generateReports()"
                                    class="w-full text-left flex items-center p-2 rounded hover:bg-blue-700 transition">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Generate Reports
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div x-show="showOrderModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-96 overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Order Details</h3>
                    <button @click="showOrderModal = false" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div x-show="selectedOrder" class="p-6">
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <h4 class="font-semibold mb-2">Order Information</h4>
                        <p class="text-sm"><strong>Order #:</strong> <span x-text="selectedOrder?.order_number"></span></p>
                        <p class="text-sm"><strong>Status:</strong> <span x-text="selectedOrder?.status"></span></p>
                        <p class="text-sm"><strong>Method:</strong> <span x-text="selectedOrder?.fulfillment_method"></span></p>
                        <p class="text-sm"><strong>Date:</strong> <span x-text="formatDate(selectedOrder?.created_at)"></span></p>
                        <p class="text-sm"><strong>Total:</strong> $<span x-text="selectedOrder?.total_aud"></span></p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-2">Customer Information</h4>
                        <p class="text-sm"><strong>Name:</strong> <span x-text="selectedOrder?.user?.first_name + ' ' + selectedOrder?.user?.last_name"></span></p>
                        <p class="text-sm"><strong>Email:</strong> <span x-text="selectedOrder?.user?.email"></span></p>
                        <p class="text-sm"><strong>Phone:</strong> <span x-text="selectedOrder?.user?.phone_number"></span></p>
                    </div>
                </div>

                <div x-show="selectedOrder?.address" class="mb-6">
                    <h4 class="font-semibold mb-2">Delivery Address</h4>
                    <p class="text-sm" x-text="selectedOrder?.address?.full_address"></p>
                    <p x-show="selectedOrder?.address?.delivery_instructions"
                       class="text-sm text-gray-600 mt-1"
                       x-text="'Instructions: ' + selectedOrder?.address?.delivery_instructions"></p>
                </div>

                <div class="mb-6">
                    <h4 class="font-semibold mb-2">Order Items</h4>
                    <template x-for="item in selectedOrder?.items" :key="item.id">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <div>
                                <p class="text-sm font-medium" x-text="item.productVariant?.product?.name"></p>
                                <p class="text-xs text-gray-500" x-text="item.productVariant?.name + ' × ' + item.quantity"></p>
                            </div>
                            <span class="text-sm font-medium">$<span x-text="item.total_price_aud"></span></span>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end space-x-3">
                    <button @click="showOrderModal = false"
                            class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 transition">
                        Close
                    </button>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        Update Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Real-time Script -->
<script src="/js/admin-realtime.js"></script>

<script>
@if(session('jwt_token'))
    axios.defaults.headers.common['Authorization'] = 'Bearer {{ session('jwt_token') }}';
    localStorage.setItem('access_token', '{{ session('jwt_token') }}');
@endif

function adminDashboard() {
    return {
        metrics: {
            total_revenue: '0.00',
            revenue_change: '+0% from last month',
            total_orders: 0,
            orders_change: '+0% from last month',
            active_customers: 0,
            customers_change: '+0% from last month',
            products_sold: 0,
            products_change: '+0% from last month'
        },
        recentOrders: [],
        filteredOrders: [],
        orderFilter: '',
        orderStatusStats: [],
        topProducts: [],
        showOrderModal: false,
        selectedOrder: null,
        loading: false,
        lastUpdateTime: new Date(),

        // Real-time features
        realtimeEnabled: true,
        soundEnabled: true,
        recentNotifications: [],
        newNotificationsCount: 0,

        async init() {
            await this.loadDashboardData();
            this.initializeRealtime();
        },

        initializeRealtime() {
            // Initialize real-time monitoring
            window.AdminRealtime.init((newStats) => {
                this.handleRealtimeUpdate(newStats);
            });

            // Load settings from AdminRealtime
            const settings = window.AdminRealtime.getSettings();
            this.realtimeEnabled = settings.autoRefreshEnabled;
            this.soundEnabled = settings.soundEnabled;

            // Sync notifications
            this.recentNotifications = window.AdminRealtime.notifications.slice(0, 10);
            this.updateNotificationCount();
        },

        handleRealtimeUpdate(newStats) {
            // Update metrics
            this.metrics.total_revenue = Number(newStats.total_revenue || 0).toFixed(2);
            this.metrics.total_orders = newStats.total_orders || 0;
            this.metrics.active_customers = newStats.active_users || 0;
            this.lastUpdateTime = new Date();

            // Refresh orders
            this.loadRecentOrders();
        },

        async loadDashboardData() {
            this.loading = true;
            try {
                // Load dashboard metrics
                const dashboardResponse = await axios.get('/api/admin/stats');
                const dashboardData = dashboardResponse.data;

                if (dashboardData.success && dashboardData.data) {
                    const stats = dashboardData.data;
                    this.metrics = {
                        total_revenue: Number(stats.total_revenue || 0).toFixed(2),
                        revenue_change: '+12% from last month',
                        total_orders: stats.total_orders || 0,
                        orders_change: '+8% from last month',
                        active_customers: stats.active_users || 0,
                        customers_change: '+15% from last month',
                        products_sold: stats.total_orders || 0,
                        products_change: '+10% from last month'
                    };

                    // Set initial order count for real-time monitoring
                    if (window.AdminRealtime) {
                        window.AdminRealtime.lastOrderCount = stats.total_orders || 0;
                    }
                }

                await this.loadRecentOrders();
                await this.loadTopProducts();

                this.lastUpdateTime = new Date();

            } catch (error) {
                console.error('Failed to load dashboard data:', error);
                this.showNotification('Failed to load dashboard data', 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadRecentOrders() {
            try {
                const ordersResponse = await axios.get('/api/realtime/orders');
                if (ordersResponse.data.success) {
                    this.recentOrders = ordersResponse.data.data || [];
                    this.filteredOrders = this.recentOrders;
                    this.calculateOrderStats();
                }
            } catch (error) {
                console.error('Failed to load recent orders:', error);
            }
        },

        async loadTopProducts() {
            try {
                const productsResponse = await axios.get('/api/admin/dashboard/top-products');
                if (productsResponse.data.success) {
                    const products = productsResponse.data.data || [];
                    this.topProducts = products.map(product => ({
                        name: product.name,
                        variant: 'Default',
                        quantity: product.total_sold || 0
                    }));
                }
            } catch (error) {
                console.error('Failed to load top products:', error);
            }
        },

        async refreshData() {
            await this.loadDashboardData();
            this.showNotification('Dashboard data refreshed', 'success');
        },

        filterOrders() {
            if (this.orderFilter) {
                this.filteredOrders = this.recentOrders.filter(order =>
                    order.status === this.orderFilter
                );
            } else {
                this.filteredOrders = this.recentOrders;
            }
        },

        calculateOrderStats() {
            const orders = this.recentOrders;
            const statusCounts = {};

            orders.forEach(order => {
                statusCounts[order.status] = (statusCounts[order.status] || 0) + 1;
            });

            this.orderStatusStats = Object.entries(statusCounts).map(([status, count]) => {
                const total = orders.length;
                const percentage = total > 0 ? (count / total) * 100 : 0;

                const colors = {
                    'PENDING': 'bg-gray-400',
                    'PAID': 'bg-blue-400',
                    'PROCESSED': 'bg-yellow-400',
                    'ON_DELIVERY': 'bg-purple-400',
                    'WAITING_PICKUP': 'bg-orange-400',
                    'DONE': 'bg-green-400',
                    'CANCELLED': 'bg-red-400'
                };

                return {
                    name: status,
                    count: count,
                    percentage: percentage.toFixed(0),
                    color: colors[status] || 'bg-gray-400'
                };
            });
        },

        viewOrderDetails(order) {
            this.selectedOrder = order;
            this.showOrderModal = true;
        },

        async updateOrderStatus(order, newStatus) {
            try {
                await axios.put(`/api/orders/${order.id}`, {
                    status: newStatus
                });

                // Update order in local data
                order.status = newStatus;
                this.calculateOrderStats();

                this.showNotification(`Order ${order.order_number} updated to ${newStatus}`, 'success');

            } catch (error) {
                this.showNotification('Failed to update order status', 'error');
            }
        },

        async exportOrders() {
            try {
                const response = await axios.get('/api/admin/orders/export', { responseType: 'blob' });

                // Create download link
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `orders-export-${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                this.showNotification('Orders exported successfully', 'success');
            } catch (error) {
                this.showNotification('Failed to export orders', 'error');
            }
        },

        async sendNotifications() {
            try {
                this.showNotification('Notification feature coming soon', 'info');
            } catch (error) {
                this.showNotification('Failed to send notifications', 'error');
            }
        },

        async generateReports() {
            try {
                window.open('/api/admin/reports/sales', '_blank');
                this.showNotification('Reports generated successfully', 'success');
            } catch (error) {
                this.showNotification('Failed to generate reports', 'error');
            }
        },

        // Real-time controls
        toggleRealtime() {
            this.realtimeEnabled = window.AdminRealtime.toggleAutoRefresh();
        },

        toggleSound() {
            this.soundEnabled = window.AdminRealtime.toggleSound();
        },

        testNotification() {
            window.AdminRealtime.addNotification('🧪 This is a test notification!', 'info', false);
            window.AdminRealtime.playNotificationSound();
            this.updateNotifications();
        },

        // Notification management
        updateNotifications() {
            this.recentNotifications = window.AdminRealtime.notifications.slice(0, 10);
            this.updateNotificationCount();
        },

        updateNotificationCount() {
            const unreadCount = this.recentNotifications.filter(n => n.timestamp > Date.now() - 300000).length; // 5 minutes
            this.newNotificationsCount = unreadCount > 9 ? '9+' : unreadCount;
        },

        removeNotification(id) {
            window.AdminRealtime.removeNotification(id);
            this.updateNotifications();
        },

        clearNotifications() {
            window.AdminRealtime.clearNotifications();
            this.updateNotifications();
        },

        getNotificationIcon(type) {
            const icons = {
                'success': 'fas fa-check-circle text-green-500',
                'error': 'fas fa-exclamation-circle text-red-500',
                'warning': 'fas fa-exclamation-triangle text-yellow-500',
                'info': 'fas fa-info-circle text-blue-500'
            };
            return icons[type] || icons.info;
        },

        getStatusColor(status) {
            const colors = {
                'PENDING': 'bg-gray-100 text-gray-800',
                'PAID': 'bg-blue-100 text-blue-800',
                'PROCESSED': 'bg-yellow-100 text-yellow-800',
                'ON_DELIVERY': 'bg-purple-100 text-purple-800',
                'WAITING_PICKUP': 'bg-orange-100 text-orange-800',
                'DONE': 'bg-green-100 text-green-800',
                'CANCELLED': 'bg-red-100 text-red-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-AU', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatTime(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleTimeString('en-AU', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        },

        showNotification(message, type = 'info') {
            window.AdminRealtime.addNotification(message, type, false);
            this.updateNotifications();
        }
    }
}
</script>
@endsection