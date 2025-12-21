<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormSubmitted;
use App\Models\Contact;
use App\Models\Setting;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    protected LoggingService $logger;

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
        return Inertia::render('Public/Contact');
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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

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
}
