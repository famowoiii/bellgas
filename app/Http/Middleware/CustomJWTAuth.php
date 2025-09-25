<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class CustomJWTAuth
{
    public function handle(Request $request, Closure $next)
    {
        \Log::info('CustomJWTAuth: Starting authentication check');

        $user = null;
        $token = null;

        // Method 1: Try JWT token from Authorization header
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            try {
                JWTAuth::setToken($bearerToken);
                $user = JWTAuth::authenticate();
                if ($user) {
                    \Log::info('CustomJWTAuth: JWT user found from bearer token', ['user_id' => $user->id]);
                    return $next($request);
                }
            } catch (JWTException $e) {
                \Log::info('CustomJWTAuth: JWT auth from bearer token failed', ['error' => $e->getMessage()]);
            }
        }

        // Method 2: Try JWT token from session
        $sessionToken = session('jwt_token') ?: session('frontend_token');
        if ($sessionToken) {
            try {
                JWTAuth::setToken($sessionToken);
                $user = JWTAuth::authenticate();
                if ($user && $user->is_active) {
                    \Log::info('CustomJWTAuth: JWT user found from session token', ['user_id' => $user->id]);
                    return $next($request);
                }
            } catch (JWTException $e) {
                \Log::info('CustomJWTAuth: JWT auth from session failed', ['error' => $e->getMessage()]);
                // Clear invalid token
                session()->forget(['jwt_token', 'frontend_token']);
            }
        }

        // Method 3: Try Laravel session auth
        if (auth()->check()) {
            $user = auth()->user();
            if ($user && $user->is_active) {
                \Log::info('CustomJWTAuth: Laravel auth user found', ['user_id' => $user->id]);
                return $next($request);
            }
        }

        // Method 4: Try session data for manual authentication
        if (session('authenticated') && session('user_data')) {
            $userData = session('user_data');
            if (isset($userData['id'])) {
                $user = \App\Models\User::find($userData['id']);
                if ($user && $user->is_active) {
                    \Log::info('CustomJWTAuth: Session user found and active', ['user_id' => $user->id]);
                    auth()->login($user);
                    return $next($request);
                }
            }
        }

        \Log::warning('CustomJWTAuth: No valid authentication found');

        // Return 401 for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Redirect to login for web requests
        return redirect('/login');
    }
}