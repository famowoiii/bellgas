@extends('layouts.app')

@section('title', 'Login - BellGas')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8" x-data="loginForm()">
        <div>
            <div class="flex justify-center">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-fire text-4xl text-orange-500"></i>
                    <span class="text-2xl font-bold text-gray-800">BellGas</span>
                </div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Sign in to your account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Or
                <a href="/register" class="font-medium text-blue-600 hover:text-blue-500">
                    create a new account
                </a>
            </p>
        </div>

        <form class="mt-8 space-y-6" method="POST" action="/login" @submit="handleSubmit">
            @csrf
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email" class="sr-only">Email address</label>
                    <input x-model="form.email" 
                           id="email" 
                           name="email" 
                           type="email" 
                           required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                           :class="errors.email ? 'border-red-500' : 'border-gray-300'"
                           placeholder="Email address">
                    <p x-show="errors.email" class="mt-1 text-sm text-red-600" x-text="errors.email?.[0]"></p>
                </div>
                
                <div class="relative">
                    <label for="password" class="sr-only">Password</label>
                    <input x-model="form.password" 
                           id="password" 
                           name="password" 
                           :type="showPassword ? 'text' : 'password'" 
                           required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 pr-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                           :class="errors.password ? 'border-red-500' : 'border-gray-300'"
                           placeholder="Password">
                    <button type="button" 
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-gray-400 hover:text-gray-600"></i>
                    </button>
                    <p x-show="errors.password" class="mt-1 text-sm text-red-600" x-text="errors.password?.[0]"></p>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input x-model="form.remember_me" 
                           id="remember_me" 
                           name="remember_me" 
                           type="checkbox" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                        Remember me
                    </label>
                </div>

                <div class="text-sm">
                    <a href="/forgot-password" class="font-medium text-blue-600 hover:text-blue-500">
                        Forgot your password?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" 
                        :disabled="loading"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-lock'" class="text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    <span x-text="loading ? 'Signing in...' : 'Sign in'"></span>
                </button>
            </div>

            <!-- Social Login Options (Optional) -->
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-gray-50 text-gray-500">New to BellGas?</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="/register" 
                       class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        <i class="fas fa-user-plus mr-2 text-gray-400"></i>
                        Create New Account
                    </a>
                </div>
            </div>

            <!-- Laravel Validation Errors -->
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Login Failed</h3>
                            @foreach ($errors->all() as $error)
                                <p class="mt-1 text-sm text-red-700">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Error Display -->
            <div x-show="generalError" class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Login Failed</h3>
                        <p class="mt-1 text-sm text-red-700" x-text="generalError"></p>
                    </div>
                </div>
            </div>
        </form>

        <!-- Demo Credentials -->
        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-md">
            <h4 class="text-sm font-medium text-blue-800 mb-2">Demo Credentials</h4>
            <div class="grid grid-cols-1 gap-2 text-xs">
                <div>
                    <strong class="text-blue-700">Customer:</strong> 
                    <span class="ml-1 text-blue-600">stripetester@bellgas.com / password123</span>
                </div>
                <div>
                    <strong class="text-blue-700">Admin:</strong> 
                    <span class="ml-1 text-blue-600">admin@bellgas.com.au / password</span>
                </div>
            </div>
            <div class="mt-2">
                <button @click="fillDemoCredentials('customer')" 
                        class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded mr-2 hover:bg-blue-200 transition">
                    Fill Customer
                </button>
                <button @click="fillDemoCredentials('admin')" 
                        class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200 transition">
                    Fill Admin
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function loginForm() {
    return {
        form: {
            email: '',
            password: '',
            remember_me: false
        },
        errors: {},
        generalError: '',
        loading: false,
        showPassword: false,
        
        fillDemoCredentials(type) {
            if (type === 'customer') {
                this.form.email = 'stripetester@bellgas.com';
                this.form.password = 'password123';
            } else if (type === 'admin') {
                this.form.email = 'admin@bellgas.com.au';
                this.form.password = 'password';
            }
        },
        
        handleSubmit(event) {
            // Show loading state
            this.loading = true;
            this.errors = {};
            this.generalError = '';

            // Let the form submit normally - no prevention
            // The form will submit to /login POST route
        }
    }
}
</script>
@endsection