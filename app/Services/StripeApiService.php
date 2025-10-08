<?php

namespace App\Services;

use Exception;

class StripeApiService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.stripe.com/v1/';

    public function __construct()
    {
        $this->apiKey = config('services.stripe.secret');
    }

    public function createPaymentIntent(int $amount, string $currency = 'aud', array $metadata = []): array
    {
        try {
            $postData = [
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => $metadata,
                'automatic_payment_methods[enabled]' => 'true',
            ];

            $response = $this->makeRequest('payment_intents', 'POST', $postData);
            
            if (!$response) {
                throw new Exception('Failed to create payment intent - no response from Stripe');
            }

            return $response;
        } catch (Exception $e) {
            throw new Exception('Failed to create payment intent: ' . $e->getMessage());
        }
    }

    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        try {
            $response = $this->makeRequest("payment_intents/{$paymentIntentId}", 'GET');
            
            if (!$response) {
                throw new Exception('Failed to retrieve payment intent - no response from Stripe');
            }

            return $response;
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve payment intent: ' . $e->getMessage());
        }
    }

    public function testConnection(): array
    {
        try {
            // Simple balance request to test connection
            $response = $this->makeRequest('balance', 'GET');
            
            if (!$response) {
                throw new Exception('No response from Stripe API');
            }

            return [
                'success' => true,
                'message' => 'Stripe API connection successful',
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Stripe API connection failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    private function makeRequest(string $endpoint, string $method = 'GET', array $data = []): ?array
    {
        $url = $this->apiUrl . $endpoint;

        \Log::info('Stripe API Request', [
            'endpoint' => $endpoint,
            'method' => $method,
            'url' => $url,
            'has_api_key' => !empty($this->apiKey)
        ]);

        $ch = curl_init();

        // Basic cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15, // Reduced from 30 to 15
            CURLOPT_CONNECTTIMEOUT => 5, // Reduced from 10 to 5
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'BellGas-Laravel/1.0',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        // Set method and data
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $duration = microtime(true) - $startTime;

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        \Log::info('Stripe API Response', [
            'endpoint' => $endpoint,
            'http_code' => $httpCode,
            'duration_seconds' => round($duration, 2),
            'has_error' => !empty($error),
            'response_length' => strlen($response)
        ]);

        if ($error) {
            \Log::error('Stripe cURL error', [
                'endpoint' => $endpoint,
                'error' => $error,
                'duration_seconds' => round($duration, 2)
            ]);
            throw new Exception("cURL error: {$error}");
        }

        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? "HTTP {$httpCode} error";
            \Log::error('Stripe API error', [
                'endpoint' => $endpoint,
                'http_code' => $httpCode,
                'error_message' => $errorMessage,
                'response' => $errorData
            ]);
            throw new Exception("Stripe API error: {$errorMessage}");
        }

        return json_decode($response, true);
    }

    public function calculateAmountInCents(float $amountAud): int
    {
        return (int) round($amountAud * 100);
    }

    public function formatAmountFromCents(int $amountCents): float
    {
        return round($amountCents / 100, 2);
    }

    public function createPaymentMethod(array $data): array
    {
        try {
            $response = $this->makeRequest('payment_methods', 'POST', $data);
            
            if (!$response) {
                throw new Exception('Failed to create payment method - no response from Stripe');
            }

            return $response;
        } catch (Exception $e) {
            throw new Exception('Failed to create payment method: ' . $e->getMessage());
        }
    }

    public function confirmPaymentIntent(string $paymentIntentId, array $data): array
    {
        try {
            $response = $this->makeRequest("payment_intents/{$paymentIntentId}/confirm", 'POST', $data);
            
            if (!$response) {
                throw new Exception('Failed to confirm payment intent - no response from Stripe');
            }

            return $response;
        } catch (Exception $e) {
            throw new Exception('Failed to confirm payment intent: ' . $e->getMessage());
        }
    }

    public function retrieveCharge(string $chargeId): array
    {
        try {
            $response = $this->makeRequest("charges/{$chargeId}", 'GET');
            
            if (!$response) {
                throw new Exception('Failed to retrieve charge - no response from Stripe');
            }

            return $response;
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve charge: ' . $e->getMessage());
        }
    }
}