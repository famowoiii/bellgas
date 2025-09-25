@extends('layouts.app')

@section('title', 'Reports - BellGas Admin')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="adminReports()" x-init="init()">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Reports & Analytics</h1>
                <p class="text-gray-600">Generate and export business reports</p>
            </div>

            <!-- Date Filter -->
            <div class="flex space-x-3">
                <input type="date" x-model="dateFrom" @change="loadReports()"
                       class="border border-gray-300 rounded-lg px-3 py-2">
                <input type="date" x-model="dateTo" @change="loadReports()"
                       class="border border-gray-300 rounded-lg px-3 py-2">
                <button @click="loadReports()"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-file-csv text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Export Orders</h3>
                        <p class="text-sm text-gray-600">Download order data as CSV</p>
                    </div>
                </div>
                <button @click="exportOrders()"
                        class="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition">
                    <i class="fas fa-download mr-2"></i>Export CSV
                </button>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Sales Report</h3>
                        <p class="text-sm text-gray-600">Detailed sales analytics</p>
                    </div>
                </div>
                <button @click="generateSalesReport()"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-chart-bar mr-2"></i>Generate Report
                </button>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-users text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Customer Report</h3>
                        <p class="text-sm text-gray-600">Customer analytics</p>
                    </div>
                </div>
                <button @click="generateCustomerReport()"
                        class="w-full bg-purple-600 text-white py-2 px-4 rounded hover:bg-purple-700 transition">
                    <i class="fas fa-user-chart mr-2"></i>Generate Report
                </button>
            </div>
        </div>

        <!-- Sales Report -->
        <div x-show="salesReport" class="bg-white rounded-lg shadow-md mb-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Sales Report</h2>
                <p class="text-gray-600 text-sm" x-text="`Period: ${formatDate(dateFrom)} to ${formatDate(dateTo)}`"></p>
            </div>

            <div class="p-6">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100">Total Orders</p>
                                <p class="text-2xl font-bold" x-text="salesReport?.summary?.total_orders || 0"></p>
                            </div>
                            <i class="fas fa-shopping-cart text-3xl text-blue-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100">Total Revenue</p>
                                <p class="text-2xl font-bold">$<span x-text="salesReport?.summary?.total_revenue || 0"></span></p>
                            </div>
                            <i class="fas fa-dollar-sign text-3xl text-green-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100">Avg Order Value</p>
                                <p class="text-2xl font-bold">$<span x-text="salesReport?.summary?.average_order_value || 0"></span></p>
                            </div>
                            <i class="fas fa-chart-line text-3xl text-purple-200"></i>
                        </div>
                    </div>
                </div>

                <!-- Charts and Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Sales by Status -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Sales by Status</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Status</th>
                                        <th class="px-4 py-2 text-right">Orders</th>
                                        <th class="px-4 py-2 text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in salesReport?.sales_by_status || []" :key="item.status">
                                        <tr class="border-b">
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 text-xs rounded"
                                                      :class="getStatusColor(item.status)"
                                                      x-text="item.status"></span>
                                            </td>
                                            <td class="px-4 py-2 text-right" x-text="item.count"></td>
                                            <td class="px-4 py-2 text-right">$<span x-text="parseFloat(item.total || 0).toFixed(2)"></span></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Sales by Product Type -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Sales by Product Type</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Product Type</th>
                                        <th class="px-4 py-2 text-right">Quantity</th>
                                        <th class="px-4 py-2 text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in salesReport?.sales_by_product_type || []" :key="item.product_type">
                                        <tr class="border-b">
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 text-xs rounded"
                                                      :class="item.product_type === 'REFILL' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800'"
                                                      x-text="item.product_type"></span>
                                            </td>
                                            <td class="px-4 py-2 text-right" x-text="item.total_quantity"></td>
                                            <td class="px-4 py-2 text-right">$<span x-text="parseFloat(item.total_revenue || 0).toFixed(2)"></span></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold mb-4">Top Products</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left">Product</th>
                                    <th class="px-4 py-2 text-left">Type</th>
                                    <th class="px-4 py-2 text-right">Sold</th>
                                    <th class="px-4 py-2 text-right">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in salesReport?.top_products || []" :key="item.name">
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-2 font-medium" x-text="item.name"></td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 text-xs rounded"
                                                  :class="item.product_type === 'REFILL' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800'"
                                                  x-text="item.product_type"></span>
                                        </td>
                                        <td class="px-4 py-2 text-right" x-text="item.total_sold"></td>
                                        <td class="px-4 py-2 text-right font-medium">$<span x-text="parseFloat(item.total_revenue || 0).toFixed(2)"></span></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Report -->
        <div x-show="customerReport" class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Customer Report</h2>
                <p class="text-gray-600 text-sm" x-text="`Period: ${formatDate(dateFrom)} to ${formatDate(dateTo)}`"></p>
            </div>

            <div class="p-6">
                <!-- Customer Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-indigo-100">New Customers</p>
                                <p class="text-2xl font-bold" x-text="customerReport?.summary?.new_customers || 0"></p>
                            </div>
                            <i class="fas fa-user-plus text-3xl text-indigo-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-100">Repeat Customers</p>
                                <p class="text-2xl font-bold" x-text="customerReport?.summary?.repeat_customers || 0"></p>
                            </div>
                            <i class="fas fa-users text-3xl text-orange-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-teal-100">Retention Rate</p>
                                <p class="text-2xl font-bold"><span x-text="customerReport?.summary?.retention_rate || 0"></span>%</p>
                            </div>
                            <i class="fas fa-chart-pie text-3xl text-teal-200"></i>
                        </div>
                    </div>
                </div>

                <!-- Top Customers -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Top Customers</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left">Customer</th>
                                    <th class="px-4 py-2 text-left">Email</th>
                                    <th class="px-4 py-2 text-right">Orders</th>
                                    <th class="px-4 py-2 text-right">Total Spent</th>
                                    <th class="px-4 py-2 text-right">Avg Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="customer in customerReport?.top_customers || []" :key="customer.id">
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-2">
                                            <div class="font-medium" x-text="customer.first_name + ' ' + customer.last_name"></div>
                                        </td>
                                        <td class="px-4 py-2 text-blue-600" x-text="customer.email"></td>
                                        <td class="px-4 py-2 text-right" x-text="customer.total_orders"></td>
                                        <td class="px-4 py-2 text-right font-medium">$<span x-text="parseFloat(customer.total_spent || 0).toFixed(2)"></span></td>
                                        <td class="px-4 py-2 text-right">$<span x-text="parseFloat(customer.avg_order_value || 0).toFixed(2)"></span></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function adminReports() {
    return {
        salesReport: null,
        customerReport: null,
        dateFrom: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 30 days ago
        dateTo: new Date().toISOString().split('T')[0], // today
        loading: false,

        async init() {
            await this.loadReports();
        },

        async loadReports() {
            // Load both reports initially
            await Promise.all([
                this.generateSalesReport(),
                this.generateCustomerReport()
            ]);
        },

        async exportOrders() {
            try {
                this.loading = true;

                const params = new URLSearchParams({
                    date_from: this.dateFrom,
                    date_to: this.dateTo
                });

                const response = await axios.get(`/api/admin/orders/export?${params}`, {
                    responseType: 'blob'
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `bellgas_orders_${this.dateFrom}_to_${this.dateTo}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);

                this.showNotification('Orders exported successfully', 'success');
            } catch (error) {
                console.error('Export failed:', error);
                this.showNotification('Failed to export orders', 'error');
            } finally {
                this.loading = false;
            }
        },

        async generateSalesReport() {
            try {
                this.loading = true;

                const params = new URLSearchParams({
                    date_from: this.dateFrom,
                    date_to: this.dateTo
                });

                const response = await axios.get(`/api/admin/reports/sales?${params}`);
                this.salesReport = response.data.data;

                this.showNotification('Sales report generated', 'success');
            } catch (error) {
                console.error('Sales report failed:', error);
                this.showNotification('Failed to generate sales report', 'error');
            } finally {
                this.loading = false;
            }
        },

        async generateCustomerReport() {
            try {
                this.loading = true;

                const params = new URLSearchParams({
                    date_from: this.dateFrom,
                    date_to: this.dateTo
                });

                const response = await axios.get(`/api/admin/reports/customers?${params}`);
                this.customerReport = response.data.data;

                this.showNotification('Customer report generated', 'success');
            } catch (error) {
                console.error('Customer report failed:', error);
                this.showNotification('Failed to generate customer report', 'error');
            } finally {
                this.loading = false;
            }
        },

        getStatusColor(status) {
            const colors = {
                'PENDING': 'bg-red-100 text-red-800',
                'PAID': 'bg-blue-100 text-blue-800',
                'PROCESSED': 'bg-yellow-100 text-yellow-800',
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
                day: 'numeric'
            });
        },

        showNotification(message, type = 'info') {
            // You can implement a toast notification system here
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }
}
</script>
@endsection