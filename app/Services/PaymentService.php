<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Photo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected ?string $stripeSecretKey;
    protected ?string $stripePublicKey;
    protected string $stripeApiUrl = 'https://api.stripe.com/v1';

    public function __construct()
    {
        $this->stripeSecretKey = config('services.stripe.secret');
        $this->stripePublicKey = config('services.stripe.key');
    }

    /**
     * Check if Stripe is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->stripeSecretKey) && !empty($this->stripePublicKey);
    }

    /**
     * Get public key for frontend.
     */
    public function getPublicKey(): ?string
    {
        return $this->stripePublicKey;
    }

    /**
     * Create a Stripe Payment Intent.
     */
    public function createPaymentIntent(Order $order): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($this->stripeSecretKey, '')
                ->asForm()
                ->post("{$this->stripeApiUrl}/payment_intents", [
                    'amount' => (int) ($order->total * 100), // Convert to cents
                    'currency' => strtolower($order->currency),
                    'description' => "Order {$order->order_number} - {$order->product_name}",
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'photo_id' => $order->photo_id,
                        'product_type' => $order->product_type,
                    ],
                    'receipt_email' => $order->customer_email,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Update order with payment intent ID
                $order->update(['payment_intent_id' => $data['id']]);

                return [
                    'client_secret' => $data['client_secret'],
                    'payment_intent_id' => $data['id'],
                ];
            }

            Log::error('Stripe Payment Intent creation failed', [
                'order_id' => $order->id,
                'response' => $response->json(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Stripe Payment Intent error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Retrieve a Payment Intent status.
     */
    public function retrievePaymentIntent(string $paymentIntentId): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($this->stripeSecretKey, '')
                ->get("{$this->stripeApiUrl}/payment_intents/{$paymentIntentId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Stripe retrieve payment intent error', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Process webhook event from Stripe.
     */
    public function handleWebhook(string $payload, string $signature): bool
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!$webhookSecret) {
            Log::warning('Stripe webhook secret not configured');
            return false;
        }

        try {
            // Verify webhook signature
            $timestamp = null;
            $signatures = [];

            foreach (explode(',', $signature) as $part) {
                [$key, $value] = explode('=', $part);
                if ($key === 't') {
                    $timestamp = $value;
                } elseif ($key === 'v1') {
                    $signatures[] = $value;
                }
            }

            if (!$timestamp || empty($signatures)) {
                Log::warning('Invalid Stripe webhook signature format');
                return false;
            }

            $signedPayload = "{$timestamp}.{$payload}";
            $expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

            if (!in_array($expectedSignature, $signatures)) {
                Log::warning('Stripe webhook signature verification failed');
                return false;
            }

            // Verify timestamp (within 5 minutes)
            if (abs(time() - $timestamp) > 300) {
                Log::warning('Stripe webhook timestamp too old');
                return false;
            }

            $event = json_decode($payload, true);

            return $this->processWebhookEvent($event);

        } catch (\Exception $e) {
            Log::error('Stripe webhook processing error', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process a specific webhook event.
     */
    protected function processWebhookEvent(array $event): bool
    {
        $type = $event['type'] ?? null;
        $data = $event['data']['object'] ?? [];

        switch ($type) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSucceeded($data);

            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailed($data);

            default:
                Log::info('Unhandled Stripe webhook event', ['type' => $type]);
                return true;
        }
    }

    /**
     * Handle successful payment.
     */
    protected function handlePaymentSucceeded(array $data): bool
    {
        $paymentIntentId = $data['id'] ?? null;

        if (!$paymentIntentId) {
            return false;
        }

        $order = Order::where('payment_intent_id', $paymentIntentId)->first();

        if (!$order) {
            Log::warning('Order not found for payment intent', [
                'payment_intent_id' => $paymentIntentId,
            ]);
            return false;
        }

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'status' => 'confirmed',
        ]);

        // Generate license key for digital products
        if ($order->product_type === 'license' && !$order->license_key) {
            $order->update([
                'license_key' => Order::generateLicenseKey(),
                'license_expires_at' => now()->addYear(),
            ]);
        }

        LoggingService::activity('order.paid', "Order {$order->order_number} paid successfully");

        // TODO: Send confirmation email

        return true;
    }

    /**
     * Handle failed payment.
     */
    protected function handlePaymentFailed(array $data): bool
    {
        $paymentIntentId = $data['id'] ?? null;

        if (!$paymentIntentId) {
            return false;
        }

        $order = Order::where('payment_intent_id', $paymentIntentId)->first();

        if (!$order) {
            return false;
        }

        $order->update([
            'payment_status' => 'failed',
        ]);

        LoggingService::activity('order.failed', "Order {$order->order_number} payment failed");

        return true;
    }

    /**
     * Calculate shipping cost.
     */
    public function calculateShipping(string $productType, string $country = 'US'): float
    {
        // Basic shipping rates
        $rates = [
            'print' => [
                'US' => 9.99,
                'CA' => 14.99,
                'default' => 24.99,
            ],
            'canvas' => [
                'US' => 19.99,
                'CA' => 29.99,
                'default' => 49.99,
            ],
            'metal' => [
                'US' => 24.99,
                'CA' => 34.99,
                'default' => 59.99,
            ],
            'license' => [
                'default' => 0, // Digital, no shipping
            ],
        ];

        $typeRates = $rates[$productType] ?? $rates['print'];

        return $typeRates[$country] ?? $typeRates['default'];
    }

    /**
     * Calculate tax (simplified - should use proper tax service in production).
     */
    public function calculateTax(float $subtotal, string $state = null, string $country = 'US'): float
    {
        if ($country !== 'US') {
            return 0;
        }

        // Simplified US sales tax rates by state
        $taxRates = [
            'CA' => 0.0725,
            'NY' => 0.08,
            'TX' => 0.0625,
            'FL' => 0.06,
            'WA' => 0.065,
        ];

        $rate = $taxRates[$state] ?? 0;

        return round($subtotal * $rate, 2);
    }
}
