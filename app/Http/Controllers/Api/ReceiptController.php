<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ReceiptMail;
use App\Models\Order;
use App\Services\StripeService;
use App\Services\PdfReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ReceiptController extends Controller
{
    public function __construct(
        private StripeService $stripeService,
        private PdfReceiptService $pdfService
    ) {}

    public function getOrderReceipt(Request $request, Order $order): JsonResponse
    {
        try {
            // Pastikan user hanya bisa akses receipt ordernya sendiri
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized access to this receipt'
                ], 403);
            }

            // Load relationships yang diperlukan
            $order->load([
                'items.productVariant.product',
                'address',
                'user',
                'events' => function($query) {
                    $query->where('event_type', 'PAID')->orderBy('created_at', 'desc');
                }
            ]);

            // Ambil informasi pembayaran dari Stripe jika ada
            $paymentDetails = null;
            $stripeReceipt = null;

            if ($order->stripe_payment_intent_id && $order->status === 'PAID') {
                try {
                    $paymentIntent = $this->stripeService->retrievePaymentIntent($order->stripe_payment_intent_id);
                    $paymentDetails = $this->extractPaymentDetails($paymentIntent->toArray());
                    
                    // Get latest charge untuk receipt URL
                    if ($paymentIntent->latest_charge) {
                        $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
                        $charge = $stripe->charges->retrieve($paymentIntent->latest_charge);
                        $stripeReceipt = $charge->receipt_url ?? null;
                    }
                } catch (\Exception $e) {
                    // Log error tapi lanjutkan generate receipt internal
                    \Log::warning("Failed to fetch Stripe payment details for order {$order->order_number}: " . $e->getMessage());
                }
            }

            // Generate receipt data
            $receipt = [
                'receipt_info' => [
                    'receipt_number' => 'RCP-' . $order->order_number,
                    'order_number' => $order->order_number,
                    'issued_at' => now()->toISOString(),
                    'status' => $order->status,
                    'payment_status' => $order->status === 'PAID' ? 'COMPLETED' : 'PENDING'
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
                    'name' => $order->user->first_name . ' ' . $order->user->last_name,
                    'email' => $order->user->email,
                    'phone' => $order->user->phone_number,
                ],
                'delivery_address' => [
                    'name' => $order->address->name,
                    'street_address' => $order->address->street_address,
                    'suburb' => $order->address->suburb,
                    'state' => $order->address->state,
                    'postcode' => $order->address->postcode,
                    'country' => $order->address->country,
                    'delivery_instructions' => $order->address->delivery_instructions,
                    'full_address' => $order->address->full_address
                ],
                'order_details' => [
                    'order_date' => $order->created_at->toISOString(),
                    'fulfillment_method' => $order->fulfillment_method,
                    'customer_notes' => $order->customer_notes,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_name' => $item->productVariant->product->name,
                            'variant_name' => $item->productVariant->name,
                            'category' => $item->productVariant->product->category,
                            'quantity' => $item->quantity,
                            'unit_price' => number_format($item->unit_price_aud, 2),
                            'total_price' => number_format($item->total_price_aud, 2),
                            'weight_kg' => $item->productVariant->weight_kg
                        ];
                    }),
                    'pricing' => [
                        'subtotal' => number_format($order->subtotal_aud, 2),
                        'shipping_cost' => number_format($order->shipping_cost_aud, 2),
                        'total' => number_format($order->total_aud, 2),
                        'currency' => 'AUD'
                    ]
                ],
                'payment_details' => $paymentDetails,
                'stripe_receipt_url' => $stripeReceipt,
                'timestamps' => [
                    'order_created' => $order->created_at->toISOString(),
                    'payment_completed' => $order->events->first()?->created_at?->toISOString(),
                    'receipt_generated' => now()->toISOString()
                ]
            ];

            return response()->json([
                'message' => 'Receipt retrieved successfully',
                'receipt' => $receipt
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStripeReceiptUrl(Request $request, Order $order): JsonResponse
    {
        try {
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized access'
                ], 403);
            }

            if (!$order->stripe_payment_intent_id || $order->status !== 'PAID') {
                return response()->json([
                    'message' => 'No payment found for this order or payment not completed'
                ], 404);
            }

            $paymentIntent = $this->stripeService->retrievePaymentIntent($order->stripe_payment_intent_id);
            
            if ($paymentIntent->latest_charge) {
                $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
                $charge = $stripe->charges->retrieve($paymentIntent->latest_charge);
                $receiptUrl = $charge->receipt_url ?? null;

                if ($receiptUrl) {
                    return response()->json([
                        'message' => 'Stripe receipt URL retrieved successfully',
                        'receipt_url' => $receiptUrl,
                        'charge_id' => $charge->id,
                        'payment_intent_id' => $order->stripe_payment_intent_id
                    ]);
                }
            }

            return response()->json([
                'message' => 'Stripe receipt not available for this payment'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve Stripe receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function emailReceipt(Request $request, Order $order): JsonResponse
    {
        try {
            // Pastikan user hanya bisa email receipt ordernya sendiri
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized access to this receipt'
                ], 403);
            }

            // Generate receipt data
            $receiptResponse = $this->getOrderReceipt($request, $order);
            $receiptData = $receiptResponse->getData(true);

            if (!isset($receiptData['receipt'])) {
                return response()->json([
                    'message' => 'Failed to generate receipt data'
                ], 500);
            }

            // Send email
            Mail::to($order->user->email)->send(new ReceiptMail($receiptData['receipt']));

            return response()->json([
                'message' => 'Receipt sent successfully to ' . $order->user->email,
                'email' => $order->user->email,
                'order_number' => $order->order_number,
                'sent_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send receipt email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF receipt
     */
    public function downloadPdf(Order $order)
    {
        // Check authorization
        $user = auth()->user();
        if ($order->user_id !== $user->id && !in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'message' => 'Unauthorized access to this receipt'
            ], 403);
        }

        try {
            return $this->pdfService->downloadReceipt($order);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate PDF receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get PDF receipt URL for preview
     */
    public function getPdfUrl(Order $order): JsonResponse
    {
        // Check authorization
        if ($order->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'staff'])) {
            return response()->json([
                'message' => 'Unauthorized access to this receipt'
            ], 403);
        }

        try {
            $url = $this->pdfService->getReceiptUrl($order);

            return response()->json([
                'message' => 'PDF receipt URL generated successfully',
                'pdf_url' => $url,
                'order_number' => $order->order_number,
                'generated_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate PDF receipt URL',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function extractPaymentDetails(array $paymentIntent): ?array
    {
        if ($paymentIntent['status'] !== 'succeeded') {
            return null;
        }

        return [
            'payment_method' => $paymentIntent['payment_method'] ?? 'Unknown',
            'amount_paid' => number_format($paymentIntent['amount_received'] / 100, 2),
            'currency' => strtoupper($paymentIntent['currency']),
            'payment_date' => isset($paymentIntent['created']) ? 
                Carbon::createFromTimestamp($paymentIntent['created'])->toISOString() : null,
            'payment_intent_id' => $paymentIntent['id'],
            'charge_id' => $paymentIntent['latest_charge'] ?? null,
            'payment_status' => 'SUCCESS',
            'processing_fee_note' => 'Payment processed securely by Stripe'
        ];
    }
}