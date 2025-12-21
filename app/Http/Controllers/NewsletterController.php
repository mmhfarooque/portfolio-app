<?php

namespace App\Http\Controllers;

use App\Services\NewsletterService;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    protected NewsletterService $newsletterService;

    public function __construct(NewsletterService $newsletterService)
    {
        $this->newsletterService = $newsletterService;
    }

    /**
     * Subscribe to newsletter.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:50',
            'honeypot' => 'nullable|string|max:0', // Honeypot field - should be empty
        ]);

        // Check honeypot
        if (!empty($validated['honeypot'])) {
            // Bot detected - return fake success
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Thank you for subscribing!']);
            }
            return back()->with('success', 'Thank you for subscribing!');
        }

        $result = $this->newsletterService->subscribe(
            $validated['email'],
            $validated['name'] ?? null,
            $validated['source'] ?? 'website'
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Confirm subscription.
     */
    public function confirm(string $token): \Inertia\Response
    {
        $result = $this->newsletterService->confirm($token);

        return \Inertia\Inertia::render('Public/Newsletter/Confirmed', [
            'success' => $result['success'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Unsubscribe from newsletter.
     */
    public function unsubscribe(string $token): \Inertia\Response
    {
        $result = $this->newsletterService->unsubscribe($token);

        return \Inertia\Inertia::render('Public/Newsletter/Unsubscribed', [
            'success' => $result['success'],
            'message' => $result['message'],
        ]);
    }
}
