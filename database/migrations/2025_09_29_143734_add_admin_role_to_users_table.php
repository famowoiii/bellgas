<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the existing enum to include ADMIN
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('CUSTOMER', 'MERCHANT', 'ADMIN') DEFAULT 'CUSTOMER'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum (this will fail if there are ADMIN users)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('CUSTOMER', 'MERCHANT') DEFAULT 'CUSTOMER'");
    }
};
