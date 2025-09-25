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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('address_id')->constrained()->onDelete('restrict');
            $table->enum('status', ['UNPAID', 'PAID', 'PROCESSING', 'READY', 'COMPLETED', 'CANCELLED'])->default('UNPAID');
            $table->enum('fulfillment_method', ['PICKUP', 'DELIVERY']);
            $table->decimal('subtotal_aud', 10, 2);
            $table->decimal('shipping_cost_aud', 10, 2)->default(0);
            $table->decimal('total_aud', 10, 2);
            $table->string('stripe_payment_intent_id')->nullable();
            $table->text('customer_notes')->nullable();
            $table->timestamp('pickup_ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
