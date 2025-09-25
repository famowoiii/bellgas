<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    protected $table = 'payment_settings';

    protected $fillable = [
        'stripe_enabled',
        'stripe_public_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'stripe_test_mode',
        'cod_enabled',
        'cod_fee',
        'cod_instructions',
        'minimum_order_amount',
        'maximum_order_amount',
        'payment_timeout_minutes',
        'refund_enabled',
        'refund_time_limit_days',
        'refund_conditions',
        'auto_capture_payments',
        'save_payment_methods',
        'payment_description_template',
        'payment_support_email',
        'payment_support_phone'
    ];

    protected $casts = [
        'stripe_enabled' => 'boolean',
        'stripe_test_mode' => 'boolean',
        'cod_enabled' => 'boolean',
        'cod_fee' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'maximum_order_amount' => 'decimal:2',
        'payment_timeout_minutes' => 'integer',
        'refund_enabled' => 'boolean',
        'refund_time_limit_days' => 'integer',
        'auto_capture_payments' => 'boolean',
        'save_payment_methods' => 'boolean'
    ];

    /**
     * Get masked secret key for display
     */
    public function getMaskedSecretKeyAttribute(): string
    {
        return $this->stripe_secret_key ? '••••••••' . substr($this->stripe_secret_key, -4) : '';
    }

    /**
     * Get masked webhook secret for display
     */
    public function getMaskedWebhookSecretAttribute(): string
    {
        return $this->stripe_webhook_secret ? '••••••••' . substr($this->stripe_webhook_secret, -4) : '';
    }

    /**
     * Check if Stripe is properly configured
     */
    public function isStripeConfigured(): bool
    {
        return $this->stripe_enabled &&
               !empty($this->stripe_public_key) &&
               !empty($this->stripe_secret_key);
    }

    /**
     * Check if COD is available
     */
    public function isCodAvailable(): bool
    {
        return $this->cod_enabled;
    }

    /**
     * Get available payment methods
     */
    public function getAvailablePaymentMethods(): array
    {
        $methods = [];

        if ($this->isStripeConfigured()) {
            $methods['stripe'] = [
                'name' => 'Credit/Debit Card',
                'type' => 'stripe',
                'fee' => 0,
                'description' => 'Secure payment via Stripe'
            ];
        }

        if ($this->isCodAvailable()) {
            $methods['cod'] = [
                'name' => 'Cash on Delivery',
                'type' => 'cod',
                'fee' => $this->cod_fee,
                'description' => $this->cod_instructions ?: 'Pay with cash upon delivery'
            ];
        }

        return $methods;
    }

    /**
     * Validate order amount against settings
     */
    public function validateOrderAmount(float $amount): array
    {
        $validation = [
            'valid' => true,
            'errors' => []
        ];

        if ($amount < $this->minimum_order_amount) {
            $validation['valid'] = false;
            $validation['errors'][] = "Minimum order amount is $" . number_format($this->minimum_order_amount, 2);
        }

        if ($amount > $this->maximum_order_amount) {
            $validation['valid'] = false;
            $validation['errors'][] = "Maximum order amount is $" . number_format($this->maximum_order_amount, 2);
        }

        return $validation;
    }

    /**
     * Calculate total with payment method fee
     */
    public function calculateTotalWithFee(float $amount, string $paymentMethod): array
    {
        $fee = 0;
        $description = '';

        switch ($paymentMethod) {
            case 'cod':
                $fee = $this->cod_fee;
                $description = 'Cash on Delivery Fee';
                break;
            case 'stripe':
                $fee = 0; // Stripe fees are handled separately
                $description = 'Card Payment';
                break;
        }

        return [
            'subtotal' => $amount,
            'payment_fee' => $fee,
            'total' => $amount + $fee,
            'fee_description' => $description
        ];
    }

    /**
     * Check if refund is available for an order
     */
    public function isRefundAvailable(\DateTime $orderDate): bool
    {
        if (!$this->refund_enabled) {
            return false;
        }

        $daysSinceOrder = $orderDate->diff(new \DateTime())->days;
        return $daysSinceOrder <= $this->refund_time_limit_days;
    }

    /**
     * Get payment description for order
     */
    public function getPaymentDescription(string $orderNumber): string
    {
        $template = $this->payment_description_template ?: 'BellGas Order #{order_number}';
        return str_replace('{order_number}', $orderNumber, $template);
    }

    /**
     * Get Stripe configuration for frontend
     */
    public function getStripeConfig(): array
    {
        return [
            'publishable_key' => $this->stripe_public_key,
            'test_mode' => $this->stripe_test_mode,
            'enabled' => $this->stripe_enabled && $this->isStripeConfigured()
        ];
    }

    /**
     * Get COD configuration for frontend
     */
    public function getCodConfig(): array
    {
        return [
            'enabled' => $this->cod_enabled,
            'fee' => $this->cod_fee,
            'instructions' => $this->cod_instructions
        ];
    }

    /**
     * Get public payment settings (safe for frontend)
     */
    public function getPublicSettings(): array
    {
        return [
            'stripe' => $this->getStripeConfig(),
            'cod' => $this->getCodConfig(),
            'limits' => [
                'minimum_amount' => $this->minimum_order_amount,
                'maximum_amount' => $this->maximum_order_amount,
                'timeout_minutes' => $this->payment_timeout_minutes
            ],
            'refund_policy' => [
                'enabled' => $this->refund_enabled,
                'time_limit_days' => $this->refund_time_limit_days,
                'conditions' => $this->refund_conditions
            ],
            'available_methods' => $this->getAvailablePaymentMethods()
        ];
    }
}