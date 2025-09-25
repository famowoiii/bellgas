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
            // Drop the foreign key constraint first
            $table->dropForeign(['address_id']);
            
            // Modify the column to be nullable
            $table->foreignId('address_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullable support
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['address_id']);
            
            // Modify the column back to not nullable
            $table->foreignId('address_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('restrict');
        });
    }
};
