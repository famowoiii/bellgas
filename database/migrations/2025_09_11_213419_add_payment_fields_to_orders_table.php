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
            // Check if columns exist before adding them
            if (!Schema::hasColumn('orders', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_intent_id', 'paid_at']);
        });
    }
};
