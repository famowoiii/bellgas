@extends('layouts.app')

@section('title', 'Create Admin User - BellGas Admin')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Create Admin User</h1>
                        <p class="mt-1 text-sm text-gray-600">Create a new admin or merchant account</p>
                    </div>
                    <a href="/admin/dashboard" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Form -->
            <div class="p-6" x-data="createAdminForm()">
                <form @submit.prevent="submitForm()">
                    <!-- Name Fields -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input x-model="form.first_name"
                                   type="text"
                                   id="first_name"
                                   required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   :class="errors.first_name ? 'border-red-300' : 'border-gray-300'">
                            <p x-show="errors.first_name" class="mt-1 text-sm text-red-600" x-text="errors.first_name?.[0]"></p>
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input x-model="form.last_name"
                                   type="text"
                                   id="last_name"
                                   required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   :class="errors.last_name ? 'border-red-300' : 'border-gray-300'">
                            <p x-show="errors.last_name" class="mt-1 text-sm text-red-600" x-text="errors.last_name?.[0]"></p>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input x-model="form.email"
                               type="email"
                               id="email"
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               :class="errors.email ? 'border-red-300' : 'border-gray-300'">
                        <p x-show="errors.email" class="mt-1 text-sm text-red-600" x-text="errors.email?.[0]"></p>
                    </div>

                    <!-- Phone Number -->
                    <div class="mb-6">
                        <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input x-model="form.phone_number"
                               type="tel"
                               id="phone_number"
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               :class="errors.phone_number ? 'border-red-300' : 'border-gray-300'">
                        <p x-show="errors.phone_number" class="mt-1 text-sm text-red-600" x-text="errors.phone_number?.[0]"></p>
                    </div>

                    <!-- Role Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">User Role</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors hover:bg-gray-50"
                                   :class="form.role === 'ADMIN' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                <input x-model="form.role" type="radio" value="ADMIN" class="sr-only">
                                <div class="flex-1">
                                    <div class="flex items-center justify-center mb-2">
                                        <i class="fas fa-user-shield text-3xl text-red-600"></i>
                                    </div>
                                    <div class="text-center">
                                        <h3 class="font-medium text-gray-900">Administrator</h3>
                                        <p class="text-sm text-gray-500">Full system access and user management</p>
                                    </div>
                                </div>
                            </label>

                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors hover:bg-gray-50"
                                   :class="form.role === 'MERCHANT' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                <input x-model="form.role" type="radio" value="MERCHANT" class="sr-only">
                                <div class="flex-1">
                                    <div class="flex items-center justify-center mb-2">
                                        <i class="fas fa-store text-3xl text-green-600"></i>
                                    </div>
                                    <div class="text-center">
                                        <h3 class="font-medium text-gray-900">Merchant</h3>
                                        <p class="text-sm text-gray-500">Product and order management</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <p x-show="errors.role" class="mt-2 text-sm text-red-600" x-text="errors.role?.[0]"></p>
                    </div>

                    <!-- Password Fields -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <div class="mt-1 relative">
                                <input x-model="form.password"
                                       :type="showPassword ? 'text' : 'password'"
                                       id="password"
                                       required
                                       minlength="8"
                                       class="block w-full pr-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       :class="errors.password ? 'border-red-300' : 'border-gray-300'">
                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-gray-400"></i>
                                </button>
                            </div>
                            <p x-show="errors.password" class="mt-1 text-sm text-red-600" x-text="errors.password?.[0]"></p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <div class="mt-1 relative">
                                <input x-model="form.password_confirmation"
                                       :type="showPasswordConfirm ? 'text' : 'password'"
                                       id="password_confirmation"
                                       required
                                       class="block w-full pr-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       :class="errors.password_confirmation ? 'border-red-300' : 'border-gray-300'">
                                <button type="button"
                                        @click="showPasswordConfirm = !showPasswordConfirm"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i :class="showPasswordConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-gray-400"></i>
                                </button>
                            </div>
                            <p x-show="errors.password_confirmation" class="mt-1 text-sm text-red-600" x-text="errors.password_confirmation?.[0]"></p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3">
                        <a href="/admin/dashboard" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit"
                                :disabled="loading"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                            <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-user-plus'" class="mr-2"></i>
                            <span x-text="loading ? 'Creating...' : 'Create User'"></span>
                        </button>
                    </div>

                    <!-- Success Message -->
                    <div x-show="successMessage" class="mt-4 bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Success!</h3>
                                <p class="mt-1 text-sm text-green-700" x-text="successMessage"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div x-show="generalError" class="mt-4 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                <p class="mt-1 text-sm text-red-700" x-text="generalError"></p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function createAdminForm() {
    return {
        form: {
            first_name: '',
            last_name: '',
            email: '',
            phone_number: '',
            password: '',
            password_confirmation: '',
            role: 'ADMIN'
        },
        errors: {},
        generalError: '',
        successMessage: '',
        loading: false,
        showPassword: false,
        showPasswordConfirm: false,

        async submitForm() {
            this.loading = true;
            this.errors = {};
            this.generalError = '';
            this.successMessage = '';

            try {
                // Get JWT token from session
                const token = localStorage.getItem('access_token') || document.querySelector('meta[name="frontend-token"]')?.getAttribute('content');

                const response = await axios.post('/api/admin/users/admin', this.form, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                this.successMessage = `${this.form.role} user "${this.form.email}" created successfully!`;

                // Reset form
                this.form = {
                    first_name: '',
                    last_name: '',
                    email: '',
                    phone_number: '',
                    password: '',
                    password_confirmation: '',
                    role: 'ADMIN'
                };

                // Auto hide success message after 5 seconds
                setTimeout(() => {
                    this.successMessage = '';
                }, 5000);

            } catch (error) {
                console.error('Error creating admin:', error);

                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors || {};
                } else if (error.response?.status === 403) {
                    this.generalError = 'Access denied. Only administrators can create admin accounts.';
                } else {
                    this.generalError = error.response?.data?.message || 'Failed to create admin user. Please try again.';
                }
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection