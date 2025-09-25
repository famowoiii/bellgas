<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        \Log::info('WebAuthController login called', ['email' => $request->email]);
        
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        \Log::info('Credentials validated', ['email' => $credentials['email']]);

        // Check if user exists and verify password manually for single verification
        \Log::info('Checking user existence and credentials', ['email' => $credentials['email']]);

        $user = \App\Models\User::where('email', $credentials['email'])->first();
        if (!$user) {
            \Log::warning('User not found in database', ['email' => $credentials['email']]);
            return back()->withErrors([
                'email' => 'User not found.',
            ])->withInput($request->only('email'));
        }

        // Check if user is active
        if (!$user->is_active) {
            \Log::warning('User account is deactivated', ['user_id' => $user->id]);
            return back()->withErrors([
                'email' => 'Account is deactivated.',
            ])->withInput($request->only('email'));
        }

        // Verify password once (single hash check)
        if (!\Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password)) {
            \Log::warning('Password verification failed', ['user_id' => $user->id]);
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->withInput($request->only('email'));
        }

        \Log::info('Password verified successfully, logging in user', ['user_id' => $user->id, 'role' => $user->role]);

        // Log in user directly (skip Auth::attempt to avoid second password check)
        Auth::login($user, $request->boolean('remember_me'));
        $request->session()->regenerate();

        \Log::info('Laravel Auth login successful');

        // Generate JWT token for this user directly without another password check
        try {
            \Log::info('Generating JWT token directly');

            // Set TTL based on user role
            $ttlMinutes = match ($user->role) {
                'CUSTOMER' => 120, // 2 hours
                'ADMIN', 'MERCHANT' => 480, // 8 hours
                default => 60 // 1 hour
            };
            config(['jwt.ttl' => $ttlMinutes]);

            // Generate JWT token directly for this user
            $token = auth('api')->login($user);

            \Log::info('JWT token generated successfully');

            // Store JWT token in session
            session(['jwt_token' => $token]);
            session(['authenticated' => true]);
            session(['user_data' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'is_active' => $user->is_active
            ]]);
            session(['frontend_token' => $token]);

        } catch (\Exception $e) {
            \Log::warning('Failed to generate JWT token, continuing with Laravel Auth only', ['error' => $e->getMessage()]);
            // Continue with Laravel Auth only
            session(['authenticated' => true]);
            session(['user_data' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role
            ]]);
        }

        // Redirect based on user role
        if ($user->role === 'ADMIN' || $user->role === 'MERCHANT') {
            \Log::info('Redirecting to admin dashboard');
            return redirect()->intended('/admin/dashboard');
        }

        \Log::info('Redirecting to regular dashboard');
        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        // Logout from Laravel session
        Auth::logout();
        
        // Clear JWT session data
        $request->session()->forget(['jwt_token', 'user_data', 'authenticated', 'frontend_token']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}