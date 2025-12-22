<?php

namespace App\Http\Controllers;

use App\Models\BlockedEmail;
use App\Models\CommentOtpVerification;
use App\Models\Photo;
use App\Models\PhotoComment;
use App\Models\PhotoLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

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
     * Request OTP for comment verification.
     */
    public function requestOtp(Request $request, Photo $photo): JsonResponse
    {
        // Honeypot check
        if ($request->filled('website')) {
            return response()->json(['success' => true, 'message' => 'OTP sent to your email.']);
        }

        $ip = $request->ip();

        // Rate limiting: 5 OTP requests per hour per IP
        $cacheKey = "otp_limit_{$ip}";
        $otpCount = Cache::get($cacheKey, 0);

        if ($otpCount >= 5) {
            return response()->json([
                'success' => false,
                'error' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $validated = $request->validate([
            'guest_name' => 'required|string|max:100',
            'guest_email' => 'required|email|max:255',
            'content' => 'required|string|min:3|max:2000',
            'parent_id' => 'nullable|exists:photo_comments,id',
        ]);

        $email = strtolower(trim($validated['guest_email']));

        // Check if email is blocked
        if (BlockedEmail::isBlocked($email)) {
            return response()->json([
                'success' => false,
                'error' => 'This email address is not allowed to comment.',
            ], 403);
        }

        // Validate parent belongs to same photo
        $parentId = null;
        if ($validated['parent_id']) {
            $parent = PhotoComment::find($validated['parent_id']);
            if (!$parent || $parent->photo_id !== $photo->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid reply target.',
                ], 400);
            }
            // Only allow one level of nesting (no replies to replies)
            $parentId = $parent->parent_id ?? $parent->id;
        }

        // Generate OTP
        $otp = CommentOtpVerification::generateOtp();

        // Delete any existing pending verification for this email + photo
        CommentOtpVerification::where('photo_id', $photo->id)
            ->where('guest_email', $email)
            ->where('verified', false)
            ->delete();

        // Create verification record
        $verification = CommentOtpVerification::create([
            'photo_id' => $photo->id,
            'parent_id' => $parentId,
            'guest_name' => $validated['guest_name'],
            'guest_email' => $email,
            'content' => $validated['content'],
            'otp_code' => $otp,
            'ip_address' => $ip,
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            'expires_at' => now()->addMinutes(CommentOtpVerification::OTP_EXPIRY_MINUTES),
        ]);

        // Send OTP email
        $this->sendOtpEmail($email, $otp, $validated['guest_name'], $photo->title);

        // Increment rate limit
        Cache::put($cacheKey, $otpCount + 1, now()->addHour());

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your email.',
            'verification_id' => $verification->id,
            'expires_in' => CommentOtpVerification::OTP_EXPIRY_MINUTES * 60,
        ]);
    }

    /**
     * Verify OTP and create comment.
     */
    public function verifyOtp(Request $request, Photo $photo): JsonResponse
    {
        $validated = $request->validate([
            'verification_id' => 'required|exists:comment_otp_verifications,id',
            'otp_code' => 'required|string|size:6',
        ]);

        $verification = CommentOtpVerification::find($validated['verification_id']);

        // Verify the verification belongs to this photo
        if ($verification->photo_id !== $photo->id) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid verification.',
            ], 400);
        }

        // Check if already verified
        if ($verification->verified) {
            return response()->json([
                'success' => false,
                'error' => 'This code has already been used.',
            ], 400);
        }

        // Check if expired
        if ($verification->isExpired()) {
            return response()->json([
                'success' => false,
                'error' => 'Verification code has expired. Please request a new one.',
            ], 400);
        }

        // Check max attempts
        if ($verification->maxAttemptsReached()) {
            return response()->json([
                'success' => false,
                'error' => 'Too many failed attempts. Please request a new code.',
            ], 400);
        }

        // Verify OTP
        if (!$verification->verifyOtp($validated['otp_code'])) {
            $remaining = CommentOtpVerification::MAX_ATTEMPTS - $verification->fresh()->attempts;
            return response()->json([
                'success' => false,
                'error' => "Invalid code. {$remaining} attempts remaining.",
            ], 400);
        }

        // Check email is still not blocked
        if (BlockedEmail::isBlocked($verification->guest_email)) {
            return response()->json([
                'success' => false,
                'error' => 'This email address is not allowed to comment.',
            ], 403);
        }

        // Create the comment
        PhotoComment::create([
            'photo_id' => $verification->photo_id,
            'parent_id' => $verification->parent_id,
            'guest_name' => $verification->guest_name,
            'guest_email' => $verification->guest_email,
            'content' => $verification->content,
            'status' => PhotoComment::STATUS_PENDING,
            'ip_address' => $verification->ip_address,
            'user_agent' => $verification->user_agent,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your comment will appear after moderation.',
        ]);
    }

    /**
     * Resend OTP.
     */
    public function resendOtp(Request $request, Photo $photo): JsonResponse
    {
        $validated = $request->validate([
            'verification_id' => 'required|exists:comment_otp_verifications,id',
        ]);

        $verification = CommentOtpVerification::find($validated['verification_id']);

        // Verify the verification belongs to this photo
        if ($verification->photo_id !== $photo->id) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid verification.',
            ], 400);
        }

        // Check if already verified
        if ($verification->verified) {
            return response()->json([
                'success' => false,
                'error' => 'This verification has already been completed.',
            ], 400);
        }

        // Rate limiting: 3 resends per verification
        if ($verification->attempts >= 3) {
            return response()->json([
                'success' => false,
                'error' => 'Too many resend attempts. Please start over.',
            ], 429);
        }

        // Generate new OTP
        $otp = CommentOtpVerification::generateOtp();

        $verification->update([
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(CommentOtpVerification::OTP_EXPIRY_MINUTES),
            'attempts' => 0,
        ]);

        // Send OTP email
        $this->sendOtpEmail($verification->guest_email, $otp, $verification->guest_name, $photo->title);

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your email.',
            'expires_in' => CommentOtpVerification::OTP_EXPIRY_MINUTES * 60,
        ]);
    }

    /**
     * Send OTP email.
     */
    protected function sendOtpEmail(string $email, string $otp, string $name, string $photoTitle): void
    {
        $siteName = config('app.name', 'Photography Portfolio');

        Mail::send([], [], function ($message) use ($email, $otp, $name, $photoTitle, $siteName) {
            $message->to($email)
                ->subject("Your Comment Verification Code - {$siteName}")
                ->html($this->getOtpEmailHtml($otp, $name, $photoTitle, $siteName));
        });
    }

    /**
     * Generate OTP email HTML.
     */
    protected function getOtpEmailHtml(string $otp, string $name, string $photoTitle, string $siteName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #1c1917 0%, #292524 100%); color: white; padding: 30px; border-radius: 12px 12px 0 0; text-align: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: 600;">{$siteName}</h1>
    </div>
    <div style="background: #ffffff; padding: 30px; border: 1px solid #e5e5e5; border-top: none; border-radius: 0 0 12px 12px;">
        <p style="margin-bottom: 20px;">Hi <strong>{$name}</strong>,</p>
        <p style="margin-bottom: 20px;">Thank you for commenting on "<strong>{$photoTitle}</strong>". Please use the verification code below to confirm your email:</p>
        <div style="background: #f5f5f4; padding: 20px; border-radius: 8px; text-align: center; margin: 25px 0;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #d97706;">{$otp}</span>
        </div>
        <p style="margin-bottom: 20px; color: #666; font-size: 14px;">This code expires in 10 minutes. If you didn't request this, please ignore this email.</p>
        <hr style="border: none; border-top: 1px solid #e5e5e5; margin: 25px 0;">
        <p style="color: #999; font-size: 12px; text-align: center; margin: 0;">
            This is an automated message from {$siteName}.<br>
            Please do not reply to this email.
        </p>
    </div>
</body>
</html>
HTML;
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
