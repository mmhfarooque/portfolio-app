<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormSubmitted;
use App\Models\Contact;
use App\Models\Setting;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    protected LoggingService $logger;

    // Common spam patterns to detect
    private array $spamPatterns = [
        '/\b(viagra|cialis|casino|lottery|winner|prize|crypto|bitcoin|investment|forex)\b/i',
        '/\b(click here|act now|limited time|urgent|congratulations)\b/i',
        '/\b(make money|earn \$|free money|income opportunity)\b/i',
        '/\b(SEO|backlink|ranking|traffic|reseller|wholesale)\b/i',
        '/https?:\/\/[^\s]+/i', // URLs in message (usually spam)
    ];

    public function __construct(LoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Display the about page.
     */
    public function about(): Response
    {
        $content = Setting::get('about_content', '');

        return Inertia::render('Public/About', [
            'content' => $content,
        ]);
    }

    /**
     * Display the contact page.
     */
    public function contact(): Response
    {
        // Get Turnstile site key from database settings (preferred) or fall back to config
        $turnstileEnabled = Setting::get('turnstile_enabled', '0') === '1';
        $turnstileSiteKey = null;

        if ($turnstileEnabled) {
            $turnstileSiteKey = Setting::get('turnstile_site_key', '');
        }

        // Fall back to env config if not set in database
        if (empty($turnstileSiteKey)) {
            $turnstileSiteKey = config('services.turnstile.site_key');
        }

        return Inertia::render('Public/Contact', [
            'turnstileSiteKey' => $turnstileSiteKey,
        ]);
    }

    /**
     * Handle contact form submission.
     */
    public function sendContact(Request $request)
    {
        // Honeypot spam protection - if this field is filled, it's a bot
        if ($request->filled('website_url')) {
            // Silently reject but pretend success
            return redirect()
                ->route('contact')
                ->with('success', 'Thank you for your message! I will get back to you soon.');
        }

        // Rate limiting: 5 submissions per hour per IP
        $ip = $request->ip();
        $cacheKey = "contact_limit_{$ip}";
        $submissions = Cache::get($cacheKey, 0);

        if ($submissions >= 5) {
            return redirect()
                ->route('contact')
                ->with('error', 'Too many submissions. Please try again later.');
        }

        // Verify Cloudflare Turnstile if configured
        $turnstileEnabled = Setting::get('turnstile_enabled', '0') === '1';
        $turnstileSecret = $turnstileEnabled ? Setting::get('turnstile_secret_key', '') : null;

        // Fall back to env config
        if (empty($turnstileSecret)) {
            $turnstileSecret = config('services.turnstile.secret_key');
        }

        if ($turnstileSecret) {
            $turnstileResponse = $request->input('cf-turnstile-response');

            if (!$turnstileResponse) {
                return redirect()
                    ->route('contact')
                    ->with('error', 'Please complete the security verification.');
            }

            $verification = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $turnstileSecret,
                'response' => $turnstileResponse,
                'remoteip' => $ip,
            ]);

            if (!$verification->successful() || !$verification->json('success')) {
                $this->logger->logActivity('turnstile_failed', 'warning', [
                    'ip' => $ip,
                    'error' => $verification->json('error-codes', []),
                ]);

                return redirect()
                    ->route('contact')
                    ->with('error', 'Security verification failed. Please try again.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // Sanitize inputs - strip HTML tags and ensure valid UTF-8
        $validated = $this->sanitizeInputs($validated);

        // Check for spam patterns
        if ($this->isSpam($validated)) {
            $this->logger->logActivity('spam_blocked', 'info', [
                'ip' => $ip,
                'email' => $validated['email'],
                'subject' => $validated['subject'],
            ]);

            // Silently reject but pretend success (don't let spammers know they're blocked)
            return redirect()
                ->route('contact')
                ->with('success', 'Thank you for your message! I will get back to you soon.');
        }

        // Store contact in database
        $contact = Contact::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'ip_address' => $ip,
            'user_agent' => $request->userAgent(),
        ]);

        // Increment rate limit counter
        Cache::put($cacheKey, $submissions + 1, now()->addHour());

        // Log the contact submission
        $this->logger->logActivity('contact_submitted', 'info', [
            'contact_id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'subject' => $contact->subject,
        ], $contact);

        // Send email notification to admin
        $contactEmail = Setting::get('contact_email');
        if ($contactEmail) {
            try {
                Mail::to($contactEmail)->send(new ContactFormSubmitted($contact));
            } catch (\Exception $e) {
                // Email failed but contact is saved - don't show error to user
                $this->logger->logActivity('contact_email_failed', 'warning', [
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage(),
                ], $contact);
            }
        }

        return redirect()
            ->route('contact')
            ->with('success', 'Thank you for your message! I will get back to you soon.');
    }

    /**
     * Sanitize input values - strip HTML and ensure valid UTF-8.
     */
    private function sanitizeInputs(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Strip HTML tags
                $value = strip_tags($value);

                // Remove potentially harmful characters
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

                // Ensure valid UTF-8
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

                // Trim whitespace
                $value = trim($value);

                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Check if the submission appears to be spam.
     */
    private function isSpam(array $data): bool
    {
        $textToCheck = implode(' ', [
            $data['name'] ?? '',
            $data['subject'] ?? '',
            $data['message'] ?? '',
        ]);

        foreach ($this->spamPatterns as $pattern) {
            if (preg_match($pattern, $textToCheck)) {
                return true;
            }
        }

        // Check for suspicious email patterns (random characters before @)
        $email = $data['email'] ?? '';
        if (preg_match('/^[a-z]{5,}[0-9]{2,}@/i', $email)) {
            // Pattern like: zekisuquc419@gmail.com
            return true;
        }

        return false;
    }
}
