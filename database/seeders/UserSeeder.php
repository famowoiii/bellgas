<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist first
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        // Create admin user (using MERCHANT role)
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'BellGas',
            'email' => 'admin@bellgas.com.au',
            'password' => Hash::make('password'),
            'phone_number' => '+61412345678',
            'role' => 'MERCHANT',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // Create staff user (using MERCHANT role)
        $staff = User::create([
            'first_name' => 'Staff',
            'last_name' => 'Member',
            'email' => 'staff@bellgas.com.au',
            'password' => Hash::make('password'),
            'phone_number' => '+61423456789',
            'role' => 'MERCHANT',
            'is_active' => true,
        ]);
        $staff->assignRole('staff');

        // Create test customers
        $customer1 = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'phone_number' => '+61434567890',
            'role' => 'CUSTOMER',
            'is_active' => true,
        ]);
        $customer1->assignRole('customer');

        $customer2 = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
            'phone_number' => '+61445678901',
            'role' => 'CUSTOMER',
            'is_active' => true,
        ]);
        $customer2->assignRole('customer');

        // Create more test customers for realistic data
        $customers = [
            ['Michael', 'Johnson', 'michael@example.com', '+61456789012'],
            ['Sarah', 'Williams', 'sarah@example.com', '+61467890123'],
            ['David', 'Brown', 'david@example.com', '+61478901234'],
            ['Lisa', 'Davis', 'lisa@example.com', '+61489012345'],
            ['Robert', 'Miller', 'robert@example.com', '+61490123456'],
            ['Jennifer', 'Wilson', 'jennifer@example.com', '+61401234567'],
        ];

        foreach ($customers as $customerData) {
            $customer = User::create([
                'first_name' => $customerData[0],
                'last_name' => $customerData[1],
                'email' => $customerData[2],
                'password' => Hash::make('password'),
                'phone_number' => $customerData[3],
                'role' => 'CUSTOMER',
                'is_active' => true,
            ]);
            $customer->assignRole('customer');
        }
    }
}
