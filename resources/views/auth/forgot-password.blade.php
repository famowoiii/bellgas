@extends('layouts.app')

@section('title', 'Forgot Password - BellGas')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8" x-data="forgotPasswordForm()">
        <div>
            <div class="flex justify-center">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-fire text-4xl text-orange-500"></i>
                    <span class="text-2xl font-bold text-gray-800">BellGas</span>
                </div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Forgot your password?
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Enter your email address and we'll send you a link to reset your password.
            </p>
        </div>

        <!-- Success Message -->
        <div x-show="successMessage" class="bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700" x-text="successMessage"></p>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div x-show="errorMessage" class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700" x-text="errorMessage"></p>
                </div>
            </div>
        </div>

        <form class="mt-8 space-y-6" @submit.prevent="submitReset()" x-show="!successMessage">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                <div class="mt-1">
                    <input x-model="email"
                           id="email"
                           name="email"
                           type="email"
                           required
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Enter your email address">
                </div>
            </div>

            <div>
                <button type="submit"
                        :disabled="loading"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <i :class="loading ? 'fas fa-spinner fa-spin mr-2' : 'fas fa-envelope mr-2'"></i>
                    <span x-text="loading ? 'Sending...' : 'Send Reset Link'"></span>
                </button>
            </div>

            <div class="text-center">
                <a href="/login" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Login
                </a>
            </div>
        </form>

        <div x-show="successMessage" class="text-center">
            <a href="/login" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                <i class="fas fa-arrow-left mr-1"></i>Back to Login
            </a>
        </div>
    </div>
</div>

<script>
function forgotPasswordForm() {
    return {
        email: '',
        loading: false,
        successMessage: '',
        errorMessage: '',

        async submitReset() {
            this.loading = true;
            this.errorMessage = '';
            this.successMessage = '';

            try {
                const response = await axios.post('/api/auth/forgot-password', {
                    email: this.email
                });

                this.successMessage = response.data.message || 'Password reset link has been sent to your email.';
            } catch (error) {
                if (error.response?.status === 422) {
                    const errors = error.response.data.errors;
                    this.errorMessage = errors?.email?.[0] || 'Please enter a valid email address.';
                } else if (error.response?.status === 404) {
                    // For security, show success even if email not found
                    this.successMessage = 'If an account with that email exists, a reset link has been sent.';
                } else {
                    this.errorMessage = error.response?.data?.message || 'Failed to send reset link. Please try again.';
                }
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
