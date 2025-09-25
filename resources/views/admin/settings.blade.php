@extends('layouts.app')

@section('title', 'Admin Settings - BellGas')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="adminSettings()" x-init="init()">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Admin Settings</h1>
                <p class="text-gray-600">Configure system settings and preferences</p>
            </div>
            
            <!-- Save Button -->
            <button @click="saveAllSettings()" 
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-save mr-2"></i>Save All Changes
            </button>
        </div>

        <div class="grid lg:grid-cols-4 gap-8">
            <!-- Settings Navigation -->
            <div class="lg:col-span-1">
                <nav class="bg-white rounded-lg shadow-md p-4">
                    <ul class="space-y-2">
                        <li>
                            <button @click="activeTab = 'general'" 
                                    :class="activeTab === 'general' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
                                    class="w-full text-left px-3 py-2 rounded-lg transition">
                                <i class="fas fa-cog mr-2"></i>General
                            </button>
                        </li>
                        <li>
                            <button @click="activeTab = 'payment'" 
                                    :class="activeTab === 'payment' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
                                    class="w-full text-left px-3 py-2 rounded-lg transition">
                                <i class="fas fa-credit-card mr-2"></i>Payment
                            </button>
                        </li>
                        <li>
                            <button @click="activeTab = 'email'" 
                                    :class="activeTab === 'email' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
                                    class="w-full text-left px-3 py-2 rounded-lg transition">
                                <i class="fas fa-envelope mr-2"></i>Email
                            </button>
                        </li>
                        <li>
                            <button @click="activeTab = 'delivery'" 
                                    :class="activeTab === 'delivery' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
                                    class="w-full text-left px-3 py-2 rounded-lg transition">
                                <i class="fas fa-truck mr-2"></i>Delivery
                            </button>
                        </li>
                        <li>
                            <button @click="activeTab = 'notifications'" 
                                    :class="activeTab === 'notifications' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
                                    class="w-full text-left px-3 py-2 rounded-lg transition">
                                <i class="fas fa-bell mr-2"></i>Notifications
                            </button>
                        </li>
                        <li>
                            <button @click="activeTab = 'security'" 
                                    :class="activeTab === 'security' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
                                    class="w-full text-left px-3 py-2 rounded-lg transition">
                                <i class="fas fa-shield-alt mr-2"></i>Security
                            </button>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Settings Content -->
            <div class="lg:col-span-3">
                <!-- General Settings -->
                <div x-show="activeTab === 'general'" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-6">General Settings</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
                            <input type="text" x-model="settings.general.site_name"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Site Description</label>
                            <textarea x-model="settings.general.site_description" rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
                            <input type="email" x-model="settings.general.contact_email"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                            <input type="text" x-model="settings.general.contact_phone"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" x-model="settings.general.maintenance_mode" id="maintenance_mode"
                                   class="mr-2 rounded border-gray-300">
                            <label for="maintenance_mode" class="text-sm font-medium text-gray-700">Maintenance Mode</label>
                        </div>
                    </div>
                </div>

                <!-- Payment Settings -->
                <div x-show="activeTab === 'payment'" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-6">Payment Settings</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Stripe Publishable Key</label>
                            <input type="text" x-model="settings.payment.stripe_publishable_key"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Stripe Secret Key</label>
                            <input type="password" x-model="settings.payment.stripe_secret_key"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Default Currency</label>
                            <select x-model="settings.payment.default_currency"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="AUD">AUD - Australian Dollar</option>
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                            </select>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" x-model="settings.payment.test_mode" id="test_mode"
                                   class="mr-2 rounded border-gray-300">
                            <label for="test_mode" class="text-sm font-medium text-gray-700">Test Mode</label>
                        </div>
                        
                        <div>
                            <button @click="testPaymentConnection()" 
                                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                                <i class="fas fa-plug mr-2"></i>Test Connection
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Email Settings -->
                <div x-show="activeTab === 'email'" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-6">Email Settings</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Host</label>
                            <input type="text" x-model="settings.email.smtp_host"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Port</label>
                                <input type="number" x-model="settings.email.smtp_port"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Encryption</label>
                                <select x-model="settings.email.smtp_encryption"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                    <option value="">None</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Username</label>
                            <input type="text" x-model="settings.email.smtp_username"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Password</label>
                            <input type="password" x-model="settings.email.smtp_password"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">From Email</label>
                            <input type="email" x-model="settings.email.from_email"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">From Name</label>
                            <input type="text" x-model="settings.email.from_name"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div>
                            <button @click="testEmailConnection()" 
                                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                                <i class="fas fa-envelope mr-2"></i>Send Test Email
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Delivery Settings -->
                <div x-show="activeTab === 'delivery'" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-6">Delivery Settings</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Fee (AUD)</label>
                            <input type="number" step="0.01" x-model="settings.delivery.fee"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Free Delivery Threshold (AUD)</label>
                            <input type="number" step="0.01" x-model="settings.delivery.free_threshold"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Zones</label>
                            <textarea x-model="settings.delivery.zones" rows="4" 
                                      placeholder="Enter delivery zones, one per line"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" x-model="settings.delivery.pickup_available" id="pickup_available"
                                   class="mr-2 rounded border-gray-300">
                            <label for="pickup_available" class="text-sm font-medium text-gray-700">Pickup Available</label>
                        </div>
                        
                        <div x-show="settings.delivery.pickup_available">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pickup Address</label>
                            <textarea x-model="settings.delivery.pickup_address" rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div x-show="activeTab === 'notifications'" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-6">Notification Settings</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Email Notifications</h4>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" x-model="settings.notifications.email.new_order" id="email_new_order"
                                           class="mr-2 rounded border-gray-300">
                                    <label for="email_new_order" class="text-sm text-gray-700">New Order</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" x-model="settings.notifications.email.order_status" id="email_order_status"
                                           class="mr-2 rounded border-gray-300">
                                    <label for="email_order_status" class="text-sm text-gray-700">Order Status Changes</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" x-model="settings.notifications.email.low_stock" id="email_low_stock"
                                           class="mr-2 rounded border-gray-300">
                                    <label for="email_low_stock" class="text-sm text-gray-700">Low Stock Alerts</label>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Admin Email</label>
                            <input type="email" x-model="settings.notifications.admin_email"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div x-show="activeTab === 'security'" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-6">Security Settings</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">JWT Token Expiry (minutes)</label>
                            <input type="number" x-model="settings.security.jwt_ttl"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" x-model="settings.security.force_https" id="force_https"
                                   class="mr-2 rounded border-gray-300">
                            <label for="force_https" class="text-sm font-medium text-gray-700">Force HTTPS</label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" x-model="settings.security.rate_limiting" id="rate_limiting"
                                   class="mr-2 rounded border-gray-300">
                            <label for="rate_limiting" class="text-sm font-medium text-gray-700">Enable Rate Limiting</label>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Password Requirements</h4>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" x-model="settings.security.password.require_uppercase" id="require_uppercase"
                                           class="mr-2 rounded border-gray-300">
                                    <label for="require_uppercase" class="text-sm text-gray-700">Require Uppercase</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" x-model="settings.security.password.require_numbers" id="require_numbers"
                                           class="mr-2 rounded border-gray-300">
                                    <label for="require_numbers" class="text-sm text-gray-700">Require Numbers</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" x-model="settings.security.password.require_symbols" id="require_symbols"
                                           class="mr-2 rounded border-gray-300">
                                    <label for="require_symbols" class="text-sm text-gray-700">Require Symbols</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function adminSettings() {
    return {
        activeTab: 'general',
        settings: {
            general: {
                site_name: 'BellGas',
                site_description: 'Premium gas delivery service',
                contact_email: 'admin@bellgas.com.au',
                contact_phone: '+61 2 1234 5678',
                maintenance_mode: false
            },
            payment: {
                stripe_publishable_key: '',
                stripe_secret_key: '',
                default_currency: 'AUD',
                test_mode: true
            },
            email: {
                smtp_host: 'smtp.gmail.com',
                smtp_port: 587,
                smtp_encryption: 'tls',
                smtp_username: '',
                smtp_password: '',
                from_email: 'noreply@bellgas.com.au',
                from_name: 'BellGas'
            },
            delivery: {
                fee: 9.95,
                free_threshold: 100.00,
                zones: 'Sydney CBD\nNorth Sydney\nManly\nBondi',
                pickup_available: true,
                pickup_address: '123 Gas Street, Sydney NSW 2000'
            },
            notifications: {
                email: {
                    new_order: true,
                    order_status: true,
                    low_stock: true
                },
                admin_email: 'admin@bellgas.com.au'
            },
            security: {
                jwt_ttl: 60,
                force_https: true,
                rate_limiting: true,
                password: {
                    require_uppercase: true,
                    require_numbers: true,
                    require_symbols: false
                }
            }
        },
        
        async init() {
            await this.loadSettings();
        },
        
        async loadSettings() {
            try {
                const response = await axios.get('/api/admin/settings');
                if (response.data.settings) {
                    this.settings = { ...this.settings, ...response.data.settings };
                }
            } catch (error) {
                console.error('Failed to load settings:', error);
                this.showNotification('Failed to load settings, using defaults', 'warning');
            }
        },
        
        async saveAllSettings() {
            try {
                await axios.put('/api/admin/settings', { settings: this.settings });
                this.showNotification('Settings saved successfully', 'success');
            } catch (error) {
                console.error('Failed to save settings:', error);
                this.showNotification('Failed to save settings', 'error');
            }
        },
        
        async testPaymentConnection() {
            try {
                const response = await axios.get('/api/test-stripe-connection');
                if (response.data.success) {
                    this.showNotification('Payment connection successful', 'success');
                } else {
                    this.showNotification('Payment connection failed: ' + response.data.message, 'error');
                }
            } catch (error) {
                this.showNotification('Payment connection test failed', 'error');
            }
        },
        
        async testEmailConnection() {
            try {
                await axios.post('/api/test/email-receipt', {
                    to: this.settings.email.from_email,
                    subject: 'Test Email from BellGas Admin',
                    message: 'This is a test email to verify your email configuration.'
                });
                this.showNotification('Test email sent successfully', 'success');
            } catch (error) {
                console.error('Failed to send test email:', error);
                this.showNotification('Failed to send test email', 'error');
            }
        }
    }
}
</script>
@endsection