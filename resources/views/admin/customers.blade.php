@extends('layouts.app')

@section('title', 'Customer Management - BellGas Admin')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="adminCustomers()" x-init="init()">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Customer Management</h1>
                <p class="text-gray-600">View and manage customer accounts</p>
            </div>
            
            <!-- Search and Filters -->
            <div class="flex space-x-3">
                <input type="text" x-model="searchTerm" @input="searchCustomers()" 
                       placeholder="Search customers..."
                       class="border border-gray-300 rounded-lg px-4 py-2 w-64">
                <button @click="refreshCustomers()" 
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>

        <!-- Customer Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.total"></p>
                        <p class="text-gray-600 text-sm">Total Customers</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-user-check text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.active"></p>
                        <p class="text-gray-600 text-sm">Active Customers</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-shopping-bag text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.with_orders"></p>
                        <p class="text-gray-600 text-sm">With Orders</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-calendar text-orange-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.new_this_month"></p>
                        <p class="text-gray-600 text-sm">New This Month</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Customer List</h2>
                    <div class="flex space-x-2">
                        <select x-model="statusFilter" @change="filterCustomers()" 
                                class="text-sm border border-gray-300 rounded px-3 py-1">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <select x-model="roleFilter" @change="filterCustomers()" 
                                class="text-sm border border-gray-300 rounded px-3 py-1">
                            <option value="">All Roles</option>
                            <option value="CUSTOMER">Customer</option>
                            <option value="MERCHANT">Merchant</option>
                            <option value="ADMIN">Admin</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="customer in filteredCustomers" :key="customer.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900" x-text="customer.first_name + ' ' + customer.last_name"></div>
                                            <div class="text-sm text-gray-500" x-text="customer.email"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="getRoleColor(customer.role)"
                                          x-text="customer.role"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="customer.phone_number || 'N/A'"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="customer.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          x-text="customer.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="customer.orders_count || 0"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(customer.created_at)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="viewCustomer(customer)" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button @click="toggleCustomerStatus(customer)" 
                                                :class="customer.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'">
                                            <i :class="customer.is_active ? 'fas fa-user-slash' : 'fas fa-user-check'"></i>
                                        </button>
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="text-gray-600 hover:text-gray-900">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div x-show="open" @click.away="open = false" 
                                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-20">
                                                <div class="py-1">
                                                    <button @click="viewOrders(customer); open = false" 
                                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                                        View Orders
                                                    </button>
                                                    <button @click="sendEmail(customer); open = false" 
                                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                                        Send Email
                                                    </button>
                                                    <button @click="changeRole(customer); open = false" 
                                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                                        Change Role
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div x-show="filteredCustomers.length === 0" class="p-12 text-center text-gray-500">
                    <i class="fas fa-users text-4xl mb-4"></i>
                    <p>No customers found</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Details Modal -->
    <div x-show="showCustomerModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-3xl w-full max-h-96 overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Customer Details</h3>
                    <button @click="showCustomerModal = false" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div x-show="selectedCustomer" class="p-6">
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-semibold mb-2">Basic Information</h4>
                        <p class="text-sm"><strong>Name:</strong> <span x-text="selectedCustomer?.first_name + ' ' + selectedCustomer?.last_name"></span></p>
                        <p class="text-sm"><strong>Email:</strong> <span x-text="selectedCustomer?.email"></span></p>
                        <p class="text-sm"><strong>Phone:</strong> <span x-text="selectedCustomer?.phone_number || 'N/A'"></span></p>
                        <p class="text-sm"><strong>Role:</strong> <span x-text="selectedCustomer?.role"></span></p>
                        <p class="text-sm"><strong>Status:</strong> 
                            <span :class="selectedCustomer?.is_active ? 'text-green-600' : 'text-red-600'"
                                  x-text="selectedCustomer?.is_active ? 'Active' : 'Inactive'"></span>
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-2">Account Information</h4>
                        <p class="text-sm"><strong>Joined:</strong> <span x-text="formatDate(selectedCustomer?.created_at)"></span></p>
                        <p class="text-sm"><strong>Last Updated:</strong> <span x-text="formatDate(selectedCustomer?.updated_at)"></span></p>
                        <p class="text-sm"><strong>Email Verified:</strong> 
                            <span x-text="selectedCustomer?.email_verified_at ? 'Yes' : 'No'"></span>
                        </p>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button @click="showCustomerModal = false" 
                            class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 transition">
                        Close
                    </button>
                    <button @click="editCustomer(selectedCustomer)" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        Edit Customer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function adminCustomers() {
    return {
        customers: [],
        filteredCustomers: [],
        searchTerm: '',
        statusFilter: '',
        roleFilter: '',
        stats: {
            total: 0,
            active: 0,
            with_orders: 0,
            new_this_month: 0
        },
        showCustomerModal: false,
        selectedCustomer: null,
        
        async init() {
            await this.loadCustomers();
        },
        
        async loadCustomers() {
            try {
                const response = await axios.get('/api/admin/customers');
                this.customers = response.data.customers || [];
                this.filteredCustomers = this.customers;
                this.calculateStats();
            } catch (error) {
                console.error('Failed to load customers:', error);
                this.customers = []; // Fallback to empty array
                this.filteredCustomers = [];
                this.showNotification('Failed to load customers', 'error');
            }
        },
        
        async refreshCustomers() {
            await this.loadCustomers();
            this.showNotification('Customers refreshed successfully', 'success');
        },
        
        searchCustomers() {
            this.filterCustomers();
        },
        
        filterCustomers() {
            let filtered = this.customers;
            
            if (this.searchTerm) {
                const term = this.searchTerm.toLowerCase();
                filtered = filtered.filter(customer => 
                    customer.first_name.toLowerCase().includes(term) ||
                    customer.last_name.toLowerCase().includes(term) ||
                    customer.email.toLowerCase().includes(term)
                );
            }
            
            if (this.statusFilter) {
                if (this.statusFilter === 'active') {
                    filtered = filtered.filter(customer => customer.is_active);
                } else if (this.statusFilter === 'inactive') {
                    filtered = filtered.filter(customer => !customer.is_active);
                }
            }
            
            if (this.roleFilter) {
                filtered = filtered.filter(customer => customer.role === this.roleFilter);
            }
            
            this.filteredCustomers = filtered;
        },
        
        calculateStats() {
            const now = new Date();
            const thisMonth = new Date(now.getFullYear(), now.getMonth(), 1);
            
            this.stats = {
                total: this.customers.length,
                active: this.customers.filter(c => c.is_active).length,
                with_orders: this.customers.filter(c => c.orders_count > 0).length,
                new_this_month: this.customers.filter(c => new Date(c.created_at) >= thisMonth).length
            };
        },
        
        viewCustomer(customer) {
            this.selectedCustomer = customer;
            this.showCustomerModal = true;
        },
        
        async toggleCustomerStatus(customer) {
            const action = customer.is_active ? 'deactivate' : 'activate';
            if (!confirm(`Are you sure you want to ${action} this customer?`)) return;
            
            try {
                await axios.put(`/api/admin/customers/${customer.id}`, {
                    is_active: !customer.is_active
                });
                
                customer.is_active = !customer.is_active;
                this.calculateStats();
                this.showNotification(`Customer ${action}d successfully`, 'success');
                
            } catch (error) {
                console.error('Failed to update customer status:', error);
                this.showNotification('Failed to update customer status', 'error');
            }
        },
        
        viewOrders(customer) {
            window.location.href = `/admin/orders?customer=${customer.id}`;
        },
        
        sendEmail(customer) {
            window.location.href = `mailto:${customer.email}`;
        },
        
        changeRole(customer) {
            const newRole = prompt('Enter new role (CUSTOMER, MERCHANT, ADMIN):', customer.role);
            if (!newRole || !['CUSTOMER', 'MERCHANT', 'ADMIN'].includes(newRole.toUpperCase())) return;
            
            this.updateCustomerRole(customer, newRole.toUpperCase());
        },
        
        async updateCustomerRole(customer, newRole) {
            try {
                await axios.put(`/api/admin/customers/${customer.id}`, {
                    role: newRole
                });
                
                customer.role = newRole;
                this.showNotification(`Customer role updated to ${newRole}`, 'success');
                
            } catch (error) {
                console.error('Failed to update customer role:', error);
                this.showNotification('Failed to update customer role', 'error');
            }
        },
        
        getRoleColor(role) {
            const colors = {
                'CUSTOMER': 'bg-blue-100 text-blue-800',
                'MERCHANT': 'bg-purple-100 text-purple-800',
                'ADMIN': 'bg-red-100 text-red-800'
            };
            return colors[role] || 'bg-gray-100 text-gray-800';
        },
        
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-AU', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}
</script>
@endsection