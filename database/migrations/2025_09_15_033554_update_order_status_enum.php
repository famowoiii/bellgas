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
        // First update existing status values to match new enum
        DB::statement("UPDATE orders SET status = 'PENDING' WHERE status = 'UNPAID'");
        DB::statement("UPDATE orders SET status = 'PROCESSED' WHERE status = 'PROCESSING'");
        DB::statement("UPDATE orders SET status = 'DONE' WHERE status IN ('READY', 'COMPLETED', 'DELIVERED')");
        
        Schema::table('orders', function (Blueprint $table) {
            // Update the enum to include new status values
            $table->enum('status', [
                'PENDING',
                'PAID', 
                'PROCESSED',
                'DONE',
                'CANCELLED'
            ])->default('PENDING')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert to original enum values
            $table->enum('status', [
                'UNPAID', 
                'PAID', 
                'PROCESSING', 
                'READY', 
                'COMPLETED', 
                'CANCELLED'
            ])->default('UNPAID')->change();
        });
    }
};
