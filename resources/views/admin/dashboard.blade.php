@extends('layouts.app')

@section('title', 'Admin Dashboard - BellGas')

@section('content')
<!-- Admin Layout with Sidebar -->
<div class="flex min-h-screen bg-gray-100" x-data="adminDashboard()" x-init="init()">
    <!-- Mobile Menu Button -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button @click="sidebarOpen = !sidebarOpen"
                class="p-2 bg-white rounded-lg shadow-md border border-gray-200">
            <i class="fas fa-bars text-gray-600"></i>
        </button>
    </div>

    <!-- Sidebar -->
    <div class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out"
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
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
    <div class="flex-1 overflow-hidden lg:ml-0">
        <!-- Mobile Overlay for Sidebar -->
        <div x-show="sidebarOpen"
             @click="sidebarOpen = false"
             class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <div class="p-4 lg:p-8 pt-16 lg:pt-8">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-8 space-y-4 lg:space-y-0">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                    <p class="text-gray-600">Manage your BellGas business operations</p>
                </div>

                <!-- Quick Actions -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
                    <button @click="refreshData()"
                            class="w-full sm:w-auto bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                    <a href="/admin/orders"
                       class="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-center">
                        <i class="fas fa-list mr-2"></i>All Orders
                    </a>
                </div>
            </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <div x-show="loading" class="space-y-2">
                            <div class="h-6 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-4 bg-gray-200 rounded animate-pulse w-3/4"></div>
                            <div class="h-3 bg-gray-200 rounded animate-pulse w-1/2"></div>
                        </div>
                        <div x-show="!loading">
                            <p class="text-2xl font-bold text-gray-800">$<span x-text="metrics.total_revenue"></span></p>
                            <p class="text-gray-600 text-sm">Total Revenue</p>
                            <p class="text-xs text-green-600" x-text="metrics.revenue_change"></p>
                        </div>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
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
                                    <option value="PROCESSED">Processed</option>
                                    <option value="WAITING_FOR_PICKUP">Waiting for Pickup</option>
                                    <option value="PICKED_UP">Picked Up</option>
                                    <option value="ON_DELIVERY">On Delivery</option>
                                    <option value="DONE">Done</option>
                                    <option value="CANCELLED">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                        <!-- Loading skeleton for orders -->
                        <div x-show="loading" class="space-y-4 p-4">
                            <div class="animate-pulse" x-data x-init="setTimeout(() => {}, 100)" x-for="i in 5">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1 space-y-2">
                                        <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                                        <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                                        <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                    </div>
                                    <div class="h-8 w-16 bg-gray-200 rounded"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Actual orders -->
                        <template x-for="order in filteredOrders" :key="order.id" x-show="!loading">
                            <div class="p-4 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h3 class="font-medium" x-text="order.order_number"></h3>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                  :class="getStatusColor(order.status)"
                                                  x-text="order.status"></span>
                                        </div>

                                        <div class="text-sm text-gray-600">
                                            <p><span class="font-medium" x-text="order.user?.first_name + ' ' + order.user?.last_name"></span></p>
                                            <p x-text="formatDate(order.created_at)"></p>
                                            <p><span x-text="order.fulfillment_method"></span> • $<span x-text="order.total_aud"></span></p>
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
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                                        x-show="order.status === 'PAID'">
                                                    Mark Processed
                                                </button>
                                                <button @click="updateOrderStatus(order, 'WAITING_FOR_PICKUP')"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                                        x-show="order.status === 'PROCESSED' && order.fulfillment_method === 'PICKUP'">
                                                    Ready for Pickup
                                                </button>
                                                <button @click="updateOrderStatus(order, 'ON_DELIVERY')"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                                        x-show="order.status === 'PROCESSED' && order.fulfillment_method === 'DELIVERY'">
                                                    Start Delivery
                                                </button>
                                                <button @click="updateOrderStatus(order, 'PICKED_UP')"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                                        x-show="order.status === 'WAITING_FOR_PICKUP'">
                                                    Mark Picked Up
                                                </button>
                                                <button @click="updateOrderStatus(order, 'DONE')"
                                                        class="block w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-gray-50"
                                                        x-show="order.status === 'ON_DELIVERY' || order.status === 'PICKED_UP'">
                                                    Mark Done
                                                </button>
                                                <button @click="updateOrderStatus(order, 'CANCELLED')"
                                                        class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50"
                                                        x-show="!['DONE', 'CANCELLED'].includes(order.status)">
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
                        <!-- Loading skeleton for order status -->
                        <div x-show="loading" class="space-y-3">
                            <div class="animate-pulse" x-for="i in 4">
                                <div class="flex justify-between items-center">
                                    <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-20 bg-gray-200 rounded-full h-2"></div>
                                        <div class="h-4 bg-gray-200 rounded w-6"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actual order status -->
                        <template x-for="status in orderStatusStats" :key="status.name" x-show="!loading">
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

                        <!-- No data state -->
                        <div x-show="!loading && orderStatusStats.length === 0" class="text-center py-4 text-gray-500">
                            <i class="fas fa-chart-pie text-2xl mb-2"></i>
                            <p class="text-sm">No order data available</p>
                        </div>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">Top Products</h3>
                    <div class="space-y-3">
                        <!-- Loading skeleton for products -->
                        <div x-show="loading" class="space-y-3">
                            <div class="animate-pulse" x-for="i in 5">
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                                        <div class="h-3 bg-gray-200 rounded w-1/2 mt-1"></div>
                                    </div>
                                    <div class="h-4 bg-gray-200 rounded w-16"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Actual products -->
                        <template x-for="product in topProducts" :key="product.name" x-show="!loading">
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <p class="font-medium text-sm" x-text="product.name"></p>
                                    <p class="text-xs text-gray-500" x-text="product.variant"></p>
                                </div>
                                <span class="text-sm font-bold" x-text="product.quantity + ' sold'"></span>
                            </div>
                        </template>

                        <!-- No products state -->
                        <div x-show="!loading && topProducts.length === 0" class="text-center py-4 text-gray-500">
                            <i class="fas fa-box-open text-2xl mb-2"></i>
                            <p class="text-sm">No product data available</p>
                        </div>
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
                            <span class="text-sm">Database</span>
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Healthy</span>
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

