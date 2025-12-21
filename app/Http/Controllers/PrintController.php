<?php

namespace App\Http\Controllers;

use App\Mail\PrintInquirySubmitted;
use App\Models\Contact;
use App\Models\Photo;
use App\Models\Setting;
use App\Services\PrintService;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class PrintController extends Controller
{
    public function __construct(
        protected PrintService $printService
    ) {}

    /**
     * Show print options for a photo.
     */
    public function show(Photo $photo): Response
    {
        // Only show for published photos
        if ($photo->status !== 'published') {
            abort(404);
        }

        $products = $this->printService->getProducts();
        $isApiConfigured = $this->printService->isConfigured();

        return Inertia::render('Public/Print/Options', [
            'photo' => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'thumbnail_path' => $photo->thumbnail_path,
                'display_path' => $photo->display_path,
            ],
            'products' => $products,
            'isApiConfigured' => $isApiConfigured,
        ]);
    }

    /**
     * Handle print inquiry submission.
     */
    public function inquiry(Request $request, Photo $photo)
    {
        // Rate limiting
        $key = 'print_inquiry:' . ($request->ip() ?? 'unknown');

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->with('error', "Too many inquiries. Please try again in {$seconds} seconds.");
        }

        RateLimiter::hit($key, 3600);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'product' => 'required|string|max:255',
            'message' => 'nullable|string|max:2000',
            'honeypot' => 'nullable|max:0', // Spam protection
        ]);

        // If honeypot is filled, silently reject
        if (!empty($validated['honeypot'])) {
            return back()->with('success', 'Thank you for your inquiry!');
        }

        // Store inquiry in contacts table
        $contact = Contact::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => 'Print Inquiry: ' . $photo->title . ' - ' . $validated['product'],
            'message' => "Photo: {$photo->title}\nProduct: {$validated['product']}\n\n" . ($validated['message'] ?? 'No additional message.'),
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        // Send email notification to admin
        $contactEmail = Setting::get('contact_email');
        if ($contactEmail) {
            try {
                Mail::to($contactEmail)->send(new PrintInquirySubmitted($contact, $photo));
            } catch (\Exception $e) {
                // Email failed but inquiry is saved - don't show error to user
                LoggingService::error('print.email_failed', 'Print inquiry email failed: ' . $e->getMessage());
            }
        }

        LoggingService::activity('print.inquiry', "Print inquiry received for photo: {$photo->id}");

        return back()->with('success', 'Thank you for your print inquiry! We will get back to you within 24-48 hours.');
    }

    /**
     * Get print products as JSON (for AJAX).
     */
    public function products()
    {
        $products = $this->printService->getProducts();

        return response()->json([
            'success' => true,
            'products' => $products,
            'is_api_configured' => $this->printService->isConfigured(),
        ]);
    }
}
