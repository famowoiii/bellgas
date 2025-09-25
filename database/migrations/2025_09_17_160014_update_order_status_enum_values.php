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
        // First update any existing WAITING_PICKUP records to WAITING_FOR_PICKUP
        DB::statement("UPDATE orders SET status = 'WAITING_FOR_PICKUP' WHERE status = 'WAITING_PICKUP'");

        // Then alter the enum to include the new value and remove the old one
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('PENDING', 'PAID', 'PROCESSED', 'WAITING_FOR_PICKUP', 'PICKED_UP', 'ON_DELIVERY', 'DONE', 'CANCELLED') NOT NULL DEFAULT 'PENDING'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any existing WAITING_FOR_PICKUP records back to WAITING_PICKUP
        DB::statement("UPDATE orders SET status = 'WAITING_PICKUP' WHERE status = 'WAITING_FOR_PICKUP'");

        // Then alter the enum back to the original values
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('PENDING', 'PAID', 'PROCESSED', 'WAITING_PICKUP', 'PICKED_UP', 'ON_DELIVERY', 'DONE', 'CANCELLED') NOT NULL DEFAULT 'PENDING'");
    }
};