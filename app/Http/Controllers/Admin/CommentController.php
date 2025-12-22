<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedEmail;
use App\Models\PhotoComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    /**
     * Display a listing of comments.
     */
    public function index(Request $request): Response
    {
        $query = PhotoComment::with(['photo:id,title,slug,thumbnail_path', 'parent:id,guest_name,user_id'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to pending
            $query->where('status', PhotoComment::STATUS_PENDING);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('guest_name', 'like', "%{$search}%")
                    ->orWhere('guest_email', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $comments = $query->paginate(20)->through(fn ($comment) => [
            'id' => $comment->id,
            'author_name' => $comment->author_name,
            'author_email' => $comment->author_email,
            'content' => Str::limit($comment->content, 150),
            'full_content' => $comment->content,
            'status' => $comment->status,
            'status_color' => $comment->status_color,
            'is_reply' => $comment->parent_id !== null,
            'photo' => $comment->photo ? [
                'id' => $comment->photo->id,
                'title' => $comment->photo->title,
                'slug' => $comment->photo->slug,
                'thumbnail_path' => $comment->photo->thumbnail_path,
            ] : null,
            'ip_address' => $comment->ip_address,
            'created_at' => $comment->created_at->format('M j, Y g:i A'),
        ]);

        $pendingCount = PhotoComment::pending()->count();

        return Inertia::render('Admin/Comments/Index', [
            'comments' => $comments,
            'pendingCount' => $pendingCount,
            'filters' => [
                'status' => $request->status ?? 'pending',
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Approve a comment.
     */
    public function approve(PhotoComment $comment)
    {
        $comment->approve(Auth::id());

        return back()->with('success', 'Comment approved.');
    }

    /**
     * Reject a comment.
     */
    public function reject(PhotoComment $comment)
    {
        $comment->reject();

        return back()->with('success', 'Comment rejected.');
    }

    /**
     * Mark comment as spam.
     */
    public function spam(PhotoComment $comment)
    {
        $comment->markAsSpam();

        return back()->with('success', 'Comment marked as spam.');
    }

    /**
     * Bulk approve comments.
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:photo_comments,id',
        ]);

        $comments = PhotoComment::whereIn('id', $validated['ids'])->get();

        foreach ($comments as $comment) {
            $comment->approve(Auth::id());
        }

        return back()->with('success', count($validated['ids']).' comments approved.');
    }

    /**
     * Delete a comment.
     */
    public function destroy(PhotoComment $comment)
    {
        $wasApproved = $comment->status === PhotoComment::STATUS_APPROVED;

        $comment->delete();

        if ($wasApproved && $comment->photo) {
            $comment->photo->decrementApprovedCommentsCount();
        }

        return back()->with('success', 'Comment deleted.');
    }

    /**
     * Reply to a comment as admin.
     */
    public function reply(Request $request, PhotoComment $comment)
    {
        $validated = $request->validate([
            'content' => 'required|string|min:3|max:2000',
        ]);

        $reply = PhotoComment::create([
            'photo_id' => $comment->photo_id,
            'parent_id' => $comment->parent_id ?? $comment->id,
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'status' => PhotoComment::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]);

        $reply->photo->incrementApprovedCommentsCount();

        return back()->with('success', 'Reply posted.');
    }

    /**
     * Block email address from commenting.
     */
    public function blockEmail(Request $request, PhotoComment $comment)
    {
        $email = $comment->guest_email;

        if (!$email) {
            return back()->with('error', 'No email address to block.');
        }

        BlockedEmail::blockEmail($email, 'Blocked from admin comments panel', Auth::id());

        // Also reject all pending comments from this email
        PhotoComment::where('guest_email', $email)
            ->where('status', PhotoComment::STATUS_PENDING)
            ->update(['status' => PhotoComment::STATUS_REJECTED]);

        return back()->with('success', "Email {$email} has been blocked.");
    }

    /**
     * Unblock an email address.
     */
    public function unblockEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        if (BlockedEmail::unblockEmail($validated['email'])) {
            return back()->with('success', "Email {$validated['email']} has been unblocked.");
        }

        return back()->with('error', 'Email was not in the blocked list.');
    }

    /**
     * List blocked emails.
     */
    public function blockedEmails(): Response
    {
        $blockedEmails = BlockedEmail::with('blockedByUser:id,name')
            ->latest()
            ->paginate(50)
            ->through(fn ($blocked) => [
                'id' => $blocked->id,
                'email' => $blocked->email,
                'reason' => $blocked->reason,
                'blocked_by' => $blocked->blockedByUser?->name ?? 'System',
                'created_at' => $blocked->created_at->format('M j, Y g:i A'),
            ]);

        return Inertia::render('Admin/Comments/BlockedEmails', [
            'blockedEmails' => $blockedEmails,
        ]);
    }
}
