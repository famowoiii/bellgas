@extends('layouts.app')

@section('title', 'My Addresses - BellGas')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="addressesPage()" x-init="init()">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Addresses</h1>
            <p class="text-gray-600">Manage your delivery addresses</p>
        </div>

        <!-- Add New Address Button -->
        <div class="mb-6">
            <button @click="showAddModal = true" 
                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-plus mr-2"></i>Add New Address
            </button>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-600">Loading addresses...</span>
        </div>

        <!-- Addresses List -->
        <div x-show="!loading" class="space-y-6">
            <div x-show="addresses.length === 0" class="text-center py-12">
                <div class="text-gray-400 text-6xl mb-4">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No addresses yet</h3>
                <p class="text-gray-500 mb-6">Add your first delivery address to get started</p>
                <button @click="showAddModal = true" 
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    Add Address
                </button>
            </div>

            <template x-for="address in addresses" :key="address.id">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-2" x-text="address.type"></span>
                                <span x-show="address.is_default" class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Default</span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800" x-text="address.name"></h3>
                            <p class="text-gray-600" x-text="address.full_address"></p>
                            <p x-show="address.delivery_instructions" class="text-sm text-gray-500 mt-1" x-text="'Instructions: ' + address.delivery_instructions"></p>
                        </div>
                        <div class="flex space-x-2">
                            <button @click="editAddress(address)" 
                                class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button @click="deleteAddress(address.id)" 
                                class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Add/Edit Address Modal -->
        <div x-show="showAddModal || showEditModal" 
            class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
            @click.self="closeModal()">
            <div class="bg-white rounded-lg max-w-md w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold" x-text="showAddModal ? 'Add New Address' : 'Edit Address'"></h3>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form @submit.prevent="showAddModal ? addAddress() : updateAddress()">
                        <!-- Address Type -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Address Type</label>
                            <select x-model="formData.type" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="HOME">Home</option>
                                <option value="WORK">Work</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>

                        <!-- Name -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Address Name</label>
                            <input type="text" x-model="formData.name" required
                                placeholder="e.g., Home, Office, etc."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Street Address -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Street Address</label>
                            <textarea x-model="formData.street_address" required rows="3"
                                placeholder="Unit/Level, Street Number and Name"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <!-- Suburb -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Suburb</label>
                            <input type="text" x-model="formData.suburb" required
                                placeholder="Suburb"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- State and Postcode -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                                <select x-model="formData.state" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="NSW">NSW</option>
                                    <option value="VIC">VIC</option>
                                    <option value="QLD">QLD</option>
                                    <option value="WA">WA</option>
                                    <option value="SA">SA</option>
                                    <option value="TAS">TAS</option>
                                    <option value="ACT">ACT</option>
                                    <option value="NT">NT</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Postcode</label>
                                <input type="text" x-model="formData.postcode" required
                                    pattern="[0-9]{4}" maxlength="4"
                                    placeholder="4000"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Delivery Instructions -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Instructions (Optional)</label>
                            <textarea x-model="formData.delivery_instructions" rows="2"
                                placeholder="Special instructions for delivery driver"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <!-- Is Default -->
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="formData.is_default" class="mr-2">
                                <span class="text-sm text-gray-700">Set as default address</span>
                            </label>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="closeModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" :disabled="saving"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                <span x-show="!saving" x-text="showAddModal ? 'Add Address' : 'Update Address'"></span>
                                <span x-show="saving">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function addressesPage() {
    return {
        addresses: [],
        loading: true,
        saving: false,
        showAddModal: false,
        showEditModal: false,
        editingAddress: null,
        formData: {
            type: 'HOME',
            name: '',
            street_address: '',
            suburb: '',
            state: 'NSW',
            postcode: '',
            delivery_instructions: '',
            is_default: false
        },
        
        async init() {
            console.log('📍 Addresses page initializing...');
            
            // Wait for authentication
            await this.waitForAuth();
            
            // Load addresses
            await this.loadAddresses();
        },
        
        async waitForAuth() {
            console.log('⏳ Waiting for authentication...');
            
            for (let i = 0; i < 50; i++) {
                if (window.app && window.app.user) {
                    console.log('✅ Authentication ready');
                    return;
                }
                await new Promise(resolve => setTimeout(resolve, 100));
            }
            
            console.warn('⚠️ Authentication timeout - proceeding anyway');
        },
        
        async loadAddresses() {
            this.loading = true;
            
            try {
                console.log('📡 Loading addresses from API...');
                const response = await axios.get('/api/addresses');
                this.addresses = response.data.data;
                console.log('✅ Addresses loaded:', this.addresses.length);
            } catch (error) {
                console.error('❌ Failed to load addresses:', error);
                this.showNotification('Failed to load addresses', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async addAddress() {
            this.saving = true;
            
            try {
                console.log('📝 Adding address...', this.formData);
                const response = await axios.post('/api/addresses', this.formData);
                console.log('✅ Address added successfully');
                
                this.addresses.push(response.data.data);
                this.closeModal();
                this.showNotification('Address added successfully', 'success');
            } catch (error) {
                console.error('❌ Failed to add address:', error);
                
                if (error.response?.status === 422) {
                    const errors = error.response.data.errors;
                    const firstError = Object.values(errors)[0][0];
                    this.showNotification(firstError, 'error');
                } else {
                    this.showNotification('Failed to add address', 'error');
                }
            } finally {
                this.saving = false;
            }
        },
        
        editAddress(address) {
            this.editingAddress = address;
            this.formData = { ...address };
            this.showEditModal = true;
        },
        
        async updateAddress() {
            this.saving = true;
            
            try {
                console.log('📝 Updating address...', this.formData);
                const response = await axios.put(`/api/addresses/${this.editingAddress.id}`, this.formData);
                console.log('✅ Address updated successfully');
                
                // Update in list
                const index = this.addresses.findIndex(a => a.id === this.editingAddress.id);
                if (index !== -1) {
                    this.addresses[index] = response.data.data;
                }
                
                this.closeModal();
                this.showNotification('Address updated successfully', 'success');
            } catch (error) {
                console.error('❌ Failed to update address:', error);
                this.showNotification('Failed to update address', 'error');
            } finally {
                this.saving = false;
            }
        },
        
        async deleteAddress(addressId) {
            if (!confirm('Are you sure you want to delete this address?')) return;
            
            try {
                console.log('🗑️ Deleting address:', addressId);
                await axios.delete(`/api/addresses/${addressId}`);
                
                this.addresses = this.addresses.filter(a => a.id !== addressId);
                this.showNotification('Address deleted successfully', 'success');
                console.log('✅ Address deleted successfully');
            } catch (error) {
                console.error('❌ Failed to delete address:', error);
                this.showNotification('Failed to delete address', 'error');
            }
        },
        
        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.editingAddress = null;
            this.formData = {
                type: 'HOME',
                name: '',
                street_address: '',
                suburb: '',
                state: 'NSW',
                postcode: '',
                delivery_instructions: '',
                is_default: false
            };
        },
        
        showNotification(message, type = 'info') {
            if (window.app && window.app.showNotification) {
                window.app.showNotification(message, type);
            } else {
                alert(message);
            }
        }
    }
}
</script>
@endsection