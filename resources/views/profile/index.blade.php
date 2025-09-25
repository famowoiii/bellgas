@extends('layouts.app')

@section('title', 'Profile Settings - BellGas')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="profilePage()" x-init="init()">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Account Settings</h1>
        
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Settings Navigation -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <nav class="space-y-2">
                        <button @click="activeTab = 'profile'" 
                                :class="activeTab === 'profile' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'text-gray-700 hover:bg-gray-50'"
                                class="w-full flex items-center px-4 py-3 text-left rounded-lg border transition">
                            <i class="fas fa-user mr-3"></i>
                            Profile Information
                        </button>
                        
                        <button @click="activeTab = 'security'" 
                                :class="activeTab === 'security' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'text-gray-700 hover:bg-gray-50'"
                                class="w-full flex items-center px-4 py-3 text-left rounded-lg border transition">
                            <i class="fas fa-shield-alt mr-3"></i>
                            Security
                        </button>
                        
                        <button @click="activeTab = 'addresses'" 
                                :class="activeTab === 'addresses' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'text-gray-700 hover:bg-gray-50'"
                                class="w-full flex items-center px-4 py-3 text-left rounded-lg border transition">
                            <i class="fas fa-map-marker-alt mr-3"></i>
                            Addresses
                        </button>
                        
                        <button @click="activeTab = 'notifications'" 
                                :class="activeTab === 'notifications' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'text-gray-700 hover:bg-gray-50'"
                                class="w-full flex items-center px-4 py-3 text-left rounded-lg border transition">
                            <i class="fas fa-bell mr-3"></i>
                            Notifications
                        </button>
                        
                        <button @click="activeTab = 'preferences'" 
                                :class="activeTab === 'preferences' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'text-gray-700 hover:bg-gray-50'"
                                class="w-full flex items-center px-4 py-3 text-left rounded-lg border transition">
                            <i class="fas fa-cog mr-3"></i>
                            Preferences
                        </button>
                    </nav>
                </div>
                
                <!-- Account Overview -->
                <div class="mt-6 bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">Account Overview</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Account Type:</span>
                            <span class="font-medium" x-text="profile?.role || 'Customer'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Member Since:</span>
                            <span class="font-medium" x-text="formatDate(profile?.created_at)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Login:</span>
                            <span class="font-medium" x-text="formatDate(profile?.last_login_at) || 'Unknown'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Orders:</span>
                            <span class="font-medium" x-text="stats.total_orders"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="lg:col-span-2">
                <!-- Profile Information Tab -->
                <div x-show="activeTab === 'profile'" class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Profile Information</h2>
                        <button @click="editMode = !editMode" 
                                :class="editMode ? 'bg-gray-600' : 'bg-blue-600'"
                                class="text-white px-4 py-2 rounded hover:opacity-90 transition">
                            <i :class="editMode ? 'fas fa-times' : 'fas fa-edit'" class="mr-2"></i>
                            <span x-text="editMode ? 'Cancel' : 'Edit'"></span>
                        </button>
                    </div>

                    <form @submit.prevent="updateProfile()">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- First Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                <input x-model="profileForm.first_name" 
                                       :disabled="!editMode"
                                       type="text" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-500">
                                <p x-show="errors.first_name" class="mt-1 text-sm text-red-600" x-text="errors.first_name?.[0]"></p>
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                <input x-model="profileForm.last_name" 
                                       :disabled="!editMode"
                                       type="text" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-500">
                                <p x-show="errors.last_name" class="mt-1 text-sm text-red-600" x-text="errors.last_name?.[0]"></p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input x-model="profileForm.email" 
                                       :disabled="!editMode"
                                       type="email" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-500">
                                <p x-show="errors.email" class="mt-1 text-sm text-red-600" x-text="errors.email?.[0]"></p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input x-model="profileForm.phone_number" 
                                       :disabled="!editMode"
                                       type="tel" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-500">
                                <p x-show="errors.phone_number" class="mt-1 text-sm text-red-600" x-text="errors.phone_number?.[0]"></p>
                            </div>

                            <!-- Role (read-only) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                                <input :value="profileForm.role" 
                                       disabled
                                       type="text" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                            </div>

                            <!-- Account Status (read-only) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Status</label>
                                <div class="flex items-center">
                                    <span :class="profileForm.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          class="px-3 py-1 rounded-full text-sm font-medium">
                                        <span x-text="profileForm.is_active ? 'Active' : 'Inactive'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div x-show="editMode" class="mt-6 flex justify-end">
                            <button type="submit" 
                                    :disabled="profileUpdateLoading"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
                                <span x-show="!profileUpdateLoading">Save Changes</span>
                                <span x-show="profileUpdateLoading">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Tab -->
                <div x-show="activeTab === 'security'" class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-6">Security Settings</h2>

                    <!-- Change Password -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium mb-4">Change Password</h3>
                        <form @submit.prevent="updatePassword()">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                    <input x-model="passwordForm.current_password" 
                                           type="password" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <p x-show="passwordErrors.current_password" class="mt-1 text-sm text-red-600" x-text="passwordErrors.current_password?.[0]"></p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                        <input x-model="passwordForm.password" 
                                               type="password" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <p x-show="passwordErrors.password" class="mt-1 text-sm text-red-600" x-text="passwordErrors.password?.[0]"></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                        <input x-model="passwordForm.password_confirmation" 
                                               type="password" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <p x-show="passwordErrors.password_confirmation" class="mt-1 text-sm text-red-600" x-text="passwordErrors.password_confirmation?.[0]"></p>
                                    </div>
                                </div>

                                <button type="submit" 
                                        :disabled="passwordUpdateLoading"
                                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
                                    <span x-show="!passwordUpdateLoading">Update Password</span>
                                    <span x-show="passwordUpdateLoading">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Updating...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Two-Factor Authentication -->
                    <div class="border-t pt-8">
                        <h3 class="text-lg font-medium mb-4">Two-Factor Authentication</h3>
                        <div class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <h4 class="font-medium">SMS Authentication</h4>
                                <p class="text-sm text-gray-600">Receive SMS codes for additional security</p>
                            </div>
                            <div class="flex items-center">
                                <span class="text-sm text-gray-500 mr-3">Coming Soon</span>
                                <button disabled class="bg-gray-300 text-gray-500 px-4 py-2 rounded-lg cursor-not-allowed">
                                    Enable
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Addresses Tab -->
                <div x-show="activeTab === 'addresses'" class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Delivery Addresses</h2>
                        <button @click="showAddressForm = true" 
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i>Add Address
                        </button>
                    </div>

                    <!-- Address List -->
                    <div x-show="addresses.length > 0" class="space-y-4">
                        <template x-for="address in addresses" :key="address.id">
                            <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-800" x-text="address.name"></h3>
                                        <p class="text-sm text-gray-600 mt-1" x-text="address.full_address"></p>
                                        <p x-show="address.delivery_instructions" 
                                           class="text-xs text-gray-500 mt-2" 
                                           x-text="'Instructions: ' + address.delivery_instructions"></p>
                                    </div>
                                    <div class="flex space-x-2 ml-4">
                                        <button @click="editAddress(address)" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="deleteAddress(address)" 
                                                :disabled="deletingAddress"
                                                class="text-red-600 hover:text-red-800 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                            <i :class="deletingAddress ? 'fas fa-spinner fa-spin' : 'fas fa-trash'"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div x-show="addresses.length === 0" class="text-center py-8">
                        <i class="fas fa-map-marker-alt text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-600 mb-2">No addresses saved</h3>
                        <p class="text-gray-500 mb-4">Add your delivery addresses for faster checkout</p>
                        <button @click="showAddressForm = true" 
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            Add Your First Address
                        </button>
                    </div>

                    <!-- Add/Edit Address Form -->
                    <div x-show="showAddressForm" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                        <div class="bg-white rounded-lg max-w-md w-full p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold" x-text="editingAddress ? 'Edit Address' : 'Add New Address'"></h3>
                                <button @click="closeAddressForm()" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <form @submit.prevent="saveAddress()">
                                <div class="space-y-4">
                                    <input x-model="addressForm.name" type="text" placeholder="Address Name (e.g., Home)" 
                                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    
                                    <input x-model="addressForm.street_address" type="text" placeholder="Street Address" 
                                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <input x-model="addressForm.suburb" type="text" placeholder="Suburb" 
                                               required class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        
                                        <input x-model="addressForm.postcode" type="text" placeholder="Postcode" 
                                               required class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <select x-model="addressForm.state" required 
                                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Select State</option>
                                            <option value="NSW">NSW</option>
                                            <option value="VIC">VIC</option>
                                            <option value="QLD">QLD</option>
                                            <option value="SA">SA</option>
                                            <option value="WA">WA</option>
                                            <option value="TAS">TAS</option>
                                            <option value="NT">NT</option>
                                            <option value="ACT">ACT</option>
                                        </select>
                                        
                                        <input x-model="addressForm.country" type="text" value="Australia" 
                                               readonly class="px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                                    </div>
                                    
                                    <textarea x-model="addressForm.delivery_instructions" placeholder="Delivery Instructions (Optional)" 
                                              rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                                </div>
                                
                                <div class="flex justify-end space-x-3 mt-6">
                                    <button type="button" @click="closeAddressForm()" 
                                            class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" 
                                            :disabled="savingAddress"
                                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 min-h-[40px]">
                                        <div x-show="savingAddress" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                                        <i x-show="!savingAddress && !editingAddress" class="fas fa-plus"></i>
                                        <i x-show="!savingAddress && editingAddress" class="fas fa-save"></i>
                                        <span x-show="savingAddress" x-text="editingAddress ? 'Updating...' : 'Adding...'"></span>
                                        <span x-show="!savingAddress" x-text="editingAddress ? 'Update Address' : 'Add Address'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Notifications Tab -->
                <div x-show="activeTab === 'notifications'" class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-6">Notification Preferences</h2>

                    <div class="space-y-6">
                        <div class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <h4 class="font-medium">Order Updates</h4>
                                <p class="text-sm text-gray-600">Receive notifications about your order status</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input x-model="notificationSettings.order_updates" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <h4 class="font-medium">Marketing Emails</h4>
                                <p class="text-sm text-gray-600">Receive promotional offers and product updates</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input x-model="notificationSettings.marketing_emails" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <h4 class="font-medium">SMS Notifications</h4>
                                <p class="text-sm text-gray-600">Receive text messages for important updates</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input x-model="notificationSettings.sms_notifications" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button @click="updateNotificationSettings()" 
                                :disabled="updatingNotifications"
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <div x-show="updatingNotifications" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                            <i x-show="!updatingNotifications" class="fas fa-save"></i>
                            <span x-show="updatingNotifications">Saving...</span>
                            <span x-show="!updatingNotifications">Save Preferences</span>
                        </button>
                    </div>
                </div>

                <!-- Preferences Tab -->
                <div x-show="activeTab === 'preferences'" class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-6">Account Preferences</h2>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Delivery Method</label>
                            <select x-model="preferences.default_fulfillment_method" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="DELIVERY">Delivery</option>
                                <option value="PICKUP">Pickup</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Currency Display</label>
                            <select x-model="preferences.currency" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="AUD">Australian Dollar (AUD)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                            <select x-model="preferences.language" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="en">English</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button @click="updatePreferences()" 
                                :disabled="updatingPreferences"
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <div x-show="updatingPreferences" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                            <i x-show="!updatingPreferences" class="fas fa-save"></i>
                            <span x-show="updatingPreferences">Saving...</span>
                            <span x-show="!updatingPreferences">Save Preferences</span>
                        </button>
                    </div>

                    <!-- Danger Zone -->
                    <div class="mt-12 pt-6 border-t border-red-200">
                        <h3 class="text-lg font-medium text-red-600 mb-4">Danger Zone</h3>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <h4 class="font-medium text-red-800 mb-2">Delete Account</h4>
                            <p class="text-sm text-red-700 mb-4">
                                Permanently delete your account and all associated data. This action cannot be undone.
                            </p>
                            <button @click="confirmDeleteAccount()" 
                                    class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function profilePage() {
    return {
        activeTab: 'profile',
        profile: null,
        stats: { total_orders: 0 },
        editMode: false,
        
        // Profile form
        profileForm: {
            first_name: '',
            last_name: '',
            email: '',
            phone_number: '',
            role: '',
            is_active: true
        },
        profileUpdateLoading: false,
        errors: {},
        
        // Password form
        passwordForm: {
            current_password: '',
            password: '',
            password_confirmation: ''
        },
        passwordUpdateLoading: false,
        passwordErrors: {},
        
        // Addresses
        addresses: [],
        showAddressForm: false,
        editingAddress: null,
        savingAddress: false,
        deletingAddress: false,
        addressForm: {
            name: '',
            street_address: '',
            suburb: '',
            state: '',
            postcode: '',
            country: 'Australia',
            delivery_instructions: ''
        },
        
        // Notifications
        notificationSettings: {
            order_updates: true,
            marketing_emails: false,
            sms_notifications: false
        },
        
        // Preferences
        preferences: {
            default_fulfillment_method: 'DELIVERY',
            currency: 'AUD',
            language: 'en'
        },
        updatingNotifications: false,
        updatingPreferences: false,
        
        async init() {
            await this.loadProfile();
            await this.loadAddresses();
            await this.loadStats();
        },
        
        async loadProfile() {
            try {
                const response = await axios.get('/api/auth/me');
                this.profile = response.data.user;
                this.profileForm = { ...this.profile };
                
            } catch (error) {
                console.error('Failed to load profile:', error);
            }
        },
        
        async loadAddresses() {
            try {
                const response = await axios.get('/api/addresses');
                this.addresses = response.data.data || [];
                
            } catch (error) {
                console.error('Failed to load addresses:', error);
            }
        },
        
        async loadStats() {
            try {
                const response = await axios.get('/api/orders');
                this.stats.total_orders = response.data.data?.length || 0;
                
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },
        
        async updateProfile() {
            this.profileUpdateLoading = true;
            this.errors = {};
            
            try {
                const response = await axios.put('/api/profile', this.profileForm);
                this.profile = response.data.user;
                this.editMode = false;
                
                this.showNotification('Profile updated successfully!', 'success');
                
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors || {};
                } else {
                    this.showNotification('Failed to update profile', 'error');
                }
            } finally {
                this.profileUpdateLoading = false;
            }
        },
        
        async updatePassword() {
            this.passwordUpdateLoading = true;
            this.passwordErrors = {};
            
            try {
                await axios.put('/api/profile/password', this.passwordForm);
                
                this.passwordForm = {
                    current_password: '',
                    password: '',
                    password_confirmation: ''
                };
                
                this.showNotification('Password updated successfully!', 'success');
                
            } catch (error) {
                if (error.response?.status === 422) {
                    this.passwordErrors = error.response.data.errors || {};
                } else {
                    this.showNotification('Failed to update password', 'error');
                }
            } finally {
                this.passwordUpdateLoading = false;
            }
        },
        
        editAddress(address) {
            this.editingAddress = address;
            this.addressForm = { ...address };
            this.showAddressForm = true;
        },
        
        async saveAddress() {
            this.savingAddress = true;
            try {
                if (this.editingAddress) {
                    // Update existing address
                    const response = await axios.put(`/api/addresses/${this.editingAddress.id}`, this.addressForm);
                    const index = this.addresses.findIndex(a => a.id === this.editingAddress.id);
                    this.addresses[index] = response.data.data;
                } else {
                    // Create new address
                    const response = await axios.post('/api/addresses', this.addressForm);
                    this.addresses.push(response.data.data);
                }
                
                this.closeAddressForm();
                this.showNotification('Address saved successfully!', 'success');
                
            } catch (error) {
                this.showNotification('Failed to save address', 'error');
            } finally {
                this.savingAddress = false;
            }
        },
        
        async deleteAddress(address) {
            if (!confirm(`Are you sure you want to delete "${address.name}"?`)) {
                return;
            }
            
            this.deletingAddress = true;
            try {
                await axios.delete(`/api/addresses/${address.id}`);
                this.addresses = this.addresses.filter(a => a.id !== address.id);
                this.showNotification('Address deleted successfully!', 'success');
                
            } catch (error) {
                this.showNotification('Failed to delete address', 'error');
            } finally {
                this.deletingAddress = false;
            }
        },
        
        closeAddressForm() {
            this.showAddressForm = false;
            this.editingAddress = null;
            this.addressForm = {
                name: '',
                street_address: '',
                suburb: '',
                state: '',
                postcode: '',
                country: 'Australia',
                delivery_instructions: ''
            };
        },
        
        async updateNotificationSettings() {
            this.updatingNotifications = true;
            try {
                // Simulate API call delay
                await new Promise(resolve => setTimeout(resolve, 1000));
                // In a real app, this would make an API call
                this.showNotification('Notification preferences saved!', 'success');
            } catch (error) {
                this.showNotification('Failed to save preferences', 'error');
            } finally {
                this.updatingNotifications = false;
            }
        },
        
        async updatePreferences() {
            this.updatingPreferences = true;
            try {
                // Simulate API call delay
                await new Promise(resolve => setTimeout(resolve, 1000));
                // In a real app, this would make an API call
                this.showNotification('Preferences saved!', 'success');
            } catch (error) {
                this.showNotification('Failed to save preferences', 'error');
            } finally {
                this.updatingPreferences = false;
            }
        },
        
        confirmDeleteAccount() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                if (confirm('This will permanently delete all your data. Are you absolutely sure?')) {
                    this.deleteAccount();
                }
            }
        },
        
        async deleteAccount() {
            try {
                await axios.delete('/api/profile');
                alert('Your account has been deleted.');
                window.location.href = '/';
                
            } catch (error) {
                this.showNotification('Failed to delete account', 'error');
            }
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