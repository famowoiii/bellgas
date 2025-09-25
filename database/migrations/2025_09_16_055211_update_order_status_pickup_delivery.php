<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Update the enum to include new status values for pickup/delivery flow
            $table->enum('status', [
                'PENDING',              // Awaiting payment
                'PAID',                 // Payment confirmed
                'PROCESSED',            // Order confirmed by admin
                'WAITING_PICKUP',       // Ready for customer pickup (pickup orders only)
                'ON_DELIVERY',          // Out for delivery (delivery orders only)
                'DONE',                 // Completed (picked up or delivered)
                'CANCELLED'             // Cancelled
            ])->default('PENDING')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert to previous enum values
            $table->enum('status', [
                'PENDING',
                'PAID',
                'PROCESSED',
                'DONE',
                'CANCELLED'
            ])->default('PENDING')->change();
        });
    }
};