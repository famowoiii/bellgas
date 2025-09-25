<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ReceiptMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailTestController extends Controller
{
    public function testEmail(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            // Sample receipt data untuk testing
            $sampleReceipt = [
                'receipt_info' => [
                    'receipt_number' => 'RCP-TEST-' . strtoupper(substr(uniqid(), -6)),
                    'order_number' => 'BG-TEST-' . strtoupper(substr(uniqid(), -6)),
                    'issued_at' => now()->toISOString(),
                    'status' => 'PAID',
                    'payment_status' => 'COMPLETED'
                ],
                'business_info' => [
                    'name' => 'BellGas',
                    'address' => 'Sydney, NSW, Australia',
                    'phone' => '+61 2 1234 5678',
                    'email' => 'support@bellgas.com.au',
                    'abn' => '12 345 678 901',
                    'website' => 'https://bellgas.com.au'
                ],
                'customer_info' => [
                    'name' => 'Test Customer',
                    'email' => $request->email,
                    'phone' => '0400 123 456'
                ],
                'delivery_address' => [
                    'name' => 'Test Delivery Address',
                    'street_address' => '123 Test Street',
                    'suburb' => 'Sydney',
                    'state' => 'NSW', 
                    'postcode' => '2000',
                    'country' => 'Australia',
                    'delivery_instructions' => 'Test delivery instructions',
                    'full_address' => '123 Test Street, Sydney NSW 2000, Australia'
                ],
                'order_details' => [
                    'order_date' => now()->subHours(2)->toISOString(),
                    'fulfillment_method' => 'DELIVERY',
                    'customer_notes' => 'This is a test email receipt',
                    'items' => [
                        [
                            'product_name' => 'LPG Full Tank',
                            'variant_name' => '9kg Cylinder',
                            'category' => 'FULL_TANK',
                            'quantity' => 1,
                            'unit_price' => '89.95',
                            'total_price' => '89.95',
                            'weight_kg' => 9.00
                        ],
                        [
                            'product_name' => 'LPG Refill',
                            'variant_name' => '4.5kg Cylinder',
                            'category' => 'REFILL',
                            'quantity' => 2,
                            'unit_price' => '32.50',
                            'total_price' => '65.00',
                            'weight_kg' => 4.5
                        ]
                    ],
                    'pricing' => [
                        'subtotal' => '154.95',
                        'shipping_cost' => '42.50',
                        'total' => '197.45',
                        'currency' => 'AUD'
                    ]
                ],
                'payment_details' => [
                    'payment_method' => 'pm_test_visa',
                    'amount_paid' => '197.45',
                    'currency' => 'AUD',
                    'payment_date' => now()->subHour()->toISOString(),
                    'payment_intent_id' => 'pi_test_' . uniqid(),
                    'charge_id' => 'ch_test_' . uniqid(),
                    'payment_status' => 'SUCCESS',
                    'processing_fee_note' => 'Payment processed securely by Stripe'
                ],
                'stripe_receipt_url' => 'https://pay.stripe.com/receipts/test_receipt_url',
                'timestamps' => [
                    'order_created' => now()->subHours(2)->toISOString(),
                    'payment_completed' => now()->subHour()->toISOString(),
                    'receipt_generated' => now()->toISOString()
                ]
            ];

            // Send test email
            Mail::to($request->email)->send(new ReceiptMail($sampleReceipt));

            return response()->json([
                'message' => 'Test receipt email sent successfully',
                'email' => $request->email,
                'receipt_number' => $sampleReceipt['receipt_info']['receipt_number'],
                'order_number' => $sampleReceipt['receipt_info']['order_number'],
                'sent_at' => now()->toISOString(),
                'mail_config' => [
                    'mailer' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send test email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}