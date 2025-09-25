<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class PaymentSettingsController extends Controller
{
    /**
     * Get payment settings
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $settings = Cache::remember('payment_settings', 300, function () {
            return PaymentSetting::first() ?? new PaymentSetting();
        });

        return response()->json([
            'success' => true,
            'data' => [
                // Stripe Settings
                'stripe_enabled' => $settings->stripe_enabled ?? true,
                'stripe_public_key' => $settings->stripe_public_key ?? '',
                'stripe_secret_key' => $settings->stripe_secret_key ? '••••••••' : '',
                'stripe_webhook_secret' => $settings->stripe_webhook_secret ? '••••••••' : '',
                'stripe_test_mode' => $settings->stripe_test_mode ?? true,

                // Cash on Delivery
                'cod_enabled' => $settings->cod_enabled ?? true,
                'cod_fee' => $settings->cod_fee ?? 0.00,
                'cod_instructions' => $settings->cod_instructions ?? 'Please have exact amount ready for payment upon delivery.',

                // Payment Options
                'minimum_order_amount' => $settings->minimum_order_amount ?? 20.00,
                'maximum_order_amount' => $settings->maximum_order_amount ?? 2000.00,
                'payment_timeout_minutes' => $settings->payment_timeout_minutes ?? 15,

                // Refund Policy
                'refund_enabled' => $settings->refund_enabled ?? true,
                'refund_time_limit_days' => $settings->refund_time_limit_days ?? 7,
                'refund_conditions' => $settings->refund_conditions ?? 'Refunds are available within 7 days of delivery for unopened products.',

                // Additional Settings
                'auto_capture_payments' => $settings->auto_capture_payments ?? true,
                'save_payment_methods' => $settings->save_payment_methods ?? false,
                'payment_description_template' => $settings->payment_description_template ?? 'BellGas Order #{order_number}',

                // Contact & Support
                'payment_support_email' => $settings->payment_support_email ?? '',
                'payment_support_phone' => $settings->payment_support_phone ?? '',
            ]
        ]);
    }

    /**
     * Update payment settings
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            // Stripe Settings
            'stripe_enabled' => 'boolean',
            'stripe_public_key' => 'nullable|string|max:255',
            'stripe_secret_key' => 'nullable|string|max:255',
            'stripe_webhook_secret' => 'nullable|string|max:255',
            'stripe_test_mode' => 'boolean',

            // Cash on Delivery
            'cod_enabled' => 'boolean',
            'cod_fee' => 'numeric|min:0|max:999.99',
            'cod_instructions' => 'nullable|string|max:1000',

            // Payment Options
            'minimum_order_amount' => 'numeric|min:0|max:999.99',
            'maximum_order_amount' => 'numeric|min:1|max:99999.99',
            'payment_timeout_minutes' => 'integer|min:5|max:60',

            // Refund Policy
            'refund_enabled' => 'boolean',
            'refund_time_limit_days' => 'integer|min:1|max:365',
            'refund_conditions' => 'nullable|string|max:2000',

            // Additional Settings
            'auto_capture_payments' => 'boolean',
            'save_payment_methods' => 'boolean',
            'payment_description_template' => 'nullable|string|max:255',

            // Contact & Support
            'payment_support_email' => 'nullable|email|max:255',
            'payment_support_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $settings = PaymentSetting::first();
            if (!$settings) {
                $settings = new PaymentSetting();
            }

            // Update Stripe settings
            $settings->stripe_enabled = $request->get('stripe_enabled', $settings->stripe_enabled);
            $settings->stripe_test_mode = $request->get('stripe_test_mode', $settings->stripe_test_mode);

            if ($request->filled('stripe_public_key')) {
                $settings->stripe_public_key = $request->stripe_public_key;
            }
            if ($request->filled('stripe_secret_key')) {
                $settings->stripe_secret_key = $request->stripe_secret_key;
            }
            if ($request->filled('stripe_webhook_secret')) {
                $settings->stripe_webhook_secret = $request->stripe_webhook_secret;
            }

            // Update COD settings
            $settings->cod_enabled = $request->get('cod_enabled', $settings->cod_enabled);
            $settings->cod_fee = $request->get('cod_fee', $settings->cod_fee);
            $settings->cod_instructions = $request->get('cod_instructions', $settings->cod_instructions);

            // Update payment options
            $settings->minimum_order_amount = $request->get('minimum_order_amount', $settings->minimum_order_amount);
            $settings->maximum_order_amount = $request->get('maximum_order_amount', $settings->maximum_order_amount);
            $settings->payment_timeout_minutes = $request->get('payment_timeout_minutes', $settings->payment_timeout_minutes);

            // Update refund policy
            $settings->refund_enabled = $request->get('refund_enabled', $settings->refund_enabled);
            $settings->refund_time_limit_days = $request->get('refund_time_limit_days', $settings->refund_time_limit_days);
            $settings->refund_conditions = $request->get('refund_conditions', $settings->refund_conditions);

            // Update additional settings
            $settings->auto_capture_payments = $request->get('auto_capture_payments', $settings->auto_capture_payments);
            $settings->save_payment_methods = $request->get('save_payment_methods', $settings->save_payment_methods);
            $settings->payment_description_template = $request->get('payment_description_template', $settings->payment_description_template);

            // Update contact & support
            $settings->payment_support_email = $request->get('payment_support_email', $settings->payment_support_email);
            $settings->payment_support_phone = $request->get('payment_support_phone', $settings->payment_support_phone);

            $settings->save();

            // Clear cache
            Cache::forget('payment_settings');

            return response()->json([
                'success' => true,
                'message' => 'Payment settings updated successfully',
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public payment information (for checkout)
     */
    public function getPublicInfo(): JsonResponse
    {
        $settings = Cache::remember('payment_settings', 300, function () {
            return PaymentSetting::first();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'stripe_enabled' => $settings->stripe_enabled ?? true,
                'stripe_public_key' => $settings->stripe_public_key ?? '',
                'stripe_test_mode' => $settings->stripe_test_mode ?? true,
                'cod_enabled' => $settings->cod_enabled ?? true,
                'cod_fee' => $settings->cod_fee ?? 0.00,
                'cod_instructions' => $settings->cod_instructions ?? '',
                'minimum_order_amount' => $settings->minimum_order_amount ?? 20.00,
                'maximum_order_amount' => $settings->maximum_order_amount ?? 2000.00,
                'payment_timeout_minutes' => $settings->payment_timeout_minutes ?? 15,
                'payment_methods' => [
                    'stripe' => $settings->stripe_enabled ?? true,
                    'cod' => $settings->cod_enabled ?? true,
                ],
                'refund_policy' => [
                    'enabled' => $settings->refund_enabled ?? true,
                    'time_limit_days' => $settings->refund_time_limit_days ?? 7,
                    'conditions' => $settings->refund_conditions ?? ''
                ]
            ]
        ]);
    }

    /**
     * Test Stripe connection
     */
    public function testStripeConnection(): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $settings = PaymentSetting::first();

        if (!$settings || !$settings->stripe_enabled || !$settings->stripe_secret_key) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe is not configured',
                'data' => [
                    'stripe_enabled' => $settings->stripe_enabled ?? false,
                    'has_secret_key' => !empty($settings->stripe_secret_key),
                    'test_mode' => $settings->stripe_test_mode ?? true
                ]
            ], 400);
        }

        try {
            // Use existing StripeApiService to test connection
            $stripeApi = new \App\Services\StripeApiService();
            $result = $stripeApi->testConnection();

            return response()->json([
                'success' => true,
                'message' => 'Stripe connection test completed',
                'data' => [
                    'connection_successful' => $result['success'],
                    'test_mode' => $settings->stripe_test_mode,
                    'details' => $result
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe connection test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate order amount against payment settings
     */
    public function validateOrderAmount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $amount = $request->amount;
        $settings = Cache::remember('payment_settings', 300, function () {
            return PaymentSetting::first();
        });

        $minAmount = $settings->minimum_order_amount ?? 20.00;
        $maxAmount = $settings->maximum_order_amount ?? 2000.00;

        $validation = [
            'valid' => true,
            'amount' => $amount,
            'minimum_amount' => $minAmount,
            'maximum_amount' => $maxAmount,
            'errors' => []
        ];

        if ($amount < $minAmount) {
            $validation['valid'] = false;
            $validation['errors'][] = "Minimum order amount is $" . number_format($minAmount, 2);
        }

        if ($amount > $maxAmount) {
            $validation['valid'] = false;
            $validation['errors'][] = "Maximum order amount is $" . number_format($maxAmount, 2);
        }

        return response()->json([
            'success' => true,
            'data' => $validation
        ]);
    }

    /**
     * Get payment fees for different methods
     */
    public function getPaymentFees(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $amount = $request->amount;
        $settings = Cache::remember('payment_settings', 300, function () {
            return PaymentSetting::first();
        });

        $fees = [
            'stripe' => [
                'enabled' => $settings->stripe_enabled ?? true,
                'fee' => 0, // Stripe fees are percentage-based and handled by Stripe
                'description' => 'Secure card payment via Stripe'
            ],
            'cod' => [
                'enabled' => $settings->cod_enabled ?? true,
                'fee' => $settings->cod_fee ?? 0.00,
                'description' => 'Cash payment upon delivery'
            ]
        ];

        $totalWithFees = [];
        foreach ($fees as $method => $info) {
            if ($info['enabled']) {
                $totalWithFees[$method] = [
                    'subtotal' => $amount,
                    'payment_fee' => $info['fee'],
                    'total' => $amount + $info['fee'],
                    'description' => $info['description']
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_amount' => $amount,
                'payment_methods' => $totalWithFees,
                'recommended_method' => $settings->cod_fee > 0 ? 'stripe' : 'cod'
            ]
        ]);
    }
}