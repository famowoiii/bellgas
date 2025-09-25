<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class WebAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        \Log::info('WebAuthMiddleware: Starting authentication check');

        $user = null;

        // Method 1: Check Laravel Auth session first
        if (Auth::check()) {
            $user = Auth::user();
            \Log::info('WebAuthMiddleware: Laravel Auth user found', ['user_id' => $user->id]);
            return $next($request);
        }

        // Method 2: Check session data with user validation
        if (session('authenticated') && session('user_data')) {
            $userData = session('user_data');
            if (isset($userData['id'])) {
                $user = \App\Models\User::find($userData['id']);
                if ($user && $user->is_active) {
                    \Log::info('WebAuthMiddleware: Session user found and active', ['user_id' => $user->id]);
                    // Login the user to Laravel Auth for consistency
                    Auth::login($user);
                    return $next($request);
                } else {
                    \Log::warning('WebAuthMiddleware: Session user not found or inactive, clearing session');
                    session()->forget(['authenticated', 'user_data', 'jwt_token', 'frontend_token']);
                }
            }
        }

        // Method 3: Try JWT token from session
        if (session('jwt_token')) {
            try {
                $token = session('jwt_token');
                JWTAuth::setToken($token);
                $user = JWTAuth::authenticate();
                if ($user && $user->is_active) {
                    \Log::info('WebAuthMiddleware: JWT user found from session token', ['user_id' => $user->id]);
                    Auth::login($user);
                    return $next($request);
                }
            } catch (JWTException $e) {
                \Log::info('WebAuthMiddleware: JWT auth from session failed', ['error' => $e->getMessage()]);
                // Clear invalid token
                session()->forget(['jwt_token', 'frontend_token']);
            }
        }

        // Method 4: Try JWT from Authorization header (for AJAX requests)
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            try {
                JWTAuth::setToken($bearerToken);
                $user = JWTAuth::authenticate();
                if ($user && $user->is_active) {
                    \Log::info('WebAuthMiddleware: JWT user found from bearer token', ['user_id' => $user->id]);
                    Auth::login($user);
                    return $next($request);
                }
            } catch (JWTException $e) {
                \Log::info('WebAuthMiddleware: JWT auth from bearer token failed', ['error' => $e->getMessage()]);
            }
        }

        \Log::warning('WebAuthMiddleware: No valid authentication found, redirecting to login');

        // Store intended URL for redirect after login
        if (!$request->expectsJson() && !$request->ajax()) {
            session(['url.intended' => $request->url()]);
        }

        // If AJAX request, return JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // If not authenticated, redirect to login
        return redirect('/login')->with('info', 'Please login to continue');
    }
}