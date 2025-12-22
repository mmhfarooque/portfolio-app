<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\PhotoComment;
use App\Models\PhotoLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PhotoInteractionController extends Controller
{
    /**
     * Toggle like on a photo.
     */
    public function toggleLike(Request $request, Photo $photo): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $ipAddress = $request->ip();

        // Check if already liked
        $existingLike = PhotoLike::where('photo_id', $photo->id)
            ->where(function ($query) use ($sessionId, $ipAddress) {
                $query->where('session_id', $sessionId)
                    ->orWhere('ip_address', $ipAddress);
            })
            ->first();

        if ($existingLike) {
            // Unlike
            $existingLike->delete();
            $photo->decrementLikesCount();

            return response()->json([
                'liked' => false,
                'likes_count' => $photo->fresh()->likes_count,
            ]);
        }

        // Rate limiting: 50 likes per hour per IP
        $cacheKey = "like_limit_{$ipAddress}";
        $likeCount = Cache::get($cacheKey, 0);

        if ($likeCount >= 50) {
            return response()->json([
                'error' => 'Rate limit exceeded. Please try again later.',
            ], 429);
        }

        // Create like
        PhotoLike::create([
            'photo_id' => $photo->id,
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]);

        $photo->incrementLikesCount();
        Cache::put($cacheKey, $likeCount + 1, now()->addHour());

        return response()->json([
            'liked' => true,
            'likes_count' => $photo->fresh()->likes_count,
        ]);
    }

    /**
     * Check if current user has liked the photo.
     */
    public function checkLike(Request $request, Photo $photo): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $ipAddress = $request->ip();

        return response()->json([
            'liked' => $photo->hasBeenLikedBy($sessionId, $ipAddress),
            'likes_count' => $photo->likes_count,
        ]);
    }

    /**
     * Store a new comment.
     */
    public function storeComment(Request $request, Photo $photo)
    {
        // Honeypot check
        if ($request->filled('website')) {
            return back()->with('success', 'Thank you for your comment! It will appear after moderation.');
        }

        // Rate limiting: 5 comments per hour per IP
        $ip = $request->ip();
        $cacheKey = "comment_limit_{$ip}";
        $commentCount = Cache::get($cacheKey, 0);

        if ($commentCount >= 5) {
            return back()->withErrors(['content' => 'Too many comments. Please try again later.']);
        }

        $validated = $request->validate([
            'guest_name' => 'required|string|max:100',
            'guest_email' => 'required|email|max:255',
            'content' => 'required|string|min:3|max:2000',
            'parent_id' => 'nullable|exists:photo_comments,id',
        ]);

        // Validate parent belongs to same photo
        if ($validated['parent_id']) {
            $parent = PhotoComment::find($validated['parent_id']);
            if (!$parent || $parent->photo_id !== $photo->id) {
                return back()->withErrors(['parent_id' => 'Invalid reply target.']);
            }
            // Only allow one level of nesting (no replies to replies)
            if ($parent->parent_id !== null) {
                $validated['parent_id'] = $parent->parent_id;
            }
        }

        PhotoComment::create([
            'photo_id' => $photo->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'guest_name' => $validated['guest_name'],
            'guest_email' => $validated['guest_email'],
            'content' => $validated['content'],
            'status' => PhotoComment::STATUS_PENDING,
            'ip_address' => $ip,
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]);

        // Increment rate limit
        Cache::put($cacheKey, $commentCount + 1, now()->addHour());

        return back()->with('success', 'Thank you for your comment! It will appear after moderation.');
    }

    /**
     * Get comments for a photo (approved only).
     */
    public function getComments(Photo $photo): JsonResponse
    {
        $comments = $photo->topLevelApprovedComments()
            ->with(['approvedReplies' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'author_name' => $comment->author_name,
                    'content' => $comment->content,
                    'is_admin' => $comment->is_admin,
                    'created_at' => $comment->created_at->diffForHumans(),
                    'created_at_formatted' => $comment->created_at->format('M j, Y'),
                    'replies' => $comment->approvedReplies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'author_name' => $reply->author_name,
                            'content' => $reply->content,
                            'is_admin' => $reply->is_admin,
                            'created_at' => $reply->created_at->diffForHumans(),
                            'created_at_formatted' => $reply->created_at->format('M j, Y'),
                        ];
                    }),
                ];
            });

        return response()->json([
            'comments' => $comments,
            'total' => $photo->comments_count,
        ]);
    }
}
