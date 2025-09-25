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
        Schema::create('delivery_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('delivery_enabled')->default(true);
            $table->decimal('delivery_fee', 8, 2)->default(10.00);
            $table->decimal('free_delivery_threshold', 8, 2)->default(100.00);
            $table->decimal('delivery_radius_km', 5, 1)->default(15.0);
            $table->integer('estimated_delivery_time_min')->default(60);

            // Delivery hours for each day
            $table->string('monday_hours', 50)->default('9:00-17:00');
            $table->string('tuesday_hours', 50)->default('9:00-17:00');
            $table->string('wednesday_hours', 50)->default('9:00-17:00');
            $table->string('thursday_hours', 50)->default('9:00-17:00');
            $table->string('friday_hours', 50)->default('9:00-17:00');
            $table->string('saturday_hours', 50)->default('9:00-17:00');
            $table->string('sunday_hours', 50)->default('closed');

            // Delivery zones as JSON
            $table->json('delivery_zones')->nullable();

            // Additional settings
            $table->text('special_instructions')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email')->nullable();
            $table->text('store_address')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_settings');
    }
};
