<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add picked_up_at timestamp
            $table->timestamp('picked_up_at')->nullable()->after('pickup_ready_at');
        });

        // SQLite does not support MODIFY COLUMN / ENUM — skip ALTER for SQLite
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('orders', function (Blueprint $table) {
                // Update the enum to include PICKED_UP status for the complete pickup flow
                $table->enum('status', [
                    'PENDING',              // Awaiting payment
                    'PAID',                 // Payment confirmed
                    'PROCESSED',            // Order confirmed by admin
                    'WAITING_PICKUP',       // Ready for customer pickup (pickup orders only)
                    'PICKED_UP',            // Customer has picked up the order (pickup orders only)
                    'ON_DELIVERY',          // Out for delivery (delivery orders only)
                    'DONE',                 // Completed (final status for both pickup and delivery)
                    'CANCELLED'             // Cancelled
                ])->default('PENDING')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Remove picked_up_at column
            $table->dropColumn('picked_up_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('orders', function (Blueprint $table) {
                // Revert to previous enum values
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
    }
};
