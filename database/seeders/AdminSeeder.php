<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main admin account
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'BellGas',
            'email' => 'adminbellgas@gmail.com',
            'password' => Hash::make('BellgasPassword123'),
            'phone_number' => '0412345678',
            'role' => 'ADMIN',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create a merchant account for testing
        User::create([
            'first_name' => 'Merchant',
            'last_name' => 'BellGas',
            'email' => 'merchant@bellgas.com.au',
            'password' => Hash::make('BellgasPassword123'),
            'phone_number' => '0412345679',
            'role' => 'MERCHANT',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Admin and Merchant users created successfully!');
        $this->command->info('Admin Email: adminbellgas@gmail.com');
        $this->command->info('Admin Password: BellgasPassword123');
        $this->command->info('Merchant Email: merchant@bellgas.com.au');
        $this->command->info('Merchant Password: BellgasPassword123');
    }
}