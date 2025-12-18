<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsletterService
{
    protected ?string $provider;
    protected ?string $apiKey;
    protected ?string $listId;

    public function __construct()
    {
        $this->provider = Setting::get('newsletter_provider'); // mailchimp, convertkit
        $this->apiKey = Setting::get('newsletter_api_key');
        $this->listId = Setting::get('newsletter_list_id');
    }

    /**
     * Check if newsletter integration is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->provider) && !empty($this->apiKey);
    }

    /**
     * Subscribe an email to the newsletter.
     */
    public function subscribe(string $email, ?string $name = null, ?string $source = null, array $metadata = []): array
    {
        // Check for existing subscriber
        $existing = NewsletterSubscriber::where('email', $email)->first();

        if ($existing) {
            if ($existing->status === 'unsubscribed') {
                // Resubscribe
                $existing->update([
                    'status' => 'pending',
                    'unsubscribed_at' => null,
                    'source' => $source,
                ]);
                return ['success' => true, 'message' => 'Successfully resubscribed!', 'subscriber' => $existing];
            }
            return ['success' => false, 'message' => 'This email is already subscribed.'];
        }

        // Create local subscriber
        $subscriber = NewsletterSubscriber::create([
            'email' => $email,
            'name' => $name,
            'status' => 'pending',
            'source' => $source,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);

        // Sync to external provider if configured
        if ($this->isConfigured()) {
            $this->syncToProvider($subscriber);
        }

        return ['success' => true, 'message' => 'Please check your email to confirm your subscription.', 'subscriber' => $subscriber];
    }

    /**
     * Confirm a subscription.
     */
    public function confirm(string $token): array
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if (!$subscriber) {
            return ['success' => false, 'message' => 'Invalid confirmation link.'];
        }

        if ($subscriber->isConfirmed()) {
            return ['success' => true, 'message' => 'Your subscription is already confirmed.'];
        }

        $subscriber->confirm();

        // Update external provider status if configured
        if ($this->isConfigured()) {
            $this->updateProviderStatus($subscriber, 'subscribed');
        }

        return ['success' => true, 'message' => 'Thank you! Your subscription has been confirmed.'];
    }

    /**
     * Unsubscribe from the newsletter.
     */
    public function unsubscribe(string $token): array
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if (!$subscriber) {
            return ['success' => false, 'message' => 'Invalid unsubscribe link.'];
        }

        $subscriber->unsubscribe();

        // Update external provider if configured
        if ($this->isConfigured()) {
            $this->updateProviderStatus($subscriber, 'unsubscribed');
        }

        return ['success' => true, 'message' => 'You have been unsubscribed from our newsletter.'];
    }

    /**
     * Sync subscriber to external provider.
     */
    protected function syncToProvider(NewsletterSubscriber $subscriber): bool
    {
        try {
            return match ($this->provider) {
                'mailchimp' => $this->syncToMailchimp($subscriber),
                'convertkit' => $this->syncToConvertKit($subscriber),
                default => false,
            };
        } catch (\Exception $e) {
            Log::error('Newsletter sync failed', [
                'provider' => $this->provider,
                'email' => $subscriber->email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update subscriber status on external provider.
     */
    protected function updateProviderStatus(NewsletterSubscriber $subscriber, string $status): bool
    {
        try {
            return match ($this->provider) {
                'mailchimp' => $this->updateMailchimpStatus($subscriber, $status),
                'convertkit' => $this->updateConvertKitStatus($subscriber, $status),
                default => false,
            };
        } catch (\Exception $e) {
            Log::error('Newsletter status update failed', [
                'provider' => $this->provider,
                'email' => $subscriber->email,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sync to Mailchimp.
     */
    protected function syncToMailchimp(NewsletterSubscriber $subscriber): bool
    {
        if (empty($this->listId)) {
            return false;
        }

        // Extract datacenter from API key (e.g., us19)
        $dc = substr($this->apiKey, strpos($this->apiKey, '-') + 1);

        $response = Http::withBasicAuth('anystring', $this->apiKey)
            ->post("https://{$dc}.api.mailchimp.com/3.0/lists/{$this->listId}/members", [
                'email_address' => $subscriber->email,
                'status' => 'pending', // Double opt-in
                'merge_fields' => [
                    'FNAME' => $subscriber->name ?? '',
                ],
                'tags' => [$subscriber->source ?? 'website'],
            ]);

        return $response->successful();
    }

    /**
     * Update Mailchimp subscriber status.
     */
    protected function updateMailchimpStatus(NewsletterSubscriber $subscriber, string $status): bool
    {
        if (empty($this->listId)) {
            return false;
        }

        $dc = substr($this->apiKey, strpos($this->apiKey, '-') + 1);
        $subscriberHash = md5(strtolower($subscriber->email));

        $response = Http::withBasicAuth('anystring', $this->apiKey)
            ->patch("https://{$dc}.api.mailchimp.com/3.0/lists/{$this->listId}/members/{$subscriberHash}", [
                'status' => $status,
            ]);

        return $response->successful();
    }

    /**
     * Sync to ConvertKit.
     */
    protected function syncToConvertKit(NewsletterSubscriber $subscriber): bool
    {
        $formId = $this->listId; // In ConvertKit, we use form ID

        if (empty($formId)) {
            return false;
        }

        $response = Http::post("https://api.convertkit.com/v3/forms/{$formId}/subscribe", [
            'api_key' => $this->apiKey,
            'email' => $subscriber->email,
            'first_name' => $subscriber->name,
            'tags' => [$subscriber->source ?? 'website'],
        ]);

        return $response->successful();
    }

    /**
     * Update ConvertKit subscriber status.
     */
    protected function updateConvertKitStatus(NewsletterSubscriber $subscriber, string $status): bool
    {
        if ($status === 'unsubscribed') {
            $response = Http::put("https://api.convertkit.com/v3/unsubscribe", [
                'api_secret' => $this->apiKey,
                'email' => $subscriber->email,
            ]);
            return $response->successful();
        }

        return true;
    }

    /**
     * Get subscriber statistics.
     */
    public function getStats(): array
    {
        return [
            'total' => NewsletterSubscriber::count(),
            'confirmed' => NewsletterSubscriber::confirmed()->count(),
            'pending' => NewsletterSubscriber::pending()->count(),
            'unsubscribed' => NewsletterSubscriber::where('status', 'unsubscribed')->count(),
            'this_month' => NewsletterSubscriber::whereMonth('created_at', now()->month)->count(),
            'by_source' => NewsletterSubscriber::active()
                ->selectRaw('source, count(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray(),
        ];
    }
}
