<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find or create a test customer
        $customer = User::where('role', 'CUSTOMER')->first();
        
        if (!$customer) {
            $customer = User::create([
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'email' => 'customer@example.com',
                'phone_number' => '0412345678',
                'password' => bcrypt('password123'),
                'role' => 'CUSTOMER',
                'email_verified_at' => now(),
            ]);
        }

        // Create a test address if customer doesn't have one
        $address = Address::where('user_id', $customer->id)->first();
        
        if (!$address) {
            $address = Address::create([
                'user_id' => $customer->id,
                'type' => 'HOME',
                'name' => 'Test Home Address',
                'street_address' => '123 Test Street',
                'suburb' => 'Sydney',
                'state' => 'NSW',
                'postcode' => '2000',
                'country' => 'Australia',
                'delivery_instructions' => 'Leave at front door',
                'is_default' => true,
            ]);
        }

        // Get a product variant for order items
        $productVariant = ProductVariant::first();
        
        if (!$productVariant) {
            $this->command->error('No product variants found. Please run ProductSeeder first.');
            return;
        }

        // Create test orders with different statuses
        $orderStatuses = ['UNPAID', 'PAID', 'PROCESSING', 'READY_FOR_PICKUP', 'DELIVERED'];
        
        foreach ($orderStatuses as $index => $status) {
            $order = Order::create([
                'order_number' => 'BG-' . strtoupper(Str::random(8)),
                'user_id' => $customer->id,
                'address_id' => $status === 'UNPAID' ? null : $address->id,
                'status' => $status,
                'fulfillment_method' => $index % 2 === 0 ? 'DELIVERY' : 'PICKUP',
                'payment_method' => $status === 'UNPAID' ? null : 'CARD',
                'subtotal_aud' => 50.00 + ($index * 10),
                'shipping_cost_aud' => $status === 'UNPAID' ? 0 : ($index % 2 === 0 ? 10.00 : 0),
                'total_aud' => 50.00 + ($index * 10) + ($index % 2 === 0 ? 10.00 : 0),
                'customer_notes' => "Test order notes for status: {$status}",
                'stripe_payment_intent_id' => $status === 'UNPAID' ? null : 'pi_test_' . Str::random(24),
                'paid_at' => $status === 'UNPAID' ? null : now()->subDays($index),
                'created_at' => now()->subDays($index + 1),
                'updated_at' => now()->subDays($index),
            ]);

            // Create order items
            $itemCount = rand(1, 3);
            for ($i = 0; $i < $itemCount; $i++) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $productVariant->id,
                    'quantity' => rand(1, 3),
                    'unit_price_aud' => $productVariant->price_aud ?? 25.00,
                    'total_price_aud' => ($productVariant->price_aud ?? 25.00) * rand(1, 3),
                ]);
            }

            $this->command->info("Created test order: {$order->order_number} with status: {$status}");
        }
    }
}
