<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PageController extends Controller
{
    /**
     * Display the about page.
     */
    public function about()
    {
        $content = Setting::get('about_content', '');

        return view('pages.about', compact('content'));
    }

    /**
     * Display the contact page.
     */
    public function contact()
    {
        return view('pages.contact');
    }

    /**
     * Handle contact form submission.
     */
    public function sendContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // For now, just store in session (in production, you'd send an email)
        // You can configure email later with SMTP settings

        $contactEmail = Setting::get('contact_email');

        if ($contactEmail) {
            // Mail would be sent here in production
            // Mail::to($contactEmail)->send(new ContactFormMail($validated));
        }

        return redirect()
            ->route('contact')
            ->with('success', 'Thank you for your message! I will get back to you soon.');
    }
}
