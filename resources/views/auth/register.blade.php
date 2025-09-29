@extends('layouts.app')

@section('title', 'Register - BellGas')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8" x-data="registerForm()">
        <div>
            <div class="flex justify-center">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-fire text-4xl text-orange-500"></i>
                    <span class="text-2xl font-bold text-gray-800">BellGas</span>
                </div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Create your account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Or
                <a href="/login" class="font-medium text-blue-600 hover:text-blue-500">
                    sign in to your existing account
                </a>
            </p>
        </div>

        <form class="mt-8 space-y-6" @submit.prevent="submitRegister()">
            <!-- Name Fields -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="first_name" class="sr-only">First Name</label>
                    <input x-model="form.first_name" 
                           id="first_name" 
                           name="first_name" 
                           type="text" 
                           required 
                           class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                           :class="errors.first_name ? 'border-red-500' : 'border-gray-300'"
                           placeholder="First Name">
                    <p x-show="errors.first_name" class="mt-1 text-sm text-red-600" x-text="errors.first_name?.[0]"></p>
                </div>
                
                <div>
                    <label for="last_name" class="sr-only">Last Name</label>
                    <input x-model="form.last_name" 
                           id="last_name" 
                           name="last_name" 
                           type="text" 
                           required 
                           class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                           :class="errors.last_name ? 'border-red-500' : 'border-gray-300'"
                           placeholder="Last Name">
                    <p x-show="errors.last_name" class="mt-1 text-sm text-red-600" x-text="errors.last_name?.[0]"></p>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="sr-only">Email address</label>
                <input x-model="form.email" 
                       id="email" 
                       name="email" 
                       type="email" 
                       required 
                       class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                       :class="errors.email ? 'border-red-500' : 'border-gray-300'"
                       placeholder="Email address">
                <p x-show="errors.email" class="mt-1 text-sm text-red-600" x-text="errors.email?.[0]"></p>
            </div>

            <!-- Phone Number -->
            <div>
                <label for="phone_number" class="sr-only">Phone Number</label>
                <input x-model="form.phone_number" 
                       id="phone_number" 
                       name="phone_number" 
                       type="tel" 
                       required 
                       class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                       :class="errors.phone_number ? 'border-red-500' : 'border-gray-300'"
                       placeholder="Phone Number (e.g., 0412345678)">
                <p x-show="errors.phone_number" class="mt-1 text-sm text-red-600" x-text="errors.phone_number?.[0]"></p>
            </div>

            <!-- Hidden Role Field (Always CUSTOMER for public registration) -->
            <input type="hidden" x-model="form.role" value="CUSTOMER">

            <!-- Password Fields -->
            <div class="space-y-3">
                <div class="relative">
                    <label for="password" class="sr-only">Password</label>
                    <input x-model="form.password" 
                           id="password" 
                           name="password" 
                           :type="showPassword ? 'text' : 'password'" 
                           required 
                           class="relative block w-full px-3 py-2 pr-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                           :class="errors.password ? 'border-red-500' : 'border-gray-300'"
                           placeholder="Password (min. 8 characters)">
                    <button type="button" 
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-gray-400 hover:text-gray-600"></i>
                    </button>
                    <p x-show="errors.password" class="mt-1 text-sm text-red-600" x-text="errors.password?.[0]"></p>
                </div>

                <div class="relative">
                    <label for="password_confirmation" class="sr-only">Confirm Password</label>
                    <input x-model="form.password_confirmation" 
                           id="password_confirmation" 
                           name="password_confirmation" 
                           :type="showPasswordConfirm ? 'text' : 'password'" 
                           required 
                           class="relative block w-full px-3 py-2 pr-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                           :class="errors.password_confirmation ? 'border-red-500' : 'border-gray-300'"
                           placeholder="Confirm Password">
                    <button type="button" 
                            @click="showPasswordConfirm = !showPasswordConfirm"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <i :class="showPasswordConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-gray-400 hover:text-gray-600"></i>
                    </button>
                    <p x-show="errors.password_confirmation" class="mt-1 text-sm text-red-600" x-text="errors.password_confirmation?.[0]"></p>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input x-model="form.agree_terms" 
                           id="agree_terms" 
                           name="agree_terms" 
                           type="checkbox" 
                           required
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                </div>
                <div class="ml-3 text-sm">
                    <label for="agree_terms" class="text-gray-600">
                        I agree to the 
                        <a href="/terms" class="text-blue-600 hover:text-blue-500">Terms and Conditions</a>
                        and 
                        <a href="/privacy" class="text-blue-600 hover:text-blue-500">Privacy Policy</a>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit" 
                        :disabled="loading || !form.agree_terms"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-user-plus'" class="text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    <span x-text="loading ? 'Creating Account...' : 'Create Account'"></span>
                </button>
            </div>

            <!-- Error Display -->
            <div x-show="generalError" class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Registration Failed</h3>
                        <p class="mt-1 text-sm text-red-700" x-text="generalError"></p>
                    </div>
                </div>
            </div>
        </form>

        <!-- Additional Info -->
        <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-md">
            <h4 class="text-sm font-medium text-gray-800 mb-2">Why Create an Account?</h4>
            <ul class="text-xs text-gray-600 space-y-1">
                <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i> Track your orders</li>
                <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i> Save delivery addresses</li>
                <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i> Faster checkout process</li>
                <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i> Access to special offers</li>
            </ul>
        </div>
    </div>
</div>

<script>
function registerForm() {
    return {
        form: {
            first_name: '',
            last_name: '',
            email: '',
            phone_number: '',
            password: '',
            password_confirmation: '',
            role: 'CUSTOMER',
            agree_terms: false
        },
        errors: {},
        generalError: '',
        loading: false,
        showPassword: false,
        showPasswordConfirm: false,
        
        async submitRegister() {
            this.loading = true;
            this.errors = {};
            this.generalError = '';
            
            try {
                const response = await axios.post('/api/auth/register', this.form);
                
                // Store token and user data
                localStorage.setItem('access_token', response.data.access_token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.access_token}`;
                
                // Show success message and redirect
                alert('Account created successfully! Welcome to BellGas.');
                
                // Redirect based on user role
                const userRole = response.data.user.role;
                if (userRole === 'MERCHANT') {
                    window.location.href = '/admin/dashboard';
                } else {
                    window.location.href = '/dashboard';
                }
                
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors || {};
                } else {
                    this.generalError = error.response?.data?.message || 'Registration failed. Please try again.';
                }
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection