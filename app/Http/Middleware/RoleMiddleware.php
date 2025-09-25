<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Check if user is authenticated (either via web or API)
        $user = null;
        
        // Try web authentication first
        if (Auth::check()) {
            $user = Auth::user();
        }
        // Then try API authentication
        elseif (Auth::guard('api')->check()) {
            $user = Auth::guard('api')->user();
        }
        // Check session for manual authentication
        elseif (session('authenticated') && session('user_data')) {
            $userData = session('user_data');
            $user = (object) $userData;
        }
        
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }
            return redirect()->route('login');
        }
        
        // Check if user has any of the required roles
        if (!in_array($user->role, $roles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. Insufficient permissions.',
                    'required_roles' => $roles,
                    'user_role' => $user->role
                ], 403);
            }
            
            // Redirect non-admin users to appropriate dashboard
            if ($user->role === 'CUSTOMER') {
                return redirect('/dashboard')->with('error', 'Access denied. Admin privileges required.');
            }
            
            return redirect('/')->with('error', 'Access denied. Insufficient permissions.');
        }

        return $next($request);
    }
}