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
        // SQLite handles nullable differently — use raw SQL to recreate the table
        if (DB::getDriverName() === 'sqlite') {
            // In SQLite, we recreate the table to make address_id nullable
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement('
                CREATE TABLE IF NOT EXISTS "__temp__orders" (
                    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    "order_number" VARCHAR NOT NULL,
                    "user_id" INTEGER NOT NULL,
                    "address_id" INTEGER NULL,
                    "status" VARCHAR NOT NULL DEFAULT \'PENDING\',
                    "fulfillment_method" VARCHAR NOT NULL,
                    "subtotal_aud" NUMERIC(10,2) NOT NULL,
                    "shipping_cost_aud" NUMERIC(10,2) NOT NULL DEFAULT 0,
                    "total_aud" NUMERIC(10,2) NOT NULL,
                    "stripe_payment_intent_id" VARCHAR NULL,
                    "customer_notes" TEXT NULL,
                    "pickup_ready_at" DATETIME NULL,
                    "delivered_at" DATETIME NULL,
                    "completed_at" DATETIME NULL,
                    "created_at" DATETIME NULL,
                    "updated_at" DATETIME NULL,
                    FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE,
                    FOREIGN KEY ("address_id") REFERENCES "addresses" ("id") ON DELETE SET NULL
                )
            ');
            DB::statement('INSERT INTO "__temp__orders" SELECT "id", "order_number", "user_id", "address_id", "status", "fulfillment_method", "subtotal_aud", "shipping_cost_aud", "total_aud", "stripe_payment_intent_id", "customer_notes", "pickup_ready_at", "delivered_at", "completed_at", "created_at", "updated_at" FROM "orders"');
            DB::statement('DROP TABLE "orders"');
            DB::statement('ALTER TABLE "__temp__orders" RENAME TO "orders"');
            DB::statement('PRAGMA foreign_keys=ON');
            return;
        }

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
        if (DB::getDriverName() === 'sqlite') {
            return; // Skip for SQLite
        }

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
