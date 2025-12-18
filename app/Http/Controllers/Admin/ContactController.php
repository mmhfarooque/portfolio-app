<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\LoggingService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    protected LoggingService $logger;

    public function __construct(LoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Display a listing of contacts.
     */
    public function index(Request $request)
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

        $contacts = $query->paginate(20);
        $unreadCount = Contact::unread()->count();

        return view('admin.contacts.index', compact('contacts', 'unreadCount'));
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact)
    {
        // Mark as read when viewed
        $contact->markAsRead();

        return view('admin.contacts.show', compact('contact'));
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

        $this->logger->logActivity('contact_status_updated', 'info', [
            'contact_id' => $contact->id,
            'new_status' => $validated['status'],
        ], $contact);

        return back()->with('success', 'Contact status updated.');
    }

    /**
     * Remove the specified contact.
     */
    public function destroy(Contact $contact)
    {
        $this->logger->logActivity('contact_deleted', 'info', [
            'contact_id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
        ], $contact);

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

        $this->logger->logActivity('contacts_bulk_deleted', 'info', [
            'count' => $count,
        ]);

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

        $this->logger->logActivity('contacts_archived', 'info', [
            'count' => $count,
            'older_than_days' => $days,
        ]);

        return back()->with('success', "{$count} contacts archived.");
    }
}
