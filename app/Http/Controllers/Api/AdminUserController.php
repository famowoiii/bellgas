<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    /**
     * Create a new admin user (only accessible by existing admins)
     */
    public function createAdmin(Request $request)
    {
        // Ensure only admin can create other admins
        $currentUser = Auth::user();
        if (!$currentUser || $currentUser->role !== 'ADMIN') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can create admin accounts.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'required|string|max:20',
            'role' => 'required|in:ADMIN,MERCHANT'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $admin = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'role' => $request->role,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Admin user created successfully',
                'data' => [
                    'id' => $admin->id,
                    'first_name' => $admin->first_name,
                    'last_name' => $admin->last_name,
                    'email' => $admin->email,
                    'phone_number' => $admin->phone_number,
                    'role' => $admin->role,
                    'is_active' => $admin->is_active,
                    'created_at' => $admin->created_at,
                    'created_by' => $currentUser->email
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create admin user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all admin and merchant users
     */
    public function index()
    {
        $currentUser = Auth::user();
        if (!$currentUser || $currentUser->role !== 'ADMIN') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $adminUsers = User::whereIn('role', ['ADMIN', 'MERCHANT'])
            ->select('id', 'first_name', 'last_name', 'email', 'phone_number', 'role', 'is_active', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $adminUsers
        ]);
    }

    /**
     * Update admin user status
     */
    public function updateStatus(Request $request, User $user)
    {
        $currentUser = Auth::user();
        if (!$currentUser || $currentUser->role !== 'ADMIN') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Don't allow admin to deactivate themselves
        if ($user->id === $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update([
                'is_active' => $request->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'is_active' => $user->is_active
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status: ' . $e->getMessage()
            ], 500);
        }
    }
}