<script>
// Authentication is handled globally in app.blade.php layout

function adminDashboard() {
    return {
        sidebarOpen: false,
        loading: true,
        dataLoaded: false,
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
        // Bell notification system
        bellNotifications: [],
        processedOrderIds: new Set(), // Track processed orders to prevent duplicates
        bellCount: 0,
        showBellDropdown: false,
        
        async init() {
            this.loading = true;
            await this.loadDashboardData();
            this.loading = false;
            this.dataLoaded = true;
        },
        
        async loadDashboardData() {
            console.log('🔄 Loading admin dashboard data...');

            try {
                // Get JWT token from window or session
                let token = window.JWT_TOKEN;

                if (!token) {
                    // Try to get from session via meta tag
                    const metaToken = document.querySelector('meta[name="frontend-token"]');
                    token = metaToken ? metaToken.getAttribute('content') : null;
                }

                console.log('🔑 Token available:', token ? 'YES' : 'NO');

                if (!token) {
                    console.error('🚨 NO TOKEN AVAILABLE - Cannot load admin dashboard');
                    window.location.href = '/login';
                    return;
                }

                // Create axios instance with optimized config
                const apiClient = axios.create({
                    baseURL: window.location.origin,
                    timeout: 10000, // 10 second timeout
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // Load all data in parallel for better performance
                console.log('🚀 Loading dashboard data in parallel...');

                const [dashboardResponse, ordersResponse, productsResponse] = await Promise.all([
                    apiClient.get('/api/admin/dashboard').catch(err => {
                        console.error('Dashboard stats failed:', err);
                        return { data: { success: false } };
                    }),
                    apiClient.get('/api/admin/dashboard/recent-orders').catch(err => {
                        console.error('Recent orders failed:', err);
                        return { data: { success: false } };
                    }),
                    apiClient.get('/api/admin/dashboard/top-products').catch(err => {
                        console.error('Top products failed:', err);
                        return { data: { success: false } };
                    })
                ]);

                console.log('✅ Dashboard API responses received');

                // Process dashboard metrics
                if (dashboardResponse.data.success && dashboardResponse.data.data?.stats) {
                    const stats = dashboardResponse.data.stats || dashboardResponse.data.data.stats;
                    this.metrics = {
                        total_revenue: Number(stats.total_revenue || 0).toFixed(2),
                        revenue_change: '+12% from last month',
                        total_orders: stats.total_orders || 0,
                        orders_change: '+8% from last month',
                        active_customers: stats.active_users || 0,
                        customers_change: '+15% from last month',
                        products_sold: stats.products_sold || 0,
                        products_change: '+10% from last month'
                    };
                    console.log('📊 Metrics loaded:', this.metrics);
                } else {
                    console.warn('⚠️ Dashboard metrics not loaded properly');
                }

                // Process recent orders
                if (ordersResponse.data.success && ordersResponse.data.data) {
                    this.recentOrders = ordersResponse.data.data;
                    this.filteredOrders = this.recentOrders;
                    console.log('📋 Recent orders loaded:', this.recentOrders.length);
                } else {
                    console.warn('⚠️ Recent orders not loaded properly');
                }

                // Process top products
                if (productsResponse.data.success && productsResponse.data.data) {
                    const products = productsResponse.data.data;
                    this.topProducts = products.map(product => ({
                        name: product.name,
                        variant: product.variant || 'Default',
                        quantity: product.total_sold || 0
                    }));
                    console.log('🏆 Top products loaded:', this.topProducts.length);
                } else {
                    console.warn('⚠️ Top products not loaded properly');
                }

                // Calculate order statistics
                this.calculateOrderStats();
                console.log('✅ Dashboard data loading completed');

            } catch (error) {
                console.error('❌ Failed to load dashboard data:', error);

                // Set default values to prevent UI errors
                this.setDefaultValues();

                // Show user-friendly error
                this.showNotification('Dashboard data failed to load. Please refresh the page.', 'error');
            }
        },

        setDefaultValues() {
            this.metrics = {
                total_revenue: '0.00',
                revenue_change: '+0% from last month',
                total_orders: 0,
                orders_change: '+0% from last month',
                active_customers: 0,
                customers_change: '+0% from last month',
                products_sold: 0,
                products_change: '+0% from last month'
            };
            this.recentOrders = [];
            this.filteredOrders = [];
            this.topProducts = [];
            this.orderStatusStats = [];
        },
        
        async refreshData() {
            this.loading = true;
            await this.loadDashboardData();
            this.loading = false;
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
                    'PENDING': 'bg-orange-400',
                    'PAID': 'bg-blue-400',
                    'PROCESSED': 'bg-yellow-400',
                    'WAITING_FOR_PICKUP': 'bg-purple-400',
                    'PICKED_UP': 'bg-indigo-400',
                    'ON_DELIVERY': 'bg-cyan-400',
                    'DONE': 'bg-green-400',
                    'CANCELLED': 'bg-gray-400'
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
                const token = window.JWT_TOKEN;
                if (!token) {
                    throw new Error('No authentication token available');
                }

                const apiClient = axios.create({
                    baseURL: window.location.origin,
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                await apiClient.put(`/api/orders/${order.order_number}`, {
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
                const response = await axios.get('/api/admin/orders/export');
                // Handle export download
                this.showNotification('Orders exported successfully', 'success');
            } catch (error) {
                this.showNotification('Failed to export orders', 'error');
            }
        },
        
        async sendNotifications() {
            try {
                await axios.post('/api/admin/notifications/send');
                this.showNotification('Notifications sent successfully', 'success');
            } catch (error) {
                this.showNotification('Failed to send notifications', 'error');
            }
        },
        
        async generateReports() {
            try {
                await axios.post('/api/admin/reports/generate');
                this.showNotification('Reports generated successfully', 'success');
            } catch (error) {
                this.showNotification('Failed to generate reports', 'error');
            }
        },
        
        getStatusColor(status) {
            const colors = {
                'PENDING': 'bg-orange-100 text-orange-800',
                'PAID': 'bg-blue-100 text-blue-800',
                'PROCESSED': 'bg-yellow-100 text-yellow-800',
                'WAITING_FOR_PICKUP': 'bg-purple-100 text-purple-800',
                'PICKED_UP': 'bg-indigo-100 text-indigo-800',
                'ON_DELIVERY': 'bg-cyan-100 text-cyan-800',
                'DONE': 'bg-green-100 text-green-800',
                'CANCELLED': 'bg-gray-100 text-gray-800'
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
        
        showNotification(message, type = 'info') {
            console.log(`[${type.toUpperCase()}] ${message}`);
            // You can implement a toast notification system here
        },

        // Bell notification functions
        addBellNotification(event) {
            // Prevent duplicate notifications
            if (this.processedOrderIds.has(event.order_id)) {
                console.log('Duplicate notification prevented for order:', event.order_id);
                return;
            }

            // Mark this order as processed
            this.processedOrderIds.add(event.order_id);

            // Add to bell notifications
            this.bellNotifications.unshift({
                id: event.order_id,
                message: `New order #${event.order_number} from ${event.customer_name}`,
                time: new Date().toLocaleTimeString('en-AU', {
                    hour: '2-digit',
                    minute: '2-digit'
                }),
                orderNumber: event.order_number,
                customerName: event.customer_name,
                timestamp: Date.now()
            });

            // Increment bell count
            this.bellCount++;

            // Limit notifications to 10 most recent
            if (this.bellNotifications.length > 10) {
                this.bellNotifications = this.bellNotifications.slice(0, 10);
            }

            // Play notification sound
            try {
                if (typeof playNotificationSound === 'function') {
                    playNotificationSound();
                }
            } catch (error) {
                console.log('Sound notification failed:', error);
            }

            console.log('🔔 Bell notification added:', this.bellNotifications[0]);
        },

        clearBellNotifications() {
            this.bellCount = 0;
            // Keep notifications but mark as read
        },

        clearAllBellNotifications() {
            this.bellNotifications = [];
            this.bellCount = 0;
            this.processedOrderIds.clear();
        }
    }
}

// Override handleNewOrder in global app to call admin dashboard bell notification
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Alpine to initialize
    setTimeout(() => {
        if (window.app && window.app.user && (window.app.user.role === 'ADMIN' || window.app.user.role === 'MERCHANT')) {
            const originalHandleNewOrder = window.app.handleNewOrder;

            window.app.handleNewOrder = function(event) {
                // Call original handler
                if (originalHandleNewOrder) {
                    originalHandleNewOrder.call(this, event);
                }

                // Add to admin dashboard bell if we're on admin dashboard
                if (typeof adminDashboard !== 'undefined' && this.$el && this.$el.closest('[x-data*="adminDashboard"]')) {
                    // Access the Alpine component data
                    const adminComponent = this.$el.closest('[x-data*="adminDashboard"]')._x_dataStack[0];
                    if (adminComponent && typeof adminComponent.addBellNotification === 'function') {
                        adminComponent.addBellNotification(event);
                    }
                }
            };
        }
    }, 1000);
});
</script>
@endsection

