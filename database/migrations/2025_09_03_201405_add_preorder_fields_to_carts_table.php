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
        Schema::table('carts', function (Blueprint $table) {
            $table->boolean('is_preorder')->default(false)->after('price');
            $table->timestamp('reserved_until')->nullable()->after('is_preorder');
            $table->text('notes')->nullable()->after('reserved_until');
            $table->decimal('original_price', 10, 2)->nullable()->after('notes');
            
            // Add more flexible indexing
            $table->index(['user_id', 'product_variant_id', 'is_preorder']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn(['is_preorder', 'reserved_until', 'notes', 'original_price']);
            $table->dropIndex(['user_id', 'product_variant_id', 'is_preorder']);
        });
    }
};
