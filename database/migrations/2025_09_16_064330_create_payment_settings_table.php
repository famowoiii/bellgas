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
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();

            // Stripe Settings
            $table->boolean('stripe_enabled')->default(true);
            $table->string('stripe_public_key')->nullable();
            $table->string('stripe_secret_key')->nullable();
            $table->string('stripe_webhook_secret')->nullable();
            $table->boolean('stripe_test_mode')->default(true);

            // Cash on Delivery Settings
            $table->boolean('cod_enabled')->default(true);
            $table->decimal('cod_fee', 8, 2)->default(0.00);
            $table->text('cod_instructions')->nullable();

            // Order Limits
            $table->decimal('minimum_order_amount', 8, 2)->default(20.00);
            $table->decimal('maximum_order_amount', 8, 2)->default(2000.00);
            $table->integer('payment_timeout_minutes')->default(15);

            // Refund Policy
            $table->boolean('refund_enabled')->default(true);
            $table->integer('refund_time_limit_days')->default(7);
            $table->text('refund_conditions')->nullable();

            // Additional Payment Options
            $table->boolean('auto_capture_payments')->default(true);
            $table->boolean('save_payment_methods')->default(false);
            $table->string('payment_description_template')->default('BellGas Order #{order_number}');

            // Support Contact
            $table->string('payment_support_email')->nullable();
            $table->string('payment_support_phone', 20)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
