<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AdminAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        \Log::info('AdminAuthMiddleware V2: Starting authentication check');
        
        $user = null;
        
        // Method 1: Check Laravel Auth session first
        if (Auth::check()) {
            $user = Auth::user();
            \Log::info('AdminAuthMiddleware V2: Laravel Auth user found', ['user_id' => $user->id, 'role' => $user->role]);
        }
        
        // Method 2: Check session data (fallback for WebAuth)
        if (!$user && session('authenticated') && session('user_data')) {
            $userData = session('user_data');
            $user = \App\Models\User::find($userData['id']);
            if ($user) {
                \Log::info('AdminAuthMiddleware V2: Session user found', ['user_id' => $user->id, 'role' => $user->role]);
                // Login the user to Laravel Auth for consistency
                if (!Auth::check()) {
                    Auth::login($user);
                    \Log::info('AdminAuthMiddleware V2: User logged in to Laravel Auth');
                }
            } else {
                \Log::warning('AdminAuthMiddleware V2: Session user data exists but user not found in database', ['user_id' => $userData['id'] ?? null]);
            }
        }
        
        // Method 3: Try JWT auth as final fallback
        if (!$user) {
            try {
                // Try to get token from session first
                $token = session('jwt_token');
                if ($token) {
                    JWTAuth::setToken($token);
                    $user = JWTAuth::authenticate();
                    \Log::info('AdminAuthMiddleware V2: JWT user found from session token', ['user_id' => $user->id, 'role' => $user->role]);
                } else {
                    // Try bearer token as last resort
                    $bearerToken = $request->bearerToken();
                    if ($bearerToken) {
                        JWTAuth::setToken($bearerToken);
                        $user = JWTAuth::authenticate();
                        \Log::info('AdminAuthMiddleware V2: JWT user found from bearer token', ['user_id' => $user->id, 'role' => $user->role]);
                    } else {
                        \Log::info('AdminAuthMiddleware V2: No JWT token found in session or header');
                    }
                }
            } catch (JWTException $e) {
                \Log::info('AdminAuthMiddleware V2: JWT auth failed', ['error' => $e->getMessage()]);
            }
        }
        
        // Check if user is authenticated and has admin/merchant role
        if (!$user) {
            \Log::warning('AdminAuthMiddleware: No authenticated user found, redirecting to login');
            
            // Store intended URL for redirect after login
            if (!$request->expectsJson() && !$request->ajax()) {
                session(['url.intended' => $request->url()]);
            }
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect('/login')->with('info', 'Please login to access admin panel');
        }
        
        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            \Log::warning('AdminAuthMiddleware: User does not have admin role', ['user_id' => $user->id, 'role' => $user->role]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access denied'], 403);
            }
            return redirect('/')->with('error', 'Access denied');
        }
        
        // Set user in Laravel Auth for consistency
        if (!Auth::check()) {
            Auth::login($user);
        }
        
        \Log::info('AdminAuthMiddleware: Access granted', ['user_id' => $user->id, 'role' => $user->role]);
        
        return $next($request);
    }
}