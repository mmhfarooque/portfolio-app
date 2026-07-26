<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContactReply;
use App\Models\Contact;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Display a listing of contacts.
     */
    public function index(Request $request): Response
    {
        $query = Contact::query()->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate(20)->through(fn($contact) => [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'subject' => $contact->subject,
            'status' => $contact->status,
            'created_at' => $contact->created_at->format('M j, Y g:i A'),
        ]);

        $unreadCount = Contact::unread()->count();

        return Inertia::render('Admin/Contacts/Index', [
            'contacts' => $contacts,
            'unreadCount' => $unreadCount,
            'filters' => [
                'status' => $request->status,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact): Response
    {
        // Mark as read when viewed
        $contact->markAsRead();

        return Inertia::render('Admin/Contacts/Show', [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'subject' => $contact->subject,
                'message' => $contact->message,
                'status' => $contact->status,
                'ip_address' => $contact->ip_address,
                'user_agent' => $contact->user_agent,
                'created_at' => $contact->created_at->format('M j, Y g:i A'),
                'replied_at' => $contact->replied_at?->format('M j, Y g:i A'),
                'reply_subject' => $contact->reply_subject,
                'reply_message' => $contact->reply_message,
            ],
        ]);
    }

    /**
     * Send an email reply to the contact.
     */
    public function reply(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:10000',
        ]);

        try {
            Mail::to($contact->email)->send(
                new ContactReply($contact, $validated['subject'], $validated['message'])
            );
        } catch (\Throwable $e) {
            LoggingService::error('contact_reply_failed', 'Failed to send contact reply', $e, $contact);

            return back()->with('error', 'Failed to send reply: ' . $e->getMessage());
        }

        $contact->update([
            'status' => 'replied',
            'replied_at' => now(),
            'reply_subject' => $validated['subject'],
            'reply_message' => $validated['message'],
        ]);

        LoggingService::activity('contact_replied', 'Reply sent to ' . $contact->email, $contact);

        return back()->with('success', 'Reply sent to ' . $contact->email);
    }

    /**
     * Update contact status.
     */
    public function updateStatus(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,read,replied,archived',
        ]);

        $contact->update(['status' => $validated['status']]);

        if ($validated['status'] === 'replied') {
            $contact->update(['replied_at' => now()]);
        }

        LoggingService::activity('contact_status_updated', 'Status changed to ' . $validated['status'], $contact);

        return back()->with('success', 'Contact status updated.');
    }

    /**
     * Remove the specified contact.
     */
    public function destroy(Contact $contact)
    {
        LoggingService::activity('contact_deleted', 'Deleted message from ' . $contact->name, $contact, [
            'email' => $contact->email,
        ]);

        $contact->delete();

        return redirect()
            ->route('admin.contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }

    /**
     * Bulk delete contacts.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id',
        ]);

        $count = Contact::whereIn('id', $validated['ids'])->delete();

        LoggingService::activity('contacts_bulk_deleted', "{$count} contacts deleted");

        return back()->with('success', "{$count} contacts deleted.");
    }

    /**
     * Archive old contacts.
     */
    public function archiveOld(Request $request)
    {
        $days = $request->input('days', 30);

        $count = Contact::where('created_at', '<', now()->subDays($days))
            ->whereNotIn('status', ['archived'])
            ->update(['status' => 'archived']);

        LoggingService::activity('contacts_archived', "{$count} contacts archived (older than {$days} days)");

        return back()->with('success', "{$count} contacts archived.");
    }
}
