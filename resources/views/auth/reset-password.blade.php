@extends('layouts.app')

@section('title', 'Reset Password - BellGas')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8" x-data="resetPasswordForm()">
        <div>
            <div class="flex justify-center">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-fire text-4xl text-orange-500"></i>
                    <span class="text-2xl font-bold text-gray-800">BellGas</span>
                </div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Reset your password
            </h2>
        </div>

        <div x-show="successMessage" class="bg-green-50 border border-green-200 rounded-md p-4">
            <p class="text-sm text-green-700" x-text="successMessage"></p>
            <div class="mt-3">
                <a href="/login" class="text-sm font-medium text-blue-600 hover:text-blue-500">Back to Login</a>
            </div>
        </div>

        <div x-show="errorMessage" class="bg-red-50 border border-red-200 rounded-md p-4">
            <p class="text-sm text-red-700" x-text="errorMessage"></p>
        </div>

        <form class="mt-8 space-y-6" @submit.prevent="submitReset()" x-show="!successMessage">
            <input type="hidden" x-model="token">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                <input x-model="email" id="email" name="email" type="email" required
                       class="mt-1 appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                       placeholder="Enter your email">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                <input x-model="password" id="password" name="password" type="password" required
                       class="mt-1 appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                       placeholder="New password (min. 8 characters)">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                <input x-model="passwordConfirmation" id="password_confirmation" name="password_confirmation" type="password" required
                       class="mt-1 appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                       placeholder="Confirm new password">
            </div>
            <button type="submit" :disabled="loading"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition">
                <span x-text="loading ? 'Resetting...' : 'Reset Password'"></span>
            </button>
        </form>
    </div>
</div>

<script>
function resetPasswordForm() {
    return {
        token: '{{ $token }}',
        email: '{{ $email ?? "" }}',
        password: '',
        passwordConfirmation: '',
        loading: false,
        successMessage: '',
        errorMessage: '',
        async submitReset() {
            this.loading = true;
            this.errorMessage = '';
            try {
                const response = await axios.post('/api/auth/reset-password', {
                    token: this.token,
                    email: this.email,
                    password: this.password,
                    password_confirmation: this.passwordConfirmation
                });
                this.successMessage = response.data.message || 'Password reset successfully!';
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Failed to reset password.';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
