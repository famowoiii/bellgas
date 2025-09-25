<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'role' => $request->role ?? 'CUSTOMER',
                'is_active' => true,
            ]);

            // Set different token expiry based on role and generate token
            $ttlMinutes = $this->getTokenTtlByRole($user->role);
            config(['jwt.ttl' => $ttlMinutes]);
            $token = auth('api')->login($user);

            return response()->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone_number' => $user->phone_number,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $ttlMinutes * 60,
                'session_duration' => $this->getSessionDurationText($user->role),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        // First, try to find the user to get their role
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account is deactivated',
            ], 403);
        }

        // Set different token expiry based on role and generate token
        $ttlMinutes = $this->getTokenTtlByRole($user->role);
        config(['jwt.ttl' => $ttlMinutes]);
        $token = auth('api')->login($user);

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $ttlMinutes * 60,
            'session_duration' => $this->getSessionDurationText($user->role),
        ]);
    }

    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    public function refresh(): JsonResponse
    {
        try {
            // Get current user before refresh
            $currentUser = auth('api')->user();
            if (!$currentUser) {
                throw new \Exception('No authenticated user found');
            }

            // Set TTL based on user role before refresh
            $ttlMinutes = $this->getTokenTtlByRole($currentUser->role);
            config(['jwt.ttl' => $ttlMinutes]);
            $token = auth('api')->refresh();

            // Get user after token refresh
            auth('api')->setToken($token);
            $user = auth('api')->user();

            if (!$user) {
                throw new \Exception('User not found after token refresh');
            }

            return response()->json([
                'message' => 'Token refreshed successfully',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'role' => $user->role,
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $ttlMinutes * 60,
                'session_duration' => $this->getSessionDurationText($user->role),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token refresh failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            auth('api')->logout();
            return response()->json([
                'message' => 'Logged out successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
            ], 500);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'message' => 'Password reset link has been sent to your email',
                    'status' => 'success'
                ]);
            }

            return response()->json([
                'message' => 'Unable to send reset link',
                'error' => __($status)
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send password reset link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->password = Hash::make($password);
                    $user->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'message' => 'Password has been reset successfully',
                    'status' => 'success'
                ]);
            }

            return response()->json([
                'message' => 'Unable to reset password',
                'error' => __($status)
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get JWT token TTL in minutes based on user role
     */
    private function getTokenTtlByRole(string $role): int
    {
        return match ($role) {
            'CUSTOMER' => 120, // 2 hours for customers
            'ADMIN', 'MERCHANT' => 480, // 8 hours for admin and merchants
            default => 60, // 1 hour default
        };
    }

    /**
     * Get human-readable session duration text
     */
    private function getSessionDurationText(string $role): string
    {
        return match ($role) {
            'CUSTOMER' => '2 hours',
            'ADMIN', 'MERCHANT' => '8 hours',
            default => '1 hour',
        };
    }
}