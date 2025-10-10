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
        // Add indexes to addresses table
        Schema::table('addresses', function (Blueprint $table) {
            $table->index('user_id', 'idx_addresses_user_id');
        });

        // Add composite index to carts table
        Schema::table('carts', function (Blueprint $table) {
            $table->index(['user_id', 'product_variant_id'], 'idx_carts_user_variant');
        });

        // Add composite index to order_items table
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id', 'product_variant_id'], 'idx_order_items_order_variant');
        });

        // Add composite index to orders table (optimize common queries)
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'idx_orders_user_status_created');
        });

        // Add index to product_variants table
        Schema::table('product_variants', function (Blueprint $table) {
            $table->index('product_id', 'idx_product_variants_product_id');
        });

        // Add index to products table for active products
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'category'], 'idx_products_active_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from addresses table
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex('idx_addresses_user_id');
        });

        // Drop indexes from carts table
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_carts_user_variant');
        });

        // Drop indexes from order_items table
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_order_variant');
        });

        // Drop indexes from orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_user_status_created');
        });

        // Drop indexes from product_variants table
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('idx_product_variants_product_id');
        });

        // Drop indexes from products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_active_category');
        });
    }
};